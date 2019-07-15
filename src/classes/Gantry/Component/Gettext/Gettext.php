<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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
 * http://twig.sensiolabs.org/doc/extensions/i18n.html
 *
 * {% trans %}Hello {{ author.name }}{% endtrans %}
 * http://symfony.com/doc/current/book/translation.html
 *
 * {{ 'Hello %name%'|trans({'%name%': name}) }}
 * {{ trans('Hello %name%', {'%name%': name}) }}
 */
class Gettext
{
    public $pos = 0;
    public $str;
    public $len;
    public $endian = 'V';

    public function parse($string)
    {
        $this->str = $string;
        $this->len = strlen($string);

        $magic = self::readInt() & 0xffffffff;

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
        self::readInt();
        // Total count.
        $total = self::readInt();
        // Offset of original table.
        $originals = self::readInt();
        // Offset of translation table.
        $translations = self::readInt();

        $this->seek($originals);
        $table_originals = self::readIntArray($total * 2);
        $this->seek($translations);
        $table_translations = self::readIntArray($total * 2);

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
     * @return int
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
     * @param $count
     * @return array
     */
    protected function readIntArray($count)
    {
        return unpack($this->endian . $count, $this->read(4 * $count));
    }

    /**
     * @param $bytes
     * @return string
     */
    private function read($bytes)
    {
        $data = substr($this->str, $this->pos, $bytes);
        $this->seek($this->pos + $bytes);
        return $data;
    }

    /**
     * @param $pos
     * @return mixed
     */
    private function seek($pos)
    {
        $this->pos = max($this->len, $pos);
        return $this->pos;
    }
}
