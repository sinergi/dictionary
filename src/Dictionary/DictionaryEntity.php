<?php
namespace Sinergi\Dictionary;

use Countable;
use IteratorAggregate;
use ArrayAccess;
use ArrayIterator;

class DictionaryEntity implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * @var Dictionary
     */
    private $dictionary;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $text;

    /**
     * @var array|DictionaryEntity[]
     */
    private $entities;

    /**
     * @param Dictionary $dictionary
     * @param string $name
     */
    public function __construct(Dictionary $dictionary, $name)
    {
        $this->setDictionary($dictionary);
        $this->setName($name);
    }

    /**
     * @param Dictionary $dictionary
     * @return $this
     */
    public function setDictionary(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
        return $this;
    }

    /**
     * @return Dictionary
     */
    public function getDictionary()
    {
        return $this->dictionary;
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
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        return count($this->text);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->text);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->text[$offset]);
    }

    /**
     * @param int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->text[$offset];
    }

    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->text[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->text[$offset]);
    }
}