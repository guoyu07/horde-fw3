<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/search/all.php,v 1.3.2.1 2009/01/06 15:22:22 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/UI/Pager.php';

$title = _("All faces");
$page = Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

$count = $faces->countAllFaces();
if (is_a($count, 'PEAR_Error')) {
    $notification->push($count->getDebugInfo());
    $count = 0;
    $results = array();
} else {
    $results = $faces->allFaces($page * $perpage, $perpage);
}

$vars = Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $count,
          'url' => 'faces/search/all.php',
          'perpage' => $perpage));

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
include ANSEL_TEMPLATES . '/faces/faces.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
