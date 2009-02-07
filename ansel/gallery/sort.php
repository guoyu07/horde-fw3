<?php
/**
 * $Horde: ansel/gallery/sort.php,v 1.23.2.2 2009/01/06 15:22:24 jan Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__) . '/..');
require_once ANSEL_BASE . '/lib/base.php';

/* If we aren't provided with a gallery, redirect to the gallery
 * list. */
$galleryId = Util::getFormData('gallery');
if (!isset($galleryId)) {
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}

$gallery = $ansel_storage->getGallery($galleryId);
if (is_a($gallery, 'PEAR_Error')) {
    $notification->push(_("There was an error accessing the gallery."), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
} elseif (!$gallery->hasPermission(Auth::getAuth(), PERMS_EDIT)) {
    $notification->push(sprintf(_("Access denied editing gallery \"%s\"."), $gallery->get('name')), 'horde.error');
    header('Location: ' . Ansel::getUrlFor('view', array('view' => 'List'),
                                           true));
    exit;
}
$style = $gallery->getStyle();
$date = Ansel::getDateParameter();
$gallery->setDate($date);

switch (Util::getPost('action')) {
case 'Sort':
    parse_str(Util::getPost('order'), $order);
    $order = $order['order'];
    foreach ($order as $pos => $id) {
        $gallery->setImageOrder($id, $pos);
    }

    $notification->push(_("Gallery sorted."), 'horde.success');
    $style = $gallery->getStyle();

    header('Location: ' . Ansel::getUrlFor('view', array_merge(
                                           array('view' => 'Gallery',
                                                 'gallery' => $galleryId,
                                                 'slug' => $gallery->get('slug')),
                                           $date),
                                           true));
    exit;
}

Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('effects.js', 'horde', true);
Horde::addScriptFile('dragdrop.js', 'horde', true);
$title = sprintf(_("%s :: Sort"), $gallery->get('name'));
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
?>
<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>

<div class="instructions">
 <form action="sort.php" method="post">
  <?php echo Util::formInput() ?>
  <input type="hidden" name="gallery" value="<?php echo (int)$galleryId ?>" />
  <input type="hidden" name="action" value="Sort" />
  <input type="hidden" name="order" id="order" />
  <input type="hidden" name="year" value="<?php echo $date['year'] ?>" />
  <input type="hidden" name="month" value="<?php echo $date['month'] ?>" />
  <input type="hidden" name="day" value="<?php echo $date['day'] ?>" />
  <p>
   <?php echo _("Drag photos to the desired sort position.") ?>
   <input type="submit" onclick="$('order').value = Sortable.serialize('sortContainer', { name: 'order' });" class="button" value="<?php echo _("Done") ?>" />
  </p>
 </form>
</div>

<div id="sortContainer" style="background:<?php echo $style['background'] ?>">

<?php
$images = $gallery->getImages();
foreach ($images as $image) {
    echo '<div id="o_' . htmlspecialchars($image->id) . '"><a title="' .
        htmlspecialchars($image->caption) . '" href="#">' .
        Horde::img(Ansel::getImageUrl($image->id, 'thumb', false, $style['name']), '', '', '') .
        '</a></div>';
}
echo '</div>';
$notification->push('Sortable.create(\'sortContainer\', {tag: \'div\', overlap: \'horizontal\', constraint: false })', 'javascript');
require $registry->get('templates', 'horde') . '/common-footer.inc';
