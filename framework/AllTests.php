<?php
/**
 * Horde Test Runner
 *
 * @category Horde
 * @package  Horde
 * @subpackage UnitTests
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * @category Horde
 * @package  Horde
 * @subpackage UnitTests
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/lgpl-license.php
 */
class AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Horde Test Runner');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        // Find all AllTests.php files.
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && $file->getFilename() == 'AllTests.php' && $file->getPathname() != __FILE__) {
                $pathname = $file->getPathname();

                // Include the test suite.
                require $pathname;

                // Generate possible classnames (namespaced and
                // non-namespaced, with and without Horde_, _tests_,
                // and the full tests/Horde/... hierarchy).
                $class = str_replace(DIRECTORY_SEPARATOR, '_', preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $class_notests = preg_replace('/_tests?/', '', $class);
                $class_horde = 'Horde_' . $class;
                $class_horde_notests = 'Horde_' . $class_notests;
                $class_pear2 = preg_replace('/' . substr($class, 0, strpos($class, '_')) . '_tests?_/', '', $class);
                $class_pear2_notests = preg_replace('/\w+_tests?_/', '', $class);

                $classnames = array($class, $class_notests, $class_horde, $class_horde_notests, $class_pear2, $class_pear2_notests);
                foreach ($classnames as $classname) {
                    $classnames[] = str_replace('_', '::', $classname);
                }

                // Look for something with a ::suite() method.
                foreach ($classnames as $classname) {
                    // We've already included the AllTests.php file,
                    // so explicitly disable autoloading in the
                    // class_exists check. Otherwise we can get all
                    // kinds of double-define errors.
                    if (class_exists($classname, false)) {
                        // Once we've found a usable test suite, add it and move on.
                        $suite->addTestSuite(call_user_func(array($classname, 'suite')));
                        break;
                    }
                }
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
