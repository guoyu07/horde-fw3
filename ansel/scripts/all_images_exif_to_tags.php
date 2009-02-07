#!/usr/bin/env php
<?php
/**
* $Horde: ansel/scripts/all_images_exif_to_tags.php,v 1.7 2008/09/04 00:30:38 mrubinsk Exp $
*
* Bare bones script to auto append an image's exif fields to it's tags.
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Michael J. Rubinsky <mrubinsk@horde.org>
*/
@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
@define('ANSEL_BASE', HORDE_BASE . '/ansel');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_CLI::init();
$cli = &Horde_CLI::singleton();

/* Command line options */
require_once 'Console/Getopt.php';
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu:p:f:',
                              array('help', 'username=', 'password=', 'fields='));

if (is_a($ret, 'PEAR_Error')) {
    $cli->fatal($ret->getMessage());
}

/* Show help and exit if no arguments were set. */
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

// Default to only DateTimeOriginal
$exif_fields = array('DateTimeOriginal');
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
    case 'h':
    case '--help':
        showHelp();
        exit;
    case '--fields':
    case 'f':
        $exif_fields = explode(':', $optValue);
        break;
    }
}

require_once ANSEL_BASE . '/lib/base.php';

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = &Auth::singleton($conf['auth']['driver']);
    if (!$auth->authenticate($username, array('password' => $password))) {
        $cli->fatal(_("Username or password is incorrect."));
    } else {
        $cli->message(sprintf(_("Logged in successfully as \"%s\"."), $username), 'cli.success');
    }
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (!Auth::isAdmin()) {
    $cli->fatal(_("You must login with an administrative account."));
}

// Get the list of image ids that have exif data.
$sql = 'SELECT DISTINCT image_id from ansel_image_attributes;';
$results = $GLOBALS['ansel_db']->query($sql);
if (is_a($results, 'PEAR_Error')) {
    $cli->fatal($results->getMessage());
}
$image_ids = $results->fetchAll(MDB2_FETCHMODE_ASSOC);
$results->free();
foreach (array_values($image_ids) as $image_id) {
    $image = $ansel_storage->getImage($image_id['image_id']);
    if (!is_a($image, 'PEAR_Error')) {
        $results = $image->exifToTags($exif_fields);
        if (is_a($results, 'PEAR_Error')) {
            $cli->message(sprintf(_("Could not extract exif fields from %s: %s"), $image_id['image_id'], $results->getMessage()), 'cli.error');
        }
        $cli->message(sprintf(_("Extracted exif fields from %s"), $image->filename), 'cli.success');
    } else {
        $cli->message(sprintf(_("Could not extract exif fields from %s: %s"), $image_id['image_id'], $image->getMessage()), 'cli.error');
    }
}
$cli->message(_("Done"));
exit;

function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln(_("-f, --fields[=exif_fields]   A ':' delimited list of exif fields to include DateTimeOriginal is default."));
}