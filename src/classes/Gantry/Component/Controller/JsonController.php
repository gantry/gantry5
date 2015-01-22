<?php
namespace Gantry\Component\Controller;

use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;

abstract class JsonController extends BaseController
{
    /**
     * Execute controller and returns JsonResponse object.
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

        if (!$response instanceof JsonResponse) {
            $response = new JsonResponse($response);
        }

        return $response;
    }
}
