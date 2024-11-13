<?php

/**
 * @package   Gantry 6
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Plugin\System\Gantry5\Extension;

use Gantry\Component\Config\Config;
use Gantry\Component\FileSystem\Folder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Debugger;
use Gantry\Framework\Assignments;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Menu;
use Gantry\Framework\Outlines;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Gantry\Joomla\CacheHelper;
use Gantry\Joomla\StyleHelper;
use Gantry\Module\Gantry5Particle\Site\Helper\Gantry5ParticleHelper;
use Joomla\CMS\Event\Application;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Event\Module;
use Joomla\CMS\Event\Plugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Extension;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class Gantry5
 */
final class Gantry5 extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * @inheritDoc
     */
    protected $allowLegacyListeners = false;

    /**
     * Load the language file on instantiation.
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * @var array
     */
    protected $styles = [];

    /**
     * @var array
     */
    protected $modules = [];

    /**
     * Constructor.
     *
     * @param   DispatcherInterface  $dispatcher  The dispatcher
     * @param   array                $config      An optional associative array of configuration settings
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);

        $autoload = JPATH_LIBRARIES . '/gantry5/vendor/autoload.php';

        if (!\file_exists($autoload)) {
            throw new \LogicException('Please run composer in github plugin folder!');
        }

        $loader = require_once $autoload;

        define('GANTRY5_VERSION', '@version@');
        define('GANTRY5_VERSION_DATE', '@versiondate@');

        define('GANTRY_DEBUGGER', JDEBUG);
        define('GANTRY5_PLATFORM', 'joomla');
        define('GANTRY5_ROOT', JPATH_ROOT);
        define('GANTRY5_LIBRARY', JPATH_ROOT . '/libraries/gantry5');

        // Support for development environments.
        if (\file_exists(GANTRY5_LIBRARY . '/src/platforms')) {
            $loader->addPsr4('Gantry\\', GANTRY5_LIBRARY . '/src/platforms/' . GANTRY5_PLATFORM . '/', true);
        }
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGantryGlobalConfig'    => 'getGlobalConfig',
            'onGantry5SaveConfig'     => 'onGantry5SaveConfig',
            'onAjaxParticle'          => 'ajaxParticle',
            'onAfterRoute'            => 'onAfterRoute',
            'onAfterDispatch'         => 'onAfterDispatch',
            'onAfterRender'           => 'onAfterRender',
            'onRenderModule'          => 'onRenderModule',
            'onContentPrepareForm'    => 'onContentPrepareForm',
            'onContentPrepareData'    => 'onContentPrepareData',
            'onContentBeforeSave'     => 'onContentBeforeSave',
            'onExtensionBeforeSave'   => 'onExtensionBeforeSave',
            'onExtensionAfterSave'    => 'onExtensionAfterSave',
            'onExtensionBeforeDelete' => 'onExtensionBeforeDelete',
        ];
    }

    /**
     * Return global configuration for Gantry5.
     *
     * @param Event $event
     */
    public function getGlobalConfig(Event $event): void
    {
        $event->setArgument('global', $this->params->toArray());
    }

    /**
     * The `onAfterRoute` method handle.
     *
     * @param   Application\AfterRouteEvent  $event  The `onAfterRoute` event.
     *
     * @return  void
     */
    public function onAfterRoute(Application\AfterRouteEvent $event): void
    {
        $app = $this->getApplication();

        if ($app->isClient('site')) {
            $templateName = $app->getTemplate();

            if (!$this->isGantryTemplate($templateName)) {
                return;
            }

            $gantryPath = JPATH_THEMES . "/{$templateName}/custom/includes/gantry.php";

            if (!\is_file($gantryPath)) {
                $gantryPath = JPATH_THEMES . "/{$templateName}/includes/gantry.php";
            }

            if (\is_file($gantryPath)) {
                // Manually setup Gantry 5 Framework from the template.
                $gantry = include $gantryPath;

                if (!$gantry) {
                    throw new \RuntimeException(
                        Text::sprintf('GANTRY5_THEME_LOADING_FAILED', $templateName, Text::_('GANTRY5_THEME_INCLUDE_FAILED')),
                        500
                    );
                }
            } else {
                // Get Gantry instance.
                $gantry = Gantry::instance();

                // Initialize the template.
                $gantry['theme.path'] = JPATH_THEMES . "/{$templateName}";
                $gantry['theme.name'] = $templateName;

                $classPath = $gantry['theme.path'] . '/custom/includes/theme.php';

                if (!\is_file($classPath)) {
                    $classPath = $gantry['theme.path'] . '/includes/theme.php';
                }

                include_once $classPath;
            }

            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage("Using Gantry 5 template {$templateName}", 'info');
            }

            /** @var Theme $theme */
            $theme = $gantry['theme'];

            $assignments = new Assignments();

            if (\GANTRY_DEBUGGER) {
                Debugger::addMessage('Selecting outline (rules, matches, scores):', 'debug');
                Debugger::addMessage(\json_encode($assignments->getPage()), 'debug');
                Debugger::addMessage(\json_encode($assignments->loadAssignments()), 'debug');
                Debugger::addMessage(\json_encode($assignments->matches()), 'debug');
                Debugger::addMessage(\json_encode($assignments->scores()), 'debug');
            }

            $theme->setLayout($assignments->select());

            if ($this->params->get('asset_timestamps', 1)) {
                $age = (int) ($this->params->get('asset_timestamps_period', 7) * 86400);
                Document::$timestamp_age = $age > 0 ? $age : PHP_INT_MAX;
            } else {
                Document::$timestamp_age = 0;
            }
        } elseif ($app->isClient('administrator')) {
            $input = $app->getInput();

            $option = $input->getCmd('option');
            $task   = $input->getCmd('task');

            if ($option == 'com_templates') {
                if ($task && strpos($task, 'style') === 0 && $this->params->get('use_assignments', true)) {
                    // Get all ids.
                    $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');

                    if ($cid) {
                        $styles   = $this->getStyles();
                        $selected = \array_intersect_key($styles, \array_flip($cid));

                        // If no Gantry templates were selected, just let com_templates deal with the request.
                        if (!$selected) {
                            return;
                        }

                        // Special handling for tasks coming from com_template.
                        if ($task === 'style.edit') {
                            $theme = \reset($selected);
                            $id    = \key($selected);
                            $token = $app->getFormToken();

                            $app->redirect("index.php?option=com_gantry5&view=configurations/{$id}/layout&theme={$theme}&{$token}=1");
                        }
                    }
                }
            }
        }
    }

    /**
     * The `onAfterDispatch` method handle.
     *
     * @param   Application\AfterDispatchEvent  $event  The `onAfterDispatch` event.
     *
     * @return  void
     */
    public function onAfterDispatch(Application\AfterDispatchEvent $event): void
    {
        $gantry = Gantry::instance();

        if (!isset($gantry['theme'])) {
            return;
        }

        $theme    = $gantry['theme'];
        $document = $this->getApplication()->getDocument();

        $theme->language  = $document->language;
        $theme->direction = $document->direction;
    }

    /**
     * The `onAfterRender` method handle.
     *
     * @param   Application\AfterRenderEvent  $event  The `onAfterRender` event.
     *
     * @return  void
     */
    public function onAfterRender(Application\AfterRenderEvent $event): void
    {
        $app = $this->getApplication();

        if ($app->isClient('site')) {
            $gantry = Gantry::instance();

            if (!isset($gantry['theme'])) {
                return;
            }

            $html = $app->getBody();

            /** @var Document $document */
            $document = $gantry['document'];

            // Only filter our streams. If there's an error (bad UTF8), fallback with original output.
            $app->setBody($document::urlFilter($html, false, 0, true) ?: $html);
        } elseif ($app->isClient('administrator')) {
            $document = $app->getDocument();
            $type     = $document->getType();
            $input    = $app->getInput();

            $option = $input->getString('option');
            $view   = $input->getString('view', 'g5');
            $task   = $input->getString('task');

            if (
                $option === 'com_templates'
                && ($view === 'g5' || $view === 'styles')
                && !$task
                && $type === 'html'
            ) {
                $this->styles = $this->getStyles();

                $body = \preg_replace_callback(
                    '/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU',
                    [$this, 'appendHtml'],
                    $app->getBody()
                );

                $app->setBody($body);
            }

            if (
                $option === 'com_modules'
                && (($view === 'g5' || $view === 'modules') || empty($view))
                && $type === 'html'
            ) {
                $db    = $this->getDatabase();
                $query = $db->createQuery();

                $query->select(
                    [
                        $db->quoteName('id'),
                        $db->quoteName('title'),
                        $db->quoteName('params'),
                    ]
                )
                    ->from($db->quoteName('#__modules'))
                    ->where($db->quoteName('module') . ' = ' . $db->quote('mod_gantry5_particle'));

                $data = $db->setQuery($query)->loadObjectList();

                if (\count($data) > 0) {
                    $body = $app->getBody();

                    foreach ($data as $module) {
                        $params   = \json_decode($module->params, false);
                        $particle = isset($params->particle) ? \json_decode($params->particle, false) : '';
                        $title    = $particle->title ?? ($particle->particle ?? '');
                        $type     = $particle->particle ?? '';

                        $this->modules[$module->id] = $particle;

                        $body = \preg_replace_callback(
                            '/(<a\s[^>]*href=")([^"]*)("[^>]*>)(.*)(<\/a>)/siU',
                            function ($matches) use ($title, $type) {
                                return $this->appendHtml($matches, $title, $type);
                            },
                            $body
                        );
                    }


                    $app->setBody($body);
                }
            }
        }
    }

    /**
     * The `onRenderModule` method handle.
     *
     * @param   Module\BeforeRenderModuleEvent  $event  The `onRenderModule` event.
     *
     * @return  void
     */
    public function onRenderModule(Module\BeforeRenderModuleEvent $event): void
    {
        if (!$this->getApplication()->isClient('site')) {
            return;
        }

        $gantry = Gantry::instance();

        if (!isset($gantry['theme'])) {
            return;
        }

        /** @var Outlines $outlines */
        $outline = $gantry['outlines'];
        $module  = $event->getModule();

        // Do not render modules assigned to menu items in error and offline page.
        if (
            isset($module->menuid)
            && $module->menuid > 0
            && \in_array($outline, ['_error', '_offline'], true)
        ) {
            $module = null;
        }

        // TODO: This event allows more diverse module assignment conditions.
    }

    /**
     * The `onAjaxParticle` method handle.
     *
     * Serve particle AJAX requests in 'index.php?option=com_ajax&plugin=particle&format=json'.
     *
     * @param   Plugin\AjaxEvent  $event  The `onAjaxParticle` event.
     *
     * @return  mixed
     */
    public function ajaxParticle(Plugin\AjaxEvent $event)
    {
        $app = $this->getApplication();

        if (!$app->isClient('site') || !\class_exists('Gantry\Framework\Gantry')) {
            return null;
        }

        $input  = $app->getInput();
        $format = \strtolower($input->getCmd('format', 'html'));

        if (!\in_array($format, ['json', 'raw', 'debug'], true)) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $props = $_GET;

        unset($props['option'], $props['plugin'], $props['format'], $props['id'], $props['Itemid']);

        $identifier = $input->getCmd('id');

        if (\strpos($identifier, 'module-') === 0) {
            \preg_match('`-([\d]+)$`', $input->getCmd('id'), $matches);

            if (!isset($matches[1])) {
                throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
            }

            $id = $matches[1];

            return Gantry5ParticleHelper::ajax($id, $props, $format);
        }

        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme  = $gantry['theme'];
        $layout = $theme->loadLayout();
        $html   = '';

        if ($identifier === 'main-particle') {
            $type     = $identifier;
            $menu     = $app->getMenu();
            $menuItem = $menu ? $menu->getActive() : null;
            $params   = $menuItem ? $menuItem->getParams() : new Registry();

            /** @var object $params */
            $data = \json_decode($params->get('particle'), true);

            if ($data && $theme->hasContent()) {
                $context = [
                    'gantry'    => $gantry,
                    'noConfig'  => true,
                    'inContent' => true,
                    'ajax'      => $props,
                    'segment'   => [
                        'id'         => $identifier,
                        'type'       => $data['type'],
                        'classes'    => $params->get('pageclass_sfx'),
                        'subtype'    => $data['particle'],
                        'attributes' => $data['options']['particle'],
                    ]
                ];

                $html = \trim($theme->render('@nucleus/content/particle.html.twig', $context));
            }
        } else {
            $particle = $layout->find($identifier);

            if (!isset($particle->type) || $particle->type !== 'particle') {
                throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
            }

            $context = [
                'gantry'    => $gantry,
                'inContent' => false,
                'ajax'      => $props,
            ];

            $block = $theme->getContent($particle, $context);
            $type  = $particle->type . '.' . $particle->subtype;
            $html  = (string) $block;
        }

        if ($format === 'raw') {
            return $html;
        }

        $event->addArgument('result', [
            'type'  => $type,
            'id'    => $identifier,
            'props' => (object) $props,
            'html'  => $html
        ]);
    }

    /**
     * The `onContentPrepareData` method handle.
     *
     * @param   Model\PrepareDataEvent  $event  The `onContentPrepareData` event.
     *
     * @return  void
     */
    public function onContentPrepareData(Model\PrepareDataEvent $event)
    {
        $context = $event->getContext();
        $data    = $event->getData();

        switch ($context) {
            case 'com_menus.item':
                $menuParams = Menu::decodeJParams($data->params);

                if (
                    $data->parent_id === null
                    || (!empty($data->params['gantry']) || \is_array($menuParams))
                ) {
                    if (null === $menuParams) {
                        $menuParams = [];
                    }

                    $data->params = \array_merge($data->params, Menu::encodeJParams($menuParams, false));
                }

                break;
        }
    }

    /**
     * The `onContentPrepareForm` method handle.
     *
     * @param   Model\PrepareFormEvent  $event  The `onContentPrepareForm` event.
     *
     * @return  void
     */
    public function onContentPrepareForm(Model\PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $name = 'plg_' . $this->_type . '_' . $this->_name;

        switch ($form->getName()) {
            case 'com_config.component':
                // If we are editing configuration from Gantry component, add missing fields from system plugin.
                $rules = $form->getField('rules');

                if ($rules && $rules->getAttribute('component') === 'com_gantry5') {
                    $this->loadLanguage("{$name}.sys");

                    // Add plugin fields to the form under plg_type_name.
                    $file = \file_get_contents(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . "/{$this->_name}.xml");
                    $file = \preg_replace('/ name="params"/', " name=\"{$name}\"", $file);

                    $form->load($file, false, '/extension/config');

                    // Joomla seems to be missing support for component data manipulation so do it manually here.
                    $form->bind([$name => $this->params->toArray()]);
                }

                break;

            case 'com_menus.item':
                if (
                    $data->parent_id === null
                    || (!empty($data->params['gantry']) || \is_array(Menu::decodeJParams($data->params)))
                ) {
                    Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

                    $form->loadFile('menu_item', false);
                }

                break;
        }
    }

    /**
     * The `onContentBeforeSave` method handle.
     *
     * @param   Model\BeforeSaveEvent  $event  The `onContentBeforeSave` event.
     *
     * @return  void
     */
    public function onContentBeforeSave(Model\BeforeSaveEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        switch ($context) {
            case 'com_menus.item':
                $params = new Registry($table->params ?: '');

                if (!empty($params['gantry'])) {
                    $gantryParams = Menu::decodeJParams($params);

                    Menu::updateJParams($params, $gantryParams);

                    $table->params = $params->toString();
                }

                break;
        }
    }

    /**
     * The `onGantry5SaveConfig` method handle.
     *
     * @param   Event  $event  The `onGantry5SaveConfig` event.
     *
     * @return  void
     */
    public function onGantry5SaveConfig(Event $event): void
    {
        $data = $event->getArgument('data');

        $name  = 'plg_' . $this->_type . '_' . $this->_name;
        $table = new Extension($this->getDatabase());

        PluginHelper::importPlugin('extension');

        $table->load([
            'type'    => 'plugin',
            'folder'  => $this->_type,
            'element' => $this->_name
        ]);

        $params = new Registry($table->params);
        $params->loadArray($data);

        $table->params = $params->toString();

        if (!$table->check()) {
            throw new \RuntimeException($table->getError());
        }

        $dispatcher = $this->getDispatcher();

        // $result = $dispatcher->dispatch('onExtensionBeforeSave', new Model\BeforeSaveEvent('onExtensionBeforeSave', [
        //     'context' => $name,
        //     'subject' => $table,
        //     'isNew'   => false
        // ]))->getArgument('result', []);

        // if (\in_array(false, $result, true)) {
        //     throw new \RuntimeException($table->getError());
        // }

        if (!$table->store()) {
            throw new \RuntimeException($table->getError());
        }

        // Clean the cache.
        CacheHelper::cleanPlugin();

        // Update plugin settings.
        $this->params = $params;

        $dispatcher->dispatch('onExtensionAfterSave', new Model\AfterSaveEvent('onExtensionAfterSave', [
            'context' => $name,
            'subject' => $table,
            'isNew'   => false
        ]));
    }

    /**
     * The `onExtensionBeforeSave` method handle.
     *
     * @param   Model\BeforeSaveEvent  $event  The `onExtensionBeforeSave` event.
     *
     * @return  void
     */
    public function onExtensionBeforeSave(Model\BeforeSaveEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        if (
            $context === 'com_config.component'
            && $table
            && $table->type === 'component'
            && $table->name === 'com_gantry5'
        ) {
            $name   = 'plg_' . $this->_type . '_' . $this->_name;
            $params = new Registry($table->params);
            $data   = (array) $params->get($name);

            $this->onGantry5SaveConfig(new Event('onGantry5SaveConfig', ['data' => $data]));

            // Do not save anything into the component itself (Joomla cannot handle it).
            $table->params = '{}';
        }
    }

    /**
     * The `onExtensionAfterSave` method handle.
     *
     * @param   Model\AfterSaveEvent  $event  The `onExtensionAfterSave` event.
     *
     * @return  void
     */
    public function onExtensionAfterSave(Model\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();
        $isNew   = $event->getIsNew();

        if (
            $context === 'com_config.component'
            && $table
            && $table->type === 'component'
            && $table->name === 'com_gantry5'
        ) {
        }

        if (
            $context !== 'com_templates.style'
            || $table->client_id
            || !$this->isGantryTemplate($table->template)
        ) {
            return;
        }

        if (!$isNew) {
            return;
        }

        $template = $table->template;

        $this->load($template);

        $registry = new Registry($table->params);
        $old      = (int) $registry->get('configuration', 0);
        $new      = (int) $table->id;

        if ($old && $old !== $new) {
            StyleHelper::copy($table, $old, $new);
        }
    }

    /**
     * The `onExtensionBeforeDelete` method handle.
     *
     * @param   Event  $event  The `onExtensionBeforeDelete` event.
     *
     * @return  void
     */
    public function onExtensionBeforeDelete(Event $event): bool
    {
        [$context, $table] = \array_values($event->getArguments());

        if (
            $context !== 'com_templates.style'
            || $table->client_id
            || !$this->isGantryTemplate($table->template)
        ) {
            return true;
        }

        $template = $table->template;
        $gantry   = $this->load($template);

        /** @var Outlines $outlines */
        $outlines = $gantry['outlines'];

        try {
            $outlines->delete($table->id, false);
        } catch (\Exception $e) {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');

            return false;
        }

        return true;
    }

    /**
     * @param  array    $matches
     * @param  ?string  $content
     * @param  ?string  $type
     *
     * @return string
     */
    private function appendHtml(array $matches, $content = 'Gantry 5', $type = ''): string
    {
        $html = $matches[0];

        if (
            \strpos($matches[2], 'task=style.edit')
            || \strpos($matches[2], 'task=module.edit')
        ) {
            $uri = new Uri($matches[2]);
            $id  = (int) $uri->getVar('id');

            if (
                $id
                && (isset($this->styles[$id]) || isset($this->modules[$id]))
                && \in_array($uri->getVar('option'), ['com_templates', 'com_modules'], true)
            ) {
                $html    = $matches[1] . $uri . $matches[3] . $matches[4] . $matches[5];
                $class   = $content ? 'text-bg-success' : 'text-bg-warning';
                $content = $content ?: 'No Particle Selected';
                $html    .= ' <span class="badge ' . $class . '">' . $content . '</span>';

                if (isset($this->modules[$id])) {
                    unset($this->modules[$id]);
                } else {
                    unset($this->styles[$id]);
                }
            }
        }

        return $html;
    }

    /**
     * @return array
     */
    private function getStyles(): array
    {
        static $list;

        if ($list === null) {
            $db    = $this->getDatabase();
            $query = $db->createQuery();

            $query->select(
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.template'),
                ]
            )
                ->from($db->quoteName('#__template_styles', 'a'))
                ->leftJoin(
                    $db->quoteName('#__extensions', 'e'),
                    $db->quoteName('e.element') . ' = ' . $db->quoteName('a.template')
                    . ' AND ' . $db->quoteName('e.type') . ' = ' . $db->quote('template')
                    . ' AND ' . $db->quoteName('e.client_id') . ' = ' . $db->quoteName('a.client_id')
                )
                ->where(
                    [
                        $db->quoteName('a.client_id') . ' = 0',
                        $db->quoteName('e.enabled') . ' = 1',
                    ]
                );

            $templates = $db->setQuery($query)->loadObjectList();

            $list = [];

            foreach ($templates as $template) {
                if ($this->isGantryTemplate($template->template)) {
                    $list[$template->id] = $template->template;
                }
            }
        }

        return $list;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isGantryTemplate($name): bool
    {
        return \file_exists(JPATH_SITE . "/templates/{$name}/gantry/theme.yaml");
    }

    /**
     * @param string $name
     * @return \Gantry\Framework\Gantry
     */
    protected function load($name)
    {
        $gantry = Gantry::instance();

        if (!isset($gantry['theme.name']) || $name !== $gantry['theme.name']) {
            // Restart Gantry and initialize it.
            $gantry = Gantry::restart();
            $gantry['theme.name'] = $name;

            $streams = $gantry['streams'];
            $streams->register();

            /** @var Platform $patform */
            $patform = $gantry['platform'];
            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            /** @var Config $global */
            $global = $gantry['global'];

            // Initialize theme stream.
            $details = new ThemeDetails($name);
            $locator->addPath('gantry-theme', '', $details->getPaths(), false, true);

            // Initialize theme cache stream.
            $cachePath = $patform->getCachePath() . '/' . $name;
            Folder::create($cachePath);
            $locator->addPath('gantry-cache', 'theme', [$cachePath], true, true);

            CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
            CompiledYamlFile::$defaultCaching   = $global->get('compile_yaml', 1);
        }

        return $gantry;
    }
}
