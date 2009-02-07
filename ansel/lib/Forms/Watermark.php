<?php
/**
 * $Horde: ansel/lib/Forms/Watermark.php,v 1.1.2.1 2009/01/06 15:22:29 jan Exp $
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

class WatermarkForm extends Horde_Form {

    var $_useFormToken = false;

    function WatermarkForm(&$vars, $title)
    {
        global $gallery, $prefs;

        parent::Horde_Form($vars, $title);

        $this->setButtons(_("Save"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);
        $this->addHidden('', 'page', 'text', false);

        $w = &$this->addVariable(_("Custom Watermark"), 'watermark', 'text',
                                 false, false, null);
        $w->setDefault($prefs->getValue('watermark_text'));

        $fonts = array('tiny' => _("Tiny"),
                       'small' => _("Small"),
                       'medium' => _("Medium"),
                       'large' => _("Large"),
                       'giant' => _("Giant"));
        $f = &$this->addVariable(_("Watermark Font"), 'font', 'enum', false,
                                 false, null, array($fonts));
        $f->setDefault($prefs->getValue('watermark_font'));

        $ha = array('left' => _("Left"),
                    'center' => _("Center"),
                    'right' => _("Right"));
        $wha = &$this->addVariable(_("Horizontal Alignment"), 'whalign', 'enum',
                                   false, false, null, array($ha));
        $wha->setDefault($prefs->getValue('watermark_horizontal'));

        $va = array('top' => _("Top"),
                    'center' => _("Center"),
                    'bottom' => _("Bottom"));
        $wva = &$this->addVariable(_("Vertical Alignment"), 'wvalign', 'enum',
                                   false, false, null, array($va));
        $wva->setDefault($prefs->getValue('watermark_vertical'));
    }

}