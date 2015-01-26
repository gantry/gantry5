<?php

namespace Gantry\Component\Stylesheet;

use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ScssPhp extends CssCompiler implements CssCompilerInterface {

    public $type = "scss";

    public $name = "scss";

    /**
     * Constructor.
     *
     * @param   array  $options  List of options used to configure the compiler
     */
    public function __construct($options)
    {
        $options['compiler'] = new scssc();;

        parent::__construct($options);
    }

    public function isSupported()
    {
        return class_exists($this->name);
    }

    public function compile($in, $out) {

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $pathIn = $locator->findResources('gantry-theme://scss');

        /** @var \scssc $compiler */
        $compiler = $this->getCompiler();
        $compiler->setVariables($this->getVariables());
        // Set the correct path for lookup
        $compiler->setImportPaths($pathIn);
        //Run the compiler compile function
        $compiler->compile('@import "' . $in . '.scss"');

        $pathOut = $locator->findResource('gantry-theme://css-compiled', true, true);

        $file = File::instance($pathOut.'/'.$out);
        // Attempt to lock the file for writing.
        $file->lock(false);
        //TODO: Better way to handle double writing files at same time
        if ($file->locked() === false) {
            // File was already locked by another process.
            return;
        }
        $file->save();
        $file->unlock();
    }
    
}