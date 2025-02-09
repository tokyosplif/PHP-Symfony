<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class AuthController extends AbstractController
{
    private AuthService $userService;
    private LoggerInterface $logger;

    public function __construct(AuthService $userService, LoggerInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'register_form', methods: ['GET'])]
    public function showRegisterForm(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if (empty($username) || empty($password)) {
            $this->addFlash('error', 'Username and password are required.');
            return $this->redirectToRoute('register_form');
        }

        $isRegistered = $this->userService->registerUser($username, $password);

        if (!$isRegistered) {
            $this->addFlash('error', 'Username is already taken.');
            return $this->redirectToRoute('register_form');
        }

        $user = $this->userService->findByUsername($username);
        if ($user) {
            $session = $request->getSession();
            $session->set('user_id', $user->getId());

            $this->logger->info('New user registered and logged in', ['user_id' => $user->getId()]);

            $this->addFlash('success', 'Registration successful. Welcome!');
            return $this->redirectToRoute('main');
        }

        $this->addFlash('error', 'Automatic login failed.');
        return $this->redirectToRoute('login_form');
    }

    #[Route('/login', name: 'login_form', methods: ['GET'])]
    public function showLoginForm(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');

        $user = $this->userService->authenticateUser($username, $password);

        if ($user) {
            $session = $request->getSession();
            $session->set('user_id', $user->getId());

            $this->logger->info('User logged in', ['user_id' => $user->getId()]);

            $this->addFlash('success', 'Login successful.');
            return $this->redirectToRoute('main');
        }

        $this->addFlash('error', 'Invalid username or password.');
        return $this->redirectToRoute('login_form');
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->clear();

        $this->addFlash('success', 'You have logged out.');
        return $this->redirectToRoute('login_form');
    }

    #[Route('/main', name: 'main', methods: ['GET'])]
    public function mainPage(Request $request): Response
    {
        $user = $this->userService->getUserFromSession($request);

        if (!$user) {
            $this->addFlash('error', 'You must log in.');
            return $this->redirectToRoute('login_form');
        }

        return $this->render('main.html.twig', [
            'user' => $user,
        ]);
    }
}
