<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function registerUser(string $username, string $password): bool
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return false;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    public function authenticateUser(string $username, string $password): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user && password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }

    public function findByUsername(string $username): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    public function findById(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function getUserFromSession(Request $request): ?User
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            $this->logger->warning('Unauthorized access attempt to the cart');
            return null;
        }

        $user = $this->findById($userId);
        if (!$user) {
            $this->logger->warning('User not found with ID: ' . $userId);
            return null;
        }

        return $user;
    }
}
