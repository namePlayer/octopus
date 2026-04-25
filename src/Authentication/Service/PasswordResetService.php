<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\DTO\ForgotPasswordDTO;
use App\Authentication\DTO\ResetForgotPasswordDTO;
use App\Authentication\Exception\AccountForgotPasswordCreationFailedException;
use App\Authentication\Exception\AccountUpdateIntoDatabaseFailedException;
use App\Authentication\Exception\AccountWasNotFoundException;
use App\Authentication\Exception\PasswordResetFailedWithInvalidatedTokenException;
use App\Authentication\Exception\PasswordResetFailedWithStillValidTokenException;
use App\Authentication\Exception\PasswordResetTokenCouldNotBeMarkedAsUsedException;
use App\Authentication\Exception\PasswordResetTokenHasAlreadyBeenUsedException;
use App\Authentication\Exception\PasswordResetTokenWasNotFoundException;
use App\Authentication\Model\Account;
use App\Authentication\Model\AccountForgotPasswordToken;
use App\Authentication\Table\AccountForgotPasswordTokenTable;
use DateTime;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

readonly class PasswordResetService
{

    public function __construct(
        private AccountService  $accountService,
        private PasswordService $passwordService,
        private AccountForgotPasswordTokenTable $accountForgotPasswordTokenTable,
        private Logger          $logger,
    )
    {
    }

    public function sendForgotPassword(ForgotPasswordDTO $forgotPasswordDTO): void
    {
        $account = $this->accountService->getUserByEmail($forgotPasswordDTO->email);
        if(!$account instanceof Account) {
            $this->logger->info('Password reset could not be executed for account because the email does not exist.',
                ['email' => $forgotPasswordDTO->email]);
            return;
        }

        $accountForgotPasswordToken = new AccountForgotPasswordToken();
        do {
            $accountForgotPasswordToken->token = $this->generateToken();
        } while($this->findPasswordForgotByToken($accountForgotPasswordToken->token) instanceof AccountForgotPasswordToken);
        $accountForgotPasswordToken->account = $account->id;
        $accountForgotPasswordToken->created = new DateTime();
        $accountForgotPasswordToken->used = null;

        if($this->accountForgotPasswordTokenTable->insert($accountForgotPasswordToken) === false)
        {
            throw new AccountForgotPasswordCreationFailedException();
        }
    }

    public function resetPasswordWhenForgotten(ResetForgotPasswordDTO $resetForgotPasswordDTO): void
    {
        $resetToken = $this->findPasswordForgotByToken($resetForgotPasswordDTO->token);
        if($resetToken === null)
        {
            throw new PasswordResetTokenWasNotFoundException();
        }

        if($resetToken->used !== null)
        {
            throw new PasswordResetTokenHasAlreadyBeenUsedException();
        }

        $account = $this->accountService->getAccountById($resetToken->account);
        if($account === null)
        {
            throw new AccountWasNotFoundException();
        }

        $account->password = $this->passwordService->hashPassword($resetForgotPasswordDTO->password);
        try {
            $this->setForgotPasswordTokenUsed($resetToken);
            $this->accountService->update($account);
        } catch (PasswordResetTokenCouldNotBeMarkedAsUsedException $e) {
            throw new PasswordResetFailedWithStillValidTokenException();
        } catch (AccountUpdateIntoDatabaseFailedException $e) {
            throw new PasswordResetFailedWithInvalidatedTokenException();
        }
    }

    private function setForgotPasswordTokenUsed(AccountForgotPasswordToken $accountForgotPasswordToken): void
    {
        $token = $this->accountForgotPasswordTokenTable->findByToken($accountForgotPasswordToken->token);
        if($token === null)
        {
            return;
        }
        $token->used = new DateTime();
        if($this->accountForgotPasswordTokenTable->update($token) === false)
        {
            throw new PasswordResetTokenCouldNotBeMarkedAsUsedException();
        }
    }

    public function findPasswordForgotByToken(string $token): ?AccountForgotPasswordToken
    {
        return $this->accountForgotPasswordTokenTable->findByToken($token);
    }

    private function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }

}
