<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get the password hasher
$passwordHasher = $container->get('security.password_hasher');

// Get the user repository
$entityManager = $container->get('doctrine.orm.entity_manager');
$userRepository = $entityManager->getRepository(\App\Entity\User::class);

// Find the user
$user = $userRepository->findOneBy(['username' => 'adminmarche']);

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User found: " . $user->getUsername() . "\n";
echo "Email: " . $user->getEmail() . "\n";
echo "User Identifier: " . $user->getUserIdentifier() . "\n";
echo "Password hash in DB: " . substr($user->getPassword(), 0, 20) . "...\n";

// Test password verification
$testPassword = 'password123';
$isValid = $passwordHasher->isPasswordValid($user, $testPassword);

echo "\nPassword verification for 'password123': " . ($isValid ? "SUCCESS" : "FAILED") . "\n";

// Get roles
echo "\nRoles: " . implode(', ', $user->getRoles()) . "\n";

echo "\nDone.\n";
