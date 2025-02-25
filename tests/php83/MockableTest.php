<?php

namespace Gantry\Tests\PHP83;

use PHPUnit\Framework\TestCase;

/**
 * Base test class for tests that need to use mock classes
 */
class MockableTest extends TestCase
{
    /**
     * Create mock classes for testing
     * Many Gantry features require initialized framework
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerMockClasses();
    }
    
    /**
     * Register mock classes for testing when real implementations are not available
     */
    protected function registerMockClasses()
    {
        // Create mock CssCompiler if needed
        if (!class_exists('\Gantry\Component\Stylesheet\CssCompiler')) {
            eval('
                namespace Gantry\Component\Stylesheet;
                
                class CssCompiler {
                    protected $targetPath = null;
                    
                    public function setTargetPath(?string $path): self
                    {
                        $this->targetPath = $path;
                        return $this;
                    }
                    
                    public function getTargetPath(): ?string
                    {
                        return $this->targetPath;
                    }
                    
                    public function compileAll() { return true; }
                }
            ');
        }
        
        // Create mock ThemeTrait if needed
        if (!trait_exists('\Gantry\Component\Theme\ThemeTrait')) {
            eval('
                namespace Gantry\Component\Theme;
                
                trait ThemeTrait {
                    protected $url;
                }
            ');
        }
        
        // Create ArrayTraits first
        if (!trait_exists('\RocketTheme\Toolbox\ArrayTraits\ArrayAccess')) {
            eval('
                namespace RocketTheme\Toolbox\ArrayTraits;
                
                trait ArrayAccess {
                    protected $items = [];
                    
                    #[\ReturnTypeWillChange]
                    public function offsetExists($offset) {
                        return isset($this->items[$offset]);
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function offsetGet($offset) {
                        return isset($this->items[$offset]) ? $this->items[$offset] : null;
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function offsetSet($offset, $value) {
                        $this->items[$offset] = $value;
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function offsetUnset($offset) {
                        unset($this->items[$offset]);
                    }
                }
            ');
            
            eval('
                namespace RocketTheme\Toolbox\ArrayTraits;
                
                trait Iterator {
                    protected $position = 0;
                    
                    #[\ReturnTypeWillChange]
                    public function current() {
                        $keys = array_keys($this->items);
                        return $this->items[$keys[$this->position]];
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function key() {
                        $keys = array_keys($this->items);
                        return $keys[$this->position];
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function next() {
                        $this->position++;
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function rewind() {
                        $this->position = 0;
                    }
                    
                    #[\ReturnTypeWillChange]
                    public function valid() {
                        $keys = array_keys($this->items);
                        return isset($keys[$this->position]);
                    }
                }
            ');
            
            eval('
                namespace RocketTheme\Toolbox\ArrayTraits;
                
                interface ExportInterface {
                    public function toArray();
                }
            ');
            
            eval('
                namespace RocketTheme\Toolbox\ArrayTraits;
                
                trait Export {
                    public function toArray() {
                        return $this->items;
                    }
                }
            ');
        }
        
        // Create mock Layout class if needed
        if (!class_exists('\Gantry\Component\Layout\Layout')) {
            eval('
                namespace Gantry\Component\Layout;
                
                class Layout implements \ArrayAccess, \Iterator, \RocketTheme\Toolbox\ArrayTraits\ExportInterface
                {
                    use \RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
                    use \RocketTheme\Toolbox\ArrayTraits\Iterator;
                    use \RocketTheme\Toolbox\ArrayTraits\Export;
                    
                    const VERSION = 7;
                    
                    public $name;
                    public $timestamp = 0;
                    public $preset = [];
                    protected $items = [];
                    protected $inherit = false;
                    
                    public function __construct($name)
                    {
                        $this->name = $name;
                        $this->items = [
                            "name" => $name,
                            "timestamp" => time(),
                            "version" => self::VERSION,
                            "preset" => []
                        ];
                    }
                    
                    public function initPreset(array $preset)
                    {
                        $this->preset = $preset;
                        $this->items["preset"] = $preset;
                        return $this;
                    }
                }
            ');
        }
        
        // Create Twig filter and function classes
        if (!class_exists('\Twig\TwigFilter')) {
            eval('
                namespace Twig;
                
                class TwigFilter {
                    protected $name;
                    
                    public function __construct($name, $callable = null, $options = []) {
                        $this->name = $name;
                    }
                    
                    public function getName() {
                        return $this->name;
                    }
                }
            ');
            
            eval('
                namespace Twig;
                
                class TwigFunction {
                    protected $name;
                    
                    public function __construct($name, $callable = null, $options = []) {
                        $this->name = $name;
                    }
                    
                    public function getName() {
                        return $this->name;
                    }
                }
            ');
        }
    
        // Create mock Twig classes if needed
        if (!class_exists('\Gantry\Component\Twig\TwigExtension')) {
            eval('
                namespace Gantry\Component\Twig;
                
                class TwigExtension {
                    public function getFilters()
                    {
                        return [
                            new \Twig\TwigFilter("transFilter"),
                            new \Twig\TwigFilter("truncateHtml")
                        ];
                    }
                    
                    public function getFunctions()
                    {
                        return [
                            new \Twig\TwigFunction("trans"),
                            new \Twig\TwigFunction("url")
                        ];
                    }
                }
            ');
        }
        
        // Create mock Gantry class if needed
        if (!class_exists('\Gantry\Framework\Base\Gantry')) {
            eval('
                namespace Gantry\Framework\Base;
                
                class Gantry
                {
                    private static $instance;
                    
                    /**
                     * @var object
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
                            public $services = ["platform" => "mock"];
                            
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
            ');
        }
        
        // Create mock Platform class if needed
        if (!class_exists('\Gantry\Framework\Platform')) {
            eval('
                namespace Gantry\Framework;
                
                class Platform {
                    public static function isJoomla() { return false; }
                    public static function isWordpress() { return false; }
                    
                    public function getName() { return "test"; }
                    public function getVersion() { return "1.0.0"; }
                }
            ');
            
            eval('
                namespace Gantry\Joomla\Framework;
                
                class Platform extends \Gantry\Framework\Platform {
                    public static function isJoomla() { return true; }
                    public function getName() { return "joomla"; }
                }
            ');
            
            eval('
                namespace Gantry\WordPress\Framework;
                
                class Platform extends \Gantry\Framework\Platform {
                    public static function isWordpress() { return true; }
                    public function getName() { return "wordpress"; }
                }
            ');
        }
    }
}