<?php
namespace Gantry\Admin\Theme;

use Gantry\Admin\Base\Theme as BaseTheme;

class Theme extends BaseTheme
{
    public function render($file, array $context = array())
    {
        // Add JavaScript Frameworks
        \JHtml::_('bootstrap.framework');

        return parent::render($file, $context);
    }
}
