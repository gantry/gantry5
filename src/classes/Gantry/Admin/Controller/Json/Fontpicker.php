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

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Admin\JsonController;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\File\JsonFile;

class Fontpicker extends JsonController
{
    protected $google_fonts = 'gantry-admin://js/google-fonts.json';

    protected $httpVerbs = [
        'GET' => [
            '/' => 'index'
        ]
    ];

    public function index()
    {
        $this->params['fonts'] = $this->loadGoogleFonts();
        $this->params['variantsMap'] = $this->variantsMap();
        $response = [
            'html' => $this->render('@gantry-admin/ajax/fontpicker.html.twig', $this->params)
        ];
        return new JsonResponse($response);
    }

    public function loadGoogleFonts()
    {
        $data = new \stdClass();
        $file = JsonFile::instance($this->google_fonts);
        $fonts = $file->content()['items'];
        $file->free();

        $data->categories = [];
        $data->subsets = [];

        // create list of unique categories and subsets
        array_walk($fonts, function (&$item) use ($data) {
            if (!in_array($item->category, $data->categories)) {
                $data->categories[] = $item->category;
            }
            $data->subsets = array_unique(array_merge($data->subsets, $item->subsets));
        });

        asort($data->categories);
        asort($data->subsets);

        $data->families = $fonts;
        $data->local_families = $this->loadLocalFonts();

        if (count($data->local_families)) {
            array_unshift($data->categories, 'local-fonts');
        }

        $data->count = count($data->families);

        return $data;
    }

    public function loadLocalFonts()
    {
        $local_fonts = $this->container['theme']->details()->get('configuration.fonts', []);
        $map = [];

        foreach ($local_fonts as $name => $variants) {
            if (is_array($variants)) {
                $list = array_keys($variants);
            } else {
                $list = ['regular'];
            }

            $map[] = ['family' => $name, 'variants' => $list, 'category' => 'local-fonts'];
        }

        return $map;
    }

    protected function variantsMap()
    {
        return [
            '100'       => 'Thin 100',
            '100italic' => 'Thin 100 Italic',
            '200'       => 'Extra-Light 200',
            '200italic' => 'Extra-Light 200 Italic',
            '300'       => 'Light 300',
            '300italic' => 'Light 300 Italic',
            '400'       => 'Normal 400',
            'regular'   => 'Normal 400',
            '400italic' => 'Normal 400 Italic',
            'italic'    => 'Normal 400 Italic',
            '500'       => 'Medium 500',
            '500italic' => 'Medium 500 Italic',
            '600'       => 'Semi-Bold 600',
            '600italic' => 'Semi-Bold 600 Italic',
            '700'       => 'Bold 700',
            '700italic' => 'Bold 700 Italic',
            '800'       => 'Extra-Bold 800',
            '800italic' => 'Extra-Bold 800 Italic',
            '900'       => 'Ultra-Bold 900',
            '900italic' => 'Ultra-Bold 900 Italic'
        ];
    }
}
