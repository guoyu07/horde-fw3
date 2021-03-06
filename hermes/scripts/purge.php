#!/usr/bin/php
<?php
/**
 * $Horde: hermes/scripts/purge.php,v 1.12.2.1 2009-01-06 15:24:00 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('HERMES_BASE', dirname(__FILE__) . '/..');
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
require_once HORDE_BASE . '/lib/core.php';

// Registry
$registry = &Registry::singleton();
$registry->pushApp('hermes', false);

// Hermes base libraries.
require_once HERMES_BASE . '/lib/Hermes.php';
$hermes = &Hermes::getDriver();

printf(_("Deleting data that was exported/billed more than %s days ago.\n"), $conf['time']['days_to_keep']);
$hermes->purge();
