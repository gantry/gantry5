<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Admin\HtmlController;
use Gantry\Framework\Exporter;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Symfony\Component\Yaml\Yaml;

class Export extends HtmlController
{
    public function index()
    {
        if (!class_exists('Gantry\Framework\Exporter')) {
            $this->forbidden();
        }

        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('Please enable PHP ZIP extension to use this feature.');
        }

        $exporter = new Exporter;
        $exported = $exporter->all();

        $zipname = $exported['export']['theme']['name'] . '-export.zip';
        $tmpname = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new \ZipArchive();
        $zip->open($tmpname, \ZipArchive::CREATE);

        $zip->addFromString("export.yaml", Yaml::dump($exported['export'], 10, 2));
        unset($exported['export']);

        foreach ($exported['positions'] as $key => $position) {
            foreach ($position['items'] as $module => $data) {
                $zip->addFromString("positions/{$key}/{$module}.yaml", Yaml::dump($data, 10, 2));
            }

            $position['ordering'] = array_keys($position['items']);
            unset($position['items']);

            $zip->addFromString("positions/{$key}.yaml", Yaml::dump($position, 10, 2));
        }

        foreach ($exported['outlines'] as $outline => &$data) {
            if (!empty($data['config'])) {
                foreach ($data['config'] as $name => $config) {
                    if (in_array($name, ['particles', 'page'])) {
                        foreach ($config as $sub => $subconfig) {
                            $zip->addFromString("outlines/{$outline}/{$name}/{$sub}.yaml", Yaml::dump($subconfig, 10, 2));
                        }
                    } else {
                        $zip->addFromString("outlines/{$outline}/{$name}.yaml", Yaml::dump($config, 10, 2));
                    }
                }
            }
            unset($data['config']);
        }
        $zip->addFromString("outlines/outlines.yaml", Yaml::dump($exported['outlines'], 10, 2));

        foreach ($exported['menus'] as $menu => $data) {
            $zip->addFromString("menus/{$menu}.yaml", Yaml::dump($data, 10, 2));
        }

        foreach ($exported['content'] as $id => $data) {
            $zip->addFromString("content/{$id}.yaml", Yaml::dump($data, 10, 2));
        }

        if (!empty($exported['categories'])) {
            $zip->addFromString("content/categories.yaml", Yaml::dump($exported['categories'], 10, 2));
        }

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        foreach ($exported['files'] as $stream => $files) {
            foreach ($files as $path => $uri) {
                $filename = $locator->findResource($uri);
                
                if (file_exists($filename)) {
                    $zip->addFile($filename, "files/{$stream}/{$path}");
                }
            }
            
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . $zipname);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmpname));

        @ob_end_clean();
        flush();

        readfile($tmpname);
        unlink($tmpname);

        exit;
    }
}
