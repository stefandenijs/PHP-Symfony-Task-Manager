<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepository, ValidatorService $validatorService, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $password = $data['password'];
        $email = $data['email'];
        $username = $data['username'];

        $userExists = $userRepository->findOneBy(['email' => $email]);
        if ($userExists) {
            return new JsonResponse(["error" => "Email already in use"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRawPassword($password);

        $validationResponse = $validatorService->validate($user);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getRawPassword()));
        $user->setRoles(['ROLE_USER']);

        $userRepository->create($user);

        return new JsonResponse(['message' => 'User successfully registered'], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse(['message' => 'You are logged in', 'user' => ['username' => $user->getUsername(), 'email' => $user->getUserIdentifier()]], Response::HTTP_OK);
    }
}
