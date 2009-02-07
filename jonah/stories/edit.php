<?php
/**
 * Script to add/edit stories.
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/stories/edit.php,v 1.43 2008/04/04 21:02:12 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once JONAH_BASE . '/lib/Forms/Story.php';
require_once 'Horde/Variables.php';
require_once 'Horde/Form/Action.php';
require_once 'Horde/Form/Renderer.php';

$news = Jonah_News::factory();

/* Set up the form variables. */
$vars = Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');

/* Fetch the channel details, needed for later and to check if valid
 * channel has been requested. */
$channel = $news->isChannelEditable($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    $notification->push(sprintf(_("Story editing failed: %s"), $channel->getMessage()), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check permissions. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Check if a story is being edited. */
$story_id = $vars->get('story_id');
if ($story_id && !$vars->get('formname')) {
    $story = $news->getStory($channel_id, $story_id);
    $story['story_tags'] = implode(',', array_values($story['story_tags']));
    $vars = new Variables($story);
}

/* Set up the form. */
$form = new StoryForm($vars);
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $result = $news->saveStory($info);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error saving the story: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("The story \"%s\" has been saved."), $info['story_title']), 'horde.success');
        $url = Util::addParameter('stories/index.php', 'channel_id', $channel_id);
        header('Location: ' . Horde::applicationUrl($url, true));
        exit;
    }
}

/* Needed javascript. */
Horde::addScriptFile('open_calendar.js', 'horde');

/* Render the form. */
$template = new Horde_Template();
$template->set('main', Util::bufferOutput(array($form, 'renderActive'), $form->getRenderer(), $vars, 'edit.php', 'post'));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

$title = $form->getTitle();
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
