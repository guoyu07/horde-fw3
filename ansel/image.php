<?php
/**
 * Responsible for making changes to image properties as well as making,
 * previewing and saving changes to the image.
 *
 * $Horde: ansel/image.php,v 1.160.2.8 2009/01/19 15:55:00 mrubinsk Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Text.php';
require_once 'Horde/Text/Filter.php';
require_once 'Horde/Variables.php';

/* Get all the form data */
$actionID = Util::getFormData('actionID');
$gallery_id = Util::getFormData('gallery');
$image_id = Util::getFormData('image');
$page = Util::getFormData('page', 0);
$watermark_font = Util::getFormData('font');
$watermark_halign = Util::getFormData('whalign');
$watermark_valign = Util::getFormData('wvalign');
$watermark = Util::getFormData('watermark', $prefs->getValue('watermark'));
$date = Ansel::getDateParameter();

/* Are we watermarking the image? */
if ($watermark) {
    require_once 'Horde/Identity.php';
    $identity = &Identity::singleton();
    $name = $identity->getValue('fullname');
    if (empty($name)) {
        $name = Auth::getAuth();
    }

    // Set up array of possible substitutions.
    $watermark_array = array('%N' => $name,            // User's fullname.
                             '%L' => Auth::getAuth()); // User login.
    $watermark = str_replace(array_keys($watermark_array),
                             array_values($watermark_array), $watermark);
    $watermark = strftime($watermark);
}

/* See if any tags were passed in to add (when js not present) */
$tags = Util::getFormData('addtag');

/* Redirect to the image list if no other action has been requested. */
if (is_null($actionID) && is_null($tags)) {
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}

/* Get the gallery object and style information */
$gallery = $ansel_storage->getGallery($gallery_id);
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push(sprintf(_("Gallery %s not found."), $gallery_id), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'), true));
    exit;
}

/* Do we have tags to update? */
if (!is_null($tags) && strlen($tags)) {
    $tags = explode(',', $tags);
    if (!empty($image_id)) {
        $resource = &$ansel_storage->getImage($image_id);
    } else {
        $resource = $gallery;
    }
    $existingTags = $resource->getTags();
    $tags = array_merge($existingTags, $tags);
    $result = $resource->setTags($tags);
    // If no other action requested, redirect back to the appropriate view
    if (empty($actionID)) {
        if (empty($image_id)) {
            $url = Ansel::getUrlFor('view', array_merge(
                                            array('view' => 'Gallery',
                                                  'gallery' => $gallery_id,
                                                  'slug' => $gallery->get('slug')),
                                            $date),
                                    true);

        } else {
            $url = Ansel::getUrlFor('view', array_merge(
                                            array('view' => 'Image',
                                                  'gallery' => $gallery_id,
                                                  'image' => $image_id,
                                                  'slug' => $gallery->get('slug')),
                                            $date),

                                    true);
        }
        header('Location: ' . $url);
        exit;
    }
}

