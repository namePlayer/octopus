<?php
declare(strict_types=1);

namespace App\Authentication\Command;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\AccountEmailExceedsMaximumLengthException;
use App\Authentication\Exception\AccountEmailIsAlreadyUsedException;
use App\Authentication\Exception\AccountEmailIsInvalidException;
use App\Authentication\Exception\PasswordRepeatDoesNotMatchException;
use App\Authentication\Exception\PasswordToShortException;
use App\Authentication\Service\AuthenticationService;
use App\Authentication\Service\PasswordService;
use App\Authentication\Validator\RegistrationValueValidator;
use App\Software;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'auth:register',
    description: 'Command for user registration from shell.'
)]
class AuthenticationRegisterCommand extends Command
{

    public function __construct(
        private readonly RegistrationValueValidator $registrationValueValidator,
        private readonly AuthenticationService $authenticationService,
        private readonly PasswordService $passwordService,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $displayPassword = false;
        $questionHelper = new QuestionHelper();
        $emailQuestion = new Question('Email: ');
        $passwordQuestion = new Question('Password: ', '');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        $email = $questionHelper->ask($input, $output, $emailQuestion);
        $password = $questionHelper->ask($input, $output, $passwordQuestion);
        if(empty($password)) {
            $password = $this->passwordService->generatePassword();
            $displayPassword = true;
        }

        $createAccountDto = new CreateAccountDTO($email, $password, $password);
        try {
            $this->registrationValueValidator->validate($createAccountDto);
            $this->authenticationService->register($createAccountDto, true);
            $output->writeln('<info>The account was created successfully.</info>');
            if($displayPassword) {
                $output->writeln('<info>As there was no password entered, we automatically generated one: '.$password.'</info>');
            }
            return Command::SUCCESS;
        } catch (AccountEmailExceedsMaximumLengthException $e) {
            $output->writeln('<error>The entered email address exceeds the maximum accepted by the application: '.Software::MAXIMUM_EMAIL_LENGTH.'</error>');
        } catch (AccountEmailIsInvalidException $e) {
            $output->writeln('<error>The entered email does not have a valid format.</error>');
        } catch (PasswordRepeatDoesNotMatchException $e) {
            $output->writeln('<error>The given passwords do not match.</error>');
        } catch (PasswordToShortException $e) {
            $output->writeln('<error>The entered password is too short. The applications minimum is: '.$_ENV['APP_MINIMUM_PASSWORD_LENGTH'].'</error>');
        } catch (AccountCreationFailedException $e) {
            $output->writeln('<error>The account could not be created, maybe check the error log for more information.</error>');
        } catch (AccountEmailIsAlreadyUsedException $e) {
            $output->writeln("<error>The entered email address is already used by another account.</error>");
        }
        return Command::FAILURE;
    }

}
