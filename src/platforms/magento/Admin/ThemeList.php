<?php
namespace Gantry\Admin;

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class ThemeList
{
    /**
     * @return array
     */
    public static function getStyles()
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $files = Folder::all('gantry-themes://', ['recursive' => false]);
        $list = array();

        foreach ($files as $theme) {
            if (file_exists($locator('gantry-themes://' . $theme . '/default/gantry/theme.yaml'))) {
                $details = new ThemeDetails($theme);

                // Stream needs to be valid URL.
                $streamName = 'gantry-themes-' . preg_replace('|[^a-z\d+.-]ui|', '-', $theme);
                if (!$locator->schemeExists($streamName)) {
                    $locator->addPath($streamName, '', $details->getPaths());
                }

                $details['name'] = $theme;
                $details['title'] = ucfirst($theme);
                $details['preview_url'] = '/' . $theme;
                $details['admin_url'] = '/' . $theme . '/admin';
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

            // Stream needs to be valid URL.
            $streamName = 'gantry-themes-' . preg_replace('|[^a-z\d+.-]|ui', '-', $name);
            $image = "{$streamName}://{$image}";
        }

        try {
            $image = $locator->findResource($image, false);
        } catch (\Exception $e) {
            $image = false;
        }

        return $image;
    }
}
