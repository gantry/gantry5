<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Request\Input;
use Gantry\Component\Request\Request as BaseRequest;

/**
 * Class Request
 * @package Gantry\Framework
 */
class Request extends BaseRequest
{
    public function init()
    {
        // Replaces parent contructor.

        $get = \stripslashes_deep($_GET);
        $this->get = new Input($get);

        $post = \stripslashes_deep($_POST);
        $this->post = new Input($post);

        $cookie = \stripslashes_deep($_COOKIE);
        $this->cookie = new Input($cookie);

        $server = \stripslashes_deep($_SERVER);
        $this->server = new Input($server);

        $request = array_merge($get, $post);
        $this->request = new Input($request);
    }
}
