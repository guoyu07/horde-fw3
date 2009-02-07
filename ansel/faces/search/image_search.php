<?php
/**
 * Image search
 *
 * $Horde: ansel/faces/search/image_search.php,v 1.3.2.1 2009/01/06 15:22:23 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see /var/www/www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/UI/Pager.php';

$page = Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

if (($face_id = Util::getGet('face_id')) !== null) {
    $face = $faces->getFaceById($face_id, true);
    if (is_a($face, 'PEAR_Error')) {
        $notification->push($face->getMessage());
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    }
    $signature = $face['face_signature'];
    $results = $faces->getSignatureMatches($signature, $face_id, $perpage * $page, $perpage);
} else {
    $tmp = Horde::getTempDir();
    $path = $tmp . '/search_face_' . Auth::getAuth() . '.sig';
    if (file_exists($path) !== true) {
        $notification->push(_("You must upload the search photo first"));
        header('Location: ' . Horde::applicationUrl('faces/search/image.php'));
    }
    $signature = file_get_contents($path);
    $results = $faces->getSignatureMatches($signature, 0, $perpage * $page, $perpage);
}
if (is_a($results, 'PEAR_Error')) {
    $notification->push($results);
    $results = array();
}

$title = _("Photo search");
$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => count($results),
          'url' => 'faces/search/image_search.php',
          'perpage' => $perpage));

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/faces/search.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';