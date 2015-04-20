<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Whoops;

use Whoops\Exception\ErrorException;

class Run extends \Whoops\Run
{
    protected $registeredPatterns = [];

    /**
     * Silence particular errors in particular files
     * @param  array|string $patterns List or a single regex pattern to match
     * @return $this
     */
    public function registerPaths($patterns)
    {
        $this->registeredPatterns = array_merge(
            $this->registeredPatterns,
            array_map(
                function ($pattern){
                    return array(
                        "pattern" => $pattern
                    );
                },
                (array) $patterns
            )
        );
        return $this;
    }

    /**
     * Converts generic PHP errors to \ErrorException
     * instances, before passing them off to be handled.
     *
     * This method MUST be compatible with set_error_handler.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws ErrorException
     */
    public function handleError($level, $message, $file = null, $line = null)
    {
        if (!$this->registeredPatterns) {
            // Just forward to parent function is there aren't no registered patterns.
            return parent::handleError($level, $message, $file, $line);

        }

        // If there are registered patterns, only handle errors if error matches one of the patterns.
        if ($level & error_reporting()) {
            foreach ($this->registeredPatterns as $entry) {
                $pathMatches = (bool) preg_match($entry["pattern"], $file);
                if ($pathMatches) {
                    return parent::handleError($level, $message, $file, $line);
                }
            }
        }

        // Propagate error to the next handler, allows error_get_last() to work on silenced errors.
        return false;
    }
}
