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
class TokenParserTry extends \Twig_TokenParser
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

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $try = $this->parser->subparse([$this, 'decideCatch']);
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $catch = $this->parser->subparse([$this, 'decideEnd']);
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TwigNodeTry($try, $catch, $lineno, $this->getTag());
    }

    public function decideCatch(\Twig_Token $token)
    {
        return $token->test(array('catch'));
    }

    public function decideEnd(\Twig_Token $token)
    {
        return $token->test(array('endtry')) || $token->test(array('endcatch'));
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
