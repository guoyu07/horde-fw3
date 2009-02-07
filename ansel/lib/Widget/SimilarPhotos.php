<?php
/**
 * Horde_Widget_SimilarPhotos:: class to display a widget containing mini
 * thumbnails of images that are similar, based on tags.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_SimilarPhotos extends Ansel_Widget {

    /**
     * @TODO
     *
     * @var unknown_type
     */
    var $_supported_views = array('Image');

    /**
     * Constructor
     *
     * @param array $params  Any parameters for this widget
     * @return Ansel_Widget_SimilarPhotos
     */
    function Ansel_Widget_SimilarPhotos($params)
    {
        parent::Ansel_Widget($params);
        $this->_title = _("Similar Photos");
    }

    /**
     * Return the HTML representing this widget.
     *
     * @return string  The HTML for this widget.
     */
    function html()
    {
        $html = $this->_htmlBegin();
        $html .= '<div id="similar">' . $this->_getRelatedImages() . '</div>';
        $html .= $this->_htmlEnd();
        return $html;
    }

    /**
     * Helper function for generating a widget of images related to this one.
     *
     * @TODO Rethink the way we determine if an image is related. This one is
     *       not ideal, as it just pops tags off the tag list until all the tags
     *       match. This could miss many related images.
     *
     * @return string  The HTML
     */
    function _getRelatedImages()
    {
        require_once ANSEL_BASE . '/lib/Tags.php';
        global $ansel_storage;

        $html = '';
        $tags = array_values($this->_view->resource->getTags());
        $imgs = Ansel_Tags::searchTags($tags);

        while (count($imgs['images']) <= 5 && count($tags)) {
            array_pop($tags);
            $newImgs = Ansel_Tags::searchTags($tags);
            $imgs['images'] = array_merge($imgs['images'], $newImgs['images']);
        }
        if (count($imgs['images'])) {
            $i = 0;
            foreach ($imgs['images'] as $imgId) {
                if ($i >= min(count($imgs['images']), 5)) {
                    break;
                }
                if ($imgId != $this->_view->resource->id) {
                    $rImg = &$ansel_storage->getImage($imgId);
                    if (is_a($rImg, 'PEAR_Error')) {
                        continue;
                    }
                    $rGal = $ansel_storage->getGallery($rImg->gallery);
                    if (is_a($rGal, 'PEAR_Error')) {
                        continue;
                    }
                    $title = sprintf(_("%s from %s"), $rImg->filename, $rGal->get('name'));
                    $html .= Horde::link(
                        Ansel::getUrlFor('view',
                                         array('image' => $imgId,
                                               'view' => 'Image',
                                               'gallery' => $rImg->gallery,
                                               'slug' => $rGal->get('slug')),
                                         true),
                        $title)
                    . '<img src="'
                    . Ansel::getImageUrl($imgId, 'mini', true)
                    . '" /></a>';
                    $i++;
                }
            }
        }
        return $html;
    }
}