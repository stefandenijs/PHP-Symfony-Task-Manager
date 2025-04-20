<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Service\ValidatorServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

// TODO: Add response documentation.
final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        description: 'Register an account',
        summary: 'Register an account',
        tags: ['Auth'],
    )]
    #[OA\RequestBody(
        description: 'Data used to register',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password', 'username'],
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'username', type: 'string'),
            ],
            type: 'object',
            example: [
                'email' => 'mail@example.com',
                'password' => 'yourPassword123@',
                'username' => 'Bob',
            ]
        )
    )]
    public function register(Request $request, UserRepositoryInterface $userRepository, ValidatorServiceInterface $validatorService, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPlainPassword($password);

        $validationResponse = $validatorService->validate($user, null, null);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $userExists = $userRepository->findOneBy(['email' => $email]);
        if ($userExists) {
            return new JsonResponse(["error" => "Email already in use"], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->setRoles(['ROLE_USER']);

        $userRepository->createOrUpdate($user);

        return new JsonResponse(['message' => 'User successfully registered'], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        description: 'Log in',
        summary: 'Log in to your account',
        tags: ['Auth'],
    )]
    #[OA\RequestBody(
        description: 'Credentials used to login',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
            ],
            type: 'object',
            example: [
                'email' => 'mail@example.com',
                'password' => 'yourPassword123@',
            ]
        )
    )]
    public function login(Request $request, UserRepositoryInterface $userRepository, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTTokenManager, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($email) || empty($password)) {
            if (empty($email)) {
                return new JsonResponse(['error' => 'email', "message" => "Email is required"], Response::HTTP_BAD_REQUEST);
            }
            if (empty($password)) {
                return new JsonResponse(["error" => "password", "message" => "Password is required"], Response::HTTP_BAD_REQUEST);
            }
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(["error" => "authentication", "message" => "Invalid credentials"], Response::HTTP_UNAUTHORIZED);
        }

        $token = $JWTTokenManager->create($user);
        $userData = json_decode($serializer->serialize($user, 'json', ['groups' => ['user']]), true);
        $responseData = ['token' => $token, 'message' => 'User logged in successfully', 'user' => $userData];

        return new JsonResponse($responseData, Response::HTTP_OK);
    }
}