/* Run through the action handlers. */
switch ($actionID) {
case 'deletetags':
    $tag = Util::getFormData('tag');
    if (!empty($image_id)) {
        $resource = &$ansel_storage->getImage($image_id);
        $page = Util::getFormData('page', 0);
        $url = Ansel::getUrlFor('view', array_merge(
                                        array('view' => 'Image',
                                              'gallery' => $gallery_id,
                                              'image' => $image_id,
                                              'page' => $page),
                                        $date),
                                true);
    } else {
        $resource = $gallery;
        $url = Ansel::getUrlFor('view', array_merge(
                                        array('view' => 'Gallery',
                                              'gallery' => $gallery_id),
                                        $date),
                                true);
    }
    $eTags = $resource->getTags();
    unset($eTags[$tag]);
    $resource->setTags($eTags);
    header('Location: ' . $url);
    exit;

case 'modify':
    $image = &$ansel_storage->getImage($image_id);
    $ret = Util::getFormData('ret', 'gallery');

    if (is_a($image, 'PEAR_Error')) {
        $notification->push(_("Photo not found."), 'horde.error');
        header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'), true));
        exit;
    }

    $title = sprintf(_("Edit properties :: %s"), $image->filename);

    /* Set up the form object. */
    require_once ANSEL_BASE . '/lib/Forms/Image.php';
    $vars = Variables::getDefaultVariables();
    if ($ret == 'gallery') {
        $vars->set('actionID', 'saveclose');
    } else {
        $vars->set('actionID', 'savecloseimage');
    }
    $form = new ImageForm($vars, $title);
    $renderer = new Horde_Form_Renderer();

    /* Set up the gallery attributes. */
    $vars->set('image_default', $image->id == $gallery->get('default'));
    $vars->set('image_desc', $image->caption);
    $vars->set('image_tags', implode(', ', $image->getTags()));
    $vars->set('image_originalDate', $image->originalDate);
    $vars->set('image_uploaded', $image->uploaded);

    require ANSEL_TEMPLATES . '/common-header.inc';
    $form->renderActive($renderer, $vars, 'image.php', 'post',
                        'multipart/form-data');

    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

case 'savecloseimage':
case 'saveclose':
case 'save':
    $title = _("Save Photo");
    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(sprintf(_("Access denied saving photo to \"%s\"."), $gallery->get('name')),
                            'horde.error');
        $imageurl = Ansel::getUrlFor('view', array_merge(
                array('gallery' => $gallery_id,
                      'slug' => $gallery->get('slug'),
                      'view' => 'Gallery',
                      'page' => $page),
                $date),
            true);
        header('Location: ' . $imageurl);
        exit;
    }

    /* Validate the form object. */
    require_once ANSEL_BASE . '/lib/Forms/Image.php';
    $vars = Variables::getDefaultVariables();
    $vars->set('actionID', 'save');
    $renderer = new Horde_Form_Renderer();
    $form = new ImageForm($vars, _("Edit a photo"));

    /* Update existing image. */
    if ($form->validate($vars)) {
         $form->getInfo($vars, $info);
        /* See if we were replacing photo */
        if (!empty($info['file0']['file']) &&
            !is_a(Browser::wasFileUploaded('file0'), 'PEAR_Error') &&
            filesize($info['file0']['file'])) {

            /* Read in the uploaded data. */
            $data = file_get_contents($info['file0']['file']);

            /* Try and make sure the image is in a recognizeable
             * format. */
            if (getimagesize($info['file0']['file']) === false) {
                $notification->push(_("The file you uploaded does not appear to be a valid photo."), 'horde.error');
                unset($data);
            }
        }

        $image = &$ansel_storage->getImage($image_id);
        $image->caption = $vars->get('image_desc');
        $image->setTags(explode(',' , $vars->get('image_tags')));

        require_once 'Horde/Date.php';
        $newDate = new Horde_Date($vars->get('image_originalDate'));
        $image->originalDate = (int)$newDate->timestamp();

        if (!empty($data)) {
            $result = $image->replace($data);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(_("There was an error replacing the photo."), 'horde.error');
            }
        }
        $image->save();

        if ($vars->get('image_default')) {
            if ($gallery->get('default') != $image_id) {
                // Changing default - force refresh of stack
                // If we have a default-pretty already, make sure we delete it
                $ids = unserialize($gallery->get('default_prettythumb'));
                if (is_array($ids)) {
                    foreach ($ids as $imageId) {
                        $gallery->removeImage($imageId, true);
                    }
                }
                $gallery->set('default_prettythumb', '');
            }
            $gallery->set('default', $image_id);
            $gallery->set('default_type', 'manual');
        } elseif ($gallery->get('default') == $image_id) {
            // Currently set as default, but we no longer wish it.
            $gallery->set('default', 0);
            $gallery->set('default_type', 'auto');
            // If we have a default-pretty already, make sure we delete it
            $ids = unserialize($gallery->get('default_prettythumb'));
            if (is_array($ids)) {
                foreach ($ids as $imageId) {
                    $gallery->removeImage($imageId);
                }
            }
            $gallery->set('default_prettythumb', '');
        }

        $gallery->save();
        $imageurl = Ansel::getUrlFor('view', array_merge(
                                             array('gallery' => $gallery_id,
                                                   'image' => $image_id,
                                                   'view' => 'Image',
                                                   'page' => $page),
                                             $date),
                                     true);
        if ($actionID == 'save') {
            /* Return to the image view. */
            header('Location: ' . $imageurl);
        } elseif ($actionID == 'saveclose') {
            Util::closeWindowJS('window.opener.location.href = window.opener.location.href; window.close();');
        } else {
            Util::closeWindowJS('window.opener.location.href = \'' . $imageurl . '\'; window.close();');
        }
        exit;
    }
    break;

