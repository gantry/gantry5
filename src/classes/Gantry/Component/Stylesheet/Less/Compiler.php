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

namespace Gantry\Component\Stylesheet\Less;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document;
use Gantry\Framework\Gantry;
use \lessc as BaseCompiler;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;
    protected $fonts;
    protected $usedFonts;

    public function setBasePath($basePath)
    {
        $this->basePath = '/' . Folder::getRelativePath($basePath);
    }

    public function setFonts(array $fonts)
    {
        $this->fonts = $fonts;
    }

    public function libUrl(array $args, Compiler $compiler)
    {
        // Function has a single parameter.
        $parsed = reset($args);
        if (!$parsed) {
            $this->throwError('url() is missing parameter');
        }

        // Compile parsed value to string.
        $url = trim($compiler->compileValue($parsed), '\'"');

        // Handle ../ inside CSS files (points to current theme).
        $uri = strpos($url, '../') === 0 ? 'gantry-theme://' . substr($url, 3) : $url;

        // Generate URL, failed streams will be kept as they are to allow users to find issues.
        $url = Document::url($uri) ?: $url;

        // Changes absolute URIs to relative to make the path to work even if the site gets moved.
        if ($url[0] == '/' && $this->basePath) {
            $url = Folder::getRelativePathDotDot($url, $this->basePath);
        }

        // Return valid CSS.
        return "url('{$url}')";
    }

    public function reset()
    {
        $this->usedFonts = [];

        return $this;
    }
}
