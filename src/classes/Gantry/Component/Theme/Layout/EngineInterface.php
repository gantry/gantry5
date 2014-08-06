<?php
namespace Gantry\Component\Theme\Layout;

interface EngineInterface
{
    /**
     * @param array $context
     * @return mixed
     */
    public function context(array &$context);
}
