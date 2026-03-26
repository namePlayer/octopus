<?php declare(strict_types=1);

namespace App\Table;

use Envms\FluentPDO\Query;
use ReflectionClass;

class AbstractTable
{

    private string $table;

    public function __construct(public Query $query)
    {
        $this->table = substr((new ReflectionClass($this))->getShortName(), 0, -5);
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function findById(int $id): bool|array
    {
        return $this->query->from($this->table)
            ->where('id', $id)
            ->fetch();
    }

    public function findAll(): bool|array
    {
        return $this->query->from($this->table)
            ->fetchAll();
    }

}
