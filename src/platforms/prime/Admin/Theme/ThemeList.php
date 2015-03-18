<?php
namespace Gantry\Admin\Theme;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeList
{
    /**
     * @return array
     */
    public static function getThemes()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $files = Folder::all('gantry-themes://', ['recursive' => false]);

        /** @var array|ThemeDetails[] $list */
        $list = [];

        ksort($files);

        foreach ($files as $theme) {
            if (file_exists(PRIME_ROOT . '/themes/' . $theme . '/includes/gantry.php')) {
                $details = new ThemeDetails($theme);

                if (!$locator->schemeExists('gantry-theme-' . $theme)) {
                    $locator->addPath('gantry-themes-' . $theme, '', $details->getPaths());
                }

                $details['name'] = $theme;
                $details['title'] = $details['details.name'];
                $details['preview_url'] = rtrim(PRIME_URI, '/') . '/' . $theme;
                $details['admin_url'] = rtrim(PRIME_URI, '/') . '/' . $theme . '/admin';
                $details['params'] = [];

                $list[$details->name] = $details;

            }
        }

        // Add Thumbnails links after adding all the paths to the locator.
        foreach ($list as $details) {
            $details['thumbnail'] = $details->getUrl("details.images.thumbnail");
        }

        return $list;
    }

    public static function getTheme($name)
    {
        $themes = static::getThemes();
        return isset($themes[$name]) ? $themes[$name] : null;
    }
}
