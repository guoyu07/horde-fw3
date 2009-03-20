--TEST--
MIME_Mail methods test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new MIME_Mail();
$mail->addHeader('Subject', 'My Subject');
$mail->setBody("This is\nthe body", 'iso-8859-15');
$mail->addHeader('To', 'recipient@example.com');
$mail->addHeader('Cc', 'null@example.com');
$mail->addHeader('Bcc', 'invisible@example.com');
$mail->addHeader('From', 'sender@example.com');
$mail->removeHeader('Cc');

echo $mail->send('dummy');

?>
--EXPECTF--
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: text/plain;
 charset=iso-8859-15;
 DelSp="Yes";
 format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the body