case 'editimage':
case 'cropedit':
case 'resizeedit':
    $imageview_url = Ansel::getUrlFor('view', array_merge(
                                     array('gallery' => $gallery_id,
                                           'image' => $image_id,
                                           'view' => 'Image',
                                           'page' => $page),
                                     $date),
                                     true);
    $imageurl = Util::addParameter('image.php', array_merge(
                                            array('gallery' => $gallery_id,
                                                  'slug' => $gallery->get('slug'),
                                                  'image' => $image_id,
                                                  'page' => $page),
                                            $date));

    $galleryurl = Ansel::getUrlFor('view', array_merge(
                                       array('gallery' => $gallery_id,
                                             'page' => $page,
                                             'view' => 'Gallery',
                                             'slug' => $gallery->get('slug')),
                                       $date));

    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(_("Access denied editing the photo."),
                            'horde.error');

        /* Return to the image view. */
        header('Location: ' . $imageview_url);
        exit;
    }

    /* Retrieve image details. */
    $image = &$ansel_storage->getImage($image_id);
    $title = sprintf(_("Edit %s :: %s"), $gallery->get('name'),
                     $image->filename);

    if ($actionID == 'cropedit') {
        $geometry = $image->getDimensions('full');
        $x1 = 0;
        $y1 = 0;
        $x2 = $geometry['width'];
        $y2 = $geometry['height'];

        /* js and css files */
        Horde::addScriptFile('prototype.js');
        Horde::addScriptFile('builder.js');
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('controls.js', 'horde', true);
        Horde::addScriptFile('dragdrop.js', 'horde', true);
        Horde::addScriptFile('cropper.js');
        Ansel::attachStylesheet('cropper.css');
    } elseif ($actionID == 'resizeedit') {
        /* js and css files */
        // TODO: Combine these cases
        $geometry = $image->getDimensions('full');
        Horde::addScriptFile('prototype.js');
        Horde::addScriptFile('builder.js');
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('slider.js', 'horde', true);
        Horde::addScriptFile('dragdrop.js', 'horde', true);
   }

    require ANSEL_TEMPLATES . '/common-header.inc';
    require ANSEL_TEMPLATES . '/menu.inc';

    if ($actionID == 'cropedit') {
        require ANSEL_TEMPLATES . '/image/crop_image.inc';
    } elseif ($actionID == 'resizeedit') {
        require ANSEL_TEMPLATES . '/image/resize_image.inc';
    } else {
        require ANSEL_TEMPLATES . '/image/edit_image.inc';
    }
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

case 'watermark':
    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(sprintf(_("Access denied saving photo to \"%s\"."),
                                    $gallery->get('name')),
                            'horde.error');
        /* Return to the image view. */
        $imageurl = Ansel::getUrlFor('view', array_merge(
                                     array('gallery' => $gallery_id,
                                           'image' => $image_id,
                                           'view' => 'Image',
                                           'page' => $page,
                                           'slug' => $gallery->get('slug')),
                                     $date),
                                     true);
        header('Location: ' . $imageurl);
        exit;
    } else {
        $image = &$ansel_storage->getImage($image_id);
        $image->watermark('screen', $watermark, $watermark_halign,
                                $watermark_valign, $watermark_font);
        $ansel_vfs->writeData($image->getVFSPath('screen'),
                                $image->getVFSName('screen'),
                                $image->_image->raw(), true);
        $imageurl = Util::addParameter('image.php',array_merge(
                                       array('gallery' => $gallery_id,
                                             'image' => $image_id,
                                             'actionID' => 'editimage',
                                             'page' => $page),
                                       $date));

        header('Location: ' . Horde::applicationUrl($imageurl, true));
        exit;
    }

