<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

/**
 * Gantry 5 Nucleus installer script.
 */
class Gantry5_NucleusInstallerScript
{
    public function uninstall($parent)
    {
        // Remove all Nucleus files manually as file installer only uninstalls files.
        $manifest = $parent->getManifest();

        // Loop through all elements and get list of files and folders
        foreach ($manifest->fileset->files as $eFiles) {
            $target = (string) $eFiles->attributes()->target;
            $targetFolder = empty($target) ? JPATH_ROOT : JPATH_ROOT . '/' . $target;

            // Check if all children exists
            if (count($eFiles->children()) > 0) {
                // Loop through all filenames elements
                foreach ($eFiles->children() as $eFileName) {
                    if ($eFileName->getName() == 'folder')
                    {
                        $folder = $targetFolder . '/' . $eFileName;

                        $files = JFolder::files($folder, '.', false, true);
                        foreach ($files as $name) {
                            JFile::delete($name);
                        }
                        $subFolders = JFolder::folders($folder, '.', false, true);
                        foreach ($subFolders as $name) {
                            JFolder::delete($name);
                        }
                    }
                }
            }
        }

        return true;
    }
}
