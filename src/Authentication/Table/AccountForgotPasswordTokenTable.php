<?php
declare(strict_types=1);

namespace App\Authentication\Table;

use App\Authentication\Model\AccountForgotPasswordToken;
use App\Base\Table\AbstractTable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

class AccountForgotPasswordTokenTable extends AbstractTable
{

    public function insert(AccountForgotPasswordToken $accountForgotPasswordToken): bool
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->insert($this->getTableName());
        foreach ($accountForgotPasswordToken->extract(false) as $column => $value) {
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

    public function update(AccountForgotPasswordToken $accountForgotPasswordToken): bool
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->update($this->getTableName())->where('id = :id')
            ->setParameter('id', $accountForgotPasswordToken->id);
        foreach ($accountForgotPasswordToken->extract(false) as $column => $value) {
            $queryResult->set($column, ':' . $column);
            $queryResult->setParameter($column, $value);
        }
        try {
            $this->logger->debug('Executing Query', [
                'sql' => $queryResult->getSQL(),
                'params' => $queryResult->getParameters()
            ]);
            $queryResult->executeQuery();
            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function findByToken(string $token): ?AccountForgotPasswordToken
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->select('*')
            ->from($this->getTableName())
            ->where('token = :token')
            ->setParameter('token', $token)
            ->fetchAssociative();
        if($queryResult !== false)
        {
            return AccountForgotPasswordToken::hydrate($queryResult);
        }
        return null;
    }

}
