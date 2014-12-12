<?php
namespace Sinergi\Dictionary\Tests;

use PHPUnit_Framework_TestCase;
use Sinergi\Dictionary\Dictionary;

class DictionaryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Dictionary
     */
    private $dictionary;

    public function setUp()
    {
        $this->dictionary = new Dictionary('en', __DIR__ . '/_files');
    }

    public function testConstruct()
    {
        $dictionary = new Dictionary;
        $this->assertNull($dictionary->getLanguage());
        $this->assertNull($dictionary->getStorage());

        $dictionary = new Dictionary('en', __DIR__);
        $this->assertEquals('en', $dictionary->getLanguage());
        $this->assertEquals(__DIR__, $dictionary->getStorage());
    }

    public function testSetLanguage()
    {
        $dictionary = new Dictionary;
        $dictionary->setLanguage('de');
        $this->assertEquals('de', $dictionary->getLanguage());
    }

    public function testSetStorage()
    {
        $dictionary = new Dictionary;
        $dictionary->setStorage(__DIR__);
        $this->assertEquals(__DIR__, $dictionary->getStorage());
    }

    public function testDictionary()
    {
        $result = $this->dictionary['example']['title'];
        $this->assertEquals('This is an example', $result);
    }

    public function testGetMethod()
    {
        $result = $this->dictionary->get('example.title');
        $this->assertEquals('This is an example', $result);
    }

    public function testGetDirMethod()
    {
        $result = $this->dictionary->get('test2');
        $this->assertInstanceOf(Dictionary::class, $result);
        $this->assertInstanceOf(Dictionary::class, $result->get('test3'));
        $this->assertEquals('yo', $result->get('test3')->get('hey'));
    }

    public function testNonExistingDictionary()
    {
        $result = $this->dictionary['example']['title2'];
        $this->assertNull($result);
    }

    public function testErrors()
    {
        $errors = $this->dictionary->errors(
            ['test_exists', 'test2_exists'],
            $this->dictionary['example']['errors']
        );
        $this->assertEquals('This is an error', $errors['test_exists']);
        $this->assertEquals('This already exists', $errors['test2_exists']);
    }

    public function testHtmlFile()
    {
        $result = $this->dictionary['test1']['example'];
        $this->assertRegExp("/Hello World/", $result);
    }

    public function testFileWithDirectory()
    {
        $result = $this->dictionary['test1']['foo'];
        $this->assertEquals('bar', $result);
    }

    public function testGetMethodFileWithDirectory()
    {
        $result = $this->dictionary->get('test1');
        $this->assertEquals('bar', $result['foo']);
        $this->assertRegExp("/Hello World/", $result['example']);
    }
}
