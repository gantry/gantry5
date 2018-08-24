<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2018 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Twig\TokenParser;

use Gantry\Component\Twig\Node\TwigNodeBreak;

/**
 * Allows to escape a for loop via {% break %}
 *
 * {% for post in posts %}
 *     {% if post.id == 10 %}
 *         {% break %}
 *     {% endif %}
 *     <h2>{{ post.heading }}</h2>
 * {% endfor %}
 *
 * @see https://stackoverflow.com/a/40949346
 */
class TokenParserBreak extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        // Trick to check if we are currently in a loop.
        $currentForLoop = 0;

        for ($i = 1; true; $i++) {
            try {
                // if we look before the beginning of the stream
                // the stream will throw a \Twig_Error_Syntax
                $token = $stream->look(-$i);
            } catch (\Twig_Error_Syntax $e) {
                break;
            } catch (\Exception $e) {
                // The error handler of Twig is causing an undefined index issue
                // https://github.com/twigphp/Twig/pull/2736
                break;
            }

            if ($token->test(\Twig_Token::NAME_TYPE, 'for')) {
                $currentForLoop++;
            } else if ($token->test(\Twig_Token::NAME_TYPE, 'endfor')) {
                $currentForLoop--;
            }
        }


        if ($currentForLoop < 1) {
            throw new \Twig_Error_Syntax(
                'Break tag is only allowed in \'for\' loops.',
                $stream->getCurrent()->getLine(),
                $stream->getSourceContext()->getName()
            );
        }

        return new TwigNodeBreak();
    }

    public function getTag()
    {
        return 'break';
    }
}
