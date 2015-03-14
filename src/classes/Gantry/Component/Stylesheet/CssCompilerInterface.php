<?php
namespace Gantry\Component\Stylesheet;

interface CssCompilerInterface
{
    /**
     * @param string $target
     * @return $this
     */
    public function setTarget($target = null);

    /**
     * @param string $configuration
     * @return $this
     */
    public function setConfiguration($configuration = null);

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths = null);

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles(array $files = null);

    /**
     * @param string $name
     * @return string
     */
    public function getCssUrl($name);

    public function getVariables();
    public function setVariables(array $variables);
    public function compileFile($in, $out = null);

    /**
     * @return $this
     */
    public function compileAll();

    public function resetCache();
}
