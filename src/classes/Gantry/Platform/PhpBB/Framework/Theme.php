<?php
namespace Gantry\Framework;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = './styles/' . $name;
    }

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        parent::add_to_twig($twig, $loader);

    }
    public function render($file, array $context = array())
    {
        $loader = new \Twig_Loader_Filesystem($this->path . '/twig');

        $params = array(
            'cache' => GANTRY5_ROOT . '/cache',
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }
}
