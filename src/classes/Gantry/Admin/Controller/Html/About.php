<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Admin\Theme\ThemeList;
use Gantry\Component\Controller\HtmlController;

class About extends HtmlController
{
    public function index()
    {
        // TODO: Find better way:
        $this->params['info'] = (new ThemeList)->getTheme($this->container['theme.name']);

        return $this->container['admin.theme']->render('@gantry-admin/pages/about/about.html.twig', $this->params);
    }
}
