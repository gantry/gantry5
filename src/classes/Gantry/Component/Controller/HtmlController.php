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

namespace Gantry\Component\Controller;

use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\Response;

abstract class HtmlController extends BaseController
{
    /**
     * Execute controller and returns Response object, defaulting to HtmlResponse.
     *
     * @param string $method
     * @param array $path
     * @param array $params
     * @return mixed
     * @throws \RuntimeException
     */
    public function execute($method, array $path, array $params)
    {
        $response = parent::execute($method, $path, $params);

        if (!$response instanceof Response) {
            $response = new HtmlResponse($response);
        }

        return $response;
    }
}
