<?php

$block_name = _("Highest-rated Bookmarks");

/**
 * Implementation of Horde_Block api to show the highest-rated bookmarks.
 *
 * $Horde: trean/lib/Block/highestrated.php,v 1.3 2008/01/02 11:14:02 jan Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Block
 */
class Horde_Block_Trean_highestrated extends Horde_Block {

    var $_app = 'trean';

    /**
     * Block configuration.
     */
    function _params()
    {
        return array('rows' => array('name' => _("Number of bookmarks to show"),
                                     'type' => 'enum',
                                     'default' => '10',
                                     'values' => array('10' => _("10 rows"),
                                                       '15' => _("15 rows"),
                                                       '25' => _("25 rows"))),
                     'template' => array('name' => _("Template"),
                                         'type' => 'enum',
                                         'default' => '1line',
                                         'values' => array('standard' => _("3 Line"),
                                                           '2line' => _("2 Line"),
                                                           '1line' => _("1 Line"))));
    }

    /**
     * The title to go in this block.
     *
     * @return string The title text.
     */
    function _title()
    {
        global $registry;
        return Horde::link(Horde::url($registry->getInitialPage(), true)) . _("Highest-rated Bookmarks") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string The content.
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once TREAN_TEMPLATES . '/star_rating_helper.php';

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $html = '';
        $bookmarks = $GLOBALS['trean_shares']->sortBookmarks('rating', 1, 0, $this->_params['rows']);
        foreach ($bookmarks as $bookmark) {
            ob_start();
            require $template;
            $html .= '<div class="linedRow">' . ob_get_clean() . '</div>';
        }

        if (!$bookmarks) {
            return '<p><em>' . _("No bookmarks to display") . '</em></p>';
        }

        return $html;
    }

}
