<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Controller\Html\Assignments;
use Gantry\Admin\Controller\Html\Overview;
use Gantry\Admin\Controller\Html\Pages;
use Gantry\Admin\Controller\Html\Settings;
use Gantry\Admin\Controller\Html\Updates;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;

class Page extends JsonController
{
    public function index(array $params)
    {
        return $this->overview($params);
    }

    public function overview(array $params)
    {
        return new JsonResponse(['html' => (new Overview($this->container))->index($params)]);
    }

    public function settings(array $params)
    {
        return new JsonResponse(['html' => (new Settings($this->container))->index($params)]);
    }

    public function pages_index(array $params)
    {
        return new JsonResponse(['html' => (new Pages($this->container))->index($params)]);
    }

    public function pages_create(array $params)
    {
        return new JsonResponse(['html' => (new Pages($this->container))->create($params)]);
    }

    public function assignments(array $params)
    {
        return new JsonResponse(['html' => (new Assignments($this->container))->index($params)]);
    }

    public function updates(array $params)
    {
        throw new \Exception('booo');
        return new JsonResponse(['html' => (new Updates($this->container))->index($params)]);
    }
}
