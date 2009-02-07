<?php
/**
 * @category Horde
 * @package Horde_Xml_Element
 * @subpackage UnitTests
 */

require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib/Horde/Xml/Element.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib/Horde/Xml/Element/Exception.php';

class Horde_Xml_Element_ElementTest extends PHPUnit_Framework_TestCase {

    public $element;
    public $namespacedElement;

    public function setUp()
    {
        $doc1 = new DOMDocument();
        $doc1->load(dirname(__FILE__) . '/fixtures/Sample.xml');
        $this->element = new Horde_Xml_Element($doc1->documentElement);

        $doc2 = new DOMDocument();
        $doc2->load(dirname(__FILE__) . '/fixtures/NamespacedSample.xml');
        $this->namespacedElement = new Horde_Xml_Element($doc2->documentElement);
    }

    public function testXml()
    {
        $el = new Horde_Xml_Element('<root href="#">Root</root>');
        $this->assertEquals((string)$el, 'Root');
        $this->assertEquals($el['href'], '#');
    }

    public function testInvalidXml()
    {
        $failed = false;
        try {
            new Horde_Xml_Element('<root');
        } catch (Horde_Xml_Element_Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed, 'Invalid XML should result in an exception');
    }

    public function testSerialization()
    {
        $elt = new Horde_Xml_Element('<entry><title>Test</title></entry>');
        $this->assertEquals($elt->title(), 'Test', 'Value before serialization/unserialization');
        $serialized = serialize($elt);
        $unserialized = unserialize($serialized);
        $this->assertEquals($unserialized->title(), 'Test', 'Value after serialization/unserialization');
    }

    public function testExists()
    {
        $this->assertFalse(isset($this->element[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->element['version']), 'Version should be set');

        $this->assertFalse(isset($this->namespacedElement[-1]), 'Negative array access should fail');
        $this->assertTrue(isset($this->namespacedElement['version']), 'Version should be set');
    }

    public function testGet()
    {
        $this->assertEquals($this->element['version'], '1.0', 'Version should be 1.0');
        $this->assertEquals($this->namespacedElement['version'], '1.0', 'Version should be 1.0');
    }

    public function testArrayGet()
    {
        $this->assertTrue(is_array($this->element->entry));
        $this->assertTrue(is_array($this->namespacedElement->entry));

        foreach ($this->element->entry as $entry) {
            $this->assertTrue($entry instanceof Horde_Xml_Element);
        }

        foreach ($this->namespacedElement->entry as $entry) {
            $this->assertTrue($entry instanceof Horde_Xml_Element);
        }
    }

    public function testSet()
    {
        $this->element['category'] = 'tests';
        $this->assertTrue(isset($this->element['category']), 'Category should be set');
        $this->assertEquals($this->element['category'], 'tests', 'Category should be tests');

        $this->namespacedElement['atom:category'] = 'tests';
        $this->assertTrue(isset($this->namespacedElement['atom:category']), 'Category should be set');
        $this->assertEquals($this->namespacedElement['atom:category'], 'tests', 'Category should be tests');

        // Changing an existing index.
        $oldEntry = $this->element['version'];
        $this->element['version'] = '1.1';
        $this->assertTrue($oldEntry != $this->element['version'], 'Version should have changed');
    }

    public function testUnset()
    {
        $doc1 = new DOMDocument();
        $doc1->load(dirname(__FILE__) . '/fixtures/Sample.xml');
        $element = new Horde_Xml_Element($doc1->documentElement);

        $doc2 = new DOMDocument();
        $doc2->load(dirname(__FILE__) . '/fixtures/NamespacedSample.xml');
        $namespacedElement = new Horde_Xml_Element($doc2->documentElement);

        unset($element['version']);
        $this->assertFalse(isset($element['version']), 'Version should be unset');
        $this->assertEquals('', $element['version'], 'Version should be equal to the empty string');

        unset($namespacedElement['version']);
        $this->assertFalse(isset($namespacedElement['version']), 'Version should be unset');
        $this->assertEquals('', $namespacedElement['version'], 'Version should be equal to the empty string');
    }

    public function testIsInitialized()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<element />');
        $e = new Horde_Xml_Element($doc->documentElement);

        $e->author->name['last'] = 'Lastname';
        $e->author->name['first'] = 'Firstname';
        $e->author->name->{'Firstname:url'} = 'www.example.com';

        $e->author->title['foo'] = 'bar';
        if ($e->pants()) {
            $this->fail('<pants> does not exist, it should not have a true value');
            // This should not create an element in the actual tree.
        }
        if ($e->pants()) {
            $this->fail('<pants> should not have been created by testing for it');
            // This should not create an element in the actual tree.
        }

        $xml = $e->saveXML();

        $this->assertFalse(strpos($xml, 'pants'), '<pants> should not be in the xml output');
        $this->assertTrue(strpos($xml, 'www.example.com') !== false, 'the url attribute should be set');
    }

