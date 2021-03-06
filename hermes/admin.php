<?php
/**
 * $Horde: hermes/admin.php,v 1.29.2.1 2009-01-06 15:23:58 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('HERMES_BASE', dirname(__FILE__));
require_once HERMES_BASE . '/lib/base.php';
require_once HERMES_BASE . '/lib/Admin.php';
require_once 'Horde/Variables.php';

if (!Auth::isAdmin()) {
    exit('forbidden.');
}

$r = new Horde_Form_Renderer();
$vars = Variables::getDefaultVariables();
$beendone = false;

function _open()
{
    static $opened;

    if (is_null($opened)) {
        global $registry, $prefs, $browser, $conf, $notification, $beendone, $title;

        $opened = true;
        $beendone = true;
        $title = _("Administration");
        require HERMES_TEMPLATES . '/common-header.inc';
        require HERMES_TEMPLATES . '/menu.inc';
    }
}

if ($vars->exists('formname')) {
    switch ($vars->get('formname')) {
    case 'addjobtypeform':
        $form = new AddJobTypeForm($vars);
        $form->validate($vars);

        if ($form->isValid()) {
            $form->getInfo($vars, $info);
            $result = $hermes->updateJobType($info);
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("The job type \"%s\" has been added."), $vars->get('name')), 'horde.success');
            } else {
                $notification->push(sprintf(_("There was an error adding the job type: %s."), $result->getMessage()), 'horde.error');
            }
        } else {
            _open();

            $form->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Add Job Type"));
            $r->renderFormActive($form, $vars);
            $r->submit();
            $r->end();
            $form->close($r);
        }
        break;

    case 'editjobtypestep1form':
        $form1 = new EditJobTypeStep1Form($vars);
        $form1->validate($vars);

        _open();

        if ($form1->isValid()) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Job Type"):
                $form2 = new EditJobTypeStep2Form($vars);
                $form2->open($r, $vars, 'admin.php', 'post');

                // render the second stage form
                $r->beginActive(_("Edit Job Type, Step 2"));
                $r->renderFormActive($form2, $vars);
                $r->submit();
                $r->end();

                $form2->close($r);
                break;

            case _("Delete Job Type"):
                $form2 = new DeleteJobTypeForm($vars);
                $form2->open($r, $vars, 'admin.php', 'post');

                // render the deletion form
                $r->beginActive(_("Delete Job Type: Confirmation"));
                $r->renderFormActive($form2, $vars);
                $r->submit();
                $r->end();

                $form2->close($r);
                break;
            }
        } else {
            $form1->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Edit job type"));
            $r->renderFormActive($form1, $vars);
            $r->submit();
            $r->end();
            $form1->close($r);
        }
        break;

    case 'editclientstep1form':
        $form1 = new EditClientStep1Form($vars);
        $form1->validate($vars);

        _open();

        if ($form1->isValid()) {
            $form2 = new EditClientStep2Form($vars);
            $form2->open($r, $vars, 'admin.php', 'post');

            // render the second stage form
            $r->beginActive(_("Edit Client Settings, Step 2"));
            $r->renderFormActive($form2, $vars);
            $r->submit();
            $r->end();

            $form2->close($r);
        } else {
            $form1->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Edit Client Settings"));
            $r->renderFormActive($form1, $vars);
            $r->submit();
            $r->end();
            $form1->close($r);
        }
        break;

    case 'editjobtypestep2form':
        $form1 = new EditJobTypeStep2Form($vars);
        $form1->validate($vars);

        if ($form1->isValid()) {
            // update everything.
            $form1->getInfo($vars, $info);
            $info['id'] = $info['jobtype'];
            $result = $hermes->updateJobType($info);
            if (!PEAR::isError($result)) {
                $notification->push(_("The job type has been modified."), 'horde.success');
            } else {
                $notification->push(sprintf(_("There was an error editing the job type: %s."), $result->getMessage()), 'horde.error');
            }
        } else {
            _open();

            $form1->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Edit job type, Step 2"));
            $r->renderFormActive($form1, $vars);
            $r->submit();
            $r->end();
            $form1->close($r);
        }
        break;

    case 'editclientstep2form':
        $form = new EditClientStep2Form($vars);
        $form->validate($vars);

        if ($form->isValid()) {
            $result = $hermes->updateClientSettings($vars->get('client'),
                                                    $vars->get('enterdescription') ? 1 : 0,
                                                    $vars->get('exportid'));
            if (PEAR::isError($result)) {
                $notification->push(sprintf(_("There was an error editing the client settings: %s."), $result->getMessage()), 'horde.error');
            } else {
                $notification->push(_("The client settings have been modified."), 'horde.success');
            }
        } else {
            _open();

            $form->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Edit Client Settings, Step 2"));
            $r->renderFormActive($form, $vars);
            $r->submit();
            $r->end();
            $form->close($r);
        }
        break;

    case 'deletejobtypeform':
        $form = new DeleteJobTypeForm($vars);
        $form->validate($vars);

        if ($form->isValid()) {
            if ($vars->get('yesno') == 1) {
                $result = $hermes->deleteJobType($vars->get('jobtype'));
                if (!PEAR::isError($result)) {
                    $notification->push(_("The job type has been deleted."), 'horde.success');
                } else {
                    $notification->push(sprintf(_("There was an error deleting the job type: %s."), $result->getMessage()), 'horde.error');
                }
            } else {
                $notification->push(_("The job type was not deleted."), 'horde.message');
            }
        } else {
            _open();

            $form->open($r, $vars, 'admin.php', 'post');
            $r->beginActive(_("Delete Job Type: Confirmation"));
            $r->renderFormActive($form, $vars);
            $r->submit();
            $r->end();
            $form->close($r);
        }
        break;
    }
}

if (!$beendone) {
    $vars = new Variables();
    $form1 = new EditJobTypeStep1Form($vars); $edit1 = _("Edit Job Type"); $edit2 = _("Delete Job Type");
    $form2 = new AddJobTypeForm($vars); $add = _("Add Job Type");
    $form3 = new EditClientStep1Form($vars); $edit3 = _("Edit Client Settings");

    _open();

    $form1->open($r, $vars, 'admin.php', 'post');
    $r->beginActive($edit1);
    $r->renderFormActive($form1, $vars);
    $r->submit(array($edit1, $edit2));
    $r->end();
    $form1->close($r);

    echo '<br />';

    $form2->open($r, $vars, 'admin.php', 'post');
    $r->beginActive($add);
    $r->renderFormActive($form2, $vars);
    $r->submit($add);
    $r->end();
    $form2->close($r);

    echo '<br />';

    $form3->open($r, $vars, 'admin.php', 'post');
    $r->beginActive($edit3);
    $r->renderFormActive($form3, $vars);
    $r->submit($edit3);
    $r->end();
    $form3->close($r);
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
