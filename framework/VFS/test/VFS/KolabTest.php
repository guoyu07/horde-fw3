<?php
/**
 * Test the Kolab based virtual file system.
 *
 * $Horde: framework/VFS/test/VFS/KolabTest.php,v 1.1.2.2 2009-01-06 15:23:47 jan Exp $
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

require_once 'VFS.php';

/**
 * Test the Kolab based virtual file system.
 *
 * $Horde: framework/VFS/test/VFS/KolabTest.php,v 1.1.2.2 2009-01-06 15:23:47 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class VFS_KolabTest extends Horde_Kolab_Test_Storage
{

    /**
     * Test setup.
     *
     * @return NULL
     */
    public function setUp()
    {
        $world = $this->prepareBasicSetup();

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $this->_vfs = VFS::factory('kolab');
    }

    /**
     * Test folder handling.
     *
     * @return NULL
     */
    public function testFolders()
    {
        $this->assertEquals(array(), $this->_vfs->listFolders());
        $this->assertNoError($this->_vfs->createFolder('/', 'test'));
        $this->assertEquals(1, count($this->_vfs->listFolders()));
        $this->assertNoError($this->_vfs->autocreatePath('/a/b/c/d'));
        $this->assertEquals(1, count($this->_vfs->listFolders('/')));
        $this->assertEquals(3, count($this->_vfs->listFolders('/INBOX')));
        $this->assertTrue($this->_vfs->exists('/INBOX/a', 'b'));
        $a = $this->_vfs->listFolder('/INBOX/a/b', null, true, true);
        $this->assertTrue(isset($a['c']));
        $this->assertTrue($this->_vfs->isFolder('/INBOX/a/b', 'c'));
        $this->assertTrue($this->_vfs->deleteFolder('/INBOX/a/b/c', 'd'));
        $this->assertFalse($this->_vfs->exists('/INBOX/a/b/c', 'd'));
        $this->assertTrue($this->_vfs->deleteFolder('/INBOX', 'a', true));
    }

    /**
     * Test file handling.
     *
     * @return NULL
     */
    public function testFiles()
    {
    }
}
