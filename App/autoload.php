<?php

spl_autoload_register(static function ($className) {
    require __DIR__ . '/../' . str_replace('\\', '/', $className) . '.php';
});