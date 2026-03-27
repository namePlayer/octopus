<?php
declare(strict_types=1);

namespace App\Authentication\Table;

use App\Authentication\Model\Account;
use App\Base\Table\AbstractTable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

class AccountTable extends AbstractTable
{

    public function insert(Account $account): bool
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->insert($this->getTableName());
        foreach ($account->extract(false) as $column => $value) {
            $queryResult->setValue($column, ':' . $column);
            $queryResult->setParameter($column, $value);
        }
        try {
            $queryResult->executeQuery();
            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function findByUuid(string $uuid): ?Account
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->select('*')
            ->from($this->getTableName())
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->fetchAssociative();
        if($queryResult !== false)
        {
            return Account::hydrate($queryResult);
        }
        return null;
    }

    public function findByEmail(string $email): ?Account
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->select('*')
            ->from($this->getTableName())
            ->where('email = :email')
            ->setParameter('email', $email)
            ->fetchAssociative();
        if($queryResult !== false)
        {
            return Account::hydrate($queryResult);
        }
        return null;
    }

}
