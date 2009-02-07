--TEST--
String::pad() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/../String.php';

/* Simple single byte tests. */
echo String::pad('abc', 2) . "\n";
echo String::pad('abc', 3) . "\n";
echo String::pad('abc', 4) . "\n";
echo String::pad('abc', 4, ' ', STR_PAD_LEFT) . "\n";
echo String::pad('abc', 4, ' ', STR_PAD_RIGHT) . "\n";
echo String::pad('abc', 4, ' ', STR_PAD_BOTH) . "\n";
echo String::pad('abc', 5, ' ', STR_PAD_LEFT) . "\n";
echo String::pad('abc', 5, ' ', STR_PAD_RIGHT) . "\n";
echo String::pad('abc', 5, ' ', STR_PAD_BOTH) . "\n";

/* Long padding tests. */
echo "\n";
echo String::pad('abc', 10, '=-+', STR_PAD_LEFT) . "\n";
echo String::pad('abc', 10, '=-+', STR_PAD_RIGHT) . "\n";
echo String::pad('abc', 10, '=-+', STR_PAD_BOTH) . "\n";

/* Multibyte tests. */
echo "\n";
echo String::pad('äöü', 4, ' ', STR_PAD_LEFT, 'UTF-8') . "\n";
echo String::pad('äöü', 4, ' ', STR_PAD_RIGHT, 'UTF-8') . "\n";
echo String::pad('äöü', 4, ' ', STR_PAD_BOTH, 'UTF-8') . "\n";
echo "\n";
echo String::pad('abc', 10, 'äöü', STR_PAD_LEFT, 'UTF-8') . "\n";
echo String::pad('abc', 10, 'äöü', STR_PAD_RIGHT, 'UTF-8') . "\n";
echo String::pad('abc', 10, 'äöü', STR_PAD_BOTH, 'UTF-8') . "\n";

/* Special cases. */
echo "\n";
echo String::pad('abc', 4, ' ', STR_PAD_RIGHT, 'UTF-8') . "\n";


?>
--EXPECT--
abc
abc
abc 
 abc
abc 
abc 
  abc
abc  
 abc 

=-+=-+=abc
abc=-+=-+=
=-+abc=-+=

 äöü
äöü 
äöü 

äöüäöüäabc
abcäöüäöüä
äöüabcäöüä

abc 
