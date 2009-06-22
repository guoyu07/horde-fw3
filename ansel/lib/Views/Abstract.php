<?php
/**
 * The Ansel_View_Abstract:: Parent class for the various Ansel_View classes
 *
 * $Horde: ansel/lib/Views/Abstract.php,v 1.35.2.6 2009/06/19 22:45:19 mrubinsk Exp $
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_Abstract {

    var $_params = array();

    /**
     * The ansel resource this view is for.
     *
     * @var mixed  Either an Ansel_Gallery or Ansel_Image
     */
    var $resource;

    /**
     * The gallery object (will be eq to $resource in a gallery view
     *
     * @var Ansel_Gallery
     */
    var $gallery;

    /**
     * Collection of Ansel_Widgets to display in this view.
     *
     * @var array
     */
    var $_widgets = array();

    function &getGallery($galleryId = null, $slug = '')
    {
        if (is_null($galleryId) && empty($slug)) {
            $galleryId = !empty($this->_params['gallery_id']) ? $this->_params['gallery_id'] : null;
            $slug = !empty($this->_params['gallery_slug']) ? $this->_params['gallery_slug'] : null;
        }

        if (empty($galleryId) && empty($slug)) {
            return PEAR::raiseError(_("No gallery specified"));
        }

        // If we have a slug, use it.
        if (!empty($slug)) {
            $gallery = &$GLOBALS['ansel_storage']->getGalleryBySlug($slug);
        } else {
            $gallery = &$GLOBALS['ansel_storage']->getGallery($galleryId);
        }
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery;
        } elseif (!$gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {
            return PEAR::raiseError(sprintf(_("Access denied to gallery \"%s\"."), $gallery->get('name')));
        }

        /* Set any date info we might have */
        if (!empty($this->_params['year'])) {
            $date = Ansel::getDateParameter(
                array('year' => $this->_params['year'],
                      'month' => $this->_params['month'],
                      'day' => $this->_params['day']));
        } else {
            $date = array();
        }
        $gallery->setDate($date);

        return $gallery;
    }

    /**
     * Add an Ansel_Widget to be displayed in this view.
     *
     * @param Ansel_Widget $widget  The Ansel_Widget to display
     */
    function addWidget($widget)
    {
        $result = $widget->attach($this);
        if (!empty($result)) {
            $this->_widgets[] = $widget;
        }
    }


    /**
     * Output any widgets associated with this view.
     *
     */
    function renderWidgets()
    {
        $this->_renderWidgets();
    }

    /**
     * Count the number of widgets we have attached.
     *
     * @return integer  The number of widgets attached to this view.
     */
    function countWidgets()
    {
        return count($this->_widgets);
    }

    /**
     * Default widget rendering, can be overridden by any subclass.
     *
     */
    function _renderWidgets()
    {
        echo '<div class="anselWidgets">';
        foreach ($this->_widgets as $widget) {
            if ($widget->_render == 'auto') {
                echo $widget->html();
                echo '<br />';
            }
        }
        echo '</div>';
    }

   /**
     * JSON representation of this gallery's images.
     *
     * @param array $images   An array of Ansel_Image objects. If this is null
     *                        the images are fetched based on $from and $count.
     *
     * @param integer $from   Image to start at.
     * @param integer $count  Number of images to get.
     *
     * @return string  A serialized JSON array.
     */
    function json($images = null, $full = false, $from = 0, $count = 0,
                  $image_view = 'screen', $view_links = false)
    {
        global $conf, $prefs;

        $json = array();
        $perpage = $prefs->getValue('tilesperpage', $conf['thumbnail']['perpage']);
        $curimage = 0;
        $curpage =  0;

        if (is_null($images)) {
            $images = $this->gallery->getImages($from, $count);
        }

        $style = $this->gallery->getStyle();

        foreach ($images as $image) {
            // Calculate the page this image will appear on in the
            // gallery view.
            if (++$curimage > $perpage) {
                ++$curpage;
                $curimage = 0;
            }

            $data = array(Ansel::getImageUrl($image->id, $image_view, $full, $style['name']),
                          htmlspecialchars($image->filename),
                          Text_Filter::filter($image->caption, 'text2html', array('parselevel' => TEXT_HTML_MICRO_LINKURL)),
                          $image->id,
                          $curpage);
            if ($view_links) {
                $data[] = Ansel::getUrlFor('view',
                    array('gallery' => $this->gallery->id,
                          'slug' => $this->gallery->get('slug'),
                          'image' => $image->id,
                          'view' => 'Image'),
                    true);
                $data[] = Ansel::getUrlFor('view',
                    array('gallery' => $image->gallery,
                          'slug' => $this->gallery->get('slug'),
                          'view' => 'Gallery'),
                    true);
            }
            // Source, Width, Height, Name, Caption, Image Id, Gallery Page
            $json[] = $data;
        }

        require_once 'Horde/Serialize.php';
        return Horde_Serialize::serialize($json, SERIALIZE_JSON, NLS::getCharset());
    }

    /**
     * @abstract
     * @return unknown_type
     */
    function viewType()
    {
    }

}