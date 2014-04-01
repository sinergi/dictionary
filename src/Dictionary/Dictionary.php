<?php
namespace Sinergi\Dictionary;

use Countable;
use IteratorAggregate;
use ArrayAccess;
use ArrayIterator;

class Dictionary implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * @var array|Dictionary[]
     */
    private $items = [];

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $storage;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $isLoaded = false;

    /**
     * @param null|string $language
     * @param null|string $storage
     * @param null|string $name
     * @param null|string $path
     */
    public function __construct($language = null, $storage = null, $name = null, $path = null)
    {
        $this->setLanguage($language);
        $this->setStorage($storage);
        $this->name = $name;
        $this->path = trim($path . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR);
    }

    /**
     * Load files and variables
     */
    private function load()
    {
        if (!$this->isLoaded) {
            $dir = $this->getDirPath();
            if (is_dir($dir)) {
                // Scan dir
                $extension = strrev('.php');
                foreach (scandir($dir) as $file) {
                    if ($file !== '.' && $file !== '..') {
                        if (strpos(strrev($file), $extension) === 0) {
                            $file = substr($file, 0, -4);
                        }
                        $this->items[$file] = new Dictionary($this->getLanguage(), $this->getStorage(), $file, $this->path);
                    }
                }
            } elseif (!empty($this->name)) {
                // Import file
                $file = $dir . '.php';
                if (is_file($file)) {
                    $variables = require $file;
                    if (is_array($variables)) {
                        $this->items = array_merge($this->items, $variables);
                    }
                }
            }

            $this->isLoaded = true;
        }
    }

    /**
     * @return string
     */
    private function getDirPath()
    {
        $path = $this->getStorage();
        $language = $this->getLanguage();
        if (!empty($language)) {
            $path = $path . DIRECTORY_SEPARATOR . $language;
        }
        if (!empty($this->path)) {
            $path = $path . DIRECTORY_SEPARATOR . $this->path;
        }
        return $path;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $storage
     * @return $this
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return string
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @param array|null $args
     * @return mixed
     */
    public function __call($name, $args = null)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->load();
        return count($this->items);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->load();
        return new ArrayIterator($this->items);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->load();
        return isset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->load();
        return $this->items[$offset];
    }

    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->load();
        $this->items[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        $this->load();
        unset($this->items[$offset]);
    }
}