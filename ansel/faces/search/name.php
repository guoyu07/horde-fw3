<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/search/name.php,v 1.2.2.1 2009/01/06 15:22:23 jan Exp $
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
require_once 'Horde/UI/Pager.php';

/* Search from */
$form = new Horde_Form($vars);
$form->addVariable(_("Face name to search"), 'face_name', 'text', true);
$form->setButtons(_("Search"));

$page = Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

$name = Util::getFormData('face_name');
if (!empty($name)) {
    $page = Util::getFormData('page', 0);
    $perpage = $prefs->getValue('faceperpage');
    $count = $faces->countSearchFaces($name);
    if ($count) {
        $results = $faces->searchFaces($name, $page * $perpage, $perpage);
    }
} else {
    $page = 0;
    $perpage = 0;
    $count = 0;
}

$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $count,
            'url' => 'faces/search/name.php',
            'perpage' => $perpage));

$title = _("Search by name");
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

include ANSEL_TEMPLATES . '/faces/faces.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';