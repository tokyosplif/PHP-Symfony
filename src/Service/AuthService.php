<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\AuthRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthService
{
    private AuthRepository $authRepository;
    private LoggerInterface $logger;

    public function __construct(AuthRepository $authRepository, LoggerInterface $logger)
    {
        $this->authRepository = $authRepository;
        $this->logger = $logger;
    }

    public function registerUser(string $username, string $password): bool
    {
        $existingUser = $this->authRepository->findByUsername($username);
        if ($existingUser) {
            return false;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

        $this->authRepository->save($user, true);

        return true;
    }

    public function authenticateUser(string $username, string $password): ?User
    {
        $user = $this->authRepository->findByUsername($username);

        if ($user && password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }

    public function findByUsername(string $username): ?User
    {
        return $this->authRepository->findByUsername($username);
    }

    public function findById(int $id): ?User
    {
        return $this->authRepository->findById($id);
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

