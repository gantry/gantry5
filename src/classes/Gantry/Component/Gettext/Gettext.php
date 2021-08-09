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

namespace Gantry\Component\Gettext;

/**
 * Class Gettext
 * @package Gantry\Component\Gettext
 *
 * Examples on translating gettext in twig:
 *
 * {% trans string_var %}
 *
 * {% trans %}Hello {{ author.name }}{% endtrans %}
 * http://symfony.com/doc/current/book/translation.html
 *
 * {{ 'Hello %name%'|trans({'%name%': name}) }}
 * {{ trans('Hello %name%', {'%name%': name}) }}
 */
class Gettext
{
    /** @var int */
    public $pos = 0;
    /** @var string */
    public $str;
    /** @var int */
    public $len;
    /** @var string */
    public $endian = 'V';

    /**
     * @param string $string
     * @return array
     * @throws \Exception
     */
    public function parse($string)
    {
        $this->str = $string;
        $this->len = strlen($string);

        $magic = $this->readInt() & 0xffffffff;

        if ($magic === 0x950412de) {
            // Low endian.
            $this->endian = 'V';
        } elseif ($magic === 0xde120495) {
            // Big endian.
            $this->endian = 'N';
        } else {
            throw new \Exception('Not a Gettext file (.mo)');
        }

        // Skip revision number.
        $this->readInt();
        // Total count.
        $total = $this->readInt();
        // Offset of original table.
        $originals = $this->readInt();
        // Offset of translation table.
        $translations = $this->readInt();

        $this->seek($originals);
        $table_originals = $this->readIntArray($total * 2);
        $this->seek($translations);
        $table_translations = $this->readIntArray($total * 2);

        $items = [];
        for ($i = 0; $i < $total; $i++) {
            $this->seek($table_originals[$i * 2 + 2]);
            $original = $this->read($table_originals[$i * 2 + 1]);

            if ($original) {
                $this->seek($table_translations[$i * 2 + 2]);
                $items[$original] = $this->read($table_translations[$i * 2 + 1]);
            }
        }

        return $items;
    }

    /**
     * @return int|false
     */
    protected function readInt()
    {
        $read = $this->read(4);
        if ($read === false) {
            return false;
        }

        $read = unpack($this->endian, $read);

        return array_shift($read);
    }

    /**
     * @param int $count
     * @return array
     */
    protected function readIntArray($count)
    {
        return unpack($this->endian . $count, $this->read(4 * $count));
    }

    /**
     * @param int $bytes
     * @return string|false
     */
    private function read($bytes)
    {
        $data = substr($this->str, $this->pos, $bytes);
        $this->seek($this->pos + $bytes);

        return strlen($data) === $bytes ? $data : false;
    }

    /**
     * @param int $pos
     * @return int
     */
    private function seek($pos)
    {
        $this->pos = max($this->len, $pos);

        return $this->pos;
    }
}
