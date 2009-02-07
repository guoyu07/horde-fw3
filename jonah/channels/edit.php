<?php
/**
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/edit.php,v 1.32 2008/01/02 11:13:16 jan Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Forms/Feed.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

$news = Jonah_News::factory();

/* Set up the form variables and the form. */
$vars = Variables::getDefaultVariables();
$form = new FeedForm($vars);

/* Set up some variables. */
$formname = $vars->get('formname');
$channel_id = $vars->get('channel_id');

/* Form not yet submitted and is being edited. */
if (!$formname && $channel_id) {
    $vars = new Variables($news->getChannel($channel_id));
}

/* Get the vars for channel type. */
$channel_type = $vars->get('channel_type');
$old_channel_type = $vars->get('old_channel_type');
$changed_type = false;

/* Check permissions and deny if not allowed. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel_type), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* If this is null then new form, so set both to default. */
if (is_null($channel_type)) {
    $channel_type = Jonah_News::getDefaultType();
    $old_channel_type = $channel_type;
}

/* Check if channel type has been changed and notify. */
if ($channel_type != $old_channel_type && $formname) {
    $changed_type = true;
    $notification->push(_("Feed type changed."), 'horde.message');
}
$vars->set('old_channel_type', $channel_type);

/* Output the extra fields required for this channel type. */
$form->setExtraFields($channel_type, $channel_id);

if ($formname && !$changed_type) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $save = $news->saveChannel($info);
        if (is_a($save, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error saving the feed: %s"), $save->getMessage()), 'horde.error');
        } else {
            $notification->push(sprintf(_("The feed \"%s\" has been saved."), $info['channel_name']), 'horde.success');
            if ($channel_type == JONAH_AGGREGATED_CHANNEL) {
                $notification->push(_("You can now edit the sub-feeds."), 'horde.message');
            } else {
                header('Location: ' . Horde::applicationUrl('channels/index.php', true));
                exit;
            }
        }
    }
}

$renderer = new Horde_Form_Renderer();
$main = Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'edit.php', 'post');

$template = new Horde_Template();
$template->set('main', $main);
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

$title = $form->getTitle();
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
