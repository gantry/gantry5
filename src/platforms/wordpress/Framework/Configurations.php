<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Admin\ThemeList;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Configurations as BaseConfigurations;
use Gantry\Joomla\StyleHelper;
use Gantry\Joomla\TemplateInstaller;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Configurations extends BaseConfigurations
{
    public function create($title = 'Untitled', $preset = 'default')
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $folder = trim(preg_replace('|[^a-z\d_-]|ui', '_', $title), '_');

        if (!$folder) {
            throw new \RuntimeException("Outline needs a name", 400);
        }

        if ($folder === 'default') {
            throw new \RuntimeException("Outline cannot use reserved name '{$folder}'", 400);
        }

        $path = $locator->findResource("gantry-config://{$folder}", true, true);
        if (is_dir($path)) {
            throw new \RuntimeException("Outline '$title' already exists.", 400);
        }
    }

    public function duplicate($id)
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("gantry-config://{$id}");
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        $folder = $this->findFreeName(trim(preg_replace('|[^a-z\d_-]|ui', '_', $id), '_'));

        $newPath = $locator->findResource("gantry-config://{$folder}", true, true);

        try {
            Folder::copy($path, $newPath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Duplicating Outline failed: ', $e->getMessage()), 500, $e);
        }
    }

    public function rename($id, $title)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException("Outline '$id' cannot be renamed", 400);
        }

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $path = $locator->findResource("gantry-config://{$id}", true, true);
        if (!$path || !is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        $folder = trim(preg_replace('|[^a-z\d_-]|ui', '_', $title), '_');

        if ($folder === 'default') {
            throw new \RuntimeException("Outline cannot use reserved name '{$folder}'", 400);
        }

        $newPath = $locator->findResource("gantry-config://{$folder}", true, true);
        if (is_dir($newPath)) {
            throw new \RuntimeException("Outline '$id' already exists.", 400);
        }

        try {
            Folder::move($path, $newPath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Renaming Outline failed: ', $e->getMessage()), 500, $e);
        }
    }

    public function delete($id)
    {
        if (!$this->canDelete($id)) {
            throw new \RuntimeException("Outline '$id' cannot be deleted", 400);
        }

        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $path = $locator->findResource("gantry-config://{$id}", true, true);
        if (!is_dir($path)) {
            throw new \RuntimeException('Outline not found', 404);
        }

        Folder::delete($path);
    }

    /**
     * @param string $id
     * @return boolean
     */
    public function canDelete($id)
    {
        if (!$id || $id[0] === '_' || $id === 'default') {
            return false;
        }
        return true;
    }

    protected function findFreeName($id)
    {
        $gantry = $this->container;

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        if (preg_match('|^(.*?)(?:_(\d+))?$|ui', $id, $matches)) {
            list (, $name, $count) = $matches;
        }

        $count = max(1, $count);

        do {
            $count++;
        } while ($locator("gantry-config://{$name}_{$count}"));

        return "{$name}_{$count}";
    }
}
