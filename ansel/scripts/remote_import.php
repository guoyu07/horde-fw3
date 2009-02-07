#!/usr/bin/env php
<?php
/**
* $Horde: ansel/scripts/remote_import.php,v 1.10.2.2 2008/12/30 18:16:29 jan Exp $
*
* This script allows for adding images to an Ansel install using an RPC
* interface. This script requires Horde's CLI and RPC libraries along with
* PEAR's Console_Getopt library.  You will need to make sure that those
* libraries reside somewhere in PHP's include path.
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Michael J. Rubinsky <mrubinsk@horde.org>
*/

/* Edit this to include your horde libs if they are not in your path */
ini_set('include_path', '/var/www/pear' . PATH_SEPARATOR . ini_get('include_path'));

/* Horde_CLI */
require_once 'Horde/CLI.php';
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}
Horde_CLI::init();
$cli = &Horde_CLI::singleton();

/* Horde_RPC */
require_once 'Horde/RPC.php';

/* Command line options */
require_once 'Console/Getopt.php';
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu:p:g:s:d:kr:zl',
                              array('help', 'username=', 'password=', 'gallery=', 'slug=', 'dir=', 'keep', 'remotehost=', 'gzip', 'lzf'));

if (is_a($ret, 'PEAR_Error')) {
    $cli->fatal($ret->getMessage());
}

/* Show help and exit if no arguments were set. */
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

/* Delete empty galleries by default */
$keepEmpties = false;

/* Assume we are creating a new gallery */
$gallery_id = null;
$gallery_slug = null;
$useCompression = 'none';

foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case 'u':
    case '--username':
        $username = $optValue;
        break;

    case 'p':
    case '--password':
        $password = $optValue;
        break;

    case 'd':
    case '--dir':
        $dir = $optValue;
        break;

    case 'h':
    case '--help':
        showHelp();
        exit;

    case 'k':
    case '--keep':
        $keepEmpties = true;
        break;
    case 'r':
    case '--remotehost':
        $rpc_endpoint = $optValue;
        break;
    case 'g':
    case '--gallery':
        $gallery_id = $optValue;
        break;
    case 's':
    case '--slug':
        $gallery_slug = $optValue;
        break;
    case 'z':
    case '--gzip':
        $useCompression = 'gzip';
        break;
    case 'l':
    case 'lzf':
        $useCompression = 'lzf';
        break;
    }
}

/* Sanity checks */
if (!empty($username) && !empty($password)) {
    $rpc_auth = array(
        'user' => $username,
        'pass' => $password);
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (empty($rpc_endpoint)) {
    $cli->fatal(_("You must specify the url for the remote Horde RPC server."));
}

if (empty($dir)) {
    $cli->fatal(_("You must specify a valid directory."));
}

processDirectory($dir, null, $gallery_id, $gallery_slug, $useCompression);

/**
 * Check for, and remove any empty galleries that may have been created during
 * import.
 */
function emptyGalleryCheck($gallery)
{
    if ($gallery->hasSubGalleries()) {
        $children = $GLOBALS['ansel_storage']->listGalleries(PERMS_SHOW, null, $gallery, false);
        foreach ($children as $child) {
            // First check all children to see if they are empty...
            emptyGalleryCheck($child);
            if (!$child->countImages() && !$child->hasSubGalleries()) {
                $result = $GLOBALS['ansel_storage']->removeGallery($child);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $child->get('name')), 'cli.success');
            }

            // Refresh the gallery values since we mucked around a bit with it
            $gallery = $GLOBALS['ansel_storage']->getGallery($gallery->getId());
            // Now that any empty children are removed, see if we are empty
            if (!$gallery->countImages() && !$gallery->hasSubGalleries()) {
                $result = $GLOBALS['ansel_storage']->removeGallery($gallery);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $gallery->get('name')), 'cli.success');
            }
        }
    }
}

/**
 * Read all images from a directory into the currently selected gallery.
 *
 * @param string $dir          The directory to create a gallery for and import.
 * @param integer $parent      Parent gallery id to attach the new gallery to.
 * @param integer $gallery_id  Start at this gallery_id.
 * @param string  $slug        Same as $gallery_id, except use this slug
 *
 * @return mixed  The gallery_id of the newly created gallery || PEAR_Error
 */
