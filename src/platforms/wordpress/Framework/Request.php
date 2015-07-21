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

namespace Gantry\Framework;

use Gantry\Component\Request\Input;
use Gantry\Component\Request\Request as BaseRequest;

class Request extends BaseRequest
{
    public function __construct()
    {
        $get = stripslashes_deep($_GET);
        $this->get = new Input($get);

        $post = stripslashes_deep($_POST);
        $this->post = new Input($post);

        $cookie = stripslashes_deep($_COOKIE);
        $this->cookie = new Input($cookie);

        $server = stripslashes_deep($_SERVER);
        $this->server = new Input($server);

        $request = array_merge($get, $post);
        $this->request = new Input($request);
    }
}
