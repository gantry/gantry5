<?php
namespace rockettheme\gantry\template\twig;

class twig extends \phpbb\template\twig\twig
{
    public function __construct(\phpbb\path_helper $path_helper, $config, $user, \phpbb\template\context $context, \phpbb\extension\manager $extension_manager = null)
    {
        $this->path_helper = $path_helper;
        $this->phpbb_root_path = $path_helper->get_phpbb_root_path();
        $this->php_ext = $path_helper->get_php_ext();
        $this->config = $config;
        $this->user = $user;
        $this->context = $context;
        $this->extension_manager = $extension_manager;

        $this->cachepath = $this->phpbb_root_path . 'cache/twig/';

        // Initiate the loader, __main__ namespace paths will be setup later in set_style_names()
        $loader = new \phpbb\template\twig\loader('');

        $this->twig = new \phpbb\template\twig\environment(
            $this->config,
            $this->path_helper,
            $this->extension_manager,
            $loader,
            array(
                'cache'			=> (defined('IN_INSTALL')) ? false : $this->cachepath,
                'debug'			=> defined('DEBUG'),
                'auto_reload'	=> (bool) $this->config['load_tplcompile'],
                'autoescape'	=> false,
            )
        );

        $this->twig->addExtension(
            new \phpbb\template\twig\extension(
                $this->context,
                $this->user
            )
        );

        // Initialize Gantry.
        $style = 'gantry';
        $this->gantry = require_once $this->phpbb_root_path . "styles/{$style}/gantry.php";
        $this->gantry['theme']->add_to_twig($this->twig);

        // Initialize lexer.
        $lexer = new \phpbb\template\twig\lexer($this->twig);

        $this->twig->setLexer($lexer);

        // Add admin namespace
        if ($this->path_helper->get_adm_relative_path() !== null && is_dir($this->phpbb_root_path . $this->path_helper->get_adm_relative_path() . 'style/'))
        {
            $this->twig->getLoader()->setPaths($this->phpbb_root_path . $this->path_helper->get_adm_relative_path() . 'style/', 'admin');
        }
    }

    /**
     * Display a template for provided handle.
     *
     * The template will be loaded and compiled, if necessary, first.
     *
     * This function calls hooks.
     *
     * @param string $handle Handle to display
     * @return \phpbb\template\template $this
     */
    public function display($handle)
    {
        $context = $this->get_template_vars();

        $result = $this->call_hook($handle, __FUNCTION__);
        if ($result !== false)
        {
            return $result[0];
        }

        $content = $this->twig->render($this->get_filename_from_handle($handle), $context);

        if ($this->gantry->wrapper())
        {
            $context['content'] = $content;

            echo $this->twig->render('wrapper.html.twig', $context);
        }
        else
        {
            echo $content;
        }

        return $this;
    }

    /**
    * Get template vars in a format Twig will use (from the context)
    *
    * @return array
    */
    protected function get_template_vars()
    {
        $vars = $this->gantry['theme']->add_to_context(parent::get_template_vars());

        $locator = $this->gantry['locator'];
        $loader = $this->twig->getLoader();

        foreach ($locator->findResources('theme://twig') as $path) {
            $loader->addPath($path);
        }

        return $vars;
    }
}

