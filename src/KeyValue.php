<?php declare(strict_types=1);

namespace TotalReturn;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareTrait;

class KeyValue
{
    use LoggerAwareTrait;

    /** @var Connection */
    protected $conn;

    protected $find;
    protected $replace;

    protected $cache = [];

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->find = $conn->prepare('select * from key_values where namespace = :namespace and id = :id');
        $this->replace = $conn->prepare('replace into key_values values(:namespace, :id, :data)');
    }

    public function get(string $ns, string $id)
    {
        return $this->has($ns, $id) ? $this->cache["$ns-$id"] : null;
    }

    public function getIds(string $ns)
    {
        $sql = 'select id from key_values where namespace = :namespace order by id asc';
        return $this->conn->executeQuery($sql, ['namespace' => $ns])->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function has(string $ns, string $id): bool
    {
        if (!array_key_exists($key = "$ns-$id", $this->cache)) {
            $this->cache[$key] = $this->find($ns, $id);
        }

        return $this->cache[$key] !== null;
    }

    public function set(string $ns, string $id, $value): void
    {
        if ($this->has($ns, $id)) {
            $oldValue = $this->get($ns, $id);
            if ($oldValue !== $value) {
                $this->replace($ns, $id, $value);
            }
        } else {
            $this->replace($ns, $id, $value);
        }
    }

    public function replace(string $ns, string $id, $value): void
    {
        $this->replace->execute([
            'namespace' => $ns,
            'id' => $id,
            'data' => serialize($value),
        ]);

        $this->cache["$ns-$id"] = $value;
    }

    public function replaceMany(array $items): void
    {
        $values = array_map(function ($item) {
            return sprintf(
                '(%s,%s,%s)',
                $this->conn->quote($item['ns']),
                $this->conn->quote($item['id']),
                $this->conn->quote(serialize($item['value']))
            );
        }, $items);

        if(count($values) > 0 ) {
            $this->conn->executeQuery('replace into key_values values ' . implode(',', $values));
            //just nuke the cache to avoid getting clever w/ too much data
            $this->cache = [];
        }
    }

    protected function find(string $ns, string $id)
    {
        $this->find->execute([
            'namespace' => $ns,
            'id' => $id,
        ]);
        $row = $this->find->fetch();

        return $row ? unserialize($row['data']) : null;
    }
}
