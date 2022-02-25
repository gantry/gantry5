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

use Gantry\Component\Twig\Node\TwigNodeTryCatch;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Handles try/catch in template file.
 *
 * <pre>
 * {% try %}
 *    <li>{{ user.get('name') }}</li>
 * {% catch %}
 *    {{ e.message }}
 * {% endcatch %}
 * </pre>
 */
class TokenParserTryCatch extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     * @return TwigNodeTryCatch
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);
        $try = $this->parser->subparse([$this, 'decideCatch']);
        $stream->next();
        $stream->expect(Token::BLOCK_END_TYPE);
        $catch = $this->parser->subparse([$this, 'decideEnd']);
        $stream->next();
        $stream->expect(Token::BLOCK_END_TYPE);

        return new TwigNodeTryCatch($try, $catch, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideCatch(Token $token)
    {
        return $token->test(['catch']);
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideEnd(Token $token)
    {
        return $token->test(['endtry']) || $token->test(['endcatch']);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'try';
    }
}
