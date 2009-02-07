<?php

$block_name = _("Bookmarks");

/**
 * Implementation of Horde_Block api to show bookmarks.
 *
 * $Horde: trean/lib/Block/bookmarks.php,v 1.37 2008/01/02 11:14:02 jan Exp $
 *
 * Copyright 2004-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @since   Trean 1.0
 * @package Horde_Block
 */
class Horde_Block_Trean_bookmarks extends Horde_Block {

    var $_app = 'trean';
    var $_folder = null;

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        /* Get folders to display. */
        $folders = Trean::listFolders(PERMS_READ);
        $default = null;
        if (is_a($folders, 'PEAR_Error')) {
            $GLOBALS['notification']->push(sprintf(_("An error occured listing folders: %s"), $folders->getMessage()), 'horde.error');
        } else {
            foreach ($folders as $key => $folder) {
                if (is_null($default)) {
                    $default = $folder->getId();
                }
                $values[$folder->getId()] = $folder->get('name');
            }
        }

        return array('folder' => array('name' => _("Folder"),
                                       'type' => 'enum',
                                       'default' => $default,
                                       'values' => $values),
                     'bookmarks' => array('name' => _("Sort by"),
                                          'type' => 'enum',
                                          'default' => 'title',
                                          'values' => array('title' => _("Title"),
                                                            'highest_rated' => _("Highest Rated"),
                                                            'most_clicked' => _("Most Clicked"))),
                     'rows' => array('name' => _("Display Rows"),
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
     * @return string   The title text.
     */
    function _title()
    {
        global $registry;

        $folder = $this->_getFolder();
        if (is_a($folder, 'PEAR_Error')) {
            $name = $registry->get('name');
        } else {
            $name = $folder->get('name');
            if (!$name) {
                $name = _("Bookmarks");
            }
        }

        return Horde::link(Horde::url($registry->getInitialPage(), true)) . $name . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once TREAN_TEMPLATES . '/star_rating_helper.php';

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $folder = $this->_getFolder();
        if (is_a($folder, 'PEAR_Error')) {
            return $folder;
        }

        $sortby = 'title';
        $sortdir = 0;
        switch ($this->_params['bookmarks']) {
        case 'highest_rated':
            $sortby = 'rating';
            $sortdir = 1;
            break;

        case 'most_clicked':
            $sortby = 'clicks';
            $sortdir = 1;
            break;
        }

        $html = '';
        $bookmarks = $folder->listBookmarks($sortby, $sortdir, 0, $this->_params['rows']);
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

    function _getFolder()
    {
        require_once dirname(__FILE__) . '/../base.php';

        if ($this->_folder == null) {
            $this->_folder = $GLOBALS['trean_shares']->getFolder($this->_params['folder']);
        }

        return $this->_folder;
    }

}
