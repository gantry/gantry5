<?php
namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Filesystem\Folder;

class Cache extends HtmlController
{
    public function index()
    {
        $locator = $this->container['locator'];

        Folder::delete($locator('gantry-cache://'), false);

        $this->params += [
            'header' => 'Cache Cleared',
            'content' => 'Cache was cleared successfully!'
        ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/custom/notification.html.twig', $this->params);
    }
}
