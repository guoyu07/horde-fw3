--TEST--
Test parsing of ORG fields.
--FILE--
<?php

$data = 'BEGIN:VCARD
VERSION:3.0
FN:Test User
ORG:My Organization;My Unit
END:VCARD';

require_once dirname(__FILE__) . '/../iCalendar.php';
$ical = new Horde_iCalendar();
$ical->parseVCalendar($data);
$card = $ical->getComponent(0);
var_dump($card->getAttributeValues('ORG'));

?>
--EXPECT--
array(2) {
  [0]=>
  string(15) "My Organization"
  [1]=>
  string(7) "My Unit"
}
