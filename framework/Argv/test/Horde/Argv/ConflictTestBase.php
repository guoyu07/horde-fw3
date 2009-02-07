<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_ConflictTestBase extends Horde_Argv_TestBase
{
    public function setUp()
    {
        $options = array(new Horde_Argv_Option('-v', '--verbose', array(
            'action' => 'count',
            'dest' => 'verbose',
            'help' => 'increment verbosity'))
        );

        $this->parser = new Horde_Argv_InterceptingParser(
            array('usage' => Horde_Argv_Option::SUPPRESS_USAGE, 'optionList' => $options)
        );
    }

    public function showVersion($option, $opt, $value, $parser)
    {
        $this->parser->values->showVersion = 1;
    }

}
