<?php

/** Ansel_View_Gallery **/
require_once ANSEL_BASE . '/lib/Views/Gallery.php';

/**
 * Ansel_View_EmbeddedRenderer_Slideshow
 *
 * $Horde: ansel/lib/Views/EmbeddedRenderers/Slideshow.php,v 1.1.2.1 2009/01/06 15:22:31 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_EmbeddedRenderer_Slideshow extends Ansel_View_Gallery {

    /**
     * Create a new renderer.
     *
     * @see Ansel_View_Embedded
     *
     * @param array $params
     *
     * @return Ansel_View_EmbeddedRenderer  The renderer object.
     */
    function makeView($params = array())
    {
        $view = new Ansel_View_EmbeddedRenderer_Carousel();
        $view->_params = $params;

        return $view;
    }

    /**
     * Build the javascript that will render the view.
     *
     * @return string  A string containing valid javascript.
     */
    function html()
    {

    }

}