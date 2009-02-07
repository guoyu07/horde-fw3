<?php
/**
 * Ansel_Tile_Image:: class wraps display of thumbnails displayed
 * for a image on the Ansel_View_Gallery view.
 *
 * $Horde: ansel/lib/Tile/Image.php,v 1.49.2.1 2008/10/27 21:34:26 mrubinsk Exp $
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

/** Text_Filter */
require_once 'Horde/Text/Filter.php';

/**
 * @package Ansel
 */
class Ansel_Tile_Image {

    /**
     * Outputs the HTML for an image thumbnail 'tile'.
     *
     * @param Ansel_Image $image     The Ansel_Image we are displaying.
     * @param Ansel_Gallery $parent  The parent Ansel_Gallery for this image.
     *                               If null, will create a new instance of
     *                               the Ansel_Gallery
     * @param array $style           A sytle definiition array.
     * @param boolean $mini          Force the use of a mini thumbnail?
     * @param string $params         Any other paramaters needed by this tile
     *
     * @return  Outputs the HTML for the image tile.
     */
    function getTile($image, $parent = null, $style = null, $mini = false,
                     $params = array())
    {
        global $conf, $registry;

        if (is_null($parent)) {
            $parent = $GLOBALS['ansel_storage']->getGallery($image->gallery);
        }

        if (is_null($style)) {
            $style = $parent->getStyle();
        }

        $page = isset($params['page']) ? $params['page'] : 0;
        $view = isset($params['view']) ? $params['view'] : 'Gallery';
        $date = $parent->getDate();

        if ($view == 'Results') {
            $haveSearch = 1;
        } else {
            $haveSearch = 0;
        }

        /* Override the thumbnail to mini or use style default? */
        $thumbstyle = $mini ? 'mini' : $style['thumbstyle'];

        /* URL for image properties/actions etc... */
        $image_url = Util::addParameter('image.php', array_merge(
             array('gallery' => $image->gallery,
                   'page' => $page,
                   'image' => $image->id,
                   'havesearch' => $haveSearch),
             $date));

        /* URL to view the image. This is the link for the Tile.
         * $view_url is the link for the thumbnail and since this might not
         * always point to the image view page, we set $img_view_url to link to
         * the image view
         */
        $img_view_url = Ansel::getUrlFor('view', array_merge(
            array('gallery' => $image->gallery,
                  'slug' => $parent->get('slug'),
                  'page' => $page,
                  'view' => 'Image',
                  'image'=> $image->id,
                  'havesearch' => $haveSearch),
            $date));

        if (!empty($params['image_view_src'])) {
            $view_url = Ansel::getImageUrl($image->id, 'screen', true);
        } elseif (empty($params['image_view_url'])) {
            $view_url = $img_view_url;
        } else {
            $view_url = str_replace(array('%i', '%g', '%s'),
                                    array($image->id, $image->gallery, $parent->get('slug')),
                                    urldecode($params['image_view_url']));

            // If we override the view_url, assume we want to override this
            // as well.
            $img_view_url = $view_url;
        }

        // Need the gallery URL to display the "From" link when showing
        // the image tile from somewhere other than the gallery view.
        if (!empty($view) || basename($_SERVER['PHP_SELF']) == 'view.php') {
            $gallery_url = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $parent->id,
                      'slug' => $parent->get('slug'),
                      'view' => 'Gallery',
                      'havesearch' => $haveSearch),
                $date));
        }

        $thumb_url = Ansel::getImageUrl($image->id, $thumbstyle, true, $style['name']);
        $option_select = $parent->hasPermission(Auth::getAuth(), PERMS_DELETE);
        $option_edit = $parent->hasPermission(Auth::getAuth(), PERMS_EDIT);
        $imgAttributes = (!empty($params['image_view_attributes'])
                         ? $params['image_view_attributes'] : array());

        $imgOnClick = (!empty($params['image_onclick'])
                      ? str_replace('%i', $image->id, $params['image_onclick']) : '');

        $imageCaption = Text_Filter::filter(
            $image->caption, 'text2html',
            array('parselevel' => TEXT_HTML_MICRO));

        if (!empty($params['image_view_title']) &&
            !empty($image->_data[$params['image_view_title']])) {
            $title = $image->_data[$params['image_view_title']];
        } else {
            $title = $image->filename;
        }

        ob_start();
        // In-line caption editing if we have PERMS_EDIT
        if ($option_edit) {
            require_once ANSEL_BASE . '/lib/XRequest.php';
            $xr = Ansel_XRequest::factory('EditCaption',
                                          array('id' => $image->id,
                                                'domid' => $image->id . 'caption'));
            $xr->attach();
        }

        include ANSEL_BASE . '/templates/tile/image.inc';
        return ob_get_clean();
    }

}
