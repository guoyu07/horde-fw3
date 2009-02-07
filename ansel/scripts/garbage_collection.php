#!/usr/bin/php
<?php
/**
 * $Horde: ansel/scripts/garbage_collection.php,v 1.6 2007/10/25 20:23:51 mrubinsk Exp $
 *
 * This script looks for images in the VFS that have no pointer in the
 * database. Any non-referenced images it finds get moved to a garbage
 * folder in Ansel's VFS directory.
 *
 * Make sure to run this as a user who has full permissions on the VFS
 * directory.
 */

@define('AUTH_HANDLER', true);
@define('ANSEL_BASE', dirname(__FILE__) . '/..');
@define('HORDE_BASE', ANSEL_BASE . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_CLI::init();

require_once ANSEL_BASE . '/lib/base.php';
require_once 'VFS.php';
require_once 'Console/Getopt.php';

// Default arguments.
$move = false;
$verbose = false;

// Parse command-line arguments.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'mv',
                              array('move', 'verbose'));

if (is_a($ret, 'PEAR_Error')) {
    die("Couldn't read command-line options.\n");
}
list($opts, $args) = $ret;
foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case '--move':
        $move = true;
        break;

    case 'v':
    case '--verbose':
        $verbose = true;
    }
}

$vfs = &VFS::singleton($conf['vfs']['type'], Horde::getDriverConfig('vfs', $conf['vfs']['type']));
$vfspath = '.horde/ansel/';
$garbagepath = $vfspath . 'garbage/';

$hash = $vfs->listFolder($vfspath, null, false, true);
sort($hash);

$count = 0;
foreach ($hash as $dir) {
    if ($dir['name'] == 'garbage') {
        continue;
    }
    $images = $vfs->listFolder($vfspath . $dir['name'] . '/full/');
    foreach ($images as $image) {
        $image_id = strpos($image['name'], '.') ? substr($image['name'], 0, strpos($image['name'], '.')) : $image['name'];
        $result = $ansel_db->queryOne('SELECT 1 FROM ansel_images WHERE image_id = ' . (int)$image_id);
        if (!$result) {
            if (!$count && !$vfs->isFolder($vfspath, 'garbage')) {
                $vfs->createFolder($vfspath, 'garbage');
            }

            $count++;

            if ($verbose) {
                echo $vfspath . $image['name'] . ' -> ' . $garbagepath . $image['name'] . "\n";
            }

            if ($move) {
                $vfs->move($vfspath . $dir['name'] . '/full/', $image['name'], $garbagepath);
                $vfs->deleteFile($vfspath . $dir['name'] . '/screen/', $image['name']);
                $vfs->deleteFile($vfspath . $dir['name'] . '/thumb/', $image['name']);
                $vfs->deleteFile($vfspath . $dir['name'] . '/mini/', $image['name']);

                // Try to clean directories too.
                $vfs->deleteFolder($vfspath . $dir['name'], 'full');
                $vfs->deleteFolder($vfspath . $dir['name'], 'screen');
                $vfs->deleteFolder($vfspath . $dir['name'], 'thumb');
                $vfs->deleteFolder($vfspath . $dir['name'], 'mini');
                $vfs->deleteFolder($vfspath, $dir['name']);
            }
        }
    }
}

if ($count) {
    echo "\nFound dangling images";
    if ($move) {
        echo " and moved $count to $garbagepath.\n";
    } else {
        echo ", run this script with --move to clean them up.\n";
    }
} else {
    echo "No cleanup necessary.\n";
}
