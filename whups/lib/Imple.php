<?php
/**
 * $Horde: whups/lib/Imple.php,v 1.1.2.1 2009/01/06 15:28:17 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Whups
 */
class Imple {

    /**
     * Parameters needed by the subclasses.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Attempts to return a concrete Imple instance based on $imple.
     *
     * @param string $imple  The type of concrete Imple subclass to return
     *                       return, based on the imple type indicated. The
     *                       code is dynamically included.
     * @param array $params  The configuration parameter array.
     *
     * @return mixed  The newly created concrete Imple instance, or false on
     *                error.
     */
    function factory($imple, $params = array())
    {
        $imple = basename($imple);
        if (!$imple) {
            return false;
        }

        $class = 'Imple_' . $imple;
        if (!class_exists($class)) {
            include_once dirname(__FILE__) . '/Imple/' . $imple . '.php';
            if (!class_exists($class)) {
                return false;
            }
        }

        return new $class($params);
    }

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed by the class.
     */
    function Imple($params)
    {
        $this->_params = $params;
        $this->attach();
    }

    /**
     * Attach the Imple object to a javascript event.
     */
    function attach()
    {
        Horde::addScriptFile('prototype.js', 'horde', true);
        Horde::addScriptFile('builder.js', 'horde', true);
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('controls.js', 'horde', true);
    }

    /**
     * TODO
     *
     * @param TODO
     */
    function handle($args)
    {
    }

    /**
     * Return the rendered HTML code.
     *
     * @return string  The HTML code.
     */
    function html()
    {
    }

    /**
     * Generate a random ID string.
     *
     * @access private
     *
     * @return string  The random ID string.
     */
    function _randomid()
    {
        return 'imple_' . uniqid(mt_rand());
    }

}
