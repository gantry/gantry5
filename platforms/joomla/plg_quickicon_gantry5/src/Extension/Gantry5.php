<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Plugin\Quickicon\Gantry5\Extension;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Gantry update notification plugin
 */
class Gantry5 extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     */
    protected $autoloadLanguage = true;

    /**
     * The document.
     *
     * @var Document
     */
    private $document;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetIcons' => 'getCoreUpdateNotification',
        ];
    }

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher   The object to observe
     * @param   Document             $document     The document
     * @param   array                $config       An optional associative array of configuration settings.
     *                                             Recognized key values include 'name', 'group', 'params', 'language'
     *                                             (this list is not meant to be comprehensive).
     */
    public function __construct($dispatcher, Document $document, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        $this->document = $document;
    }

    /**
     * This method is called when the Quick Icons module is constructing its set
     * of icons. You can return an array which defines a single icon and it will
     * be rendered right after the stock Quick Icons.
     *
     * @param   QuickIconsEvent  $event  The event object
     *
     * @return  void
     */
    public function getCoreUpdateNotification(QuickIconsEvent $event): void
    {
        $context = $event->getContext();
        $user    = $this->getApplication()->getIdentity();

        if (
            $context !== $this->params->get('context', 'update_quickicon')
            || !$user->authorise('core.manage', 'com_gantry5')
        ) {
            return;
        }

        Text::script('PLG_QUICKICON_GANTRY5_ERROR');
        Text::script('PLG_QUICKICON_GANTRY5_UPDATEFOUND');
        Text::script('PLG_QUICKICON_GANTRY5_UPTODATE');
        Text::script('MESSAGE');
        Text::script('ERROR');
        Text::script('INFO');
        Text::script('WARNING');

        $this->document->addScriptOptions(
            'js-gantry5-update',
            [
                'url'     => Uri::base() . 'index.php?option=com_gantry5',
                'ajaxUrl' => Uri::base() . 'index.php?option=com_gantry5&task=update.ajax&'
                    . $this->getApplication()->getFormToken() . '=1',
                'version' => GANTRY5_VERSION,
            ]
        );

        $this->document->getWebAssetManager()
            ->registerAndUseScript('plg_quickicon_gantry5', 'plg_quickicon_gantry5/updatecheck.js', [], ['defer' => true], ['core']);

        // Add the icon to the result array
        $result = $event->getArgument('result', []);

        $result[] = [
            [
                'link'   => 'index.php?option=com_gantry5',
                'image'  => 'fa-solid fa-paper-plane',
                'icon'   => '',
                'text'   => Text::_('PLG_QUICKICON_GANTRY5_CHECKING'),
                'id'     => 'plg_quickicon_gantry5',
                'group'  => 'MOD_QUICKICON_EXTENSIONS'
            ]
        ];

        $event->setArgument('result', $result);
    }
}
