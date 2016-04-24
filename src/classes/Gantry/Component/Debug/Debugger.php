<?php
namespace Gantry\Component\Debug;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;

/**
 * Class Debugger
 * @package Gantry\Component\Debug
 */
class Debugger
{
    /** @var JavascriptRenderer $renderer */
    protected $renderer;

    /** @var StandardDebugBar $debugbar */
    protected $debugbar;

    protected $timers = [];

    /**
     * Debugger constructor.
     */
    public function __construct()
    {
        $this->debugbar = new StandardDebugBar();
        $this->debugbar['time']->addMeasure('Loading', $this->debugbar['time']->getRequestStartTime(), microtime(true));
    }

    /**
     * Initialize the debugger.
     *
     * @return $this
     * @throws \DebugBar\DebugBarException
     */
    public function init()
    {
        //$config = Gantry::instance()['config'];
        //$this->debugbar->addCollector(new ConfigCollector($config->toArray(), 'Config'));

        return $this;
    }


    /**
     * Add the debugger assets to the Grav Assets.
     *
     * @return $this
     */
    public function assets()
    {
        $gantry = Gantry::instance();

        $gantry->load('jquery');

        $this->renderer = $this->debugbar->getJavascriptRenderer();
        $this->renderer->setIncludeVendors(false);

        $base = $this->renderer->getBasePath();
        list($css_files, $js_files) = $this->renderer->getAssets(null, null);

        foreach ($css_files as $css) {
            Document::addHeaderTag([
                'tag' => 'link',
                'rel' => 'stylesheet',
                'href' => $css
            ], 'head', 0);
        }

        foreach ($js_files as $js) {
            Document::addHeaderTag([
                'tag' => 'script',
                'src' => $js
            ], 'head', 0);
        }

        return $this;
    }

    /**
     * Adds a data collector.
     *
     * @param $collector
     *
     * @return $this
     * @throws \DebugBar\DebugBarException
     */
    public function addCollector($collector)
    {
        $this->debugbar->addCollector($collector);

        return $this;
    }

    /**
     * Returns a data collector.
     *
     * @param $collector
     *
     * @return \DebugBar\DataCollector\DataCollectorInterface
     * @throws \DebugBar\DebugBarException
     */
    public function getCollector($collector)
    {
        return $this->debugbar->getCollector($collector);
    }

    /**
     * Displays the debug bar.
     *
     * @return string
     */
    public function render()
    {
        return $this->renderer->render();
    }

    /**
     * Sends the data through the HTTP headers.
     *
     * @return $this
     */
    public function sendDataInHeaders()
    {
        $this->debugbar->sendDataInHeaders();

        return $this;
    }

    /**
     * Start a timer with an associated name and description.
     *
     * @param             $name
     * @param string|null $description
     *
     * @return $this
     */
    public function startTimer($name, $description = null)
    {
        $this->debugbar['time']->startMeasure($name, $description);
        $this->timers[] = $name;

        return $this;
    }

    /**
     * Stop the named timer.
     *
     * @param string $name
     *
     * @return $this
     */
    public function stopTimer($name)
    {
        $this->debugbar['time']->stopMeasure($name);

        return $this;
    }

    /**
     * Dump variables into the Messages tab of the Debug Bar.
     *
     * @param        $message
     * @param string $label
     * @param bool   $isString
     *
     * @return $this
     */
    public function addMessage($message, $label = 'info', $isString = true)
    {
        $this->debugbar['messages']->addMessage($message, $label, $isString);

        return $this;
    }
}
