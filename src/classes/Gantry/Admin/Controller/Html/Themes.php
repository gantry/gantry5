<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;

class Themes extends HtmlController
{
    public function index()
    {
        echo $this->container['admin.theme']->render('@gantry-admin/themes.html.twig');
    }
}
