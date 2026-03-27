<?php
declare(strict_types=1);

namespace App\Base\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;

class DatabaseFactory
{

    private Connection $connection;

    public function connect(bool $reconnect = false): Connection
    {
        $parameter = [
            'driver' => 'pdo_mysql',
            'host' => $_ENV['DB_HOST'],
            'dbname' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWORD'],
        ];

        if(!isset($this->connection) || $reconnect) {
            $this->connection = DriverManager::getConnection($parameter);
        }
        return $this->connection;
    }

}
