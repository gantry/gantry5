<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Twig\Node;

use LogicException;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TwigNodeTryCatch
 * @package Gantry\Component\Twig\Node
 */
class TwigNodeTryCatch extends Node
{
    /**
     * TwigNodeTryCatch constructor.
     * @param Node $try
     * @param Node|null $catch
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(Node $try, Node $catch = null, $lineno = 0, $tag = null)
    {
        $nodes = ['try' => $try, 'catch' => $catch];
        $nodes = array_filter($nodes);

        parent::__construct($nodes, [], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler A Twig Compiler instance
     * @return void
     * @throws LogicException
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write('try {');

        $compiler
            ->indent()
            ->subcompile($this->getNode('try'))
            ->outdent()
            ->write('} catch (\Exception $e) {' . "\n")
            ->indent()
            ->write('if ($context[\'gantry\']->debug()) throw $e;' . "\n")
            ->write('if (\GANTRY_DEBUGGER) \Gantry\Debugger::addException($e);' . "\n")
            ->write('$context[\'e\'] = $e;' . "\n");

        if ($this->hasNode('catch')) {
            $compiler->subcompile($this->getNode('catch'));
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
