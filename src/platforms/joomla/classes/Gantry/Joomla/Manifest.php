<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla;

/**
 * Joomla manifest file modifier.
 */
class Manifest
{
    protected $theme;
    protected $path;
    protected $xml;

    /**
     * @param string $theme
     * @param \SimpleXMLElement $manifest
     * @throws \RuntimeException
     */
    public function __construct($theme, \SimpleXMLElement $manifest = null)
    {
        $this->theme = $theme;
        $this->path = JPATH_SITE . "/templates/{$theme}/templateDetails.xml";

        if (!is_file($this->path)) {
            throw new \RuntimeException(sprintf('Template %s does not exist.', $theme));
        }
        $this->xml = $manifest ?: simplexml_load_file($this->path);
    }

    /**
     * @param string $variable
     * @return string
     */
    public function get($variable)
    {
        return (string) $this->xml->{$variable};
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getXml()
    {
        return $this->xml;
    }

    public function getScriptFile()
    {
        return (string) $this->xml->scriptfile;
    }

    public function setPositions(array $positions)
    {
        sort($positions);

        // Get the positions.
        $target = current($this->xml->xpath('//positions'));

        $xml = "<positions>\n        <position>" . implode("</position>\n        <position>", $positions) . "</position>\n    </positions>";
        $insert = new \SimpleXMLElement($xml);

        // Replace all positions.
        $targetDom = dom_import_simplexml($target);
        $insertDom = $targetDom->ownerDocument->importNode(dom_import_simplexml($insert), true);
        $targetDom->parentNode->replaceChild($insertDom, $targetDom);
    }


    public function save()
    {
        // Do not save manifest if template has been symbolically linked.
        if (is_link(dirname($this->path))) {
            return;
        }

        if (!$this->xml->asXML($this->path)) {
            throw new \RuntimeException(sprintf('Saving manifest for %s template failed', $this->theme));
        }
    }
}
