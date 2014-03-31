<?php
namespace Sinergi\Dictionary\Tests;

use PHPUnit_Framework_TestCase;
use Sinergi\Dictionary\Autoloader;

class AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        Autoloader::register();
        $this->assertContains(['Sinergi\\Dictionary\\Autoloader', 'autoload'], spl_autoload_functions());
    }

    public function testAutoload()
    {
        $declared = get_declared_classes();
        $declaredCount = count($declared);
        Autoloader::autoload('Foo');
        $this->assertEquals(
            $declaredCount,
            count(get_declared_classes()),
            'Sinergi\\Dictionary\\Autoloader::autoload() is trying to load classes outside of the Sinergi\\Dictionary namespace'
        );
        Autoloader::autoload('Sinergi\\Dictionary\\Dictionary');
        $this->assertTrue(
            in_array('Sinergi\\Dictionary\\Dictionary', get_declared_classes()),
            'Sinergi\\Dictionary\\Autoloader::autoload() failed to autoload the Sinergi\\Dictionary\\Dictionary class'
        );
    }
}