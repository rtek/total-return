<?php


namespace TotalReturn\Api;

trait HasJson
{
    /** @var array */
    protected $json = [];

    /**
     * @param string[] $args
     * @return mixed
     */
    public function get(...$args)
    {
        $lookup = $this->json;
        foreach ($args as $arg) {
            if(!is_array($lookup)) {
                break;
            }
            if(!array_key_exists($arg, $lookup)) {
                $keys = implode(', ', array_keys($lookup));
                throw new \RuntimeException("Could not find $arg from keys $keys");
            }
            $lookup = $lookup[$arg];
        }

        return $lookup;
    }

    /**
     * @param mixed[] $args
     * @return static
     */
    public function set(...$args)
    {
        if(count($args) < 2) {
            throw new \LogicException('Must have at least two args');
        }
        $value = array_pop($args);
        $key = array_pop($args);

        $array = &$this->json;

        foreach($args as $arg) {
            $array[$arg] = [];
            $array = &$array[$arg];
        }

        $array[$key] = $value;

        return $this;
    }

}
