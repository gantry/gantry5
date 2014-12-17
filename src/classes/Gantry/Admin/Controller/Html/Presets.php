<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Presets extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/presets.html.twig', $this->params);
    }
}
