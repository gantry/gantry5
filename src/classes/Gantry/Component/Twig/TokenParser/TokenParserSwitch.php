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

use Gantry\Component\Twig\Node\TwigNodeSwitch;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Adds ability use elegant switch instead of ungainly if statements
 *
 * {% switch type %}
 *   {% case 'foo' %}
 *      {{ my_data.foo }}
 *   {% case 'bar' %}
 *      {{ my_data.bar }}
 *   {% default %}
 *      {{ my_data.default }}
 * {% endswitch %}
 */
class TokenParserSwitch extends AbstractTokenParser
{
    /**
     * @param Token $token
     * @return TwigNodeSwitch
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $name = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        // There can be some whitespace between the {% switch %} and first {% case %} tag.
        while ($stream->getCurrent()->getType() === Token::TEXT_TYPE && trim($stream->getCurrent()->getValue()) === '') {
            $stream->next();
        }

        $stream->expect(Token::BLOCK_START_TYPE);

        $expressionParser = $this->parser->getExpressionParser();

        $default = null;
        $cases = [];
        $end = false;

        while (!$end) {
            $next = $stream->next();

            switch ($next->getValue()) {
                case 'case':
                    $values = [];

                    while (true) {
                        $values[] = $expressionParser->parsePrimaryExpression();
                        // Multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }

                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $cases[] = new Node([
                        'values' => new Node($values),
                        'body' => $body
                    ]);
                    break;

                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $default = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new TwigNodeSwitch($name, new Node($cases), $default, $lineno, $this->getTag());
    }

    /**
     * Decide if current token marks switch logic.
     *
     * @param Token $token
     * @return bool
     */
    public function decideIfFork(Token $token)
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * Decide if current token marks end of swtich block.
     *
     * @param Token $token
     * @return bool
     */
    public function decideIfEnd(Token $token)
    {
        return $token->test(['endswitch']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'switch';
    }
}
