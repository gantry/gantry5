<?php
namespace Gantry\Admin\Controller;

use Gantry\Component\Controller\BaseController;

class Updates extends BaseController
{
    public function index(array $params)
    {
        echo $this->container['admin.theme']->render('@gantry-admin/updates.html.twig');
    }
}
