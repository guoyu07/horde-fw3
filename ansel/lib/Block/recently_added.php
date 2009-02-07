<?php

$block_name = _("Recently Added Photos");

/**
 * Display most recently added images.
 *
 * $Horde: ansel/lib/Block/recently_added.php,v 1.37.2.4 2009/01/06 15:22:29 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_recently_added extends Horde_Block {

    var $_app = 'ansel';
    var $_gallery = null;

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $params = array('gallery' => array(
                            'name' => _("Gallery"),
                            'type' => 'enum',
                            'default' => '__random',
                            'values' => array('all' => 'All')),
                        'limit' => array(
                             'name' => _("Maximum number of photos"),
                             'type' => 'int',
                             'default' => 10),
        );

        if ($GLOBALS['ansel_storage']->countGalleries(Auth::getAuth(), PERMS_READ) < $GLOBALS['conf']['gallery']['listlimit']) {
            foreach ($GLOBALS['ansel_storage']->listGalleries(PERMS_READ) as $id => $gal) {
                if (!$gal->hasPasswd() && $gal->isOldEnough()) {
                    $params['gallery']['values'][$id] = $gal->get('name');
                }
            }
        }

        return $params;
    }

    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';

        if ($this->_params['gallery'] != 'all') {
            $gallery = $this->_getGallery();
            if (is_a($gallery, 'PEAR_Error')) {
                return Horde::link(
                    Ansel::getUrlFor('view', array('view' => 'List'), true))
                    . _("Gallery") . '</a>';
            }

            // Build the gallery name.
            if (isset($this->_params['gallery'])) {
                $name = @htmlspecialchars($gallery->get('name'), ENT_COMPAT,
                                          NLS::getCharset());
            }

            $style = $gallery->getStyle();
            $viewurl = Ansel::getUrlFor('view',
                                        array('slug' => $gallery->get('slug'),
                                              'gallery' => $gallery->id,
                                              'view' => 'Gallery'),
                                        true);
        } else {
            $viewurl = Ansel::getUrlFor('view', array('view' => 'List'), true);
            $name = _("All Galleries");
        }
        return sprintf(_("Recently Added Photos From %s"),
                       Horde::link($viewurl) . $name . '</a>');
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        if ($this->_params['gallery'] == 'all') {
            $galleries = array();
        } elseif (!is_array($this->_params['gallery'])) {
            $galleries = array($this->_params['gallery']);
        } else {
            $galleries = $this->_params['gallery'];
        }

        // Retrieve the images, but protect against very large values for
        // limit.
        $results = $GLOBALS['ansel_storage']->getRecentImages(
            $galleries, min($this->_params['limit'], 100));
        if (is_a($results, 'PEAR_Error')) {
            return $results->getMessage();
        }
        $preview_url = Horde::applicationUrl('preview.php', true);
        $header = array(_("Date"), _("Photo"), _("Gallery"));

        $html = <<<HEADER

<div id="ansel_preview"></div>
<script type="text/javascript">
function previewImage(e, image_id) {
    $('ansel_preview').style.left = Event.pointerX(e) + 'px';
    $('ansel_preview').style.top = Event.pointerY(e) + 'px';
    new Ajax.Updater({success:'ansel_preview'},
                     '$preview_url',
                     {method: 'post',
                      parameters:'?image=' + image_id,
                      onsuccess:$('ansel_preview').show()});
}
</script>
<table class="linedRow" cellspacing="0" style="width:100%">
 <thead><tr class="item nowrap">
  <th class="item leftAlign">$header[0]</th>
  <th class="item leftAlign">$header[1]</th>
  <th class="item leftAlign">$header[2]</th>
</tr></thead>
<tbody>
HEADER;

        foreach ($results as $image) {
            if (is_a($image, 'PEAR_Error')) {
                continue;
            }
            $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);

            // Don't show locked galleries in the block.
            if (!$gallery->isOldEnough() || $gallery->hasPasswd()) {
                continue;
            }
            $style = $gallery->getStyle();

            $galleryLink = Ansel::getUrlFor(
                'view', array('slug' => $gallery->get('slug'),
                              'gallery' => $gallery->id,
                              'view' => 'Gallery'),
                true);
            $galleryLink = Horde::link($galleryLink)
                . @htmlspecialchars($gallery->get('name'), ENT_COMPAT,
                                    NLS::getCharset())
                . '</a>';

            $caption = substr($image->caption, 0, 30);
            if (strlen($image->caption) > 30) {
                $caption .= '...';
            }

            /* Generate the image view url */
            $url = Ansel::getUrlFor(
                'view',
                array('view' => 'Image',
                      'slug' => $gallery->get('slug'),
                      'gallery' => $gallery->id,
                      'image' => $image->id,
                      'gallery_view' => $style['gallery_view']));
            $html .= '<tr><td>' . strftime('%x', $image->uploaded)
                . '</td><td class="nowrap">'
                . Horde::link(
                    $url, '', '', '', '', '', '',
                    array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");',
                          'onmouseover' => 'previewImage(event, ' . $image->id . ');'))
                . @htmlspecialchars(
                    strlen($caption) ? $caption : $image->filename,
                    ENT_COMPAT, NLS::getCharset())
                . '</a></td><td class="nowrap">' . $galleryLink . '</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    function _getGallery()
    {
        // Make sure we haven't already selected a gallery.
        if (is_a($this->_gallery, 'Ansel_Gallery')) {
            return $this->_gallery;
        }

        // Get the gallery object and cache it.
        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['ansel_storage']->getGallery(
                $this->_params['gallery']);
        } else {
            $this->_gallery = $GLOBALS['ansel_storage']->getRandomGallery();
        }

        if (empty($this->_gallery)) {
            return PEAR::raiseError(_("Gallery does not exist."));
        } elseif (is_a($this->_gallery, 'PEAR_Error') ||
                  !$this->_gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {
            return PEAR::raiseError(_("Access denied viewing this gallery."));
        }

        // Return a reference to the gallery.
        return $this->_gallery;
    }

}
