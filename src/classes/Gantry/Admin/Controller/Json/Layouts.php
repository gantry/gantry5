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
        $files = Folder::all(realpath(JPATH_THEMES . '/../../templates/gantry/common/layouts'), $options);

        $response = [];
        foreach($files as $structure) {
            $content = JsonFile::instance($structure)->content();
            $response[$content['name']] = $content;
        }
        return new JsonResponse($response);
    }
}
