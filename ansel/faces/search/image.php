<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/search/image.php,v 1.7.2.1 2009/01/06 15:22:23 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/Form.php';
require_once 'Horde/Image.php';

/* Search from */
$form = new Horde_Form($vars);
$msg = _("Please upload photo with the face to search for. You can search only one face per time.");
$form->addVariable(_("Face to search for"), 'image', 'image', true, false, $msg, array(false));
$form->setButtons(_("Upload"));

if ($form->validate()) {

    $form->getInfo(null, $info);

    $tmp = Horde::getTempDir();
    $driver = empty($conf['image']['convert']) ? 'gd' : 'im';
    $img = Horde_Image::factory($driver, array('type' => $conf['image']['type'],
                                            'temp' => $tmp));

    $result = $img->loadFile($info['image']['file']);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage());
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
        exit;
    }

    $dimensions = $img->getDimensions();
    if ($dimensions['width'] < 50 || $dimensions['height'] < 50) {
        $notification->push(_("Photo is too small. Search photo must be at least 50x50 pixels."));
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
        exit;
    }

    $result = $img->resize(min($conf['screen']['width'], $dimensions['width']),
                            min($conf['screen']['height'], $dimensions['height']));
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage());
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
        exit;
    }

    $path = $tmp . '/search_face_' . Auth::getAuth() . Ansel_Faces::getExtension();
    if (file_put_contents($path, $img->raw())) {
        header('Location: ' . Horde::applicationUrl('faces/search/image_define.php'));
    } else {
        $notification->push(_("Cannot store search photo"));
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    }
    exit;

}

$title = _("Upload face photo");
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

echo $tabs->render(Util::getGet('search_faces', 'image'));
$form->renderActive(null, null, null, 'post');

if (empty($name)) {
    // Do noting
} elseif (empty($results)) {
    echo _("No faces found");
} else {
    foreach ($results as $face_id => $face) {
        include ANSEL_TEMPLATES . '/tile/face.inc';
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';