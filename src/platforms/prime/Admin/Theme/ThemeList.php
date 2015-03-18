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
        $list = array();

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

        // Add Thumbnails links.
        foreach ($list as $details) {
            $details['thumbnail'] = self::getImage($locator, $details, 'thumbnail');
        }

        return $list;
    }

    protected static function getImage(UniformResourceLocator $locator, $details, $image)
    {
        $image = $details["details.images.{$image}"];

        if (!strpos($image, '://')) {
            $name = $details['name'];
            $image = "gantry-themes-{$name}://{$image}";
        }

        try {
            $image = $locator->findResource($image, false);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }
}
