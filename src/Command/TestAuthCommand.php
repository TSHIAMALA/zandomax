<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-auth',
    description: 'Test user authentication',
)]
class TestAuthCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testCases = [
            ['username' => 'superadmin', 'password' => 'superadmin'],
            ['username' => 'adminmarche', 'password' => 'adminmarche'],
            ['username' => 'jean.marchand', 'password' => 'merchant'],
        ];

        $io->title('Testing Authentication');

        foreach ($testCases as $test) {
            $user = $this->userRepository->findOneBy(['username' => $test['username']]);
            
            if (!$user) {
                $io->error(sprintf('User not found: %s', $test['username']));
                continue;
            }

            $isValid = $this->passwordHasher->isPasswordValid($user, $test['password']);
            
            if ($isValid) {
                $io->success(sprintf('✓ %s - Authentication OK', $test['username']));
            } else {
                $io->error(sprintf('✗ %s - Authentication FAILED', $test['username']));
            }

            $io->writeln(sprintf('  Email: %s', $user->getEmail()));
            $io->writeln(sprintf('  Enabled: %s', $user->isEnabled() ? 'Yes' : 'No'));
            $io->writeln(sprintf('  Deleted: %s', $user->isDeleted() ? 'Yes' : 'No'));
            $io->writeln('');
        }

        return Command::SUCCESS;
    }
}
