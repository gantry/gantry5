<?php

namespace Gantry\Tests\PHP83\Framework;

use Gantry\Tests\PHP83\MockableTest;
use Gantry\Framework\Base\Gantry;

/**
 * Test core Gantry framework functionality with PHP 8.3
 */
class GantryTest extends MockableTest
{
    /**
     * Test instance creation and basic functionality
     */
    public function testGantryInstance()
    {
        // Get the Gantry instance
        $gantry = Gantry::instance();
        
        // Test that we got a valid instance
        $this->assertInstanceOf(Gantry::class, $gantry);
        
        // Test that we can access the container
        $container = $gantry->container;
        $this->assertNotNull($container);
    }
    
    /**
     * Test Gantry container services
     */
    public function testGantryContainer()
    {
        $gantry = Gantry::instance();
        
        // Test platform service
        $this->assertTrue($gantry->container->has('platform'));
        
        // Test theme service
        if ($gantry->container->has('theme')) {
            $theme = $gantry->container->get('theme');
            $this->assertNotNull($theme);
        }
    }
    
    /**
     * Test debugging functionality
     */
    public function testGantryDebug()
    {
        $gantry = Gantry::instance();
        
        // Test debug mode can be set
        $gantry->debug(true);
        $this->assertTrue($gantry->debug());
        
        $gantry->debug(false);
        $this->assertFalse($gantry->debug());
    }
}