<?php

$block_name = _("Gallery");

/**
 * This file provides a selected Ansel gallery through the Horde_Blocks, by
 * extending the Horde_Blocks class.
 *
 * $Horde: ansel/lib/Block/gallery.php,v 1.45.2.7 2009/06/30 16:09:09 mrubinsk Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <Duck@obla.net>
 * @author  Marcus Ryan <marcus@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_gallery extends Horde_Block {

    var $_app = 'ansel';
    var $_gallery = null;

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $params = array('gallery' => array(
                            'name' => _("Gallery"),
                            'type' => 'enum',
                            'default' => '__random',
                            'values' => array('__random' => _("Random gallery"))),
                        'perpage' => array(
                            'name' => _("Maximum number of photos to display (0 means unlimited)"),
                            'type' => 'int',
                            'default' => 20),
                        'use_lightbox' => array(
                            'name' => _("Use a lightbox to view photos"),
                            'type' => 'checkbox',
                            'default' => true));

        if ($GLOBALS['ansel_storage']->countGalleries(Auth::getAuth(), PERMS_READ) < $GLOBALS['conf']['gallery']['listlimit']) {
            foreach ($GLOBALS['ansel_storage']->listGalleries() as $gal) {
                $params['gallery']['values'][$gal->id] = $gal->get('name');
            }
        }

        return $params;
    }

    function _title()
    {
        $gallery = $this->_getGallery();
        if (is_a($gallery, 'PEAR_Error')) {
            return Horde::link(Ansel::getUrlFor('view', array('view' => 'List'),
                                                true)) . _("Gallery") . '</a>';
        }

        // Build the gallery name.
        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] == '__random') {
            $name = _("Random Gallery") . ': ' . $gallery->get('name');
        } else {
            $name = $gallery->get('name');
        }

        $style = $gallery->getStyle();
        $viewurl = Ansel::getUrlFor('view',
            array('view' => 'Gallery',
                  'gallery' => $gallery->id,
                  'slug' => $gallery->get('slug')),
            true);
        return Horde::link($viewurl)
               . @htmlspecialchars($name, ENT_COMPAT, NLS::getCharset())
               . '</a>';

    }
    function _content()
    {
        $gallery = $this->_getGallery();
        if (is_a($gallery, 'PEAR_Error')) {
            return $gallery->getMessage();
        }

        $src = 'xrequest.php?requestType=Embed/gallery_id=' . $gallery->id
            . '/container=anselgalleryblock' . $gallery->id
            . '/count=' . $this->_params['perpage'];
        if (!empty($this->_params['use_lightbox'])) {
            $src .= '/lightbox=true';
        }
        $src = Horde::applicationUrl($src, true);

        $html = '<script type="text/javascript" src=' . $src
            . '"></script><div id="anselgalleryblock' . $gallery->id . '"></div>';


        // Be nice to people with <noscript>
        $style = $gallery->getStyle();
        $viewurl = Ansel::getUrlFor('view', array('view' => 'Gallery',
                                                  'gallery' => $gallery->id,
                                                  'slug' => $gallery->get('slug')),
                                    true);
        $html .= '<noscript>';
        $html .= Horde::link($viewurl, sprintf(_("View %s"),
                            $gallery->get('name')));

        if ($iid = $gallery->getDefaultImage('ansel_default') &&
            $gallery->hasPermission(Auth::getAuth(), PERMS_READ)) {

            $html .= '<img src="' . Ansel::getImageUrl($gallery->getDefaultImage('ansel_default'), 'thumb', true) . '" />';
        } else {
            $html .= Horde::img('thumb-error.png');
        }

        return $html . '</a></noscript>';
    }

    function _getGallery($retry = false)
    {
        require_once dirname(__FILE__) . '/../base.php';

        // Make sure we haven't already selected a gallery.
        if (is_a($this->_gallery, 'Ansel_Gallery')) {
            return $this->_gallery;
        }

        // Get the gallery object and cache it.
        if (isset($this->_params['gallery']) && $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['ansel_storage']->getGallery($this->_params['gallery']);
        } else {
            $this->_gallery = $GLOBALS['ansel_storage']->getRandomGallery();
        }

        // Protect at least a little bit against getting an empty gallery. We
        // can't just loop until we get one with images since it's possible we
        // actually don't *have* any with images yet.
        if ($this->_params['gallery'] == '__random' &&
            !empty($this->_gallery) &&
            !$this->_gallery->countImages() &&
            $this->_gallery->hasSubGalleries() && !$retry) {

            $this->_gallery = null;
            $this->_gallery = $this->_getGallery(true);
        }

        if (empty($this->_gallery)) {
            return PEAR::raiseError(_("Gallery does not exist."));
        } elseif (is_a($this->_gallery, 'PEAR_Error') ||
                  !$this->_gallery->hasPermission(Auth::getAuth(), PERMS_SHOW) ||
                  !$this->_gallery->isOldEnough() || $this->_gallery->hasPasswd()) {
            return PEAR::raiseError(_("Access denied viewing this gallery."));
        }

        // Return the gallery.
        return $this->_gallery;
    }

}
