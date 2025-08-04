<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;

/**
 * Class Site
 * @package Gantry\Framework
 */
class Site
{
    /** @var string */
    public $theme;
    /** @var string */
    public $url;
    /** @var string */
    public $title;
    /** @var string */
    public $description;

    public function __construct()
    {
        try {
            /** @var CMSApplication $application */
            $application = Factory::getApplication();
            $document = $application->getDocument();

            if ($document instanceof HtmlDocument) {
                $this->theme = $document->template;
                $this->url = $document->baseurl;
                $this->title = $document->title;
                $this->description = $document->description;
            }
        } catch (\Exception $e) {
            // Catch errors when trying to get site properties from admin
            $this->theme = '';
            $this->url = '';
            $this->title = '';
            $this->description = '';
        }
    }
}
