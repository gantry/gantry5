<?php
namespace Gantry\Component\Stylesheet\Less;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document;
use Gantry\Framework\Gantry;
use \lessc as BaseCompiler;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;

    public function setBasePath($basePath)
    {
        $this->basePath = '/' . Folder::getRelativePath($basePath);
    }

    public function libUrl(array $args, Compiler $compiler)
    {
        // Function has a single parameter.
        $url = reset($args);

        if (!$url) {
            $this->throwError('url() is missing parameter');
        }

        $value = trim($compiler->compileValue($url), '\'"');
        $list = explode('?', $value, 2);
        $url = array_shift($list);
        $params = array_shift($list);

        $uri = strpos($url, '../') === 0 ? 'gantry-theme://' . substr($url, 3) : $url;
        $url = (Document::url($uri) ?: $url) . ($params ? "?{$params}" : '');

        if ($url[0] == '/' && $this->basePath) {
            $url = Folder::getRelativePathDotDot($url, $this->basePath);
        }

        return "url('{$url}')";
    }
}
