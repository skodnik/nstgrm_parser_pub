<?php

namespace App\Views;

/**
 * Class View
 * @package App\Views
 */
class View
{
    protected $path = __DIR__ . '/Templates/';

    /**
     * @param $template
     * @param $data
     * @return mixed
     */
    public function __invoke($template, $data = null)
    {
        return include $this->path . $template . '.php';
    }
}