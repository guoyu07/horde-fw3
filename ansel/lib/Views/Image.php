<?php
/**
 * The Ansel_View_Image:: class wraps display of individual images.
 *
 * $Horde: ansel/lib/Views/Image.php,v 1.68.2.15 2009-11-15 18:24:41 mrubinsk Exp $
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Ansel
 */

/** Ansel_View_Abstract */
require_once ANSEL_BASE . '/lib/Views/Abstract.php';

/** Text_Filter */
require_once 'Horde/Text/Filter.php';

/** Tags **/
require_once ANSEL_BASE . '/lib/Tags.php';

class Ansel_View_Image extends Ansel_View_Abstract {

    /**
     *  The image selected for this view.
     *
     * @var Ansel_Image
     */
    var $image;

    /**
     * @static
     *
     * @TODO use exceptions from the constructor instead of static
     * instance-getting.
     */
    function makeView($params = array())
    {
        /* Get the image */
        $image_id = $params['image_id'];

        $image = &$GLOBALS['ansel_storage']->getImage($image_id);
        if (is_a($image, 'PEAR_Error')) {
            return $image;
        }

        /* Create the Ansel_View object */
        $view = new Ansel_View_Image;

        /* Save params */
        if (count($params)) {
            $view->_params = $params;
        }

        $view->gallery = $view->getGallery();
        if (is_a($view->gallery, 'PEAR_Error')) {
            return $view->gallery;
        }

        /* Save the image reference */
        $view->resource = $image;

        // Check user age
        if (!$view->gallery->isOldEnough()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $view->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $view->gallery->id, 'url' => $url);

            header('Location: ' . Util::addParameter(Horde::applicationUrl('disclamer.php'), $params, null, false));
            exit;
        }

        // Check password
        if ($view->gallery->hasPasswd()) {
            if (!empty($params['api'])) {
                return PEAR::raiseError(_("Locked galleries are not viewable via the api."));
            }
            $date = Ansel::getDateParameter(
                array('year' => isset($view->_params['year']) ? $view->_params['year'] : 0,
                      'month' => isset($view->_params['month']) ? $view->_params['month'] : 0,
                      'day' => isset($view->_params['day']) ? $view->_params['day'] : 0));

                $url = Ansel::getUrlFor('view', array_merge(
                    array('gallery' => $view->gallery->id,
                          'slug' => empty($params['slug']) ? '' : $params['slug'],
                          'page' => empty($params['page']) ? 0 : $params['page'],
                          'view' => 'Image',
                          'image' => $image->id),
                    $date),
                    true);

            $params = array('gallery' => $view->gallery->id, 'url' => $url);

            header('Location: ' . Util::addParameter(Horde::applicationUrl('protect.php'), $params, null, false));
            exit;
        }


        /* Any script files we may need if not calling via the api */
        if (empty($view->_params['api'])) {
            Horde::addScriptFile('prototype.js', 'horde', true);
            Horde::addScriptFile('effects.js', 'horde', true);
            Horde::addScriptFile('stripe.js', 'horde', true);
        }

