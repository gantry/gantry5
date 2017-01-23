<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Twig;

class TwigNodePageblock extends \Twig_Node implements \Twig_NodeCaptureInterface
{
    protected $tagName = 'pageblock';

    public function __construct(\Twig_NodeInterface $body = null, \Twig_Node_Expression $location = null, \Twig_Node_Expression $variables = null, $lineno, $tag = null)
    {
        parent::__construct(['body' => $body, 'location' => $location, 'variables' => $variables], [], $lineno, $tag);
    }
    /**
     * Compiles the node to PHP.
     *
     * @param \Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this)
            ->write('$pageblockVariables = ')
            ->subcompile($this->getNode('variables'))
            ->raw(";\n")
            ->write("if (\$pageblockVariables && !is_array(\$pageblockVariables)) {\n")
            ->indent()
            ->write("throw new UnexpectedValueException('{% {$this->tagName} with x %}: x is not an array');\n")
            ->outdent()
            ->write("}\n")
            ->write('$location = ')
            ->subcompile($this->getNode('location'))
            ->raw(";\n")
            ->write("if (\$location && !is_string(\$location)) {\n")
            ->indent()
            ->write("throw new UnexpectedValueException('{% {$this->tagName} in x %}: x is not a string');\n")
            ->outdent()
            ->write("}\n")
            ->write("\$priority = isset(\$pageblockVariables['priority']) ? \$pageblockVariables['priority'] : 0;\n")
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\$content = ob_get_clean();\n")
            ->write("Gantry\Framework\Gantry::instance()['document']->addHtml(\$content, \$priority, \$location);\n");
    }
}
