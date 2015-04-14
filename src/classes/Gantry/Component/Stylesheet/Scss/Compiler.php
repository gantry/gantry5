<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Stylesheet\Scss;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document;
use Gantry\Framework\Gantry;
use Leafo\ScssPhp\Compiler as BaseCompiler;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;

    public function setBasePath($basePath)
    {
        $this->basePath = '/' . Folder::getRelativePath($basePath);
    }

    public function compileValue($value)
    {
        return parent::compileValue($value);
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
