<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Response;

/**
 * Class RedirectResponse
 * @package Gantry\Component\Response
 */
class RedirectResponse extends Response
{
    /**
     * RedirectResponse constructor.
     * @param string $content
     * @param int $status
     */
    public function __construct($content = '', $status = 303)
    {
        parent::__construct('', $status);

        $this->setHeader('Location', $content);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return (string) $this->getHeaders()['Location'];
    }

    /**
     * @param string $content
     * @return Response
     */
    public function setContent($content)
    {
        $this->setHeader('Location', $content);

        return $this;
    }
}
