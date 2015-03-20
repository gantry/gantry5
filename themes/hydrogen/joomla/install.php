<?php
defined('_JEXEC') or die;

class G5_HydrogenInstallerScript
{
    public function preflight($type, $parent)
    {
        // Prevent installation if Gantry5 isn't enabled.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException('Please install Gantry5 Framework!');
            }

            Gantry5\Loader::setup();

        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }
}
