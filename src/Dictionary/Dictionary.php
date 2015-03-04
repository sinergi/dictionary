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
use InvalidArgumentException;

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
     * @var array
     */
    private $extend;

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

    /**
     * @param null|string|array $language
     * @throws InvalidArgumentException
     */
    public function extend($language)
    {
        if (null === $language) {
            return;
        }
        if (is_string($language)) {
            $language = [$language];
        }
        if (is_array($language)) {
            if (null === $this->extend) {
                $this->extend = $language;
            } else {
                $this->extend = array_merge($this->extend, $language);
            }
            return;
        }
        throw new InvalidArgumentException;
    }

    public static function createDictionary($items)
    {
        $dictionary = new Dictionary(null, null, null, null, true, null);
        $dictionary->set($items);
        return $dictionary;
    }

    private function loadSelf()
    {
        if (!$this->isLoaded) {
            $this->items = $this->load(
                $this->name,
                $this->path,
                $this->type,
                $this->items,
                $this->language,
                $this->storage,
                $this->extend
            );
            $this->isLoaded = true;
        }
    }

    /**
     * Load files and variables
     * @param string $name
     * @param string $path
     * @param string $type
     * @param array|Dictionary[] $items
     * @param string $language
     * @param string $storage
     * @param array $extend
     * @return array
     */
    private static function load($name, $path, $type, array $items, $language, $storage, array $extend = null)
    {
        $newItems = [];

        $dirPath = self::getDirPath($storage, $path, $language);

        if (null !== $extend) {
            foreach ($extend as $extendLanguage) {
                $newItems = array_merge($newItems, self::load(
                    $name,
                    $path,
                    $type,
                    [],
                    $extendLanguage,
                    $storage
                ));
            }
        }

        if (is_dir($dirPath)) {
            // Scan dir
            foreach (scandir($dirPath) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $item = null;
                    if (substr($file, -4) === '.php') {
                        $file = substr($file, 0, -4);
                        $item = new Dictionary(
                            $language,
                            $storage,
                            $file,
                            $path,
                            false,
                            Php::TYPE
                        );
                        $item->extend($extend);
                    } elseif (substr($file, -5) === '.json') {
                        $file = substr($file, 0, -5);
                        $item = new Dictionary(
                            $language,
                            $storage,
                            $file,
                            $path,
                            false,
                            Json::TYPE
                        );
                        $item->extend($extend);
                    } elseif (substr($file, -5) === '.html') {
                        $file = substr($file, 0, -5);
                        $item = new Dictionary(
                            $language,
                            $storage,
                            $file,
                            $path,
                            false,
                            Html::TYPE
                        );
                        $item->extend($extend);
                    } elseif (is_dir($dirPath . DIRECTORY_SEPARATOR . $file)) {
                        $item = new Dictionary(
                            $language,
                            $storage,
                            $file,
                            $path,
                            false
                        );
                        $item->extend($extend);
                    }

                    if (null !== $item) {
                        if (isset($items[$file])) {
                            $newItems[$file] = $items[$file]->merge($item);
                        } else {
                            $newItems[$file] = $item;
                        }
                    }
                }
            }
        }

        if (!empty($name)) {
            // Import file
            if ($type === Php::TYPE) {
                $variables = require $dirPath . "." . Php::TYPE;
                if (is_array($variables)) {
                    $newItems = array_merge($newItems, $variables);
                }
            } elseif ($type === Json::TYPE) {
                $content = file_get_contents($dirPath . "." . Json::TYPE);
                $variables = json_decode($content, true);
                if (is_array($variables)) {
                    $newItems = array_merge($newItems, $variables);
                }
            }
        }

        return $newItems;
    }

    /**
     * @param string $storage
     * @param string $path
     * @param string $language
     * @return string
     */
    private static function getDirPath($storage, $path, $language)
    {
        $returnValue = $storage;
        if (!empty($language)) {
            $returnValue = $returnValue . DIRECTORY_SEPARATOR . $language;
        }
        if (!empty($path)) {
            $returnValue = $returnValue . DIRECTORY_SEPARATOR . $path;
        }
        return $returnValue;
    }

    /**
     * @return string
     */
    private function getSelfDirPath()
    {
        return $this->getDirPath(
            $this->storage,
            $this->path,
            $this->language
        );
    }

    /**
     * @return string
     */
    private function getRawContent()
    {
        if ($this->isLoaded) {
            return $this->content;
        }
        $file = $this->getSelfDirPath() . '.' . $this->type;
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
     * @return array
     */
    public function merge(Dictionary $items)
    {
        return array_merge($this->items, $items->toArray());
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
        $this->loadSelf();
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
        $this->loadSelf();
        return count($this->items);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->loadSelf();
        return new ArrayIterator($this->items);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->loadSelf();
        return isset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->loadSelf();
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
        $this->loadSelf();
        $this->items[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        $this->loadSelf();
        unset($this->items[$offset]);
    }
}