case 'rotate90':
case 'rotate180':
case 'rotate270':
case 'flip':
case 'mirror':
case 'grayscale':
case 'crop':
case 'resize':
    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(sprintf(_("Access denied saving photo to \"%s\"."),
                                    $gallery->get('name')),
                            'horde.error');
    } else {
        $image = &$ansel_storage->getImage($image_id);
        if (is_a($image, 'PEAR_Error')) {
            $notification->push($image->getMessage(), 'horde.error');
            header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'), true));
            exit;
        }

        switch ($actionID) {
        case 'rotate90':
        case 'rotate180':
        case 'rotate270':
            $angle = intval(substr($actionID, 6));
            $image->rotate('full', $angle);
            break;

        case 'flip':
            $image->flip('full');
            break;

        case 'mirror':
            $image->mirror('full');
            break;

        case 'grayscale':
            $image->grayscale('full');
            break;

        case 'crop':
            $image->load('full');
            $params = Util::getFormData('params');
            list($x1, $y1, $x2, $y2) = explode('.', $params);
            $result = $image->crop($x1, $y1, $x2, $y2);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                $notification->push($result->getMessage(), 'horde.error');
                $error = true;
            }
            break;
        case 'resize':
            $image->load('full');
            $width = Util::getFormData('width');
            $height = Util::getFormData('height');
            $result = $image->_image->resize($width, $height, true);
            break;
        }
        if (empty($error)) {
            $image->updateData($image->_image->raw());
        }
    }

    $imageurl = Util::addParameter('image.php', array_merge(
                                                array('gallery' => $gallery_id,
                                                      'image' => $image_id,
                                                      'actionID' => 'editimage',
                                                      'page' => $page),
                                                $date));
    header('Location: ' . Horde::applicationUrl($imageurl, true));
    exit;

case 'setwatermark':
    $title = _("Watermark");
    $image = &$ansel_storage->getImage($image_id);
    if (is_a($image, 'PEAR_Error')) {
        $notification->push($image->getMessage(), 'horde.error');
        header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'), true));
        exit;
    }
    /* Set up the form object. */
    require_once ANSEL_BASE . '/lib/Forms/Watermark.php';
    $vars = Variables::getDefaultVariables();
    $vars->set('actionID', 'previewcustomwatermark');
    $form = new WatermarkForm($vars, _("Watermark"));
    $renderer = new Horde_Form_Renderer();

    require ANSEL_TEMPLATES . '/common-header.inc';
    $form->renderActive($renderer, $vars, 'image.php', 'post');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

case 'previewcustomwatermark':
    $imageurl = Util::addParameter('image.php', array_merge(
                                   array('gallery' => $gallery_id,
                                         'image' => $image_id,
                                         'page' => $page,
                                         'watermark' => $watermark,
                                         'font' => $watermark_font,
                                         'whalign' => $watermark_halign,
                                         'wvalign' => $watermark_valign,
                                         'actionID' => 'previewwatermark'),
                                   $date));

    $url = Horde::applicationUrl($imageurl);
    $url = str_replace('&amp;', '&', $url);
    Util::closeWindowJS('window.opener.location.href = "' . $url . '";');
    exit;

case 'previewgrayscale':
case 'previewwatermark':
case 'previewflip':
case 'previewmirror':
case 'previewrotate90':
case 'previewrotate180':
case 'previewrotate270':
    $title = _("Edit Photo");
    $action = substr($actionID, 7);

    /* Retrieve image details. */
    $image = &$ansel_storage->getImage($image_id);
    $title = sprintf(_("Preview changes for %s :: %s"),
                     $gallery->get('name'),
                     $image->filename);

    require ANSEL_TEMPLATES . '/common-header.inc';
    require ANSEL_TEMPLATES . '/menu.inc';
    require ANSEL_TEMPLATES . '/image/preview_image.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;

    break;

