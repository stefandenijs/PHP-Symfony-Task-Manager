<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepositoryInterface;
use App\Service\ValidatorServiceInterface;
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

final class TagController extends AbstractController
{
    #[Route('/api/tag', name: 'api_tag', methods: ['GET'])]
    #[OA\Get(
        path: '/api/tag',
        summary: 'Get all tags from an user',
        tags: ['Tag']
    )]
    public function getTags(TagRepositoryInterface $tagRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        $tags = $tagRepository->findBy(['creator' => $user->getId()]);
        $response = $serializer->serialize($tags, 'json', ['groups' => ['tag']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/tag/{id}', name: 'api_tag_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/tag/{id}',
        summary: 'Get a tag by ID',
        tags: ['Tag']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the tag',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    public function getTag(Uuid $id, TagRepositoryInterface $tagRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        $tag = $tagRepository->find($id);

        if ($tag === null) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        if ($tag->getCreator()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $response = $serializer->serialize($tag, 'json', ['groups' => ['tag']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/tag', name: 'api_tag_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/tag',
        summary: 'Creates a tag',
        tags: ['Tag']
    )]
    #[OA\RequestBody(
        description: 'Creates a tag',
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'colour'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'colour', type: 'string'),
            ],
            type: 'object',
            example: [
                'title' => 'My new tag',
                'colour' => '#4287f5',
            ]
        )
    )]
    public function createTag(Request $request, TagRepositoryInterface $tagRepository, ValidatorServiceInterface $validatorService): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        $name = $request->getPayload()->get('name');
        $colour = $request->getPayload()->get('colour');

        $newTag = new Tag();
        $newTag->setName($name);
        $newTag->setColour($colour);
        $newTag->setCreator($user);

        $validationResponse = $validatorService->validate($newTag);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $tagRepository->createOrUpdate($newTag);

        return $this->redirectToRoute('api_tag_get', ['id' => $newTag->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/tag/{id}', name: 'api_tag_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/tag/{id}',
        summary: 'Deletes a tag by ID',
        tags: ['Tag']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the tag',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    public function deleteTag(Uuid $id, TagRepositoryInterface $tagRepository): JsonResponse
    {
        $user = $this->getUser();
        $tag = $tagRepository->find($id);

        if ($tag === null) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        if ($tag->getCreator()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $tagRepository->delete($tag);
        return new JsonResponse(['success' => 'Tag deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/api/tag/{id}', name: 'api_tag_edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/tag/{id}',
        summary: 'Updates a tag by ID',
        tags: ['Tag']
    )]
    #[OA\RequestBody(
        description: 'Updates a tag',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'colour', type: 'string'),
            ],
            type: 'object',
            example: [
                'name' => 'My updated tag',
                'colour' => '#52eb34',
            ]
        )
    )]
    public function editTag(Request $request, Uuid $id, TagRepositoryInterface $tagRepository, ValidatorServiceInterface $validatorService): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();
        $tag = $tagRepository->find($id);

        if ($tag === null) {
            return new JsonResponse(['error' => 'Tag not found'], Response::HTTP_NOT_FOUND);
        }

        if ($tag->getCreator()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $name = $request->getPayload()->get('name');
        $colour = $request->getPayload()->get('colour');

        if (!empty($name)) {
            $tag->setTitle($name);
        }
        if (!empty($colour)) {
            $tag->setColour($colour);
        }

        $validationResponse = $validatorService->validate($tag, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $tag->setUpdatedAt(new DateTimeImmutable('now'));
        $tagRepository->createOrUpdate($tag);
        return $this->redirectToRoute('api_tag_get', ['id' => $id], Response::HTTP_SEE_OTHER);
    }
}