        return $view;
    }

    function getGalleryCrumbData()
    {
        return $this->gallery->getGalleryCrumbData();
    }

    /**
     * Get the title for this view.
     *
     * @return string  The title.
     */
    function getTitle()
    {
        return $this->resource->filename;
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML.
     */
    function html()
    {
        global $browser, $conf, $prefs, $registry;

        if (is_a($this->gallery, 'PEAR_Error')) {
            echo htmlspecialchars($this->gallery->getMessage());
            return;
        }

        $page = isset($this->_params['page']) ? $this->_params['page'] : 0;
        $galleryId = $this->gallery->id;
        $gallerySlug = $this->gallery->get('slug');
        $imageId = $this->resource->id;
        $galleryOwner = $this->gallery->get('owner');
        $date = $this->gallery->getDate();

        /* Allow overriding the configured view_mode */
        if (isset($this->_params['mode'])) {
            $mode = $this->_params['mode'];
        } else {
            $mode = $this->_params['mode'] = $this->gallery->get('view_mode');
        }

        /* Get any date infor the gallery has */
        $date = $this->gallery->getDate();

        $style = (empty($this->_params['style']) ?
             $this->gallery->getStyle() :
             Ansel::getStyleDefinition($this->_params['style']));

        /* Make sure the screen view is loaded and get the geometry */
        $geometry = $this->resource->getDimensions('screen');
        if (is_a($geometry, 'PEAR_Error')) {
            Horde::logMessage($geometry->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
            $geometry = $conf['screen'];
        }

        /* Get comments before any output in sent. */
        if (($conf['comments']['allow'] == 'all' || ($conf['comments']['allow'] == 'authenticated' && Auth::getAuth())) &&
            $registry->hasMethod('forums/doComments')) {
            $hasComments = true;
            if (!empty($this->_params['comment_url'])) {
                $this->_params['comment_url'] = str_replace(
                    array('%i', '%g', '%s'),
                    array($imageId, $galleryId, $gallerySlug),
                    urldecode($this->_params['comment_url']));
            }
            $url = empty($this->_params['comment_url']) ? null : $this->_params['comment_url'];
            $comments = $registry->call('forums/doComments',
                                        array('ansel', $imageId,
                                              'commentCallback', true, null,
                                              $url));
            if (is_a($comments, 'PEAR_Error')) {
                Horde::logMessage($comments, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $comments = array();
            }
        } else {
            $comments = array();
            $hasComments = false;
        }

        /* Get the index of the starting image */
        $imageList = $this->gallery->listImages();
        $revList = array_flip($imageList);
        $imageIndex = $revList[$imageId];

        /* Not needed when being called via api */
        if (empty($this->_params['api'])) {
            $ecardurl = Horde::applicationUrl(
                Util::addParameter('img/ecard.php', array_merge(
                                   array('gallery' => $galleryId,
                                         'image' => $imageId),
                                   $date)),
                true);

            $imageActionUrl = Util::addParameter(
                'image.php', array_merge(
                array('gallery' => $galleryId,
                      'image' => $imageId,
                      'page' => $page),
                $date));
        }

        /* Check for an explicit gallery view url to use */
        if (!empty($this->_params['gallery_view_url'])) {
            $galleryurl = str_replace(
                array('%g', '%s'),
                array($galleryId, $gallerySlug),
                urldecode($this->_params['gallery_view_url']));
                Util::addParameter($galleryurl, $date);
        } else {
            $galleryurl = Ansel::getUrlFor('view', array_merge(
                                           array('gallery' => $galleryId,
                                                 'slug' => $gallerySlug,
                                                 'page' => $page,
                                                 'view' => 'Gallery'),
                                           $date),
                                           true);
        }

        /* Get the image src url */
        $imageUrl = Ansel::getImageUrl($imageId, 'screen', true, $style['name']);

        /* And a self url. Can't use Horde::selfUrl() since that would ignore
         * pretty urls. */
        $selfUrl = Ansel::getUrlFor('view', array_merge(
                                    array('gallery' => $galleryId,
                                          'slug' => $gallerySlug,
                                          'image' => $imageId,
                                          'view' => 'Image',
                                          'page' => $page),
                                    $date));

        /* Get the next and previous image ids */
        if (isset($imageList[$imageIndex + 1])) {
            $next = $imageList[$imageIndex + 1];
        } else {
            $next = $imageList[0];
        }
        if (isset($imageList[$imageIndex - 1])) {
            $prev = $imageList[$imageIndex - 1];
        } else {
            $prev = $imageList[count($imageList) - 1];
        }

        /** Calculate the page number of the next/prev images */
        $perpage = $prefs->getValue('tilesperpage');
        $pagestart = $page * $perpage;
        $pageend = min(count($imageList), $pagestart + $perpage - 1);
        $page_next = $page;

        if ($revList[$imageId] + 1 > $pageend) {
            $page_next++;
        }
        $page_prev = $page;
        if ($revList[$imageId] - 1 < $pagestart) {
            $page_prev--;
        }

        /* Previous image link */
        if (!empty($this->_params['image_view_url'])) {
            $prev_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $galleryId, $gallerySlug),
                urldecode($this->_params['image_view_url']));
        } else {
            $prev_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $galleryId,
                      'slug' => $gallerySlug,
                      'image' => $prev,
                      'view' => 'Image',
                      'page' => $page_prev),
                $date));
        }
        $prvImgUrl = Ansel::getImageUrl($prev, 'screen', false, $style['name']);

        /* Next image link */
        if (!empty($this->_params['image_view_url'])) {
            $next_url = str_replace(
                array('%i', '%g', '%s'),
                array($prev, $galleryId, $gallerySlug),
                urldecode($this->_params['image_view_url']));
        } else {
            $next_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $galleryId,
                      'slug' => $gallerySlug,
                      'image' => $next,
                      'view' => 'Image',
                      'page' => $page_next),
                $date));
        }
        $nextImgUrl = Ansel::getImageUrl($next, 'screen', false, $style['name']);

        /* Slideshow link */
        if (!empty($this->_params['slideshow_link'])) {
            $slideshow_url = str_replace(array('%i', '%g'),
                                         array($imageId, $galleryId),
                                         urldecode($this->_params['slideshow_link']));
        } else {
            $slideshow_url = Horde::applicationUrl(
                Util::addParameter('view.php', array_merge(
                                   array('gallery' => $galleryId,
                                         'image' => $imageId,
                                         'view' => 'Slideshow'),
                                   $date)));
        }

        $commentHtml = '';
        if (isset($hasComments)) {
            if (!empty($comments['threads'])) {
                $commentHtml .= '<br />' . $comments['threads'];
            }
            if (!empty($comments['comments'])) {
                $commentHtml .= '<br />' . $comments['comments'];
            }
        }

        if ($prefs->getValue('showexif')) {
            require_once ANSEL_BASE . '/lib/Exif.php';
            $exifHtml = $this->_getExifHtml();
        } else {
            $exifHtml = '';
        }

        //@TODO: Refactor styles to allow dynamic inclusion/exclusion of widgets.
        /* Buffer the template file and return the html */
        ob_start();

        /* These items currently don't work when viewing through the api */
        if (empty($this->_params['api'])) {
            /* Add the widgets */
            require_once ANSEL_BASE . '/lib/Widget.php';

            // Tag widget
            $this->addWidget(Ansel_Widget::factory('Tags', array('view' => 'image')));

           // Geolocation
           $this->addWidget(Ansel_Widget::factory('Geodata', array('images' => array($this->resource->id))));

            // Similar photos
            $this->addWidget(Ansel_Widget::factory('SimilarPhotos'));

            // Faces
            if ($conf['faces']['driver']) {
                $this->addWidget(Ansel_Widget::factory('ImageFaces', array('selfUrl' => $selfUrl)));
            }

            // Links
            $this->addWidget(Ansel_Widget::factory('Links', array()));

            /* In line caption editing */
            if ($this->gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
                require_once ANSEL_BASE . '/lib/XRequest.php';
                $xr = Ansel_XRequest::factory('EditCaption',
                                              array('id' => $imageId,
                                                    'domid' => "Caption",
                                                    'cols' => 120));
                $xr->attach();
            }
        }

        /* Output the js if we are calling via the api */
        if (!empty($this->_params['api'])) {
            $includes = new Horde_Script_Files();
            $includes->disableAutoloadHordeJS();
            $includes->_add('prototype.js', 'horde', true, true);
            $includes->_add('effects.js', 'horde',true, true);
            $includes->_add('stripe.js', 'horde', true, true);
            $includes->includeFiles();
        }

        require ANSEL_TEMPLATES . '/view/image.inc';
        return ob_get_clean();
    }

    /**
     * Helper function for generating the HTML for EXIF data.
     *
     * @return string  The HTML
     */
    function _getExifHtml()
    {
        $data = Ansel_ImageData::getAttributes($this->resource, true);

        $html = '';
        if (count($data)) {
            $data = array_chunk($data, 3);
            $html .= '<table class="box striped" cellspacing="0" style="width:100%; padding:4px">';
            $i = 0;
            foreach ($data as $elem) {
                $html .= '<tr class="' . (($i++ % 2 == 0) ? 'rowEven' : 'rowOdd')
                         . '">' . implode('', $elem) . '</tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

    function viewType()
    {
        return 'Image';
    }

}
