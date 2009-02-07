<?php
/**
 * $Horde: ansel/perms.php,v 1.24.2.1 2009/01/06 15:22:19 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

$fieldsList['show'] = 0;
$fieldsList['read'] = 1;
$fieldsList['edit'] = 2;
$fieldsList['delete'] = 3;

define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';
require_once 'Horde/Group.php';

$groups = &Group::singleton();
$auth = &Auth::singleton($conf['auth']['driver']);

$form = null;
$reload = false;
$actionID = Util::getFormData('actionID', 'edit');
switch ($actionID) {
case 'edit':
    $share = &$ansel_storage->getGallery(Util::getFormData('cid'));
    if (!is_a($share, 'PEAR_Error')) {
        $form = 'edit.inc';
        $perm = &$share->getPermission();
    } elseif (($share_name = Util::getFormData('share')) !== null) {
        $share = &$ansel_storage->shares->getShare($share_name);
        if (!is_a($share, 'PEAR_Error')) {
            $form = 'edit.inc';
            $perm = &$share->getPermission();
        }
    }

    if (is_a($share, 'PEAR_Error')) {
        $notification->push($share, 'horde.error');
    } elseif (isset($share) && Auth::getAuth() != $share->get('owner')) {
        exit('permission denied');
    }
    break;

case 'editform':
case 'editforminherit':
    $share = &$ansel_storage->getGallery(Util::getFormData('cid'));
    if (is_a($share, 'PEAR_Error')) {
        $notification->push(_("Attempt to edit a non-existent share."), 'horde.error');
    } else {
        if (Auth::getAuth() != $share->get('owner')) {
            exit('permission denied');
        }
        $perm = &$share->getPermission();

        // Process owner and owner permissions.
        $old_owner = $share->get('owner');
        $new_owner = Util::getFormData('owner', $old_owner);
        if ($old_owner !== $new_owner && !empty($new_owner)) {
            if ($old_owner != Auth::getAuth() && !Auth::isAdmin()) {
                $notification->push(_("Only the owner or system administrator may change ownership or owner permissions for a share"), 'horde.error');
            } else {
                $share->set('owner', $new_owner);
                $share->save();
                if (Util::getFormData('owner_show')) {
                    $perm->addUserPermission($new_owner, PERMS_SHOW, false);
                } else {
                    $perm->removeUserPermission($new_owner, PERMS_SHOW, false);
                }
                if (Util::getFormData('owner_read')) {
                    $perm->addUserPermission($new_owner, PERMS_READ, false);
                } else {
                    $perm->removeUserPermission($new_owner, PERMS_READ, false);
                }
                if (Util::getFormData('owner_edit')) {
                    $perm->addUserPermission($new_owner, PERMS_EDIT, false);
                } else {
                    $perm->removeUserPermission($new_owner, PERMS_EDIT, false);
                }
                if (Util::getFormData('owner_delete')) {
                    $perm->addUserPermission($new_owner, PERMS_DELETE, false);
                } else {
                    $perm->removeUserPermission($new_owner, PERMS_DELETE, false);
                }
            }
        }

        // Process default permissions.
        if (Util::getFormData('default_show')) {
            $perm->addDefaultPermission(PERMS_SHOW, false);
        } else {
            $perm->removeDefaultPermission(PERMS_SHOW, false);
        }
        if (Util::getFormData('default_read')) {
            $perm->addDefaultPermission(PERMS_READ, false);
        } else {
            $perm->removeDefaultPermission(PERMS_READ, false);
        }
        if (Util::getFormData('default_edit')) {
            $perm->addDefaultPermission(PERMS_EDIT, false);
        } else {
            $perm->removeDefaultPermission(PERMS_EDIT, false);
        }
        if (Util::getFormData('default_delete')) {
            $perm->addDefaultPermission(PERMS_DELETE, false);
        } else {
            $perm->removeDefaultPermission(PERMS_DELETE, false);
        }

        // Process guest permissions.
        if (Util::getFormData('guest_show')) {
            $perm->addGuestPermission(PERMS_SHOW, false);
        } else {
            $perm->removeGuestPermission(PERMS_SHOW, false);
        }
        if (Util::getFormData('guest_read')) {
            $perm->addGuestPermission(PERMS_READ, false);
        } else {
            $perm->removeGuestPermission(PERMS_READ, false);
        }
        if (Util::getFormData('guest_edit')) {
            $perm->addGuestPermission(PERMS_EDIT, false);
        } else {
            $perm->removeGuestPermission(PERMS_EDIT, false);
        }
        if (Util::getFormData('guest_delete')) {
            $perm->addGuestPermission(PERMS_DELETE, false);
        } else {
            $perm->removeGuestPermission(PERMS_DELETE, false);
        }

        // Process creator permissions.
        if (Util::getFormData('creator_show')) {
            $perm->addCreatorPermission(PERMS_SHOW, false);
        } else {
            $perm->removeCreatorPermission(PERMS_SHOW, false);
        }
        if (Util::getFormData('creator_read')) {
            $perm->addCreatorPermission(PERMS_READ, false);
        } else {
            $perm->removeCreatorPermission(PERMS_READ, false);
        }
        if (Util::getFormData('creator_edit')) {
            $perm->addCreatorPermission(PERMS_EDIT, false);
        } else {
            $perm->removeCreatorPermission(PERMS_EDIT, false);
        }
        if (Util::getFormData('creator_delete')) {
            $perm->addCreatorPermission(PERMS_DELETE, false);
        } else {
            $perm->removeCreatorPermission(PERMS_DELETE, false);
        }

        // Process user permissions.
        $u_names = Util::getFormData('u_names');
        $u_show = Util::getFormData('u_show');
        $u_read = Util::getFormData('u_read');
        $u_edit = Util::getFormData('u_edit');
        $u_delete = Util::getFormData('u_delete');

        foreach ($u_names as $key => $user) {
            // If the user is empty, or we've already set permissions
            // via the owner_ options, don't do anything here.
            if (empty($user) || $user == $new_owner) {
                continue;
            }

            if (!empty($u_show[$key])) {
                $perm->addUserPermission($user, PERMS_SHOW, false);
            } else {
                $perm->removeUserPermission($user, PERMS_SHOW, false);
            }
            if (!empty($u_read[$key])) {
                $perm->addUserPermission($user, PERMS_READ, false);
            } else {
                $perm->removeUserPermission($user, PERMS_READ, false);
            }
            if (!empty($u_edit[$key])) {
                $perm->addUserPermission($user, PERMS_EDIT, false);
            } else {
                $perm->removeUserPermission($user, PERMS_EDIT, false);
            }
            if (!empty($u_delete[$key])) {
                $perm->addUserPermission($user, PERMS_DELETE, false);
            } else {
                $perm->removeUserPermission($user, PERMS_DELETE, false);
            }
        }

        // Process group permissions.
        $g_names = Util::getFormData('g_names');
        $g_show = Util::getFormData('g_show');
        $g_read = Util::getFormData('g_read');
        $g_edit = Util::getFormData('g_edit');
        $g_delete = Util::getFormData('g_delete');

        foreach ($g_names as $key => $group) {
            if (empty($group)) {
                continue;
            }

            if (!empty($g_show[$key])) {
                $perm->addGroupPermission($group, PERMS_SHOW, false);
            } else {
                $perm->removeGroupPermission($group, PERMS_SHOW, false);
            }
            if (!empty($g_read[$key])) {
                $perm->addGroupPermission($group, PERMS_READ, false);
            } else {
                $perm->removeGroupPermission($group, PERMS_READ, false);
            }
            if (!empty($g_edit[$key])) {
                $perm->addGroupPermission($group, PERMS_EDIT, false);
            } else {
                $perm->removeGroupPermission($group, PERMS_EDIT, false);
            }
            if (!empty($g_delete[$key])) {
                $perm->addGroupPermission($group, PERMS_DELETE, false);
            } else {
                $perm->removeGroupPermission($group, PERMS_DELETE, false);
            }
        }

        $share->setPermission($perm);
        $share->save();

        /* If we were asked to, push permissions to all child shares
         * to. */
        if ($actionID == 'editforminherit') {
            $share->inheritPermissions();
        }

        $notification->push(sprintf(_("Updated %s."), $share->get('name')),
                            'horde.success');
        $form = 'edit.inc';
    }
    break;
}

if (is_a($share, 'PEAR_Error')) {
    $title = _("Edit Permissions");
} else {
    $children = $GLOBALS['ansel_storage']->listGalleries(PERMS_READ, false,
                                                         $share);
    $title = sprintf(_("Edit Permissions for %s"), $share->get('name'));
}

if ($auth->hasCapability('list')) {
    $userList = $auth->listUsers();
    sort($userList);
} else {
    $userList = array();
}

$groupList = $groups->listGroups();
asort($groupList);
if (is_a($groupList, 'PEAR_Error')) {
    Horde::logMessage($groupList, __FILE__, __LINE__, PEAR_LOG_NOTICE);
    $groupList = array();
}

require $registry->get('templates', 'horde') . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
if (!empty($form)) {
    /* Need to temporarily put the gallery name in the share so the form
    * template will find it when rendering the Save button */
    $share->set('name', $share->get('name'));
    require $registry->get('templates', 'horde') . '/shares/' . $form;
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
