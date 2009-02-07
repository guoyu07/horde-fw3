<?php
/**
 * $Horde: jonah/stories/view.php,v 1.53 2008/01/02 11:13:19 jan Exp $
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';

$news = Jonah_News::factory();

$channel_id = Util::getFormData('channel_id');
$story_id = Util::getFormData('story_id');
if (!$story_id) {
    $story_id = $news->getLatestStoryId($channel_id);
    if (is_a($story_id, 'PEAR_Error')) {
        $notification->push(sprintf(_("Error fetching story: %s"), $story_id->getMessage()), 'horde.warning');
        require JONAH_TEMPLATES . '/common-header.inc';
        $notification->notify(array('listeners' => 'status'));
        require $registry->get('templates', 'horde') . '/common-footer.inc';
        exit;
    }
}

$story = $news->getStory($channel_id, $story_id, !$browser->isRobot());
if (is_a($story, 'PEAR_Error')) {
    $notification->push(sprintf(_("Error fetching story: %s"), $story->getMessage()), 'horde.warning');
    require JONAH_TEMPLATES . '/common-header.inc';
    $notification->notify(array('listeners' => 'status'));
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
} elseif (empty($story['story_body']) && !empty($story['story_url'])) {
    header('Location: ' . Horde::externalUrl($story['story_url']));
    exit;
}

/* Grab tag related content for entire channel */
require_once 'Horde/UI/TagCloud.php';
$cloud = new Horde_UI_TagCloud();
$allTags = $news->listTagInfo(array(), $channel_id);
foreach ($allTags as $tag_id => $taginfo) {
    $cloud->addElement($taginfo['tag_name'], Util::addParameter('results.php', array('tag_id' => $tag_id, 'channel_id' => $channel_id)), $taginfo['total']);
}

/* Prepare the story's tags for display */
$tag_html = array();
$tag_link = Util::addParameter(Horde::applicationUrl('stories/results.php'), 'channel_id', $channel_id);
foreach ($story['story_tags'] as $id => $tag) {
    $link = Util::addParameter($tag_link, 'tag_id', $id);
    $tag_html[] = Horde::link($link) . $tag . '</a>';
}

/* Filter and prepare story content. */
$story['story_title'] = htmlspecialchars($story['story_title']);
$story['story_desc'] = htmlspecialchars($story['story_desc']);
if (!empty($story['story_body_type']) && $story['story_body_type'] == 'text') {
    require_once 'Horde/Text/Filter.php';
    $story['story_body'] = Text_Filter::filter($story['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
}
if (!empty($story['story_url'])) {
    $story['story_body'] .= "\n<p>" . Horde::link(Horde::externalUrl($story['story_url'])) . htmlspecialchars($story['story_url']) . '</a></p>';
}
if (empty($story['story_published_date'])) {
    $story['story_published_date'] = false;
}

$story_template = new Horde_Template();
$story_template->set('story', $story, true);
$story_template->set('storytags', implode(', ', $tag_html));

$view_template = new Horde_Template();
$view_template->setOption('gettext', true);
$view_template->set('story', $story_template->fetch(JONAH_TEMPLATES . '/stories/story.html'));
$view_template->set('cloud', '<div class="tagSelector" ' . $cloud->buildHTML() . '</div>', true);
/* Insert link for sharing. */
if ($conf['sharing']['allow']) {
    $url = Horde::applicationUrl('stories/share.php');
    $url = Util::addParameter($url, array('story_id' => $story['story_id'], 'channel_id' => $channel_id));
    $view_template->set('sharelink', Horde::link($url) . _("Share this story") . '</a>', true);
} else {
    $view_template->set('sharelink', false, true);
}

/* Insert comments. */
if ($conf['comments']['allow']) {
    if (!$registry->hasMethod('forums/doComments')) {
        $err = 'User comments are enabled but the forums API is not available.';
        Horde::logMessage($err, __FILE__, __LINE__, PEAR_LOG_ERR);
    } else {
        $comments = $registry->call('forums/doComments', array('jonah', $story_id, 'commentCallback'));
        if (is_a($comments, 'PEAR_Error')) {
            Horde::logMessage($threads, __FILE__, __LINE__, PEAR_LOG_ERR);
            $comments = '';
        }
        $comments = $comments['threads'] . '<br />' . $comments['comments'];
        $view_template->set('comments', $comments, true);
    }
} else {
    $view_template->set('comments', false, true);
}

$view_template->set('menu', Jonah::getMenu('string'));
$view_template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $view_template->fetch(JONAH_TEMPLATES . '/stories/view.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
