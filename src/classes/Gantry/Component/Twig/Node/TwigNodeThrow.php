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

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TwigNodeThrow
 * @package Gantry\Component\Twig\Node
 */
class TwigNodeThrow extends Node
{
    /**
     * TwigNodeThrow constructor.
     * @param int $code
     * @param Node $message
     * @param int $lineno
     * @param string|null $tag
     */
    public function __construct(
        $code,
        Node $message,
        $lineno = 0,
        $tag = null
    ) {
        parent::__construct(['message' => $message], ['code' => $code], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler A Twig Compiler instance
     * @throws \LogicException
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write('throw new \RuntimeException(')
            ->subcompile($this->getNode('message'))
            ->write(', ')
            ->write($this->getAttribute('code') ?: 500)
            ->write(");\n");
    }
}
