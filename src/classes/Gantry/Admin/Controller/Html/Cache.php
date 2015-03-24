<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Cache extends HtmlController
{
    public function index()
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        Folder::delete($locator('gantry-cache://'), false);

        return new JsonResponse(['html' => 'Cache was successfully cleared', 'title' => 'Cache Cleared']);
    }
}
