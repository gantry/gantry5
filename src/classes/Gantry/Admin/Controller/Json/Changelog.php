<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2022 Tiger12, LLC
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

/**
 * Class Changelog
 * @package Gantry\Admin\Controller\Json
 */
class Changelog extends JsonController
{
    /** @var string */
    protected $fullurl = 'https://github.com/gantry/gantry5/blob/develop';
    /** @var string */
    protected $issues = 'https://github.com/gantry/gantry5/issues';
    /** @var string */
    protected $contrib = 'https://github.com';
    /** @var string */
    protected $file = 'CHANGELOG.md';
    /** @var array */
    protected $platforms = ['common' => 'share-alt', 'joomla' => '', 'wordpress' => '', 'grav' => ''];
    /** @var array */
    protected $httpVerbs = [
        'POST' => [
            '/' => 'index'
        ]
    ];

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $version = $this->request->post['version'];
        $lookup = $version;

        if ($version === '@version@') {
            $version = 'develop';
            $lookup  = '';
        }

        if (strpos($version, 'dev-') === 0) {
            $version = preg_replace('/^dev-/i', '', $version);
            $lookup  = '';
        }

        // Read the changelog bundled with the plugin (readme.txt) instead of a remote URL.
        $changelog = $this->localChangelog();

        if ($changelog) {
            $found = preg_match("/(#\\s" . $lookup . ".+?\\n.*?)(?=\\n{1,}#|$)/uis", $changelog, $changelog);

            if ($found) {
                $changelog = \Parsedown::instance()->parse($changelog[0]);

                // auto-link issues
                $changelog = preg_replace("/#(\\d{1,})/ui", '<a target="_blank" rel="noopener" href="' . $this->issues . '/$1">#$1</a>', $changelog);

                // auto-link contributors
                $changelog = preg_replace("/@([\\w]+)[^\\w]/ui", '<a target="_blank" rel="noopener" href="' . $this->contrib . '/$1">@$1</a> ', $changelog);

                // add icons for platforms
                foreach($this->platforms as $platform => $icon) {
                    $changelog = preg_replace('/(<a href="\#' . $platform . '">)/uis', '$1<i class="fa fa-' . ($icon ?: $platform) . '" aria-hidden="true"></i> ', $changelog);
                }
            } else {
                $changelog = 'No changelog for version <strong>' . $version . '</strong> was found.';
            }
        }

        $response = [
            'html' => $this->render('@gantry-admin/ajax/changelog.html.twig', [
                'changelog' => $changelog,
                'version'   => $version,
                'url'       => $this->fullurl . '/' . $this->file,
                'fullurl'   => $this->fullurl . '/' . $this->file
            ])
        ];

        return new JsonResponse($response);
    }

    /**
     * Convert the readme.txt changelog section into Markdown headings ("# x.y.z").
     *
     * @return string|null
     */
    protected function localChangelog()
    {
        if (!\defined('GANTRY5_PATH')) {
            return null;
        }
        $file = GANTRY5_PATH . '/readme.txt';
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $readme = (string) file_get_contents($file);
        $pos = strpos($readme, '== Changelog ==');
        if ($pos === false) {
            return null;
        }

        $section = substr($readme, $pos + strlen('== Changelog =='));

        return (string) preg_replace('/^=\s*(.+?)\s*=\s*$/m', '# $1', $section);
    }
}
