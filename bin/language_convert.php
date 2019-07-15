#!/usr/bin/env php
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

/**
 * The main .po to .mo function
 */
function phpmo_convert($input, $output = false)
{
    if (!$output) {
        $output = str_replace('.po', '.mo', $input);
    }

    $hash = phpmo_parse_po_file($input);
    if ($hash === false) {
        return false;
    }

    phpmo_write_mo_file($hash, $output);
    return true;
}

function phpmo_clean_helper($x)
{
    if (is_array($x)) {
        foreach ($x as $k => $v) {
            $x[$k] = phpmo_clean_helper($v);
        }
    } else {
        if ($x[0] == '"') {
            $x = substr($x, 1, -1);
        }
        $x = str_replace("\"\n\"", '', $x);
        $x = str_replace('$', '\\$', $x);
    }
    return $x;
}

/* Parse gettext .po files. */
/* @link http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files */
function phpmo_parse_po_file($in)
{
    // read .po file
    $fh = fopen($in, 'r');
    if ($fh === false) {
        // Could not open file resource
        return false;
    }

    // results array
    $hash = [];
    // temporary array
    $temp = [];
    // state
    $state = null;
    $fuzzy = false;

    // iterate over lines
    while(($line = fgets($fh, 65536)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        list ($key, $data) = preg_split('/\s/', $line, 2);

        switch ($key) {
            case '#,': // flag...
                $fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));
            case '#': // translator-comments
            case '#.': // extracted-comments
            case '#:': // reference...
            case '#|': // msgid previous-untranslated-string
                // start a new entry
                if (sizeof($temp) && array_key_exists('msgid', $temp) && array_key_exists('msgstr', $temp)) {
                    if (!$fuzzy) {
                        $hash[] = $temp;
                    }
                    $temp = [];
                    $state = null;
                    $fuzzy = false;
                }
                break;
            case 'msgctxt' :
                // context
            case 'msgid' :
                // untranslated-string
            case 'msgid_plural' :
                // untranslated-string-plural
                $state = $key;
                $temp[$state] = $data;
                break;
            case 'msgstr' :
                // translated-string
                $state = 'msgstr';
                $temp[$state][] = $data;
                break;
            default :
                if (strpos($key, 'msgstr[') !== false) {
                    // translated-string-case-n
                    $state = 'msgstr';
                    $temp[$state][] = $data;
                } else {
                    // continued lines
                    switch ($state) {
                        case 'msgctxt' :
                        case 'msgid' :
                        case 'msgid_plural' :
                            $temp[$state] .= "\n" . $line;
                            break;
                        case 'msgstr' :
                            $temp[$state][sizeof($temp[$state]) - 1] .= "\n" . $line;
                            break;
                        default :
                            // parse error
                            fclose($fh);
                            return false;
                    }
                }
                break;
        }
    }
    fclose($fh);

    // add final entry
    if ($state == 'msgstr') {
        $hash[] = $temp;
    }

    // Cleanup data, merge multiline entries, reindex hash for ksort
    $temp = $hash;
    $hash = [];
    foreach ($temp as $entry) {
        foreach ($entry as & $v) {
            $v = phpmo_clean_helper($v);
            if ($v === false) {
                // parse error
                return false;
            }
        }
        $hash[$entry['msgid']] = $entry;
    }

    return $hash;
}

/* Write a GNU gettext style machine object. */
/* @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files */
function phpmo_write_mo_file($hash, $out)
{
    // sort by msgid
    ksort($hash, SORT_STRING);
    // our mo file data
    $mo = '';
    // header data
    $offsets = [];
    $ids = '';
    $strings = '';

    foreach ($hash as $entry) {
        $id = $entry['msgid'];
        if (isset($entry['msgid_plural']))
            $id .= "\x00" . $entry['msgid_plural'];
        // context is merged into id, separated by EOT (\x04)
        if (array_key_exists('msgctxt', $entry))
            $id = $entry['msgctxt'] . "\x04" . $id;
        // plural msgstrs are NUL-separated
        $str = implode("\x00", $entry['msgstr']);
        // keep track of offsets
        $offsets[] = [strlen($ids), strlen($id), strlen($strings), strlen($str)];
        // plural msgids are not stored (?)
        $ids .= $id . "\x00";
        $strings .= $str . "\x00";
    }

    // keys start after the header (7 words) + index tables ($#hash * 4 words)
    $key_start = 7 * 4 + sizeof($hash) * 4 * 4;
    // values start right after the keys
    $value_start = $key_start + strlen($ids);
    // first all key offsets, then all value offsets
    $key_offsets = [];
    $value_offsets = [];
    // calculate
    foreach ($offsets as $v) {
        list ($o1, $l1, $o2, $l2) = $v;
        $key_offsets[] = $l1;
        $key_offsets[] = $o1 + $key_start;
        $value_offsets[] = $l2;
        $value_offsets[] = $o2 + $value_start;
    }
    $offsets = array_merge($key_offsets, $value_offsets);

    // write header
    $mo .= pack('Iiiiiii', 0x950412de, // magic number
        0, // version
        sizeof($hash), // number of entries in the catalog
        7 * 4, // key index offset
        7 * 4 + sizeof($hash) * 8, // value index offset,
        0, // hashtable size (unused, thus 0)
        $key_start // hashtable offset
    );
    // offsets
    foreach ($offsets as $offset) {
        $mo .= pack('i', $offset);
    }

    // ids
    $mo .= $ids;
    // strings
    $mo .= $strings;

    file_put_contents($out, $mo);
}

$parameters = array(
  'i:' => 'input:',
  'o:' => 'output:',
  'c' => 'compile',
);
$options = getopt(implode('', array_keys($parameters)), $parameters);

$input_file = $options['input'];
$output_file = $options['output'];
$compile = isset($options['compile']);

if (!file_exists($input_file) || !is_file($input_file)) {
    echo sprintf("No file %s exists\n", $input_file);
    die;
}
if (file_exists($output_file) && !is_writable($output_file)) {
    echo sprintf("Unable to write to file %s\n", $output_file);
    die;
}

$ini_values = parse_ini_file($input_file);
$pot_output = ['msgid ""
msgstr ""
"Project-Id-Version: Gantry 5\n"
"Report-Msgid-Bugs-To: \n"
"POT-Creation-Date: ' . date('Y-m-d H:i:sO') . '\n"
"PO-Revision-Date: ' . date('Y-m-d H:i:sO') . '\n"
"Last-Translator: Gantry 5 Team\n"
"Language-Team: Gantry 5 Team\n"
"Language: English\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-KeywordsList: __;_e\n"
"X-Poedit-Basepath: .\n"
"X-Poedit-SearchPath-0: ..\n"'];
foreach($ini_values as $msgid => $msgstr) {
    $pot_output[] = sprintf('msgid "%s"', $msgid) . "\n" . sprintf('msgstr "%s"', $msgstr);
}

file_put_contents($output_file, implode("\n\n", $pot_output));
if ($compile) {
    phpmo_convert($output_file, false);
}

