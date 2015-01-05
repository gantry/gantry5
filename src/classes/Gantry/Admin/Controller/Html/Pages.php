<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use RocketTheme\Toolbox\File\JsonFile;

class Pages extends HtmlController
{
    public function index()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_index.html.twig', $this->params);
    }

    public function create()
    {
        return $this->container['admin.theme']->render('@gantry-admin/pages_create.html.twig', $this->params);
    }

    public function edit($id)
    {
        $locator = $this->container['locator'];

        // TODO: remove hardcoded layout.
        $this->params['layout'] = JsonFile::instance($locator('gantry-theme://layouts/test.json'))->content();

        return $this->container['admin.theme']->render('@gantry-admin/pages_edit.html.twig', $this->params);
    }
}
