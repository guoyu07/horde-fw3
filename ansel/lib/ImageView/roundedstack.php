 <?php
/**
 * ImageView to create the gallery image stacks.
 *
 * $Horde: ansel/lib/ImageView/roundedstack.php,v 1.10 2008/08/13 23:27:06 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 *
 */
class Ansel_ImageView_roundedstack extends Ansel_ImageView {

    var $need = array('photo_stack');

    function _create()
    {
        $imgobjs = array();
        $images = $this->_getStackImages();
        $style = $this->_params['style'];
        foreach ($images as $image) {
            $result = $image->load('screen');
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            $imgobjs[] = $image->_image;
        }

        $params = array('width' => 100,
                        'height' => 100,
                        'background' => $style['background']);

        $baseImg = Ansel::getImageObject($params);
        $result = $baseImg->addEffect(
            'photo_stack',
            array('images' => $imgobjs,
                  'resize_height' => $GLOBALS['conf']['thumbnail']['height'],
                  'padding' => 0,
                  'background' => $style['background'],
                  'type' => 'rounded'));

        $baseImg->applyEffects();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $baseImg->resize($GLOBALS['conf']['thumbnail']['width'],
                         $GLOBALS['conf']['thumbnail']['height']);

        return $baseImg;

    }

}
