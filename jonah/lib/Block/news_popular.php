<?php

$block_name = _("Most Popular Stories");

/**
 * This class extends Horde_Block:: to provide an api to embed news
 * in other Horde applications.
 *
 * $Horde: jonah/lib/Block/news_popular.php,v 1.7 2008/05/13 12:15:28 jan Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 *
 * @package Horde_Block
 */
class Horde_Block_Jonah_news_popular extends Horde_Block {

    var $_app = 'jonah';

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';
        require JONAH_BASE . '/config/templates.php';

        $params['source'] = array('name' => _("Feed"),
                                  'type' => 'enum',
                                  'values' => array());

        $news = Jonah_News::factory();
        $channels = $news->getChannels();
        foreach ($channels as  $channel) {
            if ($channel['channel_type'] == JONAH_INTERNAL_CHANNEL) {
                $params['source']['values'][$channel['channel_id']] = $channel['channel_name'];
            }
        }
        natcasesort($params['source']['values']);

        $params['view'] = array('name' => _("View"),
                                'type' => 'enum',
                                'values' => array());
        foreach ($templates as $key => $template) {
            $params['view']['values'][$key] = $template['name'];
        }

        $params['max'] = array('name' => _("Maximum Stories"),
                               'type' => 'int',
                               'default' => 10,
                               'required' => false);

        return $params;
    }

    function _title()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        $news = Jonah_News::factory();
        $channel = $news->getChannel($this->_params['source']);
        if (is_a($channel, 'PEAR_Error')) {
            return @htmlspecialchars($channel->getMessage(), ENT_COMPAT, NLS::getCharset());
        }

        if (!empty($channel['channel_link'])) {
            $title = Horde::link(htmlspecialchars($channel['channel_link']), '', '', '_blank')
                . @htmlspecialchars($channel['channel_name'], ENT_COMPAT, NLS::getCharset())
                . _(" - Most read stories") . '</a>';
        } else {
            $title = @htmlspecialchars($channel['channel_name'], ENT_COMPAT, NLS::getCharset())
                . _(" - Most read stories");
        }

        return $title;
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once JONAH_BASE . '/lib/Jonah.php';
        require_once JONAH_BASE . '/lib/News.php';

        if (empty($this->_params['source'])) {
            return _("No feed specified.");
        }

        require_once 'Horde/Template.php';
        $news = Jonah_News::factory();
        $params = $this->_params();

        $view = isset($this->_params['view']) ? $this->_params['view'] : 'standard';
        if (!isset($this->_params['max'])) {
            $this->_params['max'] = $params['max']['default'];
        }


        return $news->renderChannel($this->_params['source'], $view, $this->_params['max'], 0, JONAH_ORDER_READ);
    }

}
