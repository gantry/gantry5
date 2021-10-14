<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Translator;

use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Translator
 * @package Gantry\Component\Translator
 */
class Translator implements TranslatorInterface
{
    /** @var string */
    protected $default = 'en';
    /** @var string */
    protected $active = 'en';
    /** @var array */
    protected $sections = [];
    /** @var array */
    protected $translations = [];
    /** @var array */
    protected $untranslated = [];

    /**
     * @param string $string
     * @return string
     */
    public function translate($string)
    {
        if (preg_match('|^GANTRY5(_[A-Z0-9]+){2,}$|', $string)) {
            list(, $section, $code) = explode('_', $string, 3);

            $string = ($this->find($this->active, $section, $string) ?: $this->find($this->default, $section, $string)) ?: $string;
        }

        if (func_num_args() === 1) {
            return $string;
        }

        $args = func_get_args();
        $args[0] = $string;

        return sprintf(...$args);
    }

    /**
     * Set new active language if given and return previous active language.
     *
     * @param  string  $language  Language code. If not given, current language is kept.
     * @return string  Previously active language.
     */
    public function active($language = null)
    {
        $previous = $this->active;

        if ($language) {
            $this->active = $language;
        }

        return $previous;
    }

    /**
     * @return array
     */
    public function untranslated()
    {
        return $this->untranslated;
    }

    /**
     * @param string $language
     * @param string $section
     * @param string $string
     * @return string|null
     */
    protected function find($language, $section, $string)
    {
        if (!isset($this->sections[$language][$section])) {
            $translations = $this->load($language, $section);

            if (isset($this->translations[$language])) {
                $this->translations[$language] += $translations;
            } else {
                $this->translations[$language] = $translations;
            }

            $this->sections[$language][$section] = !empty($translations);
        }

        if (!isset($this->translations[$language][$string])) {
            $this->untranslated[$language][$section][$string] = null;

            return null;
        }

        return $this->translations[$language][$string];
    }

    /**
     * @param string $language
     * @param string $section
     * @return array
     */
    protected function load($language, $section)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $section = strtolower($section);
        if ($section === 'engine') {
            // TODO: add support for other engines than nucleus.
            $section = 'nucleus';
        }

        $filename = 'gantry-admin://translations/' . $language . '/' . $section . '.yaml';
        $file = CompiledYamlFile::instance($filename);

        if (!$file->exists() && ($pos = strpos($language, '-'))) {
            $filename = 'gantry-admin://translations/' . substr($language, 0, $pos) . '/' . $section . '.yaml';
            $file = CompiledYamlFile::instance($filename);
        }

        /** @var string $cachePath */
        $cachePath = $locator->findResource('gantry-cache://translations', true, true);
        $translations = (array) $file->setCachePath($cachePath)->content();
        $file->free();

        return $translations;
    }
}
