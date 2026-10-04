<?php
declare(strict_types=1);

namespace App\Authentication\Table;

use App\Authentication\Model\AccountForgotPasswordToken;
use App\Authentication\Model\AccountJwtRefreshToken;
use App\Base\Table\AbstractTable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

class AccountJwtRefreshTokenTable extends AbstractTable
{

    public function insert(AccountJwtRefreshToken $accountJwtRefreshToken): bool
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->insert($this->getTableName());
        foreach ($accountJwtRefreshToken->extract(false) as $column => $value) {
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

    public function findByToken(string $token): ?AccountJwtRefreshToken
    {
        $queryBuilder = new QueryBuilder($this->query);
        $queryResult = $queryBuilder->select('*')
            ->from($this->getTableName())
            ->where('token = :token')
            ->setParameter('token', $token)
            ->fetchAssociative();
        if($queryResult !== false)
        {
            return AccountJwtRefreshToken::hydrate($queryResult);
        }
        return null;
    }

}
