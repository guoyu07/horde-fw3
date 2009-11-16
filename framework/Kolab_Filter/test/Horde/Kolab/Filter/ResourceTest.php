<?php
/**
 * Test resource handling within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4.2.5 2009-11-16 17:23:06 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Filter.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Resource.php';
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/iCalendar.php';
require_once 'Horde/iCalendar/vfreebusy.php';

/**
 * Test resource handling
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/ResourceTest.php,v 1.4.2.5 2009-11-16 17:23:06 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_ResourceTest extends Horde_Kolab_Test_Filter
{

    /**
     * Test retrieval of the resource information
     */
    public function testGetResourceData()
    {
        $r = &new Kolab_Resource();
        $d = $r->_getResourceData('test@example.org', 'wrobel@example.org');
        $this->assertNoError($d);
        $this->assertEquals('wrobel@example.org', $d['id']);
        $this->assertEquals('home.example.org', $d['homeserver']);
        $this->assertEquals('ACT_REJECT_IF_CONFLICTS', $d['action']);
        $this->assertEquals('Gunnar Wrobel', $d['cn']);
    }

    /**
     * Test manual actions
     */
    public function testManual()
    {
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('otherhost', 'test@example.org', 'wrobel@example.org', null));
        $r = &new Kolab_Resource();
        $this->assertTrue($r->handleMessage('localhost', 'test@example.org', 'wrobel@example.org', null));
    }


    /**
     * Test invitation.
     */
    public function testRecurrenceInvitation()
    {
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $this->assertEquals(1222419600, $events[0]['start-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test an that contains a long string.
     */
    public function testLongStringInvitation()
    {
        require_once 'Horde/iCalendar/vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/longstring_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            $summaries[] = $event['summary'];
        }
        $this->assertContains('invitationtest2', $summaries);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test invitation when no default has been given.
     */
    public function testRecurrenceNodefault()
    {
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation.eml',
                           dirname(__FILE__) . '/fixtures/recur_invitation.ret',
                           '', '', 'wrobel@example.org', 'else@example.org', 
                           'home.example.org', $params);
    }

    /**
     * Test an issue with recurring invitations.
     *
     * https://issues.kolab.org/issue3868
     */
    public function testIssue3868()
    {
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20090901T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20091101T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/recur_invitation2.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $this->assertEquals(1251950400, $events[0]['start-date']);

        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

    /**
     * Test that the attendee status gets transferred.
     */
    public function testAttendeeStatusInvitation()
    {
        require_once 'Horde/iCalendar/vfreebusy.php';
        $GLOBALS['KOLAB_FILTER_TESTING'] = &new Horde_iCalendar_vfreebusy();
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTSTART', Horde_iCalendar::_parseDateTime('20080926T000000Z'));
        $GLOBALS['KOLAB_FILTER_TESTING']->setAttribute('DTEND', Horde_iCalendar::_parseDateTime('20081126T000000Z'));

        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/attendee_status_invitation.eml',
                           dirname(__FILE__) . '/fixtures/null.ret',
                           '', '', 'test@example.org', 'wrobel@example.org',
                           'home.example.org', $params);

        $result = $this->auth->authenticate('wrobel', array('password' => 'none'));
        $this->assertNoError($result);

        $folder = $this->storage->getFolder('INBOX/Kalender');
        $data = $folder->getData();
        $events = $data->getObjects();
        $summaries = array();
        foreach ($events as $event) {
            foreach ($event['attendee'] as $attendee) {
                switch ($attendee['smtp-address']) {
                case 'needs@example.org':
                    $this->assertEquals('none', $attendee['status']);
                    break;
                case 'accepted@example.org':
                    $this->assertEquals('accepted', $attendee['status']);
                    break;
                case 'declined@example.org':
                    $this->assertEquals('declined', $attendee['status']);
                    break;
                case 'tentative@example.org':
                    $this->assertEquals('tentative', $attendee['status']);
                    break;
                case 'delegated@example.org':
                    $this->assertEquals('none', $attendee['status']);
                    break;
                default:
                    $this->fail('Unexpected attendee!');
                    break;
                }
            }
        }
        $result = $data->deleteAll();
        $this->assertNoError($result);
    }

}
