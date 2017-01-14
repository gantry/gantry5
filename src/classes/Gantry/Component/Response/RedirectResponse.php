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

namespace Gantry\Component\Response;

class RedirectResponse extends Response
{
    public function __construct($content = '', $status = 303)
    {
        parent::__construct('', $status);

        $this->setHeader('Location', $content);
    }

    public function getContent()
    {
        return (string) $this->getHeaders()['Location'];
    }

    public function setContent($content)
    {
        $this->setHeader('Location', $content);
    }
}
