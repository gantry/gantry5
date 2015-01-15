<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Styles extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/styles.html.twig', $this->params);
    }
}
