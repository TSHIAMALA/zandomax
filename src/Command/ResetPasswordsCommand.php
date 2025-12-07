<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-passwords',
    description: 'Reset default user passwords',
)]
class ResetPasswordsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = [
            'superadmin@zando.local' => 'superadmin',
            'adminmarche@zando.local' => 'adminmarche',
            'jean.kasongo@example.com' => 'merchant',
        ];

        foreach ($users as $email => $password) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if ($user) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $io->success(sprintf('Password reset for %s (password: %s)', $email, $password));
            } else {
                $io->warning(sprintf('User not found: %s', $email));
            }
        }

        $this->entityManager->flush();
        $io->success('All passwords have been reset!');

        return Command::SUCCESS;
    }
}
