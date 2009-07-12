<?php
/**
 * Ansel_Tile_Gallery:: class wraps display of thumbnail 'tiles' displayed
 * for a gallery on the Ansel_View_Gallery view.
 *
 * $Horde: ansel/lib/Tile/Gallery.php,v 1.36.2.3 2009/07/05 17:35:25 mrubinsk Exp $
 *
 * @author Michael Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Tile_Gallery {

    /**
     * Outputs the html for a gallery tile.
     *
     * @param Ansel_Gallery $gallery  The Ansel_Gallery we are displaying.
     * @param array $style            A style definition array.
     * @param boolean $mini           Force the use of a mini thumbail?
     * @param array $params           An array containing additional parameters.
     *                                Currently, gallery_view_url and
     *                                image_view_url are used to override the
     *                                respective urls. %g and %i are replaced
     *                                with image id and gallery id, respectively
     *
     *
     * @return  Outputs the HTML for the tile.
     */
    function getTile($gallery, $style = null, $mini = false,
                     $params = array())
    {
        /*
         * See what view we are being displayed in to see if we need to show
         * the owner info or not.
         */
        $view = Util::getFormData('view', 'Gallery');
        $haveSearch = ($view == 'Results') ? 1 : 0;
        if (($view == 'Results' || $view == 'List') ||
            (basename($_SERVER['PHP_SELF']) == 'index.php' &&
             $GLOBALS['prefs']->getValue('defaultview') == 'galleries')) {
            $showOwner = true;
        } else {
            $showOwner = false;
        }

        /* Check gallery permissions and get appropriate tile image */
        if ($gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {
            if (is_null($style)) {
                $style = $gallery->getStyle();
            }
            $thumbstyle = $mini ? 'mini' : $style['thumbstyle'];

            if ($gallery->hasPasswd()) {
                $gallery_image = Horde::img($GLOBALS['registry']->getImageDir() . '/gallery-locked.png', '', '', '');
            } else {
            $gallery_image = Ansel::getImageUrl(
                $gallery->getDefaultImage($style['name']),
                $thumbstyle, true, $style['name']);
                $gallery_image = '<img src="' . $gallery_image . '" alt="' . $gallery->get('name') . '" />';
            }
        } else {
            $gallery_image = Horde::img(
                $GLOBALS['registry']->getImageDir() . '/thumb-error.png', '',
                '', '');
        }

        /* Check for being called via the api and generate correct view links */
        if (!isset($params['gallery_view_url'])) {
            if (empty($params['style'])) {
                $gstyle = $gallery->getStyle();
            } else {
                $gstyle = Ansel::getStyleDefinition($params['style']);
            }
            $view_link = Ansel::getUrlFor('view',
                                          array('gallery' => $gallery->id,
                                                'view' => 'Gallery',
                                                'havesearch' => $haveSearch,
                                                'slug' => $gallery->get('slug')));

            $view_link = Horde::link($view_link);
        } else {
            $view_link = Horde::link(
                str_replace(array('%g', '%s'),
                            array($gallery->id, $gallery->get('slug')),
                            urldecode($params['gallery_view_url'])));
        }

        $image_link = $view_link . $gallery_image . '</a>';
        $text_link = $view_link . htmlspecialchars($gallery->get('name'))
                     . '</a>';

        if ($gallery->hasPermission(Auth::getAuth(), PERMS_EDIT) && !$mini) {
            $properties_link = Util::addParameter(
                    Horde::applicationUrl('gallery.php', true),
                        array('gallery' => $gallery->id,
                              'actionID' => 'modify',
                              'havesearch' => $haveSearch,
                              'url' => Horde::selfUrl(true, false, true)));
            $properties_link = Horde::link($properties_link)
                               . _("Gallery Properties") . '</a>';
        }

        if ($showOwner && !$mini &&
            Auth::getAuth() != $gallery->get('owner')) {
            $owner_link = Ansel::getUrlFor('view',
                                            array('view' => 'List',
                                                  'owner' => $gallery->get('owner'),
                                                  'groupby' => 'owner'),
                                            true);
            $owner_link = Horde::link($owner_link);
            $gallery_owner = $gallery->getOwner();
            $owner_string = $gallery_owner->getValue('fullname');
            if (empty($owner_string)) {
                $owner_string = $gallery->get('owner');
            }
            $owner_link = $owner_link . htmlspecialchars($owner_string) . '</a>';
        }

        $gallery_count = $gallery->countImages(true);
        $background_color = $style['background'];

        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $created = _("Created:") . ' '
                   . strftime($date_format, (int)$gallery->get('date_created'));
        $modified = _("Modified") . ' '
                   . strftime($date_format, (int)$gallery->get('last_modified'));

        ob_start();
        include ANSEL_TEMPLATES . '/tile/gallery' . ($mini ? 'mini' : '') . '.inc';
        return ob_get_clean();
    }

}
