<?php

namespace App\Controller;

use App\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class UserController extends AbstractController
{
    #[Route('/api/user/{id}', name: 'api_user_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/user/{id}',
        summary: 'Gets an user by ID',
        tags: ['User']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the user',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    public function getUserById(Uuid $id, SerializerInterface $serializer, UserRepositoryInterface $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $response = $serializer->serialize($user, 'json', ['groups' => ['user']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/user/', name: 'api_user_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/user/',
        summary: 'Update the current logged in user',
        tags: ['User']
    )]
    #[OA\RequestBody(
        description: 'Updates the current user',
        required: true,
        content: new OA\JsonContent(
            required: ['username'],
            properties: [
                new OA\Property(property: 'username', type: 'string'),
            ],
            type: 'object',
            example: [
                'username' => 'Mike',
            ],
        )
    )]
    public function editUser(Request $request, UserRepositoryInterface $userRepository): JsonResponse|RedirectResponse
    {
        $currentUser = $this->getUser();
        $userToUpdate = $userRepository->find($currentUser->getId());

        if (!$userToUpdate) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Keep the check just in case.
        if ($currentUser->getId() !== $userToUpdate->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;

        if (!empty($username)) {
            $userToUpdate->setUsername($username);
        }

        $userToUpdate->setUpdatedAt(new DateTimeImmutable('now'));
        $userRepository->createOrUpdate($userToUpdate);

        return $this->redirectToRoute('api_user_get', ['id' => $userToUpdate->getId()], Response::HTTP_SEE_OTHER);
    }
}
