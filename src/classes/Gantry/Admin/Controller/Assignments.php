<?php
namespace Gantry\Admin\Controller;

use Gantry\Component\Controller\BaseController;

class Assignments extends BaseController
{
    public function index()
    {
        echo $this->container['admin.theme']->render('@gantry-admin/assignments.html.twig');
    }
}
