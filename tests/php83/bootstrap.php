<?php
/**
 * Gantry Framework - PHP 8.3 Compatibility Test Suite
 *
 * @copyright (c) 2024
 */

// Define paths
define('GANTRY5_ROOT', dirname(dirname(__DIR__)));
define('GANTRY5_CLASSES', GANTRY5_ROOT . '/src/classes');
define('GANTRY5_TESTS', __DIR__);

// Load test base classes first
require_once GANTRY5_TESTS . '/MockableTest.php';

// Initialize a list of classes we want to mock completely
$mockedClasses = [
    'Gantry\\Component\\Layout\\Layout',
    'RocketTheme\\Toolbox\\ArrayTraits\\ArrayAccess',
    'RocketTheme\\Toolbox\\ArrayTraits\\Iterator',
    'RocketTheme\\Toolbox\\ArrayTraits\\Export',
    'RocketTheme\\Toolbox\\ArrayTraits\\ExportInterface',
    'Gantry\\Component\\Stylesheet\\CssCompiler',
    'Gantry\\Component\\Theme\\ThemeTrait',
    'Gantry\\Component\\Twig\\TwigExtension',
    'Gantry\\Framework\\Platform',
    'Gantry\\Joomla\\Framework\\Platform',
    'Gantry\\WordPress\\Framework\\Platform',
    'Gantry\\Framework\\Base\\Gantry'
];

// Register class autoloader for Gantry classes
spl_autoload_register(function ($class) use ($mockedClasses) {
    // Skip classes we've already mocked
    if (in_array($class, $mockedClasses)) {
        return false;
    }
    
    // First check for test classes
    $testFile = GANTRY5_TESTS . '/' . str_replace(['Gantry\\Tests\\PHP83\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($testFile)) {
        include_once $testFile;
        return true;
    }
    
    // Check for mock classes in Framework dir
    if (strpos($class, 'Gantry\\Framework\\Base\\') === 0) {
        $filename = GANTRY5_TESTS . '/Framework/' . basename(str_replace('\\', '/', $class)) . '.php';
        $mockFilename = GANTRY5_TESTS . '/Framework/' . basename(str_replace('\\', '/', $class)) . 'Mock.php';
        
        if (file_exists($mockFilename)) {
            include_once $mockFilename;
            return true;
        }
        if (file_exists($filename)) {
            include_once $filename;
            return true;
        }
    }
    
    // Only load real classes if they're not in our mock list
    if (!in_array($class, $mockedClasses)) {
        // Then check for real Gantry classes
        $filename = GANTRY5_CLASSES . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($filename)) {
            include_once $filename;
            return true;
        }
        
        // Try src/platforms paths
        $platforms = glob(GANTRY5_ROOT . '/src/platforms/*/classes/' . str_replace('\\', '/', $class) . '.php');
        if (!empty($platforms)) {
            include_once $platforms[0];
            return true;
        }
    }
    
    return false;
});

// Try to load vendor autoloader if available
$vendorAutoload = GANTRY5_ROOT . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);