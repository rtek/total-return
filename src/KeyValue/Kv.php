<?php declare(strict_types=1);

namespace TotalReturn\KeyValue;

class Kv
{
    /** @var string */
    protected $ns;
    /** @var string */
    protected $id;
    /** @var mixed */
    protected $value;

    public function __construct(string $ns, string $id, $value)
    {
        $this->ns = $ns;
        $this->id = $id;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->ns;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->getNamespace() .'-'. $this->getId();
    }
}
