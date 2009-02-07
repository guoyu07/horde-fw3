--TEST--
MIME_Mail HTML test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new MIME_Mail('My Subject', null, 'recipient@example.com',
                      'sender@example.com');
$mail->setBody("This is\nthe plain text body.");
echo $mail->send('dummy');

echo "====================================================================\n";

$mail = new MIME_Mail('My Subject', null, 'recipient@example.com',
                      'sender@example.com');
$mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>",
                   'iso-8859-1', false);
echo $mail->send('dummy');

echo "====================================================================\n";

$mail = new MIME_Mail('My Subject', null, 'recipient@example.com',
                      'sender@example.com');
$mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>");
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
	charset=iso-8859-1;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the plain text body.
====================================================================
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: text/html;
	charset=iso-8859-1
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

<h1>Header Title</h1>
<p>This is<br />the html text body.</p>
====================================================================
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: multipart/alternative;
	boundary="=_%s"
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_%s
Content-Type: text/plain;
	charset=iso-8859-1;
	DelSp="Yes";
	format="flowed"
Content-Description: Plaintext Version of Message
Content-Disposition: inline
Content-Transfer-Encoding: 7bit



HEADER TITLE

This is
the html text body.

--=_%s
Content-Type: text/html;
	charset=iso-8859-1
Content-Description: HTML Version of Message
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

<h1>Header Title</h1>
<p>This is<br />the html text body.</p>
--=_%s--
