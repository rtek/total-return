<?php

namespace TotalReturn\Api;

class EndpointIterator implements \Iterator
{
    /** @var array */
    protected $entries = [];

    /** @var string */
    protected $class = EndPoint::class;

    /** @var array|null */
    protected $next;

    /** @var int */
    protected $key;

    /** @var mixed */
    protected $current;


    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return Endpoint
     */
    public function current()
    {
        $cls = $this->class;
        return new $cls($this->current);
    }

    public function next(): void
    {
        $this->current = array_shift($this->entries);
        $this->key++;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return $this->current !== null;
    }

    public function rewind(): void
    {
        $this->next();
        $this->key = 0;
    }

    /**
     * @return Endpoint[]
     */
    public function toArray(): array
    {
        $ret = [];
        foreach($this as $obj) {
            $ret[] = $obj;
        }
        return $ret;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return static
     */
    public function setClass(string $class): EndpointIterator
    {
        $this->class = $class;
        return $this;
    }
}
