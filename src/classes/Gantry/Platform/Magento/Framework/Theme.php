<?php
namespace Gantry\Framework;

class Theme extends Base\Theme
{
    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \Mage::getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_SKIN);
    }

    public function render($file, array $context = array())
    {
        $loader = new \Twig_Loader_Filesystem($this->path . '/twig');

        $params = array(
            'cache' => \Mage::getBaseDir('cache') . '/cache',
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        $gantry = \Gantry\Framework\Gantry::instance();

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }
}
