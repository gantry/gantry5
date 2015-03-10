<?php
namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Component\Controller\HtmlController;
use Gantry\Framework\Menu;
use Gantry\Framework\Pages;

class Assignments extends HtmlController
{
    public function index()
    {
        $this->params['pages'] = new Pages();

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/assignments/assignments.html.twig', $this->params);
    }

    public function store()
    {
        throw new \RuntimeException('Not Implemented', 404);
    }
}
