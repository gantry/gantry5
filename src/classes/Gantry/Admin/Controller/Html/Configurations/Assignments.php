<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Controller\HtmlController;

class Assignments extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }
}
