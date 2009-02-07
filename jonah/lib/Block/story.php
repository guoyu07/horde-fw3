<?php

$block_name = _("Story");

/**
 * This class extends Horde_Block:: to provide the api to embed news
 * in other Horde applications.
 *
 * $Horde: jonah/lib/Block/story.php,v 1.8 2008/02/05 11:07:11 jan Exp $
 *
 * Copyright 2002-2007 Roel Gloudemans <roel@gloudemans.info>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Horde_Block
 */
class Horde_Block_Jonah_story extends Horde_Block {

    var $_app = 'jonah';

    var $_story = null;

    /**
     */
    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $news = Jonah_News::factory();
        $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);
        $channel_choices = array();
        foreach ($channels as $channel) {
            $channel_choices[$channel['channel_id']] = $channel['channel_name'];
        }
        natcasesort($channel_choices);

        return array('source' => array(
                         'name' => _("Feed"),
                         'type' => 'enum',
                         'values' => $channel_choices),
                     'story' => array(
                         'name' => _("Story"),
                         'type' => 'int'),
                     'countReads' => array(
                         'name' => _("Count reads of this story when this block is displayed"),
                         'type' => 'boolean',
                         'default' => false),
        );
    }

    /**
     */
    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        if (empty($this->_params['source']) ||
            empty($this->_params['story'])) {
            return _("Story");
        }

        $story = $this->_fetch();
        return is_a($story, 'PEAR_Error')
            ? @htmlspecialchars($story->getMessage(), ENT_COMPAT, NLS::getCharset())
            : '<span class="storyDate">'
                . @htmlspecialchars($story['story_updated_date'], ENT_COMPAT, NLS::getCharset())
                . '</span> '
                . @htmlspecialchars($story['story_title'], ENT_COMPAT, NLS::getCharset());
    }

    /**
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        if (empty($this->_params['source']) || empty($this->_params['story'])) {
            return _("No story is selected.");
        }

        $story = $this->_fetch();
        if (is_a($story, 'PEAR_Error')) {
            return sprintf(_("Error fetching story: %s"), $story->getMessage());
        }

        if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
            require_once 'Horde/Text/Filter.php';
            $story['story_body'] = Text_Filter::filter($story['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
        }

        $tag_html = array();
        foreach ($story['story_tags'] as $id => $tag) {
            $link = Util::addParameter('results.php', array('tag_id' => $id, 'channel_id' => $this->_params['source']));
            $tag_html[] = Horde::link($link) . $tag . '</a>';
        }

        return '<p class="storyTags">' . _("Tags: ")
            . implode(', ', $story['story_tags'])
            . '</p><p class="storySubtitle">'
            . htmlspecialchars($story['story_desc'])
            . '</p><div class="storyBody">' . $story['story_body']
            . '</div>';
    }

    /**
     * Get the story the block is configured for.
     */
    function _fetch()
    {
        if (is_null($this->_story)) {
            $news = Jonah_News::factory();
            $this->_story = $news->getStory($this->_params['source'],
                                            $this->_params['story'],
                                            $this->_params['countReads']);
        }

        return $this->_story;
    }

}
