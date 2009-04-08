<?php

class Driver {
    function getCalendar()
    {
        return 'foo';
    }
}
class Prefs {
    function getValue()
    {
        return 0;
    }
}
$prefs = new Prefs;

require 'Date/Calc.php';
require 'Horde/Date.php';
require 'Horde/Util.php';
require 'Horde/iCalendar.php';

$iCal = new Horde_iCalendar();
$iCal->parsevCalendar(file_get_contents(dirname(__FILE__) . '/bug2813.ics'));
$components = $iCal->getComponents();

putenv('TZ=US/Eastern');

define('KRONOLITH_BASE', dirname(__FILE__) . '/../..');
require KRONOLITH_BASE . '/lib/Kronolith.php';
require KRONOLITH_BASE . '/lib/Driver.php';
require KRONOLITH_BASE . '/lib/Recurrence.php';
$event = new Kronolith_Event(new Driver);
foreach ($components as $content) {
    if (is_a($content, 'Horde_iCalendar_vevent')) {
        $event->fromiCalendar($content);
        break;
    }
}

$after = array('year' => 2006, 'month' => 6);
for ($mday = 16; $mday <= 18; $mday++) {
    $after['mday'] = $mday;
    var_dump((array)$event->recurrence->nextRecurrence($after));
}

?>
