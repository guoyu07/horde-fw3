<?php
/**
 * Ansel_Search:: Provides a generic interface for various types of image
 * searches that are to be displayed in a paged results view.
 *
 * $Horde: ansel/lib/Search.php,v 1.1.2.1 2009/01/06 15:22:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Search {

    /**
     * The type of search we are performing.
     *
     * @var string
     */
    var $_type = '';

    /**
     * The field we are searching
     *
     * @var string
     */
    var $_field = '';

    /**
     * Parameters
     *
     * @var array
     */
    var $_params = array();

    /**
     * Create a concrete search instance.
     *
     * @param string $type   The type of search to perform.
     * @param array $params  Parameters for the concrete class.
     */
    function factory($type, $params = array())
    {
        $type = basename($type);
        $class = 'Ansel_Search_' . $type;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Search/' . $type . '.php';
        }
        if (class_exists($class)) {
            $search = new $class($params);
            return $search;
        }

        return PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class));
    }

    /**
     * Save the current search terms to the session
     *
     */
    function save()
    {
        $_SESSION['ansel_search'][$this->_type] = $this->_filter;
    }

    /**
     * Load any search terms in the session
     *
     */
    function load()
    {
        $this->_filter = (!empty($_SESSION['ansel_search'][$this->_type]) ?
            $_SESSION['ansel_search'][$this->_type] :
            array());
    }
    /**
     * retrieve a slice of the current search
     *
     * @param unknown_type $page
     * @param unknown_type $perpage
     */
    function getSlice($page, $perpage)
    {
    }

    /**
     * Add a search term
     *
     * @param array $filter value to filter.
     */
    function addFilter($filter)
    {
    }

    /**
     * Get the total number of resources that match
     */
    function count()
    {
    }

}