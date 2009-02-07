<?php

$block_name = _("Latest News");

/**
 * This class extends Horde_Block:: to provide the api to embed news
 * in other Horde applications.
 *
 * $Horde: jonah/lib/Block/latest.php,v 1.22 2008/02/05 11:07:11 jan Exp $
 *
 * Copyright 2002-2007 Roel Gloudemans <roel@gloudemans.info>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Horde_Block
 */
class Horde_Block_Jonah_latest extends Horde_Block {

    var $_app = 'jonah';

    var $_story = null;

    /**
     */
    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $params['source'] = array('name' => _("News Source"),
                                  'type' => 'enum',
                                  'values' => array());

        $news = Jonah_News::factory();
        $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);
        foreach ($channels as $channel) {
            $params['source']['values'][$channel['channel_id']] = $channel['channel_name'];
        }
        natcasesort($params['source']['values']);

        // Get first news source.
        $channel = reset($channels);
        $params['source']['default'] = $channel['channel_id'];

        $params['countReads'] = array(
            'name' => _("Count reads of the latest story when this block is displayed"),
            'type' => 'boolean',
            'default' => false);

        return $params;
    }

    /**
     */
    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        if (empty($this->_params['source'])) {
            return _("Latest News");
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

        if (empty($this->_params['source'])) {
            return _("No channel specified.");
        }

        $story = $this->_fetch();
        if (is_a($story, 'PEAR_Error')) {
            return sprintf(_("Error fetching story: %s"), $story->getMessage());
        }

        if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
            require_once 'Horde/Text/Filter.php';
            $story['story_body'] = Text_Filter::filter($story['story_body'], 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'class' => null));
        }

        return '<p class="storySubtitle">' . htmlspecialchars($story['story_desc']) .
            '</p><div class="storyBody">' . $story['story_body'] . '</div>';
    }

    /**
     * Get the latest story.
     */
    function _fetch()
    {
        if (empty($this->_params['source'])) {
            return;
        }

        if (is_null($this->_story)) {
            $news = Jonah_News::factory();
            $this->_story = $news->getStory($this->_params['source'],
                                            $news->getLatestStoryId($this->_params['source']),
                                            !empty($this->_params['countReads']));
        }

        return $this->_story;
    }

}
