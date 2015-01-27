<?php
namespace Gantry\Component\Stylesheet;

use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ScssPhp extends CssCompiler implements CssCompilerInterface
{
    /**
     * @var string
     */
    public $type = "scss";

    /**
     * @var string
     */
    public $name = "scss";

    /**
     * Constructor.
     *
     * @param   array  $options  List of options used to configure the compiler
     */
    public function __construct(array $options = null)
    {
        $options['compiler'] = new \scssc();

        parent::__construct($options);
    }

    public function isSupported()
    {
        return class_exists($this->name);
    }

    public function compile($in)
    {
        $inCode = $in;

        /** @var \scssc $compiler */
        // Get the active compiler
        $compiler = $this->getCompiler();
        $out = $compiler->compile($inCode);
        return $out;
    }

    public function compileFile($in = 'base', $out) {
        $inName = $in;
        $outName = null;

        // Use the in name for output file if no output file is specified
        if(isset($out)) {
            $outName = $in;
        } else {
            $outName = $out;
        }

        // Load Gantry Instance
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $pathIn = $locator->findResources('gantry-theme://scss');

        /** @var \scssc $compiler */
        // Get the active compiler
        $compiler = $this->getCompiler();
        //$compiler->setVariables($this->getVariables());
        // Set the correct path for lookup
        $compiler->setImportPaths($pathIn);
        //Run the compiler compile function

        $compiler->compile('@import "' . $inName . '.scss"');

        $pathOut = $locator->findResource('gantry-theme://css-compiled', true, true);

        $file = File::instance($pathOut.'/'.$outName);
        // Attempt to lock the file for writing.
        $file->lock(false);
        //TODO: Better way to handle double writing files at same time
        if ($file->locked() === false) {
            // File was already locked by another process.
            return false;
        }
        $file->save();
        $file->unlock();
        return $file;
    }
}
