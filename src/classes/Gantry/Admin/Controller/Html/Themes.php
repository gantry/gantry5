<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Admin\Theme\ThemeList;
use Gantry\Component\Controller\HtmlController;

class Themes extends HtmlController
{
    public function index(array $params)
    {
        $styles = (new ThemeList)->getStyles();

        return $this->container['admin.theme']->render('@gantry-admin/themes.html.twig', ['styles' => $styles]);
    }
}
