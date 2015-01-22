<?php
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
