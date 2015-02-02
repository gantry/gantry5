<?php
namespace Gantry\Component\Stylesheet;

use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ScssCompiler extends CssCompiler
{
    /**
     * @var string
     */
    public $type = 'scss';

    /**
     * @var string
     */
    public $name = 'SCSS';

    /**
     * @var \scssc
     */
    protected $compiler;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->compiler = new \scssc();
    }

    public function compile($in)
    {
        return $this->compiler->compile($in);
    }

    public function resetCache()
    {
    }

    /**
     * @param string $in    Filename without path or extension.
     * @param string $out   Full path to the file to be written.
     * @return bool         True if the output file was saved.
     */
    public function compileFile($in, $out = null)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Set the lookup paths.
        $this->compiler->setImportPaths($locator->findResources('gantry-theme://scss'));
        $this->compiler->setFormatter('scss_formatter_nested');

        // Run the compiler.
        $this->compiler->setVariables($this->getVariables());
        $css = $this->compiler->compile('@import "' . $in . '.scss"');

        if (!$out) {
            $out = $locator->findResource("gantry-theme://css-compiled/{$in}.css", true, true);
        }

        $file = File::instance($out);

        // Attempt to lock the file for writing.
        $file->lock(false);

        //TODO: Better way to handle double writing files at same time.
        if ($file->locked() === false) {
            // File was already locked by another process.
            return false;
        }

        $file->save($css);
        $file->unlock();

        return true;
    }
}