case 'imagerotate90':
case 'imagerotate180':
case 'imagerotate270':
    $view = Util::getFormData('view');
    $angle = intval(substr($actionID, 11));
    $image = &$ansel_storage->getImage($image_id);
    $image->rotate($view, $angle);
    $image->display($view);
    exit;

case 'imageflip':
    $view = Util::getFormData('view');
    $image = &$ansel_storage->getImage($image_id);
    $image->flip($view);
    $image->display($view);
    exit;

case 'imagemirror':
    $view = Util::getFormData('view');
    $image = &$ansel_storage->getImage($image_id);
    $image->mirror($view);
    $image->display($view);
    exit;

case 'imagegrayscale':
    $view = Util::getFormData('view');
    $image = &$ansel_storage->getImage($image_id);
    $image->grayscale($view);
    $image->display($view);
    exit;

case 'imagewatermark':
    $view = Util::getFormData('view');
    $image = &$ansel_storage->getImage($image_id);
    $image->watermark($view, $watermark, $watermark_halign, $watermark_valign,
                      $watermark_font);
    $image->display($view);
    exit;

case 'delete':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }

    /* Delete the images if we're provided with a valid image ID. */
    if (count($images)) {
        if (!$gallery->hasPermission(Auth::getAuth(), PERMS_DELETE)) {
            $notification->push(sprintf(_("Access denied deleting photos from \"%s\"."), $gallery->get('name')), 'horde.error');
        } else {
            foreach ($images as $image) {
                $result = $gallery->removeImage($image);
                if (is_a($result, 'PEAR_Error')) {
                    $notification->push(
                        sprintf(_("There was a problem deleting photos: %s"),
                                $result->getMessage()), 'horde.error');
                } else {
                    $notification->push(_("Deleted the photo."),
                                        'horde.success');
                }
            }
        }
    }

    /* Recalculate the number of pages, since it might have changed */
    $children = $gallery->countGalleryChildren(PERMS_SHOW);
    $perpage = min($prefs->getValue('tilesperpage'),
                   $conf['thumbnail']['perpage']);
    $pages = ceil($children / $perpage);
    if ($page > $pages) {
        $page = $pages;
    }

    /* Return to the image list. */
    $imageurl = Ansel::getUrlFor('view', array_merge(
                                 array('gallery' => $gallery_id,
                                       'view' => 'Gallery',
                                       'page' => $page,
                                       'slug' => $gallery->get('slug')),
                                 $date),
                                 true);
    header('Location: ' . $imageurl);
    exit;

case 'move':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }

    /* Move the images if we're provided with at least one valid image_id. */
    $newGallery = Util::getFormData('new_gallery');
    if ($images && $newGallery) {
        $newGallery = $ansel_storage->getGallery($newGallery);
        if (is_a($newGallery, 'PEAR_Error')) {
            $notification->push(_("Bad input."), 'horde.error');
        } else {
            $result = $gallery->moveImagesTo($images, $newGallery);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result, 'horde.error');
            } else {
                $notification->push(
                    sprintf(ngettext("Moved %d photo from \"%s\" to \"%s\"",
                                     "Moved %d photos from \"%s\" to \"%s\"",
                                     $result),
                            $result, $gallery->get('name'), $newGallery->get('name')),
                    'horde.success');
            }
        }
    }

    /* Recalculate the number of pages, since it might have changed */
    $children = $gallery->countGalleryChildren(PERMS_SHOW);
    $perpage = min($prefs->getValue('tilesperpage'),
                   $conf['thumbnail']['perpage']);
    $pages = ceil($children / $perpage);
    if ($page > $pages) {
        $page = $pages;
    }

    /* Return to the image list. */
    $imageurl = Ansel::getUrlFor('view', array_merge(
                                 array('gallery' => $gallery_id,
                                       'view' => 'Gallery',
                                       'page' => $page,
                                       'slug' => $gallery->get('slug')),
                                 $date),
                                 true);
    header('Location: ' . $imageurl);
    exit;

