<?php
/**
 * Ansel_Widget_Actions:: class to wrap the display of gallery actions
 *
 * $Horde: ansel/lib/Widget/Actions.php,v 1.26.2.24 2009/06/15 17:16:49 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Actions extends Ansel_Widget {

    var $_supported_views = array('Gallery');

    function Ansel_Widget_Actions($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("Gallery Actions");
    }

    function html()
    {
        global $registry;
        $html = $this->_htmlBegin();
        $id = $this->_view->gallery->id;
        $galleryurl = Util::addParameter(Horde::applicationUrl('gallery.php'),
                                                               'gallery', $id);
        if ($this->_view->gallery->hasFeature('upload')) {
            $uploadurl = Util::addParameter(Horde::applicationUrl('img/upload.php'),
                                            array('gallery' => $id,
                                                  'page' => !empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0));
        }
        $html .= '<ul style="list-style-type:none;">';
        if (empty($this->_params['hide_slideshow']) &&
            $this->_view->gallery->hasFeature('slideshow') &&
            $this->_view->gallery->countImages()) {
            /* Slideshow link */
            if (!empty($this->_params['slideshow_link'])) {
                $slideshow_url = str_replace(array('%i', '%g'),
                                             array(array_pop($this->_view->gallery->listImages(0, 1)), $id),
                                             urldecode($this->_params['slideshow_link']));
            } else {
                /* Get any date info the gallery has */
                $date = $this->_view->gallery->getDate();
                $slideshow_url = Horde::applicationUrl(
                    Util::addParameter('view.php', array_merge(
                                       array('gallery' => $id,
                                             'image' => array_pop($this->_view->gallery->listImages(0, 1)),
                                             'view' => 'Slideshow'),
                                       $date)));
            }
            $html .= '<li>' . Horde::link($slideshow_url, '', 'widget') . Horde::img('slideshow_play.png', _("Start Slideshow"), '', Horde::url($registry->getImageDir(), true, -1)) . ' ' . _("Start Slideshow") . '</a></li>';
        }
        if (!empty($uploadurl) && $this->_view->gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            $html .= '<li>' . Horde::link($uploadurl, '', 'widget') . Horde::img('image_add.png') . ' ' . _("Upload photos") . '</a></li>';

            /* Subgalleries */
            if ($this->_view->gallery->hasFeature('subgalleries')) {
                $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, 'actionID', 'addchild'), '', 'widget') . Horde::img('add.png', '', '', $GLOBALS['registry']->getImageDir()) . ' ' . _("Create a subgallery") . '</a></li>';
            }
        }
        $html .= '</ul>';
        $html .= $this->_getGalleryActions();

        $selfurl = Horde::selfUrl(true, true);
        $html .=  '<div class="control"><a href="'
                 . Util::addParameter($selfurl, 'actionID',
                                     'show_actions')
                 . '" id="gallery-actions-toggle" class="'
                 . (($GLOBALS['prefs']->getValue('show_actions'))
                 ? 'hide'
                 : 'show') . '">&nbsp;</a></div>' . "\n";

        $html .= $this->_htmlEnd();
        return $html;
    }

   /**
    * Helper function for generating the gallery actions selection widget.
    *
    * @return string  The HTML
    */
    function _getGalleryActions()
    {
        global $registry, $prefs, $conf;

        $id = $this->_view->gallery->id;
        $galleryurl = Util::addParameter(Horde::applicationUrl('gallery.php'),
                                                               'gallery', $id);

        $selfurl = Horde::selfUrl(true, false, true);
        $count = $this->_view->gallery->countImages();
        $date = $this->_view->gallery->getDate();

        $html = '<div style="display:' . (($prefs->getValue('show_actions')) ? 'block' : 'none') . ';" id="gallery-actions">';

        /* Attach the ajax action */
        ob_start();
        require_once ANSEL_BASE . '/lib/XRequest.php';
        $xrequest = Ansel_XRequest::factory(
            'ToggleGalleryActions',
            array('bindTo' => 'gallery-actions'));

        $xrequest->attach();
        $html .= ob_get_clean();

        /* Buid the url parameters to the zip link */
        $view_params = array(
            'gallery' => $this->_view->gallery->id,
            'view' => 'Gallery',
            'slug' => $this->_view->gallery->get('slug'),
            'page' => (!empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0));

        /* Append the date information to the parameters if we need it */
        $view_params = array_merge($view_params, $date);

        $html .= '<ul style="list-style-type:none;">';

        /* Bookmark link */
        if ($registry->hasMethod('bookmarks/getAddUrl')) {
             $api_params = array(
                'url' => Ansel::getUrlFor('view', $view_params, true),
                'title' => $this->_view->gallery->get('name'));

            $url = $registry->call('bookmarks/getAddUrl', array($api_params));
            if (!is_a($url, 'PEAR_Error')) {
                $html .= '<li>' . Horde::link($url, '', 'widget') . Horde::img('trean.png', '', '', $registry->getImageDir('trean')) . ' ' . _("Add to bookmarks") . '</a></li>';
            }
        }

        /* Download as ZIP link */
        if (!empty($conf['gallery']['downloadzip']) &&
            $this->_view->gallery->canDownload() &&
            $count &&
            $this->_view->gallery->hasFeature('zipdownload')) {

            $zip_params = array_merge(array('actionID' => 'downloadzip'), $date);
            $html .= '<li>' . Horde::link(Horde::applicationUrl(Util::addParameter($galleryurl, $zip_params)), '', 'widget') . Horde::img('compressed.png', '', '', $GLOBALS['registry']->getImageDir('horde') . '/mime') . ' ' .  _("Download as zip file") . '</a></li>';
        }

        /* Image upload, subgalleries, captions etc... */
        if ($this->_view->gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            /* Properties */
            $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, array('actionID' => 'modify', 'url' => $selfurl)), '', 'widget') . Horde::img('edit.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Change properties") . '</a></li>';
            if ($count) {
                /* Captions */
                if ($this->_view->gallery->hasFeature('image_captions')) {
                    $params = array_merge(array('gallery' => $id), $date);
                    $html .= '<li>' . Horde::link(Horde::applicationUrl(Util::addParameter('gallery/captions.php', $params)), '', 'widget') . Horde::img('text.png') . ' ' . _("Set captions") . ' ' . '</a></li>';
                }

                /* Sort */
                if ($this->_view->gallery->hasFeature('sort_images')) {
                    $sorturl = Util::addParameter(Horde::applicationUrl('gallery/sort.php'), array_merge(array('gallery' => $id), $date));
                    $html .= '<li>' . Horde::link(Util::addParameter($sorturl, 'actionId' , 'getOrder'), '', 'widget') . Horde::img('arrow_switch.png') . ' ' . _("Sort photos") . '</a></li>';
                }

                /* Regenerate Thumbnails */
                $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, array('actionID' => 'generateThumbs')), '', 'widget') . Horde::img('reload.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Reset all thumbnails") . '</a></li>';

                /* Regenerate all views  */
                $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, array('actionID' => 'deleteCache')), '', 'widget') . Horde::img('reload.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Regenerate all photo views") . '</a></li>';

                /* Find faces */
                if ($conf['faces']['driver'] && $this->_view->gallery->hasFeature('faces')) {
                    $html .= '<li>' . Horde::link(Horde::applicationUrl(Util::addParameter('faces/gallery.php', array_merge($date, array('gallery' => $id, 'page' => (!empty($this->_view->_params['page']) ? $this->_view->_params['page'] : 0))))), '', 'widget') . Horde::img('user.png','', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Find faces") . '</a></li>';
                }

            } /* end if ($count) {} */

            if (Ansel::isAvailable('photo_stack') && $this->_view->gallery->hasFeature('stacks')) {
                $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, array('actionID' => 'generateDefault', 'url' => $selfurl)), '', 'widget') . Horde::img('reload.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Reset default photo") . '</a></li>';
            }
        }

        if ($this->_view->gallery->get('owner') == Auth::getAuth()) {
            $html .= '<li>' . Horde::link('#','','popup widget', '', "popup('" . Util::addParameter(Horde::applicationUrl('perms.php'), 'cid', $this->_view->gallery->id) . "');") . Horde::img('perms.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Set permissions") . '</a></li>';
        } elseif (!empty($conf['report_content']['driver']) &&
                  (($conf['report_content']['allow'] == 'authenticated' && Auth::isAuthenticated()) ||
                   $conf['report_content']['allow'] == 'all')) {

            $reporturl = Util::addParameter(Horde::applicationUrl('report.php'),
                                            'gallery', $id);
            $html .= '<li>' . Horde::link($reporturl, '', 'widget') . ' ' . _("Report") . "</a></li>\n";
        }

        if ($this->_view->gallery->hasPermission(Auth::getAuth(), PERMS_DELETE)) {
            $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, 'actionID', 'empty'), '', 'widget') . Horde::img('delete.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Delete All Photos") . '</a></li>';
            $html .= '<li>' . Horde::link(Util::addParameter($galleryurl, 'actionID', 'delete'), '', 'widget') . Horde::img('delete.png', '', '', $GLOBALS['registry']->getImageDir('horde')) . ' ' . _("Delete Entire Gallery") . '</a></li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

}
