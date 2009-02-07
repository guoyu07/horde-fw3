<?php
/**
 * $Horde: ansel/xppublish.php,v 1.45.2.2 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('AUTH_HANDLER', true);
@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
require_once ANSEL_BASE . '/lib/XPPublisher.php';

$cmd = Util::getFormData('cmd');
if (empty($cmd)) {
    $publisher = new Horde_XPPublisher();
    $publisher->sendRegFile(
        $registry->getApp() . '-' . $conf['server']['name'],
        $registry->get('name'),
        sprintf(_("Publish your photos to %s on %s."), $registry->get('name'), $conf['server']['name']),
        Horde::applicationUrl(Util::addParameter('xppublish.php', 'cmd', 'publish'), true, -1),
        Horde::url($registry->getImageDir() . '/favicon.ico', true, -1));
    exit;
}

$PUBLISH_BUTTONS = 'false,true,false';
$PUBLISH_ONBACK = '';
$PUBLISH_ONNEXT = '';
$PUBLISH_CMD = '';

$title = sprintf(_("Publish to %s"), $registry->get('name'));
require ANSEL_TEMPLATES . '/common-header.inc';

// Check for a login.
if ($cmd == 'login') {
    $username = Util::getFormData('username');
    $password = Util::getFormData('password');
    if ($username && $password) {
        $auth = &Auth::singleton($conf['auth']['driver']);
        if ($auth->authenticate($username,
                                array('password' => $password))) {
            $cmd = 'list';
            $PUBLISH_BUTTONS = 'true,true,false';
            $PUBLISH_ONBACK = 'history.go(-1);';
        } else {
            echo '<span class="form-error">' . _("Username or password are incorrect.") . '</span>';
            $PUBLISH_BUTTONS = 'false,true,false';
        }
    } else {
        echo '<span class="form-error">'. _("Please enter your username and password.") . '</span>';
        $PUBLISH_BUTTONS = 'false,true,false';
    }
}

// If we don't have a valid login, print the login form.
if (!Auth::isAuthenticated()) {
    $PUBLISH_ONNEXT = 'login.submit();';
    $PUBLISH_CMD = 'login.username.focus();';
    require ANSEL_TEMPLATES . '/xppublish/login.inc';
    require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

// If we already have a login (through sessions or whatever), and this
// is the initial request, assume we want to list galleries.
if ($cmd == 'publish') {
    $cmd = 'list';
}

// We're listing galleries.
$galleryId = Util::getFormData('gallery');
if ($cmd == 'list') {
    $PUBLISH_ONNEXT = 'folder.submit();';
    $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=publish', true) . '";';
    $PUBLISH_BUTTONS = 'true,true,true';
    require ANSEL_TEMPLATES . '/xppublish/list.inc';
}

// Check if a gallery was selected from the list.
if ($cmd == 'select') {
    if (!$galleryId || !$ansel_storage->galleryExists($galleryId)) {
        $error = _("Invalid gallery specified.") . "<br />\n";
    } else {
        $gallery = $ansel_storage->getGallery($galleryId);
        if (is_a($gallery, 'PEAR_ERROR')) {
            $error = _("There was an error accessing the gallery");
        } else {
            $error = false;
        }
    }

    if ($error) {
        echo '<span class="form-error">' . $error . '</span><br />';
        echo _("Press the \"Back\" button and try again.");
        $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=list', true) . '";';
        $PUBLISH_BUTTONS = 'true,false,true';
    } else {
        echo '<form id="folder">';
        Util::pformInput();
        echo '<input type="hidden" name="gallery" value="' . $galleryId . '" />';
        echo '</form>';

        $PUBLISH_CMD = 'publish();';
    }
}

// We're creating a new gallery.
if ($cmd == 'new') {
    $create = Util::getFormData('create');
    $galleryId = Util::getFormData('gallery_id');
    $gallery_name = Util::getFormData('gallery_name');
    $gallery_desc = Util::getFormData('gallery_desc');
    if ($create) {
        /* Creating a new gallery. */
        $gallery = $ansel_storage->createGallery(array('name' => $gallery_name,
                                                       'desc' => $gallery_desc));
        if (is_a($gallery, 'PEAR_Error')) {
            $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"), $gallery_name, $gallery->getMessage());
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        } else {
            $galleryId = $gallery->id;
            $msg = sprintf(_("The gallery \"%s\" was created successfully."), $gallery_name);
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        }
    } else {
        if (empty($galleryId) && $prefs->getValue('autoname')) {
            $galleryId = md5(microtime());
        }
        if (!$gallery_name) {
            $gallery_name = _("Untitled");
        }
        $PUBLISH_CMD = 'folder.gallery_name.focus(); folder.gallery_name.select();';
        $PUBLISH_ONNEXT = 'folder.submit();';
        $PUBLISH_ONBACK = 'window.location.href="' . Horde::applicationUrl('xppublish.php?cmd=list', true) . '";';
        $PUBLISH_BUTTONS = 'true,true,true';
        require ANSEL_TEMPLATES . '/xppublish/new.inc';
        require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }

    if ($error) {
        echo '<span class="form-error">' . $error . '</span><br />';
        echo _("Press the \"Back\" button and try again.");
        echo '<form id="folder">';
        Util::pformInput();
        echo '<input type="hidden" name="cmd" value="new" />';
        echo '<input type="hidden" name="gallery_name" value="' . $gallery_name . '" />';
        echo '</form>';
        $PUBLISH_ONBACK = 'folder.submit();';
        $PUBLISH_BUTTONS = 'true,false,true';
    } else {
        echo '<form id="folder">';
        Util::pformInput();
        echo '<input type="hidden" name="gallery" value="' . $galleryId . '" />';
        echo '<input type="hidden" name="cmd" value="list" />';
        echo '</form>';

        $PUBLISH_CMD = 'folder.submit();';
    }
}

// We're adding a photo.
if ($cmd == 'add') {
    $galleryId = Util::getFormData('gallery');
    $name = isset($_FILES['imagefile']['name']) ? Util::dispelMagicQuotes($_FILES['imagefile']['name']) : null;
    $file = isset($_FILES['imagefile']['tmp_name']) ? $_FILES['imagefile']['tmp_name'] : null;
    if (!$galleryId || !$ansel_storage->galleryExists($galleryId)) {
        $error = _("Invalid gallery specified.") . "<br />\n";
    } else {
        $gallery = $ansel_storage->getGallery($galleryId);
        if (is_a($gallery, 'PEAR_ERROR') || !$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            $error = sprintf(_("Access denied adding photos to \"%s\"."), $gallery->get('name'));
        } else {
            $error = false;
        }
    }
    if (!$name || $error) {
        $error = _("No file specified");
    } elseif (is_a($result = Browser::wasFileUploaded('imagefile', _("photo")), 'PEAR_Error')) {
        $error = $result->getMessage();
    } else {
        $image = &Ansel::getImageFromFile($file, array('image_filename' => $name));
        if (is_a($image, 'PEAR_Error')) {
            $error = $image->getMessage();
        }  else {
            $gallery = $ansel_storage->getGallery($galleryId);
            $image_id = $gallery->addImage($image);
            if (is_a($image_id, 'PEAR_Error')) {
                $error = _("There was a problem uploading the photo.");
            } else {
                $error = false;
            }
            if (is_a($image_id, 'PEAR_Error')) {
                $image_id = $image_id->getMessage();
            }
        }
    }

    if ($error) {
        printf(_("ERROR: %s"), $error);
    } else {
        echo 'SUCCESS';
    }
}

require ANSEL_TEMPLATES . '/xppublish/javascript.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
