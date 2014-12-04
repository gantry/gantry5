<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Controller\Html\Assignments;
use Gantry\Admin\Controller\Html\Menu;
use Gantry\Admin\Controller\Html\Overview;
use Gantry\Admin\Controller\Html\Pages;
use Gantry\Admin\Controller\Html\Presets;
use Gantry\Admin\Controller\Html\Settings;
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

    public function presets(array $params)
    {
        return new JsonResponse(['html' => (new Presets($this->container))->index($params)]);
    }

    public function settings(array $params)
    {
        return new JsonResponse(['html' => (new Settings($this->container))->index($params)]);
    }

    public function menu(array $params)
    {
        return new JsonResponse(['html' => (new Menu($this->container))->index($params)]);
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
}
