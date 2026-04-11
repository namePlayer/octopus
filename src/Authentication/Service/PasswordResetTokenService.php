<?php declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\Exception\PasswordResetTokenAlreadyUsedException;
use App\Authentication\Exception\PasswordResetTokenExpiredException;
use App\Authentication\Exception\PasswordResetTokenInvalidException;
use App\Authentication\Model\Account;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

class PasswordResetTokenService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Logger $logger
    )
    {
    }

    public function generateToken(Account $account, int $expiryMinutes = 60): ?Account
    {
        $tokenBytes = random_bytes(32);
        $token = bin2hex($tokenBytes);
        
        if (strlen($token) !== 64) {
            $this->logger->critical('Token generated with wrong length', [
                'token_bytes_length' => strlen($token),
                'account_uuid' => $account->uuid->toString(),
            ]);
            return null;
        }
        
        $updatedAt = new \DateTime(sprintf('+%d minutes', $expiryMinutes));
        
        $stmt = $this->connection->prepare('
            UPDATE db.Account 
            SET passwordResetToken = :token,
                passwordResetTokenExpires = :expiresAt
            WHERE id = :id
        ');
        
        $stmt->execute([
            ':token' => $token,
            ':expiresAt' => $updatedAt->format('Y-m-d H:i:s'),
            ':id' => $account->id,
        ]);
        
        if ($stmt->rowCount() === 0) {
            $this->logger->warning('Token update failed', [
                'account_uuid' => $account->uuid->toString(),
                'token' => $token,
            ]);
            return null;
        }
        
        return $account;
    }

    public function isValidToken(string $token, ?Account $account = null): bool
    {
        if (empty($token)) {
            return false;
        }
        
        try {
            // Account laden (da UPDATE im Hintergrund ausgeführt wurde)
            $stmtGet = $this->connection->prepare('
                SELECT id, passwordResetToken, passwordResetTokenExpires
                FROM db.Account 
                WHERE passwordResetToken = :token
            ');
            
            $stmtGet->execute([':token' => $token]);
            $accountData = $stmtGet->fetchNumeric();
            
            if ($accountData !== false) {
                $account = Account::hydrate($accountData);
                $account->passwordResetToken = $accountData[1];
                $account->passwordResetTokenExpires = \DateTime::createFromFormat('Y-m-d H:i:s', $accountData[2]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Fehler beim Laden Account: %s', $e->getMessage()));
            return false;
        }
    }

    public function expireToken(string $token, ?Account $account = null): bool
    {
        if (empty($token)) {
            return false;
        }
        
        if ($account === null) {
            // Ohne Account - Token leeren (abgelaufen)
            $stmt = $this->connection->prepare('
                UPDATE db.Account 
                SET passwordResetToken = NULL,
                    passwordResetTokenExpires = NULL
                WHERE passwordResetToken = :token
            ');
            return $stmt->execute([':token' => $token]) > 0;
        }
        
        // Mit Account - Token leeren (benutzt)
        $stmt = $this->connection->prepare('
            UPDATE db.Account 
            SET passwordResetToken = NULL,
                passwordResetTokenExpires = NULL
            WHERE id = :id
        ');
        
        return $stmt->execute([':id' => $account->id]) > 0;
    }

    public function validateAndExpire(string $token, ?Account $account = null): ?Account
    {
        if (!$this->isValidToken($token, $account)) {
            return null;
        }
        
        $this->expireToken($token, $account);
        return $account;
    }

    public function isTokenUsed(string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        
        $stmt = $this->connection->prepare('
            SELECT COUNT(*) as count
            FROM db.Account 
            WHERE passwordResetToken = :token
        ');
        
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetchNumeric();
        
        return ($result[0] ?? 0) > 0;
    }
}
