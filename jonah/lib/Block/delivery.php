<?php

$block_name = _("Feeds");

/**
 * This class extends Horde_Block:: to provide a list of deliverable internal
 * channels.
 *
 * $Horde: jonah/lib/Block/delivery.php,v 1.19 2008/02/05 11:07:11 jan Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Horde_Block
 */
class Horde_Block_Jonah_delivery extends Horde_Block {

    var $_app = 'jonah';

    function _title()
    {
        return _("Feeds");
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $news = Jonah_News::factory();

        $channels = array();
        $channels = $news->getChannels(JONAH_INTERNAL_CHANNEL);
        if (is_a($channels, 'PEAR_Error')) {
            $channels = array();
        }

        $html = '';

        foreach ($channels as $key => $channel) {
            /* Link for HTML delivery. */
            $url = Horde::applicationUrl('delivery/html.php');
            $url = Util::addParameter($url, 'channel_id', $channel['channel_id']);
            $label = sprintf(_("\"%s\" stories in HTML"), $channel['channel_name']);
            $html .= '<tr><td width="140">' .
                Horde::img('story_marker.png') . ' ' .
                Horde::link($url, $label, '', '', '', $label) .
                htmlspecialchars($channel['channel_name']) . '</a></td>';

            $html .= '<td>' . ($channel['channel_updated'] ? date('M d, Y H:i', (int)$channel['channel_updated']) : '-') . '</td>';

            /* Link for feed delivery. */
            $url = Horde::applicationUrl('delivery/rss.php', true, -1);
            $url = Util::addParameter($url, 'channel_id', $channel['channel_id']);
            $label = sprintf(_("RSS Feed of \"%s\""), $channel['channel_name']);
            $html .= '<td align="right" class="nowrap">' .
                     Horde::link($url, $label) .
                     Horde::img('feed.png') . '</a> ';

            /* Link for email delivery. */
            $url = Horde::applicationUrl('delivery/email.php');
            $url = Util::addParameter($url, 'channel_id', $channel['channel_id']);
            $label = sprintf(_("Have \"%s\" emailed to you"), $channel['channel_name']);
            $html .= Horde::link($url, $label, '', '', '', $label) .
                Horde::img('email.png', $label) .
                '</a></td></tr>';
        }

        if ($html) {
            return '<table cellspacing="0" width="100%" class="linedRow striped">' . $html . '</table>';
        } else {
            return '<p><em>' . _("No feeds are available.") . '</em></p>';
        }
    }

}