function processDirectory($dir, $parent = null, $gallery_id = null, $slug = null, $compress = 'none')
{
    global $cli, $rpc_auth, $rpc_endpoint;

    if (!is_dir($dir)) {
        $cli->fatal(sprintf(_("\"%s\" is not a directory."), $dir));
    }

    /* Create a gallery or use an existing one? */
    if (!empty($gallery_id) || !empty($slug)) {
        /* Start with an existing gallery */
        $method = 'images.getGalleries';
        $params = array(
            is_null($gallery_id) ? null : array($gallery_id),
            null,
            is_null($slug) ? null : array($slug),
        );
        $result = Horde_RPC::request('jsonrpc', $rpc_endpoint, $method, $params, $rpc_auth);
        if (is_a($result, 'PEAR_Error')) {
            $cli->fatal($result->getMessage());
        }
        $result = $result->result;
        if (is_a($result, 'PEAR_Error')) {
            $cli->fatal($result->getMessage());
        }
        if (empty($result)) {
            $cli->fatal(sprintf(_("Gallery %s not found."), (empty($slug) ? $gallery_id : $slug)));
        }

        /* Should have only one here, but jsonrpc returns an object, not array */
        foreach ($result as $gallery_info) {
           $name = $gallery_info->attribute_name;
           $gallery_id = $gallery_info->share_id;
        }
        if (empty($name)) {
            $cli->fatal(sprintf(_("Gallery %s not found."), (empty($slug) ? $gallery_id : $slug)));
        }
    } else {
        /* Creating a new gallery */
        $name = basename($dir);
        $cli->message(sprintf(_("Creating gallery: \"%s\""), $name), 'cli.message');
        $method = 'images.createGallery';
        $params = array(null, array('name' => $name), null, $parent);
        $result = Horde_RPC::request('jsonrpc', $rpc_endpoint, $method, $params, $rpc_auth);
        if (is_a($result, 'PEAR_Error')) {
            $cli->fatal($result->getMessage());
        }
        $gallery_id = $result->result;
        if (is_a($gallery_id, 'PEAR_Error')) {
            $cli->fatal(sprintf(_("The gallery \"%s\" couldn't be created: %s"), $name, $gallery_id->getMessage()));
        } else {
            $cli->message(sprintf(_("The gallery \"%s\" was created successfully."), $name), 'cli.success');
        }
    }

    /* Get the files and directories */
    $files = array();
    $directories = array();
    $h = opendir($dir);
    while (false !== ($entry = readdir($h))) {
        if ($entry == '.' ||
            $entry == '..' ||
            $entry == 'Thumbs.db' ||
            $entry == '.DS_Store' ||
            $entry == '.localized' ||
            $entry == '__MACOSX' ||
            strpos($entry, '.') === 1) {
            continue;
        }

        if (is_dir($dir . '/' . $entry)) {
            $directories[] = $entry;
        } else {
            $files[] = $entry;
        }
    }
    closedir($h);

    if ($files) {
        chdir($dir);

        // Process each file and upload to the gallery.
        $added_images = array();
        foreach ($files as $file) {
            $image = getImageFromFile($dir . '/' . $file, $compress);
            if (is_a($image, 'PEAR_Error')) {
                $cli->message($image->getMessage(), 'cli.error');
                continue;
            }

            $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
            $method = 'images.saveImage';
            $params = array(null, $gallery_id, $image, false, null, 'binhex', $slug, $compress);
            $result = Horde_RPC::request('jsonrpc', $rpc_endpoint, $method, $params, $rpc_auth);
            if (is_a($result, 'PEAR_Error')) {
                $cli->fatal($result->getMessage());
            }

            if (!is_a($result, 'stdClass')) {
                $cli->fatal(sprintf(_("There was an unspecified error. The server returned: %s"), print_r($result, true)));
            }
            $image_id = $result->result;
            if (is_a($image_id, 'PEAR_Error')) {
                $cli->message($image_id->getMessage(), 'cli.error');
                continue;
            }

            $added_images[] = $file;
        }

        $cli->message(sprintf(ngettext("Successfully added %d photo (%s) to gallery \"%s\" from \"%s\".", "Successfully added %d photos (%s) to gallery \"%s\" from \"%s\".", count($added_images)),
                              count($added_images), join(', ', $added_images), $name, $dir), 'cli.success');
    }

    if ($directories) {
        $cli->message(_("Adding subdirectories:"), 'cli.message');
        foreach ($directories as $directory) {
            processDirectory($dir . '/' . $directory, $gallery_id);
        }
    }

    return $gallery_id;
}

/**
 * Read an image from the filesystem.
 *
 * @TODO: pass in location of magic_db?
 *
 * @param string $file     The filename of the image.
 *
 * @return array  The image data of the file as an array or PEAR_Error
 */
function getImageFromFile($file, $compress = 'none')
{
    if (!file_exists($file)) {
        return PEAR::raiseError(sprintf(_("The file \"%s\" doesn't exist."),
                                $file));
    }

    global $conf, $cli;

    // Get the mime type of the file (and make sure it's an image).
    require_once 'Horde/MIME/Magic.php';
    $mime_type = MIME_Magic::analyzeFile($file, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
    if (strpos($mime_type, 'image') === false) {
        return PEAR::raiseError(sprintf(_("Can't get unknown file type \"%s\"."), $file));
    }

    if ($compress == 'gzip' && Util::loadExtension('zlib')) {
        $data = gzcompress(file_get_contents($file));
    } elseif ($compress == 'gzip') {
        $cli->fatal(_("Could not load the gzip extension"));
    } elseif ($compress == 'lzf' && Util::loadExtension('lzf')) {
        $data = lzf_compress(file_get_contents($file));
    } elseif ($compress == 'lzf') {
        $cli->fatal(_("Could not load the lzf extension"));
    } else {
        $data = file_get_contents($file);
    }

    $image = array('filename' => basename($file),
                   'description' => '',
                   'type' => $mime_type,
                   'data' => bin2hex($data),
                   );

    return $image;
}



/**
 * Show the command line arguments that the script accepts.
 */
function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-d, --dir[=directory]        Recursively add all files from the directory, creating\n                             a gallery for each directory"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln(_("-g, --gallery[=gallery_id]   The gallery id to add directory contents to"));
    $cli->writeln(_("-s, --slug[=gallery_slug]    The gallery slug to add directory contents to"));
    //$cli->writeln(_("-k, --keep                   Do not delete empty galleries after import is complete."));
    $cli->writeln(_("-r, --remotehost[=url]       The url of the remote rpc server."));
}
