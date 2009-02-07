<?php
/**
 * @package Jonah
 */

/**
 * Horde_Form
 */
require_once 'Horde/Form.php';

/**
 * Horde_Form_Action
 */
require_once 'Horde/Form/Action.php';

/**
 * This class extends Horde_Form to provide the form to add/edit
 * feeds.
 *
 * $Horde: jonah/lib/Forms/Feed.php,v 1.12 2008/01/02 11:13:18 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
class FeedForm extends Horde_Form
{
    /**
     */
    function FeedForm(&$vars)
    {
        $channel_id = $vars->get('channel_id');
        $editing = (!empty($channel_id));

        parent::Horde_Form($vars, ($editing ? _("Edit Feed") : _("New Feed")));

        $this->addHidden('', 'channel_id', 'int', false);
        $this->addHidden('', 'old_channel_type', 'text', false);

        $select_type =& $this->addVariable(_("Type"), 'channel_type', 'enum', true, false, null, array(Jonah_News::getAvailableTypes()));
        $select_type->setDefault(Jonah_News::getDefaultType());
        $select_type->setHelp('feed-type');
        $select_type->setAction(Horde_Form_Action::factory('submit'));

        $this->addVariable(_("Name"), 'channel_name', 'text', true);
        $this->addVariable(_("Extra information for this feed type"), 'extra_info', 'header', false);
    }

    /**
     */
    function getInfo(&$vars, &$info)
    {
        parent::getInfo($vars, $info);
        if ($vars->get('channel_type') == JONAH_COMPOSITE_CHANNEL &&
            is_array($vars->get('subchannels'))) {
                $info['channel_url'] = implode(':', $vars->get('subchannels'));
        }
    }

    /**
     */
    function setExtraFields($type = null, $channel_id = null)
    {
        if (is_null($type)) {
            $type = Jonah_News::getDefaultType();
        }

        switch ($type) {
        case JONAH_INTERNAL_CHANNEL:
            $this->addVariable(_("Description"), 'channel_desc', 'text', false);
            $this->addVariable(_("Channel URL if not the default one. %c gets replaced by the feed ID."), 'channel_link', 'text', false);
            $this->addVariable(_("Channel URL for further pages, if not the default one. %c gets replaced by the feed ID, %n by the story offset."), 'channel_page_link', 'text', false);
            $this->addVariable(_("Story URL if not the default one. %c gets replaced by the feed ID, %s by the story ID."), 'channel_story_url', 'text', false);
            break;

        case JONAH_EXTERNAL_CHANNEL:
            $interval = Jonah_News::getIntervalLabel();
            $v = &$this->addVariable(_("Caching"), 'channel_interval', 'enum', false, false, _("The interval before stories in this feed are rechecked for updates. If none, then stories will always be refetched from the source."), array($interval));
            $v->setDefault('86400');
            $this->addVariable(_("Source URL"), 'channel_url', 'text', true, false, _("The url to use to fetch the stories, for example 'http://www.example.com/stories.rss'"));
            $this->addVariable(_("Link"), 'channel_link', 'text', false);
            $this->addVariable(_("Image"), 'channel_img', 'text', false);
            break;

        case JONAH_AGGREGATED_CHANNEL:
            $this->addHidden('', 'channel_url', 'text', false);
            $interval = Jonah_News::getIntervalLabel();
            $this->addVariable(_("Description"), 'channel_desc', 'text', false);
            $v = &$this->addVariable(_("Caching"), 'channel_interval', 'enum', false, false, _("The interval before stories aggregated into this feeds are rechecked for updates. If none, then stories will always be refetched from the sources."), array($interval));
            $v->setDefault('86400');
            if (!empty($channel_id)) {
                $edit_url = Horde::applicationUrl('channels/aggregate.php');
                $edit_url = Util::addParameter($edit_url, 'channel_id', $channel_id);
                $edit_url = Util::addParameter($edit_url, 'channel_id', $channel_id);
                $this->addVariable(_("Source URLs"), 'channel_urls', 'link', false, false, null, array(array('text' => _("Edit aggregated feeds"), 'url' => $edit_url)));
            }
            break;

        case JONAH_COMPOSITE_CHANNEL:
            global $news;
            $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);
            $enum = array();
            foreach ($channels as $channel) {
                $enum[$channel['channel_id']] = $channel['channel_name'];
            }
            $this->addVariable(_("Description"), 'channel_desc', 'text', false);
            $this->addVariable(_("Channel URL if not the default one. %c gets replaced by the feed ID, %n by the story offset."), 'channel_page_link', 'text', false);
            $this->addVariable(_("Story URL if not the default one. %c gets replaced by the feed ID, %s by the story ID."), 'channel_story_url', 'text', false);
            $v = &$this->addVariable(_("Composite feeds"), 'subchannels', 'multienum', false, false, '', array($enum));
            if (!empty($channel_id)) {
                $channel = $news->getChannel($channel_id);
                $v->setDefault(explode(':', $channel['channel_url']));
            }
            break;
        }
    }

}
