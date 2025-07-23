<?php

namespace Gantry\Tests\PHP83\Component\Layout;

use Gantry\Tests\PHP83\MockableTest;
use Gantry\Component\Layout\Layout;

/**
 * Test layout component with PHP 8.3
 */
class LayoutTest extends MockableTest
{
    /**
     * Test layout initialization
     */
    public function testLayoutInitialization()
    {
        // Test creating a layout instance with minimal parameters
        $layout = new Layout('test');
        $this->assertInstanceOf(Layout::class, $layout);
        
        // Test getting layout name
        $this->assertEquals('test', $layout->name);
    }
    
    /**
     * Test layout preset loading
     */
    public function testLayoutPresets()
    {
        $layout = new Layout('test');
        
        // Test preset functionality
        $preset = [
            'name' => 'Test Preset',
            'sections' => [
                'main' => [
                    'type' => 'section',
                    'attributes' => ['id' => 'main']
                ]
            ]
        ];
        
        $layout->initPreset($preset);
        
        // Test that preset was applied
        $this->assertNotEmpty($layout->preset);
    }
    
    /**
     * Test layout rendering with PHP 8.3 compatibility
     */
    public function testLayoutRendering()
    {
        $layout = new Layout('test');
        
        // Simple preset for testing
        $preset = [
            'name' => 'Test Preset',
            'sections' => [
                'main' => [
                    'type' => 'section',
                    'attributes' => ['id' => 'main'],
                    'children' => []
                ]
            ]
        ];
        
        $layout->initPreset($preset);
        
        // Test that the layout can be converted to array
        $array = $layout->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
    }
}