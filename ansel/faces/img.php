<?php
/**
 * Fetch face image for display
 *
 * $Horde: ansel/faces/img.php,v 1.9.2.1 2009/01/06 15:22:20 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';

$face_id = Util::getFormData('face');
$faces = Ansel_Faces::factory();
if (is_a($faces, 'PEAR_Error')) {
    Horde::logMessage($faces, __FILE__, __LINE__, PEAR_LOG_ERR);
    exit;
}

// Sendfile support. Lighttpd < 1.5 only understands the X-LIGHTTPD-send-file
// header
if ($conf['vfs']['src'] == 'sendfile') {
    $face = $faces->getFaceById($face_id);

    // Make sure the view exists
    if (!$faces->viewExists($face['image_id'], $face_id, true)) {
        Horde::logMessage(sprintf('Unable to locate or create face_id %u.',
                                  $face_id));
        exit;
    }

    // We definitely have an image for the face.
    $filename = $ansel_vfs->readFile(
        $faces->getVFSPath($face['image_id']) . 'faces',
        $face_id . $faces->getExtension());
    if (is_a($filename, 'PEAR_ERROR')) {
        Horde::logMessage($filename, __FILE__, __LINE__, PEAR_LOG_ERR);
        exit;
    }
    header('Content-type: image/' . $GLOBALS['conf']['image']['type']);
    header('X-LIGHTTPD-send-file: ' . $filename);
    header('X-Sendfile: ' . $filename);
    exit;
}

// Run it through PHP
$img = $faces->getFaceImageObject($face_id);
if (is_a($img, 'PEAR_Error')) {
    exit;
}
header('Content-type: image/' . $GLOBALS['conf']['image']['type']);
echo $img->raw();
