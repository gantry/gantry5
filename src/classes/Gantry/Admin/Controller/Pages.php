<?php
namespace Gantry\Admin\Controller;

use Gantry\Component\Controller\BaseController;

class Pages extends BaseController
{
    public function index()
    {
        echo $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig');
    }

    public function create()
    {
        echo $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig');
    }

    public function edit($id)
    {
        echo $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig');
    }
}
