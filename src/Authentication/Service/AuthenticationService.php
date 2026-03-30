<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\DTO\LoginAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\AccountEmailIsAlreadyUsedException;
use App\Authentication\Exception\AccountInsertIntoDatabaseFailedException;
use App\Authentication\Exception\AccountInvalidLoginCredentialsEnteredException;
use App\Authentication\Exception\EmailCouldNotBeAssociatedWithAccountException;
use App\Authentication\Exception\PasswordCouldNotBeAssociatedWithAccountException;
use App\Authentication\Model\Account;
use Monolog\Logger;

class AuthenticationService
{

    public function __construct(
        private readonly AccountService $accountService,
        private readonly PasswordService $passwordService,
        private readonly Logger $logger,
    )
    {
    }

    public function register(CreateAccountDTO $createAccountDTO, bool $throwDuplicateEmailError = false): Account
    {
        $emailAssociatedAccount = $this->accountService->getUserByEmail($createAccountDTO->email);
        if($throwDuplicateEmailError && $emailAssociatedAccount instanceof Account) {
            throw new AccountEmailIsAlreadyUsedException();
        }

        if($emailAssociatedAccount instanceof Account){
            return $emailAssociatedAccount;
        }

        try {
            $account = $this->accountService->getUserByUuid($this->accountService->create($createAccountDTO));
            if($account instanceof Account){
                return $account;
            }
        } catch (AccountInsertIntoDatabaseFailedException $e) {}

        throw new AccountCreationFailedException();
    }

    public function login(LoginAccountDTO $loginAccountDTO): Account
    {
        $emailAssociatedAccount = $this->accountService->getUserByEmail($loginAccountDTO->email);
        if(!$emailAssociatedAccount instanceof Account){
            $this->logger->warning('User login email could not be found in the database.', ['email' => $loginAccountDTO->email]);
            throw new AccountInvalidLoginCredentialsEnteredException();
        }

        if(!$this->passwordService->verifyPassword($loginAccountDTO->password, $emailAssociatedAccount->password)){
            $this->logger->warning('User login password could not be matched with database stored one.' , ['id' => $emailAssociatedAccount->id]);
            throw new AccountInvalidLoginCredentialsEnteredException();
        }

        $this->logger->info('User authentication was successful.', ['id' => $emailAssociatedAccount->id]);
        return $emailAssociatedAccount;
    }

}
