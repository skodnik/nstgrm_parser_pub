<?php

namespace App\Controllers;

use App\Models;
use App\Views;

abstract class Controller
{
    public $service;
    public $options;
    public $view;

    public function __construct($options = null)
    {
        $this->service = new Models\Service;
        $this->view = new Views\View;
        $this->options = $options;
    }
}
