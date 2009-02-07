<?php
/**
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: jonah/stories/index.php,v 1.64 2008/05/31 21:15:01 chuck Exp $
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

$news = Jonah_News::factory();

/* Redirect to the news index if no channel_id is specified. */
$channel_id = Util::getFormData('channel_id');
if (empty($channel_id)) {
    $notification->push(_("No channel requested."), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

$channel = $news->getChannel($channel_id);
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), PERMS_EDIT, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* Check if a forced refresh is being called for an external channel. */
$refresh = Util::getFormData('refresh');

/* Check if a URL has been passed. */
$url = Util::getFormData('url');

$stories = $news->getStories($channel_id, null, 0, !empty($refresh), null, true);
if (is_a($stories, 'PEAR_Error')) {
    $notification->push(sprintf(_("Invalid channel requested. %s"), $stories->getMessage()), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Do some state tests. */
if (empty($stories)) {
    $notification->push(_("No available stories."), 'horde.warning');
}
if (!empty($refresh)) {
    $notification->push(_("Channel refreshed."), 'horde.success');
}
if (!empty($url)) {
    header('Location: ' . $url);
    exit;
}

/* Get channel details, for title, etc. */
$channel = $news->getChannel($channel_id);

/* Build story specific fields. */
foreach ($stories as $key => $story) {
    /* story_published is the publication/release date, story_updated
     * is the last change date. */
    if (!empty($stories[$key]['story_published'])) {
        $stories[$key]['story_published_date'] = strftime($prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p'), $stories[$key]['story_published']);
    } else {
        $stories[$key]['story_published_date'] = '';
    }

    /* Default to no links. */
    $stories[$key]['pdf_link'] = '';
    $stories[$key]['edit_link'] = '';
    $stories[$key]['delete_link'] = '';

    /* These links only if internal channel. */
    if ($channel['channel_type'] == JONAH_INTERNAL_CHANNEL ||
        $channel['channel_type'] == JONAH_COMPOSITE_CHANNEL) {
        $stories[$key]['view_link'] = Horde::link(Horde::url($story['story_link']), $story['story_desc']) . htmlspecialchars($story['story_title']) . '</a>';

        /* PDF link. */
        $url = Horde::applicationUrl('stories/pdf.php');
        $url = Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
        $stories[$key]['pdf_link'] = Horde::link($url, _("PDF version")) . Horde::img('mime/pdf.png', _("PDF version"), '', $registry->getImageDir('horde')) . '</a>';

        /* Edit story link. */
        $url = Horde::applicationUrl('stories/edit.php');
        $url = Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
        $stories[$key]['edit_link'] = Horde::link($url, _("Edit story")) . Horde::img('edit.png', _("Edit story"), '', $registry->getImageDir('horde')) . '</a>';

        /* Delete story link. */
        $url = Horde::applicationUrl('stories/delete.php');
        $url = Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
        $stories[$key]['delete_link'] = Horde::link($url, _("Delete story")) . Horde::img('delete.png', _("Delete story"), '', $registry->getImageDir('horde')) . '</a>';

        /* Comment counter. */
        if ($conf['comments']['allow'] &&
            $registry->hasMethod('forums/numMessages')) {
            $comments = $registry->call('forums/numMessages', array($stories[$key]['story_id'], 'jonah'));
            if (!is_a($comments, 'PEAR_Error')) {
                $stories[$key]['comments'] = $comments;
            }
        }
    } else {
        if (!empty($story['story_body'])) {
            $stories[$key]['view_link'] = Horde::link(Horde::url($story['story_link']), $story['story_desc'], '', '_blank') . htmlspecialchars($story['story_title']) . '</a>';
        } else {
            $stories[$key]['view_link'] = Horde::link(Horde::externalUrl($story['story_url']), $story['story_desc'], '', '_blank') . htmlspecialchars($story['story_title']) . '</a>';
        }
    }
}

/* Set up the template action links. */
$actions = array();

/* Show a new story link only if internal channel. */
if ($channel['channel_type'] == JONAH_INTERNAL_CHANNEL) {
    $url = Horde::applicationUrl('stories/edit.php');
    $actions[_("New Story")] = Util::addParameter($url, 'channel_id', $channel_id);
}

/* Show a link to list management. */
if (Auth::isAdmin('jonah:admin', PERMS_EDIT)) {
    $url = Horde::applicationUrl('lists/index.php');
    $actions[_("Email subscribers")] = Util::addParameter($url, 'channel_id', $channel_id);
}

$template = new Horde_Template();
$template->setOption('gettext', true);
$template->set('actions', Jonah::setupActions($actions));
$template->set('header', htmlspecialchars($channel['channel_name']));
$template->set('refresh', Horde::link(Util::addParameter(Horde::selfUrl(true), array('refresh' => 1)), _("Refresh Channel")) . Horde::img('reload.png', '', '', $registry->getImageDir('horde')) . '</a>');
$template->set('listheaders', array(_("Story"), _("Date")));
$template->set('stories', $stories, true);
$template->set('read', $channel['channel_type'] == JONAH_INTERNAL_CHANNEL || $channel['channel_type'] == JONAH_COMPOSITE_CHANNEL, true);
$template->set('comments', $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages') && $channel['channel_type'] == JONAH_INTERNAL_CHANNEL, true);
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

$title = $channel['channel_name'];
require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/stories/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
