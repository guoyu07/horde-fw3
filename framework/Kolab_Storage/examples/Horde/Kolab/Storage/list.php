<?php
/**
 * @package Kolab_Storage
 *
 * $Horde: framework/Kolab_Storage/examples/Horde/Kolab/Storage/list.php,v 1.1.2.2 2008/10/10 20:20:15 wrobel Exp $
 */

require_once 'Horde/Kolab/Storage/List.php';

$list = &Kolab_List::singleton();
var_dump($list->listFolders());
