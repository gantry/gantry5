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


class TwigNodeTry extends \Twig_Node
{
    public function __construct(\Twig_NodeInterface $try, \Twig_NodeInterface $catch = null, $lineno, $tag = null)
    {
        parent::__construct(array('try' => $try, 'catch' => $catch), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param \Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write('try {')
        ;

        $compiler
            ->indent()
            ->subcompile($this->getNode('try'))
        ;

        if ($this->hasNode('catch') && null !== $this->getNode('catch')) {
            $compiler
                ->outdent()
                ->write('} catch (\Exception $e) {' . "\n")
                ->indent()
                ->write('if ($context[\'gantry\']->debug()) throw $e;' . "\n")
                ->write('GANTRY_DEBUGGER && method_exists(\'Gantry\\Debugger\', \'addException\') && \Gantry\Debugger::addException($e);' . "\n")
                ->write('$context[\'e\'] = $e;' . "\n")
                ->subcompile($this->getNode('catch'))
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
