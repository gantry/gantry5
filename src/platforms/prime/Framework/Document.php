<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Framework\Base\Document as BaseDocument;

class Document extends BaseDocument
{
    public static function rootUri()
    {
        return PRIME_URI;
    }

    public static function load($framework)
    {
        switch ($framework) {
            case 'jquery':
            case 'jquery.framework':
            Document::addHeaderTag([
                'tag' => 'script',
                'src' => 'https://code.jquery.com/jquery-2.2.2.min.js',
                'integrity' => 'sha256-36cp2Co+/62rEAAYHLmRCPIych47CvdM+uTBJwSzWjI=',
                'crossorigin' => 'anonymous'
            ], 'head', 10);
                break;
            case 'jquery.ui.core':
            case 'jquery.ui.sortable':
                Document::addHeaderTag([
                    'tag' => 'script',
                    'src' => 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js',
                    'integrity' => 'sha256-xNjb53/rY+WmG+4L6tTl9m6PpqknWZvRt0rO1SRnJzw=',
                    'crossorigin' => 'anonymous'
                ], 'head', 10);
                break;
            case 'bootstrap.2':
                Document::addHeaderTag([
                    'tag' => 'script',
                    'src' => 'https://maxcdn.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js'
                ], 'head', 10);
                break;
            case 'bootstrap.3':
                Document::addHeaderTag([
                    'tag' => 'script',
                    'src' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js'
                ], 'head', 10);
                break;
            case 'mootools':
            case 'mootools.framework':
            case 'mootools.core':
            Document::addHeaderTag([
                'tag' => 'script',
                'src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools/1.5.2/mootools-core-compat.min.js'
            ], 'head', 10);
                break;
            case 'mootools.more':
                Document::addHeaderTag([
                    'tag' => 'script',
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/mootools-more/1.5.2/mootools-more-compat-compressed.js'
                ], 'head', 10);
                break;
            default:
                return false;
        }

        return true;
    }
}
