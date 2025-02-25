<?php

namespace Gantry\Tests\PHP83\Platform;

use Gantry\Tests\PHP83\MockableTest;

/**
 * Test platform detection and compatibility
 */
class PlatformTest extends MockableTest
{
    /**
     * Test platform detection functionality
     */
    public function testPlatformDetection()
    {
        // Get platform instance - dynamically determine which to test
        if (class_exists('\\Gantry\\Framework\\Platform')) {
            $platform = new \Gantry\Framework\Platform();
            $this->assertNotNull($platform);
            
            // Test platform name
            $this->assertNotEmpty($platform->getName());
        } else {
            $this->markTestSkipped('Platform class not available in this context');
        }
    }
    
    /**
     * Test Joomla platform specifics - always skipped for testing
     */
    public function testJoomlaPlatform()
    {
        $this->markTestSkipped('Skipping Joomla-specific test in standalone test environment');
    }
    
    /**
     * Test WordPress platform specifics - always skipped for testing
     */
    public function testWordPressPlatform()
    {
        $this->markTestSkipped('Skipping WordPress-specific test in standalone test environment');
    }
}