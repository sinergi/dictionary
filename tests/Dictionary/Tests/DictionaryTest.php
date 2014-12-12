<?php
namespace Sinergi\Dictionary\Tests;

use PHPUnit_Framework_TestCase;
use Sinergi\Dictionary\Dictionary;

class DictionaryTest extends PHPUnit_Framework_TestCase
{
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
        $dictionary = new Dictionary('en', __DIR__ . '/_files');
        $this->assertEquals('This is an example', $dictionary['example']['title']);
    }

    public function testNonExistingDictionary()
    {
        $dictionary = new Dictionary('en', __DIR__ . '/_files');
        $this->assertNull($dictionary['example']['title2']);
    }

    public function testErrors()
    {
        $dictionary = new Dictionary('en', __DIR__ . '/_files');
        $errors = $dictionary->errors(['test_exists', 'test2_exists'], $dictionary['example']['errors']);
        $this->assertEquals('This is an error', $errors['test_exists']);
        $this->assertEquals('This already exists', $errors['test2_exists']);
    }

    public function testHtmlFile()
    {
        $dictionary = new Dictionary('en', __DIR__ . '/_files');
        $result = $dictionary['test1']['example'];
        $this->assertRegExp("/Hello World/", $result);
    }

    public function testFileWithDirectory()
    {
        $dictionary = new Dictionary('en', __DIR__ . '/_files');
        $result = $dictionary['test1']['foo'];
        $this->assertEquals('bar', $result);
    }
}
