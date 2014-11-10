<?php
namespace Gantry\Admin\Controller;

use Gantry\Component\Controller\BaseController;

class Settings extends BaseController
{
    public function index(array $params)
    {
        echo $this->container['admin.theme']->render('@gantry-admin/settings.html.twig');
    }
}
