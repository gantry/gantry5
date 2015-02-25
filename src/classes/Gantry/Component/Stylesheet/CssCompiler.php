<?php
namespace Gantry\Component\Stylesheet;

abstract class CssCompiler implements CssCompilerInterface
{
    protected $type;

    protected $name;

    protected $debug = false;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array
     */
    protected $files;

    /**
     * @var string
     */
    protected $scope = '';

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        if ($scope) {
            $this->scope = $scope != 'default' ? "_{$scope}" : '';
        }

        return $this;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths = null)
    {
        if ($paths !== null) {
            $this->paths = $paths;
        }

        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles(array $files = null)
    {
        if ($files !== null) {
            $this->files = $files;
        }

         return $this;
    }

    /**
     * @param array $variables
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = array_filter($variables);

        return $this;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Compile all predefined files.
     * @return $this
     */
    public function compileFiles()
    {
        $files = $this->files ?: $this->getDefaultFiles();

        foreach ($files as $file) {
            $this->compileFile($file);
        }

        return $this;
    }
}
