<?php
/**
 * $Horde: ansel/lib/Forms/Image.php,v 1.5.2.3 2009/01/06 15:22:29 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/** Horde_Form **/
require_once 'Horde/Form.php';

class ImageForm extends Horde_Form {

    var $_useFormToken = false;

    function ImageForm(&$vars, $title)
    {
        global $gallery;

        parent::Horde_Form($vars, $title);

        $this->setButtons(_("Save"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);
        $this->addHidden('', 'page', 'text', false);

        $filesize = ini_get('upload_max_filesize');
        if (substr($filesize, -1) == 'M') {
            $filesize = $filesize * 1048576;
        }
        $filesize = $this->_get_size($filesize);
        $this->addVariable(_("Make this the default photo for this gallery?"),
                           'image_default', 'boolean', false);
        $this->addVariable(_("Caption"), 'image_desc', 'longtext', false, false,
                           null, array('4', '40'));

        $this->addVariable(_("Original Date"), 'image_originalDate',
                           'monthdayyear', true, false, null,
                           array('start_year' => 1900));

        $this->addVariable(_("Tags"), 'image_tags', 'text', false);

        $this->addHidden('', 'image0', 'text', false);
        $upload = &$this->addVariable(
        _("Replace photo with this file"), 'file0', 'image', false, false,
        _("Maximum photo size:") . ' '  . $filesize, array(false));
        $upload->setHelp('upload');
    }

    /**
     * Format file size
     */
    function _get_size($size)
    {
        $bytes = array('B', 'KB', 'MB', 'GB', 'TB');

        foreach ($bytes as $val) {
            if ($size > 1024) {
                $size = $size / 1024;
            } else {
                break;
            }
        }

        return round($size, 2) . ' '  . $val;
    }

}