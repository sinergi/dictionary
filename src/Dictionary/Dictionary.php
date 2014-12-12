<?php
namespace Sinergi\Dictionary;

use JsonSerializable;
use Countable;
use IteratorAggregate;
use ArrayAccess;
use ArrayIterator;
use Sinergi\Dictionary\FileType\Html;
use Sinergi\Dictionary\FileType\Json;
use Sinergi\Dictionary\FileType\Php;

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
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @var bool
     */
    private $isLoaded = false;

    /**
     * @param null|string $language
     * @param null|string $storage
     * @param null|string $name
     * @param null|string $path
     * @param null|bool $isLoaded
     * @param string $type
     */
    public function __construct($language = null, $storage = null, $name = null, $path = null, $isLoaded = null, $type = null)
    {
        $this->setLanguage($language);
        $this->setStorage($storage);
        $this->name = $name;
        $this->path = trim($path . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR);
        if (null !== $isLoaded) {
            $this->isLoaded = $isLoaded;
        }
        $this->type = $type;
    }

    public static function createDictionary($items)
    {
        $dictionary = new Dictionary(null, null, null, null, true, null);
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
                        $item = null;
                        if (substr($file, -4) === '.php') {
                            $file = substr($file, 0, -4);
                            $item = new Dictionary(
                                $this->getLanguage(),
                                $this->getStorage(),
                                $file,
                                $this->path,
                                false,
                                Php::TYPE
                            );
                        } elseif (substr($file, -5) === '.json') {
                            $file = substr($file, 0, -5);
                            $item = new Dictionary(
                                $this->getLanguage(),
                                $this->getStorage(),
                                $file,
                                $this->path,
                                false,
                                Json::TYPE
                            );
                        } elseif (substr($file, -5) === '.html') {
                            $file = substr($file, 0, -5);
                            $item = new Dictionary(
                                $this->getLanguage(),
                                $this->getStorage(),
                                $file,
                                $this->path,
                                false,
                                Html::TYPE
                            );
                        }
                        if (null !== $item) {
                            if (isset($this->items[$file])) {
                                $this->items[$file]->merge($item);
                            } else {
                                $this->items[$file] = $item;
                            }
                        }
                    }
                }
            }

            if (!empty($this->name)) {
                // Import file
                if ($this->type === Php::TYPE) {
                    $variables = require $dir . "." . Php::TYPE;
                    if (is_array($variables)) {
                        $this->items = array_merge($this->items, $variables);
                    }
                } elseif ($this->type === Json::TYPE) {
                    $content = file_get_contents($dir . "." . Json::TYPE);
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
     * @return string
     */
    private function getRawContent()
    {
        if ($this->isLoaded) {
            return $this->content;
        }
        $file = $this->getDirPath() . '.' . $this->type;
        $this->content = file_get_contents($file);
        $this->isLoaded = true;
        return $this->content;
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param mixed $items
     */
    public function set($items)
    {
        $this->items = $items;
    }

    /**
     * @param Dictionary $items
     */
    public function merge(Dictionary $items)
    {
        $this->items = array_merge($this->items, $items->toArray());
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
        if (isset($this->items[$offset])) {
            $item = $this->items[$offset];
            if ($item instanceof Dictionary && $item->getType() === Html::TYPE) {
                return $this->items[$offset]->getRawContent();
            }
            return $this->items[$offset];
        }
        return null;
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
