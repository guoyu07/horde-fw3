<?php
/**
 * $Horde: ansel/lib/Forms/Ecard.php,v 1.1 2006/12/02 07:13:55 chuck Exp $
 *
 * @package Ansel
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/**
 * Ecard generator.
 *
 * @package Ansel
 */

class EcardForm extends Horde_Form {

    var $_useFormToken = false;

    function EcardForm(&$vars, $title)
    {
        parent::Horde_Form($vars, $title);

        $this->setButtons(_("Send"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);

        $user = Auth::getAuth();
        if (empty($user)) {
            $this->addVariable(_("Use the following return address:"), 'ecard_retaddr', 'text', true);
        } else {
            require_once 'Horde/Identity.php';
            $identity = &Identity::singleton();
            $from_addr = $identity->getDefaultFromAddress();
            $vars->set('ecard_retaddr', $from_addr);
            $this->addHidden('', 'ecard_retaddr', 'text', true);
        }

        $this->addVariable(_("Send ecard to the following address:"), 'ecard_addr', 'text', true);
        $this->addVariable(_("Comments:"), 'ecard_comments', 'longtext', false, false, null, array('15', '60'));
    }

}
