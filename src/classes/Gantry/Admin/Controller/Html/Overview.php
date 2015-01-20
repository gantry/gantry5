<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Overview extends HtmlController
{
    public function index()
    {
        if (!isset($this->container['theme'])) {
            return $this->container['admin.theme']->render('@gantry-admin/welcome.html.twig', $this->params);
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/overview/overview.html.twig', $this->params);
    }
}
