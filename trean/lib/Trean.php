<?php
/**
 * Trean Base Class.
 *
 * $Horde: trean/lib/Trean.php,v 1.85.2.1 2008/11/01 18:38:42 chuck Exp $
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Trean
 */
class Trean {

    /**
     */
    function getDb()
    {
        require_once 'MDB2.php';
        $config = $GLOBALS['conf']['sql'];
        unset($config['charset']);
        return MDB2::factory($config);
    }

    /**
     * List folders.
     *
     * @return array  A list of folders.
     */
    function &listFolders($perm = PERMS_SHOW, $parent = null, $allLevels = true)
    {
        return $GLOBALS['trean_shares']->getFolders(Auth::getAuth(), $perm, $parent, $allLevels);
    }

    /**
     * Counts the number of current user's folders list from storage.
     *
     * @param integer $perm       The level of permissions to require for a
     *                            folder to return it.
     *
     * @return integer  The number of matching folders.
     */
    function countFolders($perm = PERMS_SHOW)
    {
        $folders = $GLOBALS['trean_shares']->listFolders(Auth::getAuth(), $perm);
        if (is_a($folders, 'PEAR_Error')) {
            $GLOBALS['notification']->push(sprintf(_("An error occurred counting folders: %s"), $folders->getMessage()), 'horde.error');
            return 0;
        }
        return count($folders);
    }

    /**
     * Generates the body of a &lt;select&gt; form input to select a
     * folder. The &lt;select&gt; and &lt;/select&gt; tags are NOT included
     * in the output of this function.
     *
     * @param string $selected  The folder to have selected by default.
     *                          Defaults to the first option in the list.
     *
     * @return string  A string containing <option> elements for each folder
     *                 in the list.
     */
    function folderSelect($selected = null, $perm = PERMS_SHOW, $new = false)
    {
        // Default to the user's own main bookmarks.
        if (is_null($selected)) {
            $selected = Auth::getAuth();
        }

        $folders = Trean::listFolders($perm);
        if (is_a($folders, 'PEAR_Error')) {
            $folders = array();
        }

        require_once 'Horde/Tree.php';
        $tree = Horde_Tree::factory('folder_select', 'select');

        foreach ($folders as $folder_name => $folder) {
            /* Selected or not? */
            $params['selected'] = ($folder->getId() == $selected || $folder_name == $selected);

            /* Add the node and add the node params. */
            $tree->addNode($folder->getId(), $folder->getParent(), $folder->get('name'), substr_count($folder_name, ':'), true, $params);
        }

        $select = $new
            ? '<option value="">----</option><option value="*new*">' . _("New Folder") . '</option>'
            : '';

        return $select . $tree->renderTree();
    }

    /**
     */
    function sortOrder($sortby)
    {
        switch ($sortby) {
        case 'title':
            return 0;

        case 'rating':
        case 'clicks':
            return 1;
        }
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission, currently only 'max_folders'
     *                            and 'max_bookmarks'.
     *
     * @return mixed  The value of the specified permission.
     */
    function hasPermission($permission)
    {
        global $perms;

        if (!$perms->exists('trean:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('trean:' . $permission);
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_folders':
            case 'max_bookmarks':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
    }

    /**
     * Builds Trean's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $conf, $registry;

        require_once 'Horde/Menu.php';

        $menu = new Menu();
        $menu->add(Horde::applicationUrl('browse.php'), _("_Browse"), 'trean.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        $menu->add(Horde::applicationUrl('search.php'), _("_Search"), 'search.png', $registry->getImageDir('horde'));
        $menu->add(Horde::applicationUrl('reports.php'), _("_Reports"), 'reports.png');

        /* Import/Export. */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::applicationUrl('data.php'), _("_Import/Export"), 'data.png', $registry->getImageDir('horde'));
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Returns the "Reason Phrase" associated with the given HTTP status code
     * according to rfc2616.
     */
    function HTTPStatus($status_code)
    {
        switch ($status_code) {
        case '100': return _("Continue");
        case '101': return _("Switching Protocols");
        case '200': return _("OK");
        case '201': return _("Created");
        case '202': return _("Accepted");
        case '203': return _("Non-Authoritative Information");
        case '204': return _("No Content");
        case '205': return _("Reset Content");
        case '206': return _("Partial Content");
        case '300': return _("Multiple Choices");
        case '301': return _("Moved Permanently");
        case '302': return _("Found");
        case '303': return _("See Other");
        case '304': return _("Not Modified");
        case '305': return _("Use Proxy");
        case '307': return _("Temporary Redirect");
        case '400': return _("Bad Request");
        case '401': return _("Unauthorized");
        case '402': return _("Payment Required");
        case '403': return _("Forbidden");
        case '404': return _("Not Found");
        case '405': return _("Method Not Allowed");
        case '406': return _("Not Acceptable");
        case '407': return _("Proxy Authentication Required");
        case '408': return _("Request Time-out");
        case '409': return _("Conflict");
        case '410': return _("Gone");
        case '411': return _("Length Required");
        case '412': return _("Precondition Failed");
        case '413': return _("Request Entity Too Large");
        case '414': return _("Request-URI Too Large");
        case '415': return _("Unsupported Media Type");
        case '416': return _("Requested range not satisfiable");
        case '417': return _("Expectation Failed");
        case '500': return _("Internal Server Error");
        case '501': return _("Not Implemented");
        case '502': return _("Bad Gateway");
        case '503': return _("Service Unavailable");
        case '504': return _("Gateway Time-out");
        case '505': return _("HTTP Version not supported");
        default: return '';
        }
    }

    /**
     * Returns an apropriate icon for the given bookmark.
     */
    function getFavicon($bookmark)
    {
        global $registry;

        // Initialize VFS.
        require_once 'VFS.php';
        $vfs_params = Horde::getVFSConfig('favicons');
        if (is_a($vfs_params, 'PEAR_Error')) {
            // Default to the protocol icon.
            $protocol = substr($bookmark->url, 0, strpos($bookmark->url, '://'));
            return $registry->getImageDir() . '/protocol/' .
                (empty($protocol) ? 'http' : $protocol) . '.png';
        }

        $vfs = &VFS::singleton($vfs_params['type'], $vfs_params['params']);
        if ($bookmark->favicon
            && $vfs->exists('.horde/trean/favicons/', $bookmark->favicon)) {
            return Util::addParameter(Horde::applicationUrl('favicon.php'),
                                      'bookmark_id', $bookmark->id);
        }

        // Default to the protocol icon.
        $protocol = substr($bookmark->url, 0, strpos($bookmark->url, '://'));
        return $registry->getImageDir() . '/protocol/' .
            (empty($protocol) ? 'http' : $protocol) . '.png';
    }

}
