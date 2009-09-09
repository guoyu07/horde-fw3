<?php
/**
 * Ansel_Widget_Geodata:: class to wrap the display of a Google map showing
 * images with geolocation data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 * $Horde: ansel/lib/Widget/Geodata.php,v 1.1.2.39 2009/07/28 15:43:05 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Geodata extends Ansel_Widget {

    var $_supported_views = array('Image', 'Gallery');
    var $_params = array('default_zoom' => 15,
                         'max_auto_zoom' => 15);

    function Ansel_Widget_Geodata($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("Location");
    }

    function attach($view)
    {
         // Don't even try if we don't have an api key
        if (empty($GLOBALS['conf']['api']['googlemaps'])) {
            return false;
        }
        parent::attach($view);

        return true;
    }

    function html()
    {
        global $ansel_storage;

        Horde::addScriptFile('prototype.js', 'horde');
        Horde::addScriptFile('popup.js', 'horde');

        $geodata = $ansel_storage->getImagesGeodata($this->_params['images']);
        $url = Horde::applicationUrl('map_edit.php', true);
        $rtext = _("Relocate this image");
        $dtext = _("Delete geotag");
        $xrequestUrl = Horde::applicationUrl('xrequest.php', true);
        $permsEdit = $this->_view->gallery->hasPermission(Auth::getAuth(), PERMS_EDIT);
        $viewType = $this->_view->viewType();

        if (count($geodata) == 0 && $viewType != 'Image') {
            return '';
        } elseif (count($geodata) == 0) {
            $noGeotag = true;
        }

        // Bring in googlemap.js now that we know we need it.
        $sfiles = &Ansel_Script_Files::singleton();
        $sfiles->addExternalScript('http://maps.google.com/maps?file=api&v=2&sensor=false&key=' . $GLOBALS['conf']['api']['googlemaps']);
        $sfiles->addExternalScript('http://gmaps-utility-library.googlecode.com/svn/trunk/markermanager/1.1/src/markermanager.js');
        Horde::addScriptFile('googlemap.js');

        $html = $this->_htmlBegin() . "\n";
        $content = '<div id="ansel_geo_widget">';

        // Add extra information to the JSON data to be sent:
        foreach ($geodata as $id => $data) {
            $geodata[$id]['icon'] = Ansel::getImageUrl($geodata[$id]['image_id'], 'mini', true);
            $geodata[$id]['link'] = Ansel::getUrlFor('view', array('view' => 'Image',
                                                                   'gallery' => $this->_view->gallery->id,
                                                                   'image' => $geodata[$id]['image_id']), true);
            $geodata[$id]['markerOnly'] = ($viewType == 'Image');
        }

        // If this is an image view, get the other gallery images
        if ($viewType == 'Image') {
            $image_id = $this->_view->resource->id;
            $others = $this->_getGalleryImagesWithGeodata();
            foreach ($others as $id => $data) {
                if ($id != $image_id) {
                    $others[$id]['icon'] = Ansel::getImageUrl($others[$id]['image_id'], 'mini', true);
                    $others[$id]['link'] = Ansel::getUrlFor('view', array('view' => 'Image',
                                                                         'gallery' => $this->_view->gallery->id,
                                                                         'image' => $others[$id]['image_id']), true);
                } else {
                    unset($others[$id]);
                }
            }
            $geodata = array_values(array_merge($geodata, $others));

            if (empty($noGeotag)) {
                $content .= '<div id="ansel_map"></div>';
                $content .= '<div class="ansel_geolocation">';
                $content .= '<div id="ansel_locationtext"></div>';
                $content .= '<div id="ansel_latlng"></div>';
                $content .= '<div id="ansel_relocate"></div><div id="ansel_deleteGeotag"></div></div>';
                $content .= '<div id="ansel_map_small"></div>';

            } elseif ($permsEdit) {
                // Image view, but no geotags, provide ability to add it.
                $addurl = Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $this->_params['images'][0]);
                $addLink = Horde::link($addurl, '', '', '', 'popup(\'' . Util::addParameter(Horde::applicationUrl('map_edit.php'), 'image', $this->_params['images'][0]) . '\', 750, 600); return false;');
                $imgs = $ansel_storage->getRecentImagesGeodata(Auth::getAuth());
                    if (count($imgs) > 0) {
                        $imgsrc = '<div class="ansel_location_sameas">';
                        foreach ($imgs as $id => $data) {
                            if (!empty($data['image_location'])) {
                                $title = $data['image_location'];
                            } else {
                                $title = $this->_point2Deg($data['image_latitude'], true) . ' ' . $this->_point2Deg($data['image_longitude']);
                            }
                            $imgsrc .= Horde::link($addurl, $title, '', '', "setLocation('" . $data['image_latitude'] . "', '" . $data['image_longitude'] . "');return false") . '<img src="' . Ansel::getImageUrl($id, 'mini', true) . '" /></a>';
                                                    }
                        $imgsrc .= '</div>';
                        $content .= sprintf(_("No location data present. Place using %s map %s or click on image to place at the same location."), $addLink, '</a>') . $imgsrc;
                    } else {
                        $content .= _("No location data present. You may add some ") . $addLink . _("here") . '</a>';
                    }
            } else {
                // For now, just put up a notice. In future, maybe provide a link
                // to suggest a location using the Report API?
                $content .= _("No location data present.");
            }

        } else {
            // Gallery view-------------
            // Avoids undefined error when we build the js function below.
            $image_id = 0;
            $content .= '<div id="ansel_map"></div><div id="ansel_locationtext" style="min-height: 20px;"></div><div id="ansel_map_small"></div>';

        }

        $content .= '</div>';
        $json = Horde_Serialize::serialize(array_values($geodata), SERIALIZE_JSON);
        $html .= <<<EOT
        <script type="text/javascript">
        var map = {};
        var pageImages = {$json};
        options = {
            smallMap: 'ansel_map_small',
            mainMap:  'ansel_map',
            viewType: '{$viewType}',
            relocateUrl: '{$url}',
            relocateText: '{$rtext}',
            deleteGeotagText: '{$dtext}',
            hasEdit: {$permsEdit},
            calculateMaxZoom: true,
            updateEndpoint: '{$xrequestUrl}',
            deleteGeotagCallback: function() {deleteLocation();}
        };

        function setLocation(lat, lng)
        {
            params = {requestType: 'ImageSaveGeolocation/type=geotag/img=' + {$image_id} + '/lat=' + lat + '/lng=' + lng};
            url = "{$xrequestUrl}";
             new Ajax.Request(url, {
                method: 'post',
                parameters: params,
                onComplete: function(transport) {
                     if (transport.responseText == 1) {
                        if (typeof ToolTips != 'undefined') {
                            ToolTips.out();
                        }
                        w = new Element('div');
                        w.appendChild(new Element('div', {id: 'ansel_map'}));
                        ag = new Element('div', {'class': 'ansel_geolocation'});
                        ag.appendChild(new Element('div', {id: 'ansel_locationtext'}));
                        ag.appendChild(new Element('div', {id: 'ansel_latlng'}));
                        ag.appendChild(new Element('div', {id: 'ansel_relocate'}));
                        ag.appendChild(new Element('div', {id: 'ansel_deleteGeotag'}));
                        w.appendChild(ag);
                        w.appendChild(new Element('div', {id: 'ansel_map_small'}));
                        $('ansel_geo_widget').update(w);
                        pageImages.unshift({image_id: {$image_id}, image_latitude: lat, image_longitude: lng, image_location:'', markerOnly:true});
                        doMap(pageImages);
                     }
                 }
            });
        }

        function deleteLocation() {
            params = {requestType: 'ImageSaveGeolocation/type=untag/img=' + {$image_id}};
            url = "{$xrequestUrl}";
            new Ajax.Request(url, {
                method: 'post',
                parameters: params,
                onComplete: function(transport) {
                    $('ansel_geo_widget').update(transport.responseText)
                }
            });

        }

        function doMap(points) {
            map = new Ansel_GMap(options);
            map.getLocationCallback_ = map.getLocationCallback;
            map.getLocationCallback = function(points, marker) {
                map.getLocationCallback_(points, marker, Object.isUndefined(points.NoUpdate));
            }.bind(map);
            map.addPoints(points);
            map.display();
        }
EOT;

        if (empty($noGeotag)) {
            $html .= "\n" . 'Event.observe(window, "load", function() {doMap(pageImages);});' . "\n";
        }
        $html .= '</script>' . "\n";
        $html .= $content. $this->_htmlEnd();

        return $html;
    }

    function _getGalleryImagesWithGeodata()
    {
        return $GLOBALS['ansel_storage']->getImagesGeodata(array(), $this->_view->gallery->id);
    }

    function _point2Deg($value, $lat = false)
    {
        $letter = $lat ? ($value > 0 ? "N" : "S") : ($value > 0 ? "E" : "W");
        $value = abs($value);
        $deg = floor($value);
        $min = floor(($value - $deg) * 60);
        $sec = ($value - $deg - $min / 60) * 3600;
        return $deg . "&deg; " . $min . '\' ' . round($sec, 2) . '" ' . $letter;
    }

}
