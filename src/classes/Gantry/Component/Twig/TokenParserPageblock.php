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

/**
 * Adds javascript / style assets to head/footer/custom location.
 *
 * {% pageblock in 'bottom' with { priority: 0 } %}
 *   <div>Bottom HTML</div>
 * {% endpageblock -%}
 */
class TokenParserPageblock extends \Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     *
     * @return \Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        list($location, $variables) = $this->parseArguments($token);

        $content = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TwigNodePageblock($content, $location, $variables, $lineno, $this->getTag());
    }

    /**
     * @param \Twig_Token $token
     * @return array
     */
    protected function parseArguments(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $lineno = $token->getLine();
        $location = new \Twig_Node_Expression_Constant($stream->expect(\Twig_Token::NAME_TYPE)->getValue(), $lineno);

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
        return $token->test('endpageblock');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'pageblock';
    }
}
