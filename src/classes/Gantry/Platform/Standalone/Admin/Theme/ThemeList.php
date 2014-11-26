<?php
namespace Gantry\Admin\Theme;

use Gantry\Component\Filesystem\Folder;

class ThemeList
{
    /**
     * @return array
     */
    public static function getStyles()
    {
        $files = Folder::all('gantry-themes://', ['recursive' => false]);
        $list = array();

        foreach ($files as $theme) {
            if (file_exists(STANDALONE_ROOT . '/themes/' . $theme . '/includes/gantry.php')) {
                $template = new \stdClass();
                $template->name = $theme;
                // TODO: We need to be able to add proper lookup paths for gantry-themes://name
                $template->thumbnail = 'common/admin/images/template_thumbnail.png';
                $template->params = [];

                $list[$template->name] = $template;
            }
        }

        return $list;
    }
}
