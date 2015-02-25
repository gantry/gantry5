<?php
namespace Gantry\Component\Stylesheet;

interface CssCompilerInterface
{
    /**
     * Get default lookup paths.
     *
     * @return array
     */
    public function getDefaultPaths();

    /**
     * Get default files to compile.
     *
     * @return array
     */
    public function getDefaultFiles();

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope);

    /**
     * @param array $paths  List of lookup paths.
     * @return $this
     */
    public function setPaths(array $paths = null);

    /**
     * @param array $files  List of files to compile.
     * @return $this
     */
    public function setFiles(array $files = null);

    /**
     * @param array $variables  Array of variables with their values.
     * @return $this
     */
    public function setVariables(array $variables);

    /**
     * @return array
     */
    public function getVariables();

    /**
     * @param string $in    Filename without path or extension.
     * @param string $out   Full path to the file to be written.
     * @return bool         True if the output file was saved.
     */
    public function compileFile($in, $out = null);

    /**
     * Compile all predefined files.
     * @return $this
     */
    public function compileFiles();

    /**
     * Remove cached files.
     */
    public function resetCache();
}
