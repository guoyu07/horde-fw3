<?php
/**
 * $Horde: ansel/gallery/delete.php,v 1.20.2.1 2009/01/06 15:22:24 jan Exp $
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

// Delete/empty the gallery if we're provided with a valid galleryId.
$actionID = Util::getPost('action');
$galleryId = Util::getPost('gallery');

if ($galleryId) {
    $gallery = $ansel_storage->getGallery($galleryId);

    switch ($actionID) {
    case 'delete':
        if (is_a($gallery, 'PEAR_Error')) {
            $notification->push($gallery->getMessage(), 'horde.error');
        } elseif (!$gallery->hasPermission(Auth::getAuth(), PERMS_DELETE)) {
            $notification->push(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->get('name')), 'horde.error');
        } else {
            $result = $ansel_storage->removeGallery($gallery);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(
                    _("There was a problem deleting %s: %s"),
                    $gallery->get('name'), $result->getMessage()),
                    'horde.error');
            } else {
                $notification->push(sprintf(
                    _("Successfully deleted %s."),
                    $gallery->get('name')), 'horde.success');
            }
        }

        // Clear the OtherGalleries widget cache
        if ($GLOBALS['conf']['ansel_cache']['usecache']) {
            $GLOBALS['cache']->expire('Ansel_OtherGalleries' . $gallery->get('owner'));
        }

        // Return to the default view.
        header('Location: ' . Ansel::getUrlFor('default_view', array()));
        exit;

    case 'empty':
        if (is_a($gallery, 'PEAR_Error')) {
            $notification->push($gallery->getMessage(), 'horde.error');
        } elseif (!$gallery->hasPermission(Auth::getAuth(), PERMS_DELETE)) {
            $notification->push(sprintf(_("Access denied deleting gallery \"%s\"."),
                                        $gallery->get('name')),
                                'horde.error');
        } else {
            $ansel_storage->emptyGallery($gallery);
            $notification->push(sprintf(_("Successfully emptied \"%s\""), $gallery->get('name')));
        }
        header('Location: '
               . Ansel::getUrlFor('view',
                                  array('view' => 'Gallery',
                                        'gallery' => $galleryId,
                                        'slug' => $gallery->get('slug')),
                                  true));
        exit;
    }
}