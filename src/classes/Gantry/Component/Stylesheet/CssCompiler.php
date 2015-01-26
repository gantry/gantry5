<?php
namespace Gantry\Component\Stylesheet;

use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class CssCompiler
{

    private $_compiler;

    protected $type;

    protected $name;

    protected $debug = false;

    /**
     * Constructor.
     *
     * @param   array  $options  List of options used to configure the compiler
     */
    public function __construct($options)
    {
        // Initialise object variables.
        $this->_compiler = $options['compiler'];
    }

    /**
     * @return array
     *
     * Comes from Joomla, will need to be updated, more for proof of concept
     */
    // TODO: Fix so we can load compilers from different locations if possible, such as a custom compiler that is distributed with the template.
    public function getCompilers()
    {
        $compilers = array();

        // Get an iterator and loop trough the driver classes.
        $iterator = new \DirectoryIterator(__DIR__ . '/Compilers');

        /**
         * @var \DirectoryIterator  $file
         */
        foreach ($iterator as $file)
        {
            $fileName = $file->getFilename();

            // Only load for php files.
            // Note: DirectoryIterator::getExtension only available PHP >= 5.3.6
            if (!$file->isFile() || $file->getExtension() != 'php')
            {
                continue;
            }

            // Derive the class name from the type.
            $class = str_ireplace('.php', '', trim($fileName));

            // If the class doesn't exist we have nothing left to do but look at the next type. We did our best.
            if (!class_exists($class))
            {
                continue;
            }

            // Sweet!  Our class exists, so now we just need to know if it passes its test method.
            if ($class::isSupported())
            {
                // Connector names should not have file extensions.
                $compilers[] = str_ireplace('.php', '', $fileName);
            }
        }

        return $compilers;
    }

    public function getVariables()
    {
        $gantry = Gantry::instance();

        $config = $gantry['config']->get('styles');

        return $config;
    }

    public function resetCache()
    {

    }

    protected function getCompiler()
    {
        return $this->_compiler;
    }

}
