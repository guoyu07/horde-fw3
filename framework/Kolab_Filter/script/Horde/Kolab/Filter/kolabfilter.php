#!@php_bin@
<?php
/**
 *  A filter for outgoing mail on a Kolab Server. It rewrites headers
 *  and handles Outlook issues.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * $Horde: framework/Kolab_Filter/script/Horde/Kolab/Filter/kolabfilter.php,v 1.3 2008/12/12 15:24:04 wrobel Exp $
 *
 * @package Kolab
 */

/** Kolab filter library */
require_once 'Horde/Kolab/Filter/Content.php';
require_once 'Horde/Kolab/Filter/Response.php';

/* Parse the mail */
$parser = &new Horde_Kolab_Filter_Content();
$response = &new Horde_Kolab_Filter_Response();

$result = $parser->parse();
if (is_a($result, 'PEAR_Error')) {
    $response->handle($result);
}
exit(0);
?>
