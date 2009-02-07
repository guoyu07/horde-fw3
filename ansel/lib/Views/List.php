<?php
/**
 * @package Ansel
 *
 * $Horde: ansel/lib/Views/List.php,v 1.33.2.2 2008/11/18 04:05:51 chuck Exp $
 */

/** Ansel_View_Abstract */
require_once ANSEL_BASE . '/lib/Views/Abstract.php';

/** Tags **/
require_once ANSEL_BASE . '/lib/Tags.php';

/** Gallery Tiles **/
require_once ANSEL_BASE . '/lib/Tile/Gallery.php';

/** Horde_UI_Pager */
require_once 'Horde/UI/Pager.php';

/** Text_Filter */
require_once 'Horde/Text/Filter.php';

/** Variables */
require_once 'Horde/Variables.php';

/**
 * The Ansel_View_Gallery:: class wraps display of individual images.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_List extends Ansel_View_Abstract {

    /**
     * @static
     *
     * @param array $params  Any parameters that the view might need.
     * <pre>
     *  In addition to the params taken by Ansel_View_Gallery, this view
     *  can also take:
     *
     *  groupby      -  Group the results (owner, category etc...)
     *
     *  owner        -  The owner to group by
     *
     *  category     -  The category to group by
     *
     *  gallery_ids  -  No fitering, just show these galleries
     *
     *  pager_url    -  The url for the pager to use see Ansel_Gallery for
     *                  more information on the url parameters.
     *
     * @TODO use exceptions from the constructor instead of static
     * instance-getting.
     */
    function makeView($params = array())
    {
        $view = new Ansel_View_List;
        $view->_params = $params;
        return $view;
    }

    /**
     * Get this view's title.
     *
     * @return string  The gallery's title.
     */
    function getTitle()
    {
        return _("Gallery List");
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     *
     */
    function html()
    {
        global $conf, $prefs, $registry, $ansel_storage, $notification;

        // If we aren't supplied with a page number, default to page 0.
        if (isset($this->_params['page'])) {
            $page = $this->_params['page'];
        } else {
            $page = Util::getFormData('page', 0);
        }
        $galleries_perpage = $prefs->getValue('tilesperpage');

        // Check for grouping.
        if (empty($this->_params['groupby'])) {
            $groupby = Util::getFormData('groupby', $prefs->getValue('groupby'));
        } else {
            $groupby = $this->_params['groupby'];
        }

        if (empty($this->_params['owner'])) {
            $owner = Util::getFormData('owner');
            $owner = empty($owner) ? null : $owner;
        } else {
            $owner = $this->_params['owner'];
        }

        $special = Util::getFormData('special');

        if (empty($this->_params['category'])) {
            $category = Util::getFormData('category');
            $category = empty($category) ? null : $category;
        } else {
            $category = $this->_params['category'];
        }
        if (!$owner && !$category && !$special && $groupby != 'none' ) {
            header('Location: ' . Ansel::getUrlFor('group', array('groupby' => $groupby), true));
            exit;
        }

        // We'll need this in the template.
        $sortby = !empty($this->_params['sort']) ? $this->_params['sort'] : 'name';
        $sortdir = isset($this->_params['sort_dir']) ? $this->_params['sort_dir'] : 0;

        // If we are calling from the api, we can just pass a list of gallery
        // ids instead of doing grouping stuff.
        if (!empty($this->_params['api']) &&
            !empty($this->_params['gallery_ids']) &&
            count($this->_params['gallery_ids'])) {

            $start = $page * $galleries_perpage;
            $num_galleries = count($this->_params['gallery_ids']);
            if ($num_galleries > $start) {
                $getThese = array_slice($this->_params['gallery_ids'], $start, $galleries_perpage);
                $try = $ansel_storage->getGalleries($getThese);
                $gallerylist = array();
                foreach ($try as $id => $gallery) {
                    if ($gallery->hasPermission(Auth::getAuth(), PERMS_SHOW)) {
                        $gallerylist[$id] = $gallery;
                    }
                }
            } else {
                $gallerylist = array();
            }
        } else {
            // Set list filter/title
            $filter = array();
            if (!is_null($owner)) {
                $filter['owner'] = $owner;
            }

            if (!is_null($category)) {
                $filter['category'] = $category;
            }

            if ($owner) {
                if ($owner == Auth::getAuth() && empty($this->_params['api'])) {
                    $list_title = _("My Galleries");
                } else {
                    $uprefs = &Prefs::singleton($GLOBALS['conf']['prefs']['driver'],
                                                'ansel', $owner, '', null, false);
                    $fullname = $uprefs->getValue('grouptitle');
                    if (!$fullname) {
                        require_once 'Horde/Identity.php';
                        $identity = &Identity::singleton('none', $owner);
                        $fullname = $identity->getValue('fullname');
                        if (!$fullname) {
                            $fullname = $owner;
                        }
                        $list_title = sprintf(_("%s's Galleries"), $fullname);
                    } else {
                        $list_title = $fullname;
                    }
                }
            } elseif ($category || ($groupby == 'category' && $special)) {
                if ($special == 'unfiled') {
                    $list_title = sprintf(_("Galleries in category \"%s\""),
                                          _("Unfiled"));
                    $filter['category'] = '';
                } else {
                    $list_title = sprintf(_("Galleries in category \"%s\""), $category);
                }
            } else {
                $list_title = _("Gallery List");
            }

            $num_galleries = $ansel_storage->countGalleries(
                Auth::getAuth(), PERMS_SHOW, $filter, null, false);
            if (is_a($num_galleries, 'PEAR_Error')) {
                return $num_galleries->getMessage();
            }

            if ($num_galleries == 0 && empty($this->_params['api'])) {
                if ($filter == $owner && $owner == Auth::getAuth()) {
                    $notification->push(_("You have no photo galleries, add one!"),
                                        'horde.message');
                    header('Location: ' . Util::addParameter(Horde::applicationUrl('gallery.php'), 'actionID', 'add'));
                    exit;
                }
                $notification->push(_("There are no photo galleries available."), 'horde.message');
                $gallerylist = array();
            } else {
                $gallerylist = $ansel_storage->listGalleries(
                    PERMS_SHOW, $filter, null, false, $page * $galleries_perpage,
                    $galleries_perpage, $sortby, $sortdir);
            }

        }

        $vars = Variables::getDefaultVariables();
        if (!empty($this->_params['page'])) {
            $vars->_vars['page'] = $this->_params['page'];
        }

        if (!empty($this->_params['pager_url'])) {
            $pagerurl = $this->_params['pager_url'];
            $override = true;
        } else {
            $override = false;
            $pagerurl = Ansel::getUrlFor('view',
                                    array('owner' => $owner,
                                          'category' => $category,
                                          'special' => $special,
                                          'groupby' => $groupby,
                                          'view' => 'List'));
        }
        $p_params = array('num' => $num_galleries,
                          'url' => $pagerurl,
                          'perpage' => $galleries_perpage);

        if ($override) {
            $p_params['url_callback'] = null;
        }
        $pager = new Horde_UI_Pager('page', $vars, $p_params);
        $preserve = array('sort_dir' => $sortdir);
        if (!empty($sortby)) {
            $preserve['sort'] = $sortby;
        }
        $pager->preserve($preserve);

        if ($num_galleries) {
            $min = $page * $galleries_perpage;
            $max = $min + $galleries_perpage;
            if ($max > $num_galleries) {
                $max = $num_galleries - $min;
            }
            $start = $min + 1;
            $end = min($num_galleries, $min + $galleries_perpage);

            if ($owner) {
                $refresh_link = Ansel::getUrlFor('view',
                                                 array('groupby' => $groupby,
                                                       'owner' => $owner,
                                                       'page' => $page,
                                                       'view' => 'List'));

            } else {
                $refresh_link = Ansel::getUrlFor('view',
                                                 array('view' => 'List',
                                                       'groupby' => $groupby,
                                                       'page' => $page,
                                                       'category' => $category));
            }

            // Get top-level / default gallery style.
            if (empty($this->_params['style'])) {
                $style = Ansel::getStyleDefinition(
                    $prefs->getValue('default_gallerystyle'));
            } else {
                $style = Ansel::getStyleDefinition($this->_params['style']);
            }
            $count = 0;
            $width = round(100 / $prefs->getValue('tilesperrow'));

            ob_start();
            include ANSEL_TEMPLATES . '/view/list.inc';
            $html = ob_get_clean();
            return $html;
        }
        return '';
    }

}
