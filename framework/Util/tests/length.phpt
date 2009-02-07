--TEST--
String::length() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/../String.php';

echo String::length('Welcome', 'Big5'). "\n";
echo String::length('Welcome', 'Big5'). "\n";
echo String::length('�w��', 'Big5') . "\n";
echo String::length('歡��', 'utf-8') . "\n";

?>
--EXPECT--
7
7
2
3
