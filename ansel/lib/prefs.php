<?php
/**
 * $Horde: ansel/lib/prefs.php,v 1.1.2.2 2009/01/06 15:22:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Ansel
 */

function handle_default_category_select($updated)
{
    $default_category = Util::getFormData('default_category_select');
    if (!is_null($default_category)) {
        $GLOBALS['prefs']->setValue('default_category', $default_category);
        return true;
    }

    return $updated;
}

function handle_default_gallerystyle_select($updated)
{
    $default_style = Util::getFormData('default_gallerystyle_select');
    if (!is_null($default_style)) {
        $GLOBALS['prefs']->setValue('default_gallerystyle', $default_style);
        return true;
    }

    return $updated;
}


