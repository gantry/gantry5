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

namespace Gantry\Component\Twig\TokenParser;

use Gantry\Component\Twig\Node\TwigNodeScripts;

/**
 * Adds javascript / style assets to head/footer/custom location.
 *
 * {% assets in 'head' with { priority: 2 } %}
 *   <script type="text/javascript" src="{{ url('gantry-theme://js/my.js') }}"></script>
 *   <link rel="stylesheet" href="{{ url('gantry-assets://css/font-awesome.min.css') }}" type="text/css"/>
 * {% endassets -%}
 */
class TokenParserAssets extends \Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     *
     * @return \Twig_Node A Twig_Node instance
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        list($location, $variables) = $this->parseArguments($token);

        $content = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TwigNodeScripts($content, $location, $variables, $lineno, $this->getTag());
    }

    /**
     * @param \Twig_Token $token
     * @return array
     */
    protected function parseArguments(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $location = null;
        if ($stream->nextIf(\Twig_Token::OPERATOR_TYPE, 'in')) {
            $location = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $lineno = $token->getLine();
            $location = new \Twig_Node_Expression_Constant('head', $lineno);
        }

        if ($stream->nextIf(\Twig_Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $lineno = $token->getLine();
            $variables = new \Twig_Node_Expression_Array([], $lineno);
            $variables->setAttribute('priority', 0);
        }
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return [$location, $variables];
    }

    public function decideBlockEnd(\Twig_Token $token)
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
