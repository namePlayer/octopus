<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\AccountEmailIsAlreadyUsedException;
use App\Authentication\Exception\AccountInsertIntoDatabaseFailedException;
use App\Authentication\Model\Account;

class AuthenticationService
{

    public function __construct(
        private readonly AccountService $accountService,
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

}
