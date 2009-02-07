<?php
/**
 * $Horde: ansel/group.php,v 1.19.2.3 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Ben Chavet <ben@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';

// check for grouping
$groupby = basename(Util::getFormData('groupby', $prefs->getValue('groupby')));

// check for pref update
$actionID = Util::getFormData('actionID');
if ($actionID == 'groupby' &&
    ($groupby == 'owner' || $groupby == 'category' || $groupby == 'none')) {
    $prefs->setValue('groupby', $groupby);
}

// If we aren't supplied with a page number, default to page 0.
$gbpage = Util::getFormData('gbpage', 0);
$groups_perpage = $prefs->getValue('groupsperpage');

switch ($groupby) {
case 'category':
    $num_groups = $ansel_storage->countCategories(PERMS_SHOW);
    if (is_a($num_groups, 'PEAR_Error')) {
        $notification->push($num_groups);
        $num_groups = 0;
        $groups = array();
    } elseif ($num_groups) {
        $groups = $ansel_storage->listCategories(PERMS_SHOW,
                                                 $gbpage * $groups_perpage,
                                                 $groups_perpage);
    } else {
        $groups = array();
    }
    break;

case 'owner':
    require_once 'Horde/Identity.php';
    $num_groups = $ansel_storage->shares->countOwners(PERMS_SHOW, null,
                                                      false);
    if (is_a($num_groups, 'PEAR_Error')) {
        $notification->push($num_groups);
        $num_groups = 0;
        $groups = array();
    } elseif ($num_groups) {
        $groups = $ansel_storage->shares->listOwners(PERMS_SHOW, null,
                                                     false,
                                                     $gbpage * $groups_perpage,
                                                     $groups_perpage);
    } else {
        $groups = array();
    }
    break;

default:
    header('Location: ' . Ansel::getUrlFor('view',
                                           array('view' => 'List',
                                                 'groupby' => $groupby),
                                           true));
    exit;
}

// Set up pager.
require_once 'Horde/UI/Pager.php';
require_once 'Horde/Variables.php';
$vars = Variables::getDefaultVariables();
$group_pager = new Horde_UI_Pager('gbpage', $vars,
                                  array('num' => $num_groups,
                                        'url' => 'group.php',
                                        'perpage' => $groups_perpage));

$min = $gbpage * $groups_perpage;
$max = $min + $groups_perpage;
if ($max > $num_groups) {
    $max = $num_groups - $min;
}
$start = $min + 1;
$end = min($num_groups, $min + $groups_perpage);
$count = 0;
$groupby_links = array();
if ($groupby !== 'owner') {
    $groupby_links[] = Horde::link(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'owner'))) . _("owner") . '</a>';
} elseif ($groupby !== 'category') {
    $groupby_links[] = Horde::link(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'category'))) . _("category") . '</a>';
}
if ($groupby !== 'none') {
    $groupby_links[] = Horde::link(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'none'))) . _("none") . '</a>';
}

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
require ANSEL_TEMPLATES . '/group/header.inc';
foreach ($groups as $group) {
    require ANSEL_TEMPLATES . '/group/' . $groupby . '.inc';
}
require ANSEL_TEMPLATES . '/group/footer.inc';
require ANSEL_TEMPLATES . '/group/pager.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
