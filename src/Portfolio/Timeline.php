<?php declare(strict_types=1);

namespace TotalReturn\Portfolio;

class Timeline
{
    /** @var \DateTime */
    protected $start;

    /** @var array */
    protected $days;

    protected $index;

    public function __construct(\DateTime $start, array $days)
    {
        $this->start = new \DateTime($start->format('Y-m-d'));
        $this->days = $this->prepareDays($days);
        $this->index = $this->findNearestIndex($this->start);
    }

    protected function prepareDays(array $in)
    {
        $out = [];
        foreach ($in as $i) {
            $dt = new \DateTime($i->format('Y-m-d'));
            $out[$dt->getTimestamp()] = $dt;
        }
        ksort($out);
        return array_values($out);
    }

    public function today(): \DateTime
    {
        return clone $this->days[$this->index];
    }

    public function next(): ?\DateTime
    {
        return $this->isEnd() ? null : clone $this->days[$this->index+1];
    }

    public function forward(): void
    {
        $this->index++;
    }

    public function isEnd(): bool
    {
        return $this->index === count($this->days) - 1;
    }

    public function formatToday(): string
    {
        return $this->today()->format('Y-m-d D');
    }
    protected function findNearestIndex(\DateTime $day)
    {
        foreach ($this->days as $i => $d) {
            if ($d >= $day) {
                return $i;
            }
        }

        throw new \LogicException('Could not find nearest day');
    }
}
