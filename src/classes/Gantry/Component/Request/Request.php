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

namespace Gantry\Component\Request;

class Request
{
    /**
     * @var string
     */
    protected $method;

    /**
     * @var Input
     */
    public $get;

    /**
     * @var Input
     */
    public $post;

    /**
     * @var Input
     */
    public $cookie;

    /**
     * @var Input
     */
    public $server;

    /**
     * @var Input
     */
    public $request;

    public function __construct()
    {
        $this->init();
    }

    public function getMethod()
    {
        if (!$this->method) {
            $method = $this->server['REQUEST_METHOD'] ?: 'GET';
            if ('POST' === $method) {
                $method = $this->server['X-HTTP-METHOD-OVERRIDE'] ?: $method;
                $method = $this->post['METHOD'] ?: $method;
            }
            $this->method = strtoupper($method);
        }

        return $this->method;
    }

    protected function init()
    {
        $this->get = new Input($_GET);
        $this->post = new Input($_POST);
        $this->cookie = new Input($_COOKIE);
        $this->server = new Input($_SERVER);
        $this->request = new Input($_REQUEST);
    }
}
