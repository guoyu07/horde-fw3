<?php
/**
 * Find faces and display faces UI for entire gallery.
 *
 * TODO: Turn this into an Ansel_View::
 *
 * $Horde: ansel/faces/gallery.php,v 1.10.2.3 2009/07/06 15:58:55 mrubinsk Exp $
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
require_once 'Horde/Serialize.php';
require_once 'Horde/UI/Pager.php';
require_once 'Horde/Variables.php';

$gallery_id = (int)Util::getFormData('gallery');
if (empty($gallery_id)) {
    $notification->push(_("No gallery specified"), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('default_view', array()));
    exit;
}
$gallery = $ansel_storage->getGallery($gallery_id);
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push($gallery->getMessage(), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $gallery_id)));
    exit;
} elseif (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('gallery' => $gallery_id)));
    exit;
}
$gallery->setDate(Ansel::getDateParameter());
$page = Util::getFormData('page', 0);
$perpage = min($prefs->getValue('tilesperpage'), $conf['thumbnail']['perpage']);
$images = $gallery->getImages($page * $perpage, $perpage);

$reloadimage = $registry->getImageDir('horde') . '/reload.png';
$customimage = $registry->getImageDir('horde') . '/layout.png';
$customurl = Util::addParameter(Horde::applicationUrl('faces/custom.php'), 'page', $page);
$autogenerate = Ansel_Faces::autogenerate();

$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $gallery->countImages(),
          'url' => 'faces/gallery.php',
          'perpage' => $perpage));
$pager->preserve('gallery',  $gallery_id);

$title = sprintf(_("Searching for faces in %s"), Horde::link(Ansel::getUrlFor('view', array('gallery' => $gallery_id))) . $gallery->get('name') . '</a>');
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('stripe.js', 'horde', true);
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/faces/gallery.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
