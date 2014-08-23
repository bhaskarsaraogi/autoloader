<?php

namespace Autoloader\Tests;

use Collectivism\Autoloader\Autoloader;

class AutoloaderTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
    }

    public function testLoad()
    {
        $classMap = array(
            'Autoloader\\Tests\\Namespace1' => __DIR__ . '/Namespace1',
            'Autoloader\\Tests\\Namespace2' => __DIR__ . '/Namespace2'
            );

        $autoloader = Autoloader::getInstance();
        $autoloader->register($classMap);
    }
}
