<?php
namespace Sinergi\Dictionary;

use JsonSerializable;
use Countable;
use IteratorAggregate;
use ArrayAccess;
use ArrayIterator;

/**
 * @method array errors(array $errors, array $text)
 */
class Dictionary implements Countable, IteratorAggregate, ArrayAccess, JsonSerializable
{
    const ERRORS_METHOD = 'errors';

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

    public static function createDictionary($items)
    {
        $dictionary = new Dictionary();
        $dictionary->set($items);
        return $dictionary;
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
                foreach (scandir($dir) as $file) {
                    if ($file !== '.' && $file !== '..') {
                        if (substr($file, -4) === '.php') {
                            $file = substr($file, 0, -4);
                        } elseif (substr($file, -5) === '.json') {
                            $file = substr($file, 0, -5);
                        }
                        $this->items[$file] = new Dictionary($this->getLanguage(), $this->getStorage(), $file, $this->path);
                    }
                }
            } elseif (!empty($this->name)) {
                // Import file
                $phpFile = $dir . '.php';
                $jsonFile = $dir . '.json';
                if (is_file($phpFile)) {
                    $variables = require $phpFile;
                    if (is_array($variables)) {
                        $this->items = array_merge($this->items, $variables);
                    }
                } elseif (is_file($jsonFile)) {
                    $content = file_get_contents($jsonFile);
                    $variables = json_decode($content, true);
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

    public function set($items)
    {
        $this->items = $items;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (is_array($name)) {
            $retval = [];
            foreach ($name as $string) {
                $value = $this->get($string);
                if (is_object($value)) {
                    $value = $value->toArray();
                }
                if (!is_array($value)) {
                    $value = [$value];
                }
                $retval = array_merge($retval, $value);
            }
            return Dictionary::createDictionary($retval);
        }

        $parts = explode('.', $name, 2);
        if (isset($parts[0])) {
            $name = $parts[0];
        }
        if (isset($parts[1])) {
            $object = $this->offsetGet($name);
            if (is_object($object)) {
                return $object->get($parts[1]);
            }
        } else {
            return $this->offsetGet($name);
        }
        return null;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->jsonSerialize();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $this->load();
        $retval = [];
        foreach ($this->items as $key => $item) {
            $retval[$key] = $item;
        }
        return $retval;
    }

    /**
     * @param array $errors
     * @param array $text
     * @return array
     */
    public function __errors(array $errors = null, array $text = null)
    {
        if (null === $errors) {
            $errors = [];
        }
        if (null === $text) {
            $text = [];
        }
        $matches = [];
        $retval = [];
        foreach ($errors as $error) {
            if (isset($text[$error])) {
                $retval[$error] = $text[$error];
            } else {
                foreach ($text as $key => $value) {
                    if (strncmp($error, $key, strlen($key)) === 0 && !isset($matches[$key])) {
                        $matches[$key] = true;
                        $retval[$error] = $value;
                        break;
                    }
                }
            }
        }
        return $retval;
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
        if ($name === self::ERRORS_METHOD && !$this->offsetExists($name) || null === $args) {
            return call_user_func_array([$this, '__errors'], $args);
        }
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
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
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
