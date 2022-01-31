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

namespace Gantry\Component\Twig\TokenParser;

use Gantry\Component\Twig\Node\TwigNodeScripts;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Adds javascript / style assets to head/footer/custom location.
 *
 * {% assets in 'head' with { priority: 2 } %}
 *   <script type="text/javascript" src="{{ url('gantry-theme://js/my.js') }}"></script>
 *   <link rel="stylesheet" href="{{ url('gantry-assets://css/font-awesome.min.css') }}" type="text/css"/>
 * {% endassets -%}
 */
class TokenParserAssets extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token A Twig Token instance
     * @return Node A Twig Node instance
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        list($location, $variables) = $this->parseArguments($token);

        $content = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new TwigNodeScripts($content, $location, $variables, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return array
     * @throws SyntaxError
     */
    protected function parseArguments(Token $token)
    {
        $stream = $this->parser->getStream();
        $location = null;
        if ($stream->nextIf(Token::OPERATOR_TYPE, 'in')) {
            $location = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $lineno = $token->getLine();
            $location = new ConstantExpression('head', $lineno);
        }

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $lineno = $token->getLine();
            $variables = new ArrayExpression([], $lineno);
            $variables->setAttribute('priority', 0);
        }
        $stream->expect(Token::BLOCK_END_TYPE);

        return [$location, $variables];
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideBlockEnd(Token $token)
    {
        return $token->test('endassets');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'assets';
    }
}