case 'copy':
    if (is_array($image_id)) {
        $images = array_keys($image_id);
    } else {
        $images = array($image_id);
    }

    /* Move the images if we're provided with at least one valid image
     * ID. */
    $newGallery = Util::getFormData('new_gallery');
    if ($images && $newGallery) {
        $newGallery = $ansel_storage->getGallery($newGallery);
        if (is_a($newGallery, 'PEAR_Error')) {
            $notification->push(_("Bad input."), 'horde.error');
        } else {
            $result = $gallery->copyImagesTo($images, $newGallery);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push($result, 'horde.error');
            } else {
                $notification->push(
                    sprintf(ngettext("Copied %d photo to %s",
                                     "Copied %d photos to %s", $result),
                            $result, $newGallery->get('name')),
                    'horde.success');
            }
        }
    }

    /* Return to the image list. */
    $imageurl = Ansel::getUrlFor('view', array_merge(
                                 array('gallery' => $gallery_id,
                                       'view' => 'Gallery',
                                       'page' => $page,
                                       'slug' => $gallery->get('slug')),
                                 $date),
                                 true);
    header('Location: ' . $imageurl);
    exit;

case 'downloadzip':
    $galleryId = Util::getFormData('gallery');
    if ($galleryId) {
        $gallery = $ansel_storage->getGallery($galleryId);
        if (!Auth::getAuth() || is_a($gallery, 'PEAR_Error') ||
            !$gallery->hasPermission(Auth::getAuth(), PERMS_READ) ||
            $gallery->hasPasswd() || !$gallery->isOldEnough()) {

            $name = is_a($gallery, 'PEAR_Error') ? $galleryId : $gallery->get('name');
            $notification->push(sprintf(_("Access denied downloading photos from \"%s\"."), $name),
                                'horde.error');
            header('Location: ' . Horde::applicationUrl('view.php?view=List', true));
            exit;
        }
    }
    if (count($image_id)) {
        Ansel::downloadImagesAsZip(null, array_keys($image_id));
    } else {
        $notification->push(_("You must select images to download."), 'horde.error');
        if ($galleryId) {
            $url = Ansel::getUrlFor('view', array('gallery' => $galleryId,
                                                  'view' => 'Gallery',
                                                  'page' => $page,
                                                  'slug' => $gallery->get('slug')));
        } else {
            $url = Ansel::getUrlFor('view', array('view' => 'List'));
        }
        header('Location: ' . $url);
        exit;
    }
    exit;
    break;

case 'previewcrop':

    if (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
        $notification->push(_("Access denied editing the photo."), 'horde.error');
        $imageurl = Ansel::getUrlFor(
            'view', array('gallery' => $gallery_id,
                          'image' => $image_id,
                          'view' => 'Image',
                          'page' => $page));
        header('Location: ' . $imageurl);
    } else {
        $x1 = (int)Util::getFormData('x1');
        $y1 = (int)Util::getFormData('y1');
        $x2 = (int)Util::getFormData('x2');
        $y2 = (int)Util::getFormData('y2');
        $title = _("Crop");
        $action = substr($actionID, 7);

        /* Retrieve image details. */
        $image = &$ansel_storage->getImage($image_id);
        $title = sprintf(_("Preview changes for %s :: %s"),
                         $gallery->get('name'),
                         $image->filename);

        $params = $x1 . '.' . $y1 . '.' . $x2 . '.' . $y2;

        require ANSEL_TEMPLATES . '/common-header.inc';
        require ANSEL_TEMPLATES . '/menu.inc';
        require ANSEL_TEMPLATES . '/image/preview_cropimage.inc';
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }
    exit;

case 'imagecrop':
        if ($gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
            $params = Util::getFormData('params');
            list($x1, $y1, $x2, $y2) = explode('.', $params);
            $image = &$ansel_storage->getImage($image_id);
            $image->load('full');
            $image->crop($x1, $y1, $x2, $y2);
            $image->_image->display();
        }
        exit;

default:
    header('Location: ' . Ansel::getUrlFor('default_view', array()));
    exit;
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
$form->renderActive($renderer, $vars, 'image.php', 'post',
                    'multipart/form-data');
require $registry->get('templates', 'horde') . '/common-footer.inc';