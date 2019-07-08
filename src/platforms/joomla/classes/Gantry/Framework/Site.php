<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 20179RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Joomla\JoomlaFactory;
use Joomla\CMS\Document\HtmlDocument;

/**
 * Class Site
 * @package Gantry\Framework
 */
class Site
{
    public function __construct()
    {
        $document = JoomlaFactory::getDocument();

        if ($document instanceof HtmlDocument) {
            $this->theme = $document->template;
            $this->url = $document->baseurl;
            $this->title = $document->title;
            $this->description = $document->description;
        }
    }
}
