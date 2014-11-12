<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\File\JsonFile;

class Layouts extends JsonController
{
    public function index(array $params)
    {
        $options = [
            'compare' => 'Filename',
            'pattern' => '|\.json|',
            'filters' => ['key' => '|\.json|'],
            'key' => 'SubPathname',
            'value' => 'Pathname'
        ];

        // FIXME: need access to current theme stream 'theme://common/layouts'
        $files = Folder::all(realpath(JPATH_THEMES . '/../../templates/gantry/common/layouts/presets'), $options);

        $response = [];
        foreach($files as $name => $structure) {
            $content = JsonFile::instance($structure)->content();
            $response[$name] = $content;
        }

        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/layouts/picker.html.twig', ['presets' => $response]);

        return new JsonResponse($response);
    }
}
