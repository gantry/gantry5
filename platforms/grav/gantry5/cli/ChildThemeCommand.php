<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Grav\Plugin\Console;

use Grav\Common\Filesystem\Folder;
use Grav\Common\Inflector;
use Grav\Common\Theme;
use Grav\Common\Themes;
use Grav\Console\ConsoleCommand;
use Grav\Common\Grav;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class ChildThemeCommand
 * @package Grav\Plugin\Gantry5
 */
class ChildThemeCommand extends ConsoleCommand
{
    /**
     * @var array
     */
    protected $options = [];

    protected function configure()
    {
        $this
            ->setName('child-theme')
            ->setAliases(['child-theme', 'childtheme'])
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_REQUIRED,
                'Parent theme name'
            )
            ->addOption(
                'child',
                'c',
                InputOption::VALUE_REQUIRED,
                'Child theme name'
            )
            ->addOption(
                'clone',
                'l',
                InputOption::VALUE_NONE,
                'Clone outlines and configuration to the child theme'
            )
            ->addOption(
                'no-clone',
                'L',
                InputOption::VALUE_NONE,
                'Do not clone outlines and configuration to the child theme'
            )
            ->setDescription('Creates a new child theme')
            ->setHelp('The <info>child-theme</info> creates a new child theme from an existing Gantry theme')
        ;
    }

    protected function serve()
    {
        $this->options = [
            'parent' => $this->input->getOption('parent'),
            'child'   => $this->input->getOption('child'),
            'clone'   => $this->input->getOption('clone'),
            'no-clone'   => $this->input->getOption('no-clone'),
        ];

        $this->validateOptions();

        $helper = $this->getHelper('question');

        $this->output->writeln('<green>Creating new child theme</green>');
        $this->output->writeln('');

        if (!$this->options['parent']) {
            // Get username and validate
            $question = new Question('Enter <yellow>parent theme</yellow> name: ');
            $question->setValidator(function ($value) {
                return $this->validate('parent', $value);
            });

            $parent = $helper->ask($this->input, $this->output, $question);
        } else {
            $parent = $this->options['parent'];
        }

        if (!$this->options['child']) {
            // Get username and validate
            $question = new Question('Enter <yellow>child theme</yellow> name: ');
            $question->setValidator(function ($value) {
                return $this->validate('child', $value);
            });

            $child = $helper->ask($this->input, $this->output, $question);
        } else {
            $child = $this->options['child'];
        }

        if (!$this->options['clone'] && !$this->options['no-clone']) {
            // Get username and validate
            $question = new ConfirmationQuestion('Clone outlines and configuration to the child theme [Y/n]: ', 'y');

            $clone = $helper->ask($this->input, $this->output, $question);
        } else {
            $clone = (bool)$this->options['clone'];
        }

        $grav = Grav::instance();
        $grav['config']->set('system.pages.theme', $parent);

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];

        /** @var Inflector $inflector */
        $inflector = $grav['inflector'];

        /** @var Themes $themes */
        $themes = $grav['themes'];
        $themes->init();

        $folder = $locator->findResource('themes://' . $child, true, true);
        $parentClass = get_class($this->loadTheme($parent));
        if (strpos($parentClass, 'Grav\\Theme\\') === 0) {
            $parentClass = substr($parentClass, 11);
        } else {
            $parentClass = '\\' . $parentClass;
        }
        $childClass = $inflector->camelize($child);

        Folder::create($folder);
        $file = File::instance("{$folder}/{$child}.yaml");
        $file->save(<<<PHP
streams:
 schemes:
   theme:
     type: ReadOnlyStream
     prefixes:
       '':
         - themes://{$child}
         - themes://{$parent}
PHP
        );

        $file = File::instance($folder . '/theme.php');
        $file->save(<<<PHP
<?php
namespace Grav\Theme;

class {$childClass} extends {$parentClass}
{
}
PHP
);

        $oldFile = File::instance($locator->findResource("themes://{$parent}/blueprints.yaml"));
        $content = $oldFile->content();
        $content = preg_replace('|name: (.*)|ui', 'name: \\1 Child', $content);
        $file = File::instance($folder . '/blueprints.yaml');
        $file->save($content);

        $oldFile = File::instance($locator->findResource("themes://{$parent}/gantry/theme.yaml"));
        $content = $oldFile->content();
        $content = preg_replace('|( +)name: (.*)|ui', '\\1name: \\2 Child', $content);
        $file = File::instance($folder . '/gantry/theme.yaml');
        $file->save($content);

        // Clone configuration if requested.
        if ($clone) {
            $oldConfig = $locator->findResource('user://data/gantry5/themes/' . $parent);
            if ($oldConfig) {
                $newConfig = $locator->findResource('user://data/gantry5/themes/' . $child, true, true);
                Folder::copy($oldConfig, $newConfig);
            }
        }

        $this->output->writeln('');
        $this->output->writeln('<green>Success!</green> Child theme <cyan>' . $child . '</cyan> created.');
    }

    protected function validateOptions()
    {
        foreach (array_filter($this->options) as $type => $value) {
            $this->validate($type, $value);
        }
    }

    /**
     * @param string $type
     * @param string $value
     *
     * @return string
     */
    protected function validate($type, $value)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];

        switch ($type) {
            case 'parent':
                if ($value === null || trim($value) === '') {
                    throw new \RuntimeException('Theme name cannot be empty');
                }

                $folder = $locator->findResource('themes://' . $value);

                if (!$folder) {
                    throw new \RuntimeException('Theme does not exist');
                }

                break;
            case 'child':
                if ($value === null || trim($value) === '') {
                    throw new \RuntimeException('Theme name cannot be empty');
                }

                if (preg_match('|[^a-z0-9_-]|i', $value)) {
                    throw new \RuntimeException('Theme name can contain only alphanumeric characters');
                }

                $folder = $locator->findResource('themes://' . $value);

                if ($folder) {
                    throw new \RuntimeException('Theme already exists');
                }

                break;
        }

        return $value;
    }

    protected function loadTheme($name)
    {
        // NOTE: ALL THE LOCAL VARIABLES ARE USED INSIDE INCLUDED FILE, DO NOT REMOVE THEM!
        $grav = Grav::instance();
        $config = $grav['config'];

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];
        $file = $locator("themes://{$name}/theme.php") ?: $locator("themes://{$name}/{$name}.php");

        /** @var Inflector $inflector */
        $inflector = $grav['inflector'];

        if ($file) {
            // Local variables available in the file: $grav, $config, $name, $file
            $class = include_once $file;
            if ($class === true) {
                $class = $grav['theme'];
            }

            if (!is_object($class)) {
                $themeClassFormat = [
                    'Grav\\Theme\\' . $name,
                    'Grav\\Theme\\' . $inflector->camelize($name)
                ];

                foreach ($themeClassFormat as $themeClass) {
                    if (class_exists($themeClass)) {
                        $themeClassName = $themeClass;
                        $class = new $themeClassName($grav, $config, $name);
                        break;
                    }
                }
            }
        }

        if (empty($class)) {
            $class = new Theme($grav, $config, $name);
        }

        return $class;
    }
}