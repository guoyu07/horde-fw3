<?php
/**
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/channels/aggregate.php,v 1.15 2008/05/26 21:10:51 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

function _getLinks($id, $subid, $name, $title)
{
    $url = Horde::applicationUrl('channels/aggregate.php');
    $url = Util::addParameter($url, 'channel_id', $id);
    $url = Util::addParameter($url, 'subchannel_id', $subid);
    $edit = array('url' => Util::addParameter($url,'action', 'edit'), 'text' => sprintf(_("Edit channel \"%s\""), $name), 'title' => $title);
    $delete = array('url' => Util::addParameter($url, 'action', 'delete'), 'text' => sprintf(_("Remove channel \"%s\""), $name), 'title' => $title);
    return array($edit, $delete);
}

$news = Jonah_News::factory();
$renderer = new Horde_Form_Renderer();
$vars = Variables::getDefaultVariables();

/* Set up some variables. */
$channel_id = $vars->get('channel_id');
$channel = $news->getChannel($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    Horde::fatal($channel, __FILE__, __LINE__);
}
$channel_name = $channel['channel_name'];
$ids = preg_split('/:/', $channel['channel_url'], -1, PREG_SPLIT_NO_EMPTY);

/* Get the vars for channel type. */
$channel_type = $channel['channel_type'];
if ($channel_type != JONAH_AGGREGATED_CHANNEL) {
    $notification->push(_("This is no aggregated channel."), 'horde.error');
    header('Location: ' . Util::addParameter(Horde::applicationUrl('channels/edit.php', true), 'channel_id', $channel_id));
    exit;
}

/* Check permissions and deny if not allowed. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel_type), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Set up the form. */
$form = new Horde_Form($vars, sprintf(_("Aggregated channels for channel \"%s\""), $channel_name), 'channel_aggregate');
$form->setButtons(_("Add"));
$form->addHidden('', 'channel_id', 'int', false);
$form->addVariable(_("Channel Name"), 'channel_name', 'text', true);
$form->addVariable(_("Source URL"), 'channel_url', 'text', true, false, _("The url to use to fetch the stories, for example 'http://www.example.com/stories.rss'"));
$form->addVariable(_("Link"), 'channel_link', 'text', false);
$form->addVariable(_("Image"), 'channel_img', 'text', false);

if ($form->validate($vars)) {
    $subchannel = array('channel_url' => $vars->get('channel_url'),
                        'channel_name' => $vars->get('channel_name'),
                        'channel_link' => $vars->get('channel_link'),
                        'channel_img' => $vars->get('channel_img'),
                        'channel_type' => JONAH_EXTERNAL_CHANNEL);
    if ($vars->get('subchannel_id')) {
        $subchannel['channel_id'] = $vars->get('subchannel_id');
    }
    $save = $news->saveChannel($subchannel);
    if (is_a($save, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error saving the channel: %s"), $save->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("The channel \"%s\" has been saved."), $vars->get('channel_name')), 'horde.success');
        if (!$vars->get('subchannel_id')) {
            $ids[] = $save;
            $channel['channel_url'] = implode(':', $ids);
            $save = $news->saveChannel($channel);
            if (is_a($save, 'PEAR_Error')) {
                $notification->push(sprintf(_("There was an error updating the channel: %s"), $save->getMessage()), 'horde.error');
            } else {
                $notification->push(sprintf(_("The channel \"%s\" has been updated."), $channel['channel_name']), 'horde.success');
            }
        }

        header('Location: ' . Util::addParameter(Horde::applicationUrl('channels/aggregate.php', true), 'channel_id', $channel_id));
        exit;
    }
} elseif ($vars->get('action') == 'delete') {
    $subchannel = $news->getChannel($vars->get('subchannel_id'));
    $result = $news->deleteChannel($vars->get('subchannel_id'));
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error removing the channel: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("The channel \"%s\" has been removed."), $subchannel['channel_name']), 'horde.success');
        array_splice($ids, array_search($subchannel['channel_id'], $ids), 1);
        $channel['channel_url'] = implode(':', $ids);
        $save = $news->saveChannel($channel);
        if (is_a($save, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error updating the channel: %s"), $save->getMessage()), 'horde.error');
        } else {
            $notification->push(sprintf(_("The channel \"%s\" has been updated."), $channel['channel_name']), 'horde.success');
        }
    }

    header('Location: ' . Util::addParameter(Horde::applicationUrl('channels/aggregate.php', true), 'channel_id', $channel_id));
    exit;
} elseif ($vars->get('action') == 'edit') {
    $form->addHidden('', 'subchannel_id', 'int', false);
    $form->setButtons(_("Update"));
    $subchannel = $news->getChannel($vars->get('subchannel_id'));
    $vars->set('channel_name', $subchannel['channel_name']);
    $vars->set('channel_url', $subchannel['channel_url']);
    $vars->set('channel_link', $subchannel['channel_link']);
    $vars->set('channel_img', $subchannel['channel_img']);
}

foreach ($ids as $id) {
    $subchannel = $news->getChannel($id);
    if (is_a($subchannel, 'PEAR_Error')) {
        $name = $subchannel->getMessage();
        $url = '';
    } elseif (empty($subchannel['channel_name'])) {
        $name = $subchannel['channel_url'];
        $url = $subchannel['channel_url'];
    } else {
        $name = $subchannel['channel_name'];
        $url = $subchannel['channel_url'];
    }
    $form->insertVariableBefore('channel_name', '', 'subchannel' . $id, 'link', false, false, null, array(_getLinks($channel_id, $id, $name, $url)));
}

$main = Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'aggregate.php', 'post');

$template = new Horde_Template();
$template->set('main', $main);
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
