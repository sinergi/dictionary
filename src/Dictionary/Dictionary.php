<?php
namespace Sinergi\Dictionary;

class Dictionary
{
    /**
     * @var array|DictionaryEntity[]
     */
    private $entities;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $storage;

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
}