<?php
namespace Gantry\Admin\Controller\Json;

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
        $layout = $this->container['admin.theme']->render('@gantry-admin/overview.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }

    public function settings(array $params)
    {
        $layout = $this->container['admin.theme']->render('@gantry-admin/settings.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }

    public function pages_index(array $params)
    {
        $layout = $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }

    public function pages_create(array $params)
    {
        $layout = $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }

    public function assignments(array $params)
    {
        $layout = $this->container['admin.theme']->render('@gantry-admin/assignments.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }

    public function updates(array $params)
    {
        $layout = $this->container['admin.theme']->render('@gantry-admin/updates.html.twig', ['ajax' => $params['ajax']]);

        return new JsonResponse(['html' => $layout]);
    }
}
