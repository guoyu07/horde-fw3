<?php
/**
 * $Horde: ansel/gallery/captions.php,v 1.15.2.1 2009/01/06 15:22:24 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__) . '/..');
require_once ANSEL_BASE . '/lib/base.php';
require_once 'Horde/Text/Filter.php';

$galleryId = Util::getFormData('gallery');
if (!$galleryId) {
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}

$gallery = $ansel_storage->getGallery($galleryId);
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push(sprintf(_("Error accessing %s: %s"), $galleryId, $gallery->getMessage()), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}

if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(sprintf(_("Access denied setting captions for %s."), $gallery->get('name')), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}

/* We might be browsing by date */
$date = Ansel::getDateParameter();
$gallery->setDate($date);

/* Run through the action handlers. */
$do = Util::getFormData('do');
switch ($do) {
case 'save':
    /* Save a batch of captions. */
    $images = $gallery->getImages();
    foreach ($images as $image) {
        if (($caption = Util::getFormData('img' . $image->id)) !== null) {
            $image->caption = $caption;
            $image->save();
        }
    }

    $notification->push(_("Captions Saved."), 'horde.success');
    $style = $gallery->getStyle();
    header('Location: ' . Ansel::getUrlFor('view', array_merge(
                                           array('gallery' => $galleryId,
                                                 'slug' => $gallery->get('slug'),
                                                 'view' => 'Gallery'),
                                           $date), true));
    exit;
}

$title = _("Caption Editor");
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/captions/captions.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