    public function testStrings()
    {
        $xml = "<entry>
    <title> Using C++ Intrinsic Functions for Pipelined Text Processing</title>
    <id>http://www.oreillynet.com/pub/wlg/8356</id>
    <link rel='alternate' href='http://www.oreillynet.com/pub/wlg/8356'/>
    <summary type='xhtml'>
    <div xmlns='http://www.w3.org/1999/xhtml'>
    A good C++ programming technique that has almost no published material available on the WWW relates to using the special pipeline instructions in modern CPUs for faster text processing. Here's example code using C++ intrinsic functions to give a fourfold speed increase for a UTF-8 to UTF-16 converter compared to the original C/C++ code.
    </div>
    </summary>
    <author><name>Rick Jelliffe</name></author>
    <updated>2005-11-07T08:15:57-08:00</updated>
</entry>";

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $element = new Horde_Xml_Element($doc->documentElement);

        $this->assertTrue($element->summary instanceof Horde_Xml_Element, '__get access should return a Horde_Xml_Element instance');
        $this->assertFalse($element->summary() instanceof Horde_Xml_Element, 'method access should not return a Horde_Xml_Element instance');
        $this->assertTrue(is_string($element->summary()), 'method access should return a string');
        $this->assertFalse(is_string($element->summary), '__get access should not return a string');
    }

    public function testAppendChild()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<element />');
        $e = new Horde_Xml_Element($doc->documentElement);

        $doc2 = new DOMDocument();
        $doc2->loadXML('<child />');
        $e2 = new Horde_Xml_Element($doc2->documentElement);

        $e->appendChild($e2);

        $this->assertEquals($e->saveXmlFragment(),
                            '<element><child/></element>');
    }

    public function testFromArray()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<element />');
        $e = new Horde_Xml_Element($doc->documentElement);

        $e->fromArray(array('user' => 'Joe Schmoe',
                            'atom:title' => 'Test Title',
                            'child#href' => 'http://www.example.com/',
                            '#title' => 'Test Element'));

        $this->assertEquals($e->saveXmlFragment(),
                            '<element title="Test Element"><user>Joe Schmoe</user><atom:title xmlns:atom="http://www.w3.org/2005/Atom">Test Title</atom:title><child href="http://www.example.com/"/></element>');

        $e = new Horde_Xml_Element('<element />');
        $e->fromArray(array('author' => array('name' => 'Joe', 'email' => 'joe@example.com')));

        $this->assertEquals($e->saveXmlFragment(),
                            '<element><author><name>Joe</name><email>joe@example.com</email></author></element>');
    }

    public function testIllegalFromArray()
    {
        $failed = false;
        $e = new Horde_Xml_Element('<element />');
        try {
            $e->fromArray(array('#name' => array('foo' => 'bar')));
        } catch (InvalidArgumentException $e) {
            $failed = true;
        }
        $this->assertTrue($failed);
    }

}
