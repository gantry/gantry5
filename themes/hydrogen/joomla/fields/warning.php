<?php
class JFormFieldWarning extends JFormField
{
    protected $type = 'Warning';

    protected function getInput()
    {
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('TPL_G5_HYDROGEN_INSTALL_GANTRY'), 'error');
    }
}
