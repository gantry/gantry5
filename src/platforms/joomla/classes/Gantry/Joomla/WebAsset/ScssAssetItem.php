<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\WebAsset;

use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Joomla\CMS\WebAsset\WebAssetItem;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Asset Item class
 *
 * Asset Item are "read only" object, all properties must be set through class constructor.
 * Only properties allowed to be edited is an attributes and an options.
 * Changing an uri or a dependencies are not allowed, prefer to create a new asset instance.
 *
 * @since  5.6.0
 */
class ScssAssetItem extends WebAssetItem
{
    /**
     * Get the URI of the asset
     *
     * @param  boolean  $resolvePath  Whether need to search for a real paths
     *
     * @return string
     *
     * @since  5.6.0
     */
    public function getUri($resolvePath = true): string
    {
        $gantry = Gantry::instance();

        /** @var Document $document */
        $document = $gantry['document'];

        /** @var Theme|null $theme */
        $theme = $gantry['theme'] ?? null;

        $uri = $this->uri;

        if ($theme && preg_match('|\.scss$|', $this->uri)) {
            $uri = $theme->css(Gantry::basename($this->uri, '.scss'));
            $uri = $document::url($uri, null, 0, false);
            $uri = \ltrim($uri, '/');
        }

        return $uri;
    }
}
