<?php

namespace App\Models;

use Countable;

/**
 * Class User
 * @package App\Models
 *
 * @property mixed|null username
 * @property mixed|null usersn
 */
class User implements Countable
{
    protected $meta;

    /**
     * User constructor.
     * @param $userName
     */
    public function __construct($userName = null)
    {
        $this->meta['username'] = $userName;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->meta[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->meta[$name]);
    }

    /**
     * @param $name
     * @return |null
     */
    public function __get($name)
    {
        return $this->meta[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @param array $meta
     */
    public function addMeta(array $meta): void
    {
        $this->meta = array_merge($this->getMeta(), $meta);
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->meta);
    }
}
