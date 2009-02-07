<?php
/**
 * $Horde: jonah/stories/share.php,v 1.25 2008/01/02 11:13:19 jan Exp $
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

function _mail($story_part, $from, $recipients, $subject, $note)
{
    global $conf;

    /* Create the MIME message. */
    require_once JONAH_BASE . '/lib/version.php';
    require_once 'Horde/MIME/Mail.php';
    $mail = new MIME_Mail($subject, null, $recipients, $from, NLS::getCharset());
    $mail->addHeader('User-Agent', 'Jonah ' . JONAH_VERSION);

    /* If a note has been provided, add it to the message as a text part. */
    if (strlen($note) > 0) {
        $message_note = new MIME_Part('text/plain', null, NLS::getCharset());
        $message_note->setContents($message_note->replaceEOL($note));
        $message_note->setDescription(_("Note"));
        $mail->addMIMEPart($message_note);
    }

    /* Get the story as a MIME part and add it to our message. */
    $mail->addMIMEPart($story_part);

    /* Log the pending outbound message. */
    Horde::logMessage(sprintf('<%s> is sending "%s" to (%s)',
                              $from, $subject, $recipients),
                      __FILE__, __LINE__, PEAR_LOG_INFO);

    /* Send the message and return the result. */
    return $mail->send($conf['mailer']['type'], $conf['mailer']['params']);
}

$session_control = 'readonly';
@define('AUTH_HANDLER', true);
@define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

$news = Jonah_News::factory();

/* Set up the form variables. */
$vars = Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');
$story_id = $vars->get('story_id');

if (!$conf['sharing']['allow']) {
    $url = Horde::applicationUrl('stories/view.php', true);
    $url = Util::addParameter($url, array('story_id' => $story_id, 'channel_id' => $channel_id));
    header('Location: ' . $url);
    exit;
}

$story = $news->getStory($channel_id, $story_id);
if (is_a($story, 'PEAR_Error')) {
    $notification->push(sprintf(_("Error fetching story: %s"), $story->getMessage()), 'horde.warning');
    $story = '';
}
$vars->set('subject', $story['story_title']);

/* Set up the form. */
$form = new Horde_Form($vars);
$title = _("Share Story");
$form->setTitle($title);
$form->setButtons(_("Send"));
$form->addHidden('', 'channel_id', 'int', false);
$form->addHidden('', 'story_id', 'int', false);
$v = &$form->addVariable(_("From"), 'from', 'email', true, false);
if (Auth::getAuth()) {
    require_once 'Horde/Identity.php';
    $identity = Identity::factory();
    $v->setDefault($identity->getValue('from_addr'));
}
$form->addVariable(_("To"), 'recipients', 'email', true, false, _("Separate multiple email addresses with commas."), true);
$form->addVariable(_("Subject"), 'subject', 'text', true);
$form->addVariable(_("Include"), 'include', 'enum', true, false, null, array(array(_("A link to the story"), _("The complete text of the story"))));
$form->addVariable(_("Message"), 'message', 'longtext', false, false, null, array(4, 40));

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);

    $channel = $news->getChannel($channel_id);
    if (empty($channel['channel_story_url'])) {
        $story_url = Horde::applicationUrl('stories/view.php', true);
        $story_url = Util::addParameter($story_url, array('channel_id' => '%c', 'story_id' => '%s'));
    } else {
        $story_url = $channel['channel_story_url'];
    }

    $story_url = str_replace(array('%25c', '%25s'), array('%c', '%s'), $story_url);
    $story_url = str_replace(array('%c', '%s', '&amp;'), array($channel_id, $story['story_id'], '&'), $story_url);

    if ($info['include'] == 0) {
        require_once 'Horde/MIME/Part.php';

        /* TODO: Create a "URL link" MIME part instead. */
        $message_part = new MIME_Part('text/plain');
        $message_part->setContents($message_part->replaceEOL($story_url));
        $message_part->setDescription(_("Story Link"));
    } else {
        $message_part = Jonah_News::getStoryAsMessage($story);
    }

    $result = _mail($message_part, $info['from'], $info['recipients'],
                    $info['subject'], $info['message']);

    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Unable to send story: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(_("The story was sent successfully."), 'horde.success');
        header('Location: ' . $story_url);
        exit;
    }
}

$share_template = new Horde_Template();
$share_template->set('main', Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, 'share.php', 'post'));
$share_template->set('notify', Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $share_template->fetch(JONAH_TEMPLATES . '/stories/share.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
