<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class About extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages/about/about.html.twig', $this->params);
    }
}
