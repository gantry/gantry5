<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Framework\Exporter;
use Symfony\Component\Yaml\Yaml;

class Export extends HtmlController
{
    public function index()
    {
        // Experimental module exporter...
        $list = [];
        if (class_exists('Gantry\Framework\Exporter')) {
            $exporter = new Exporter;
            $contents = Yaml::dump($exporter->positions(), 10, 2);

            $filename = 'positions.yaml';
            $filesize = strlen($contents);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $filesize);

            @ob_end_clean();
            flush();
            echo $contents;
            exit;
        }
    }
}
