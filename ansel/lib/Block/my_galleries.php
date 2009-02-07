<?php

$block_name = _("My Galleries");

/**
 * Display summary information on top level galleries.
 *
 * $Horde: ansel/lib/Block/my_galleries.php,v 1.25.2.1 2009/01/06 15:22:29 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_my_galleries extends Horde_Block {

    var $_app = 'ansel';

    function _params()
    {
        $params = array('limit' => array(
                            'name' => _("Maximum number of galleries"),
                            'type' => 'int',
                            'default' => 0));
        return $params;
    }

    function _title()
    {
        return Horde::link(
            Ansel::getUrlFor('view',array('groupby' => 'owner',
                                          'owner' => Auth::getAuth(),
                                          'view' => 'List')))
            . _("My Galleries") . '</a>';
    }

    function _content()
    {
        /* Get the top level galleries */
        $galleries = $GLOBALS['ansel_storage']->listGalleries(
            PERMS_EDIT, Auth::getAuth(), null, false, 0,
            empty($this->_params['limit']) ? 0 : $this->_params['limit'],
            'last_modified', 1);

        if (is_a($galleries, 'PEAR_Error')) {
            return $galleries->getMessage();
        }

        $preview_url = Horde::applicationUrl('preview.php');
        $header = array(_("Gallery Name"), _("Last Modified"), _("Photo Count"));
        $html = <<<HEADER
<div id="ansel_preview"></div>
<script type="text/javascript">
function previewImageMg(e, image_id)
{
    $('ansel_preview').style.left = Event.pointerX(e) + 'px';
    $('ansel_preview').style.top = Event.pointerY(e) + 'px';
    new Ajax.Updater({success: 'ansel_preview'}, '$preview_url', {method: 'post', parameters: '?image=' + image_id, onsuccess: $('ansel_preview').show()});
}
</script>
<table class="linedRow" cellspacing="0" style="width:100%">
 <thead><tr class="item nowrap">
  <th class="item leftAlign">$header[0]</th>
  <th class="item leftAlign">$header[1]</th>
  <th class="item leftAlign">$header[2]</th>
 </tr></thead>
 <tbody>
HEADER;

        foreach ($galleries as $gallery) {
            $style = $gallery->getStyle();
            $url = Ansel::getUrlFor('view', array('view' => 'Gallery',
                                                  'slug' => $gallery->get('slug'),
                                                  'gallery' => $gallery->id),
                                    true);
            $html .= '<tr><td>'
                . Horde::link($url, '', '', '', '', '', '', array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");', 'onmouseover' => 'previewImageMg(event, ' . $gallery->getDefaultImage('ansel_default') . ');'))
                . @htmlspecialchars($gallery->get('name'), ENT_COMPAT, NLS::getCharset()) . '</a></td><td>'
                . strftime($GLOBALS['prefs']->getValue('date_format'), $gallery->get('last_modified'))
                . '</td><td>' . (int)$gallery->countImages(true) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

}
