<?php
/**
 * Explicitly add/edit a face range to an image.
 *
 * $Horde: ansel/faces/custom.php,v 1.10.2.1 2009/01/06 15:22:20 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$image_id = (int)Util::getFormData('image');
$face_id = (int)Util::getFormData('face');
$url = Util::getFormData('url');

$image = &$ansel_storage->getImage($image_id);
if (is_a($image, 'PEAR_Error')) {
    $notification->push($image);
    header('Location: ' . Horde::applicationUrl('list.php'));
    exit;
}

$gallery = $ansel_storage->getGallery($image->gallery);
if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(_("Access denied editing the photo."));
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $image->gallery)));
    exit;
}

$x1 = 0;
$y1 = 0;
$x2 = $conf['screen']['width'];
$y2 = $conf['screen']['width'];
$name = Util::getFormData('name');

if ($face_id) {
    require_once ANSEL_BASE . '/lib/Faces.php';
    $faces = Ansel_Faces::factory();

    if (is_a($faces, 'PEAR_Error')) {
        $notification->push($faces);
        header('Location: ' . $back_url);
        exit;
    }

    $face = $faces->getFaceById($face_id, true);
    if (is_a($face, 'PEAR_Error')) {
        $notification->push($face);
    } else {
        $x1 = $face['face_x1'];
        $y1 = $face['face_y1'];
        $x2 = $face['face_x2'];
        $y2 = $face['face_y2'];
        if (!empty($face['face_name'])) {
            $name = $face['face_name'];
        }
    }

}

$height = $x2 - $x1;
$width = $y2 - $y1;

$title = _("Create a new face");

Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('builder.js');
Horde::addScriptFile('effects.js', 'horde', true);
Horde::addScriptFile('controls.js', 'horde', true);
Horde::addScriptFile('dragdrop.js', 'horde', true);
Horde::addScriptFile('cropper.js');
Horde::addScriptFile('stripe.js', 'horde', true);

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/faces/custom.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
