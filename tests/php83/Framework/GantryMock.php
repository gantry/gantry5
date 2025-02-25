<?php

namespace Gantry\Framework\Base;

/**
 * Mock Gantry class for testing
 */
class Gantry
{
    private static $instance;
    
    /**
     * @var \stdClass
     */
    public $container;
    
    private $debugMode = false;
    
    /**
     * Constructor.
     */
    protected function __construct()
    {
        // Create container as a class to allow method calls
        $this->container = new class {
            public $services = ['platform' => 'mock'];
            
            public function has($service) {
                return isset($this->services[$service]);
            }
            
            public function get($service) {
                return $this->services[$service] ?? null;
            }
        };
    }
    
    /**
     * Get instance of the Gantry Framework
     *
     * @return Gantry
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }
    
    /**
     * Set/Get debug mode.
     *
     * @param bool|null $enabled  True to enable debugging, null to ignore.
     * @return bool
     */
    public function debug($enabled = null)
    {
        if (isset($enabled)) {
            $this->debugMode = (bool) $enabled;
        }

        return $this->debugMode;
    }
}