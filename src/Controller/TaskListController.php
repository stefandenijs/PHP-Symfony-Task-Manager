<?php

namespace App\Controller;

use App\Entity\TaskList;
use App\Repository\TaskListRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use App\Service\ValidatorServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class TaskListController extends AbstractController
{
    #[Route('/api/task-list', name: 'api_task_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/task-list',
        summary: 'Get all lists from an user',
        tags: ['List']
    )]
    public function getLists(TaskListRepositoryInterface $taskListRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        $taskLists = $taskListRepository->findBy(['owner' => $user->getId()]);
        $response = $serializer->serialize($taskLists, 'json', ['groups' => ['list', 'task', 'task_owner', 'task_parent']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/task-list/{id}', name: 'api_task_list_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/task-list/{id}',
        summary: 'Get list by ID',
        tags: ['List']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the list',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    public function getListById(TaskListRepositoryInterface $taskListRepository, SerializerInterface $serializer, Uuid $id): Response
    {
        $user = $this->getUser();

        $taskList = $taskListRepository->find($id);
        if (empty($taskList)) {
            return new JsonResponse(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        if ($taskList->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $response = $serializer->serialize($taskList, 'json', ['groups' => ['list', 'task', 'task_owner', 'task_parent']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/task-list', name: 'api_task_list_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/task-list',
        summary: 'Creates a list',
        tags: ['List']
    )]
    #[OA\RequestBody(
        description: 'Creates a list',
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
            ],
            type: 'object',
            example: [
                'name' => 'My new list',
            ]
        )
    )]
    public function createTaskList(Request $request, TaskListRepositoryInterface $taskListRepository, ValidatorServiceInterface $validatorService): Response
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        $taskList = new TaskList();
        $taskList->setName($name);
        $taskList->setOwner($user);

        $validationResponse = $validatorService->validate($taskList, null, ['list']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $taskListRepository->createOrUpdate($taskList);

        return $this->redirectToRoute('api_task_list_get', ['id' => $taskList->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/task-list/{id}', name: 'api_task_list_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/task-list/{id}',
        summary: 'Deletes a list',
        tags: ['List']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the list',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    public function deleteTaskList(TaskListRepositoryInterface $taskListRepository, Uuid $id): Response
    {
        $user = $this->getUser();

        $taskList = $taskListRepository->find($id);
        if (empty($taskList)) {
            return new JsonResponse(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        if ($taskList->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $taskListRepository->delete($taskList);
        return new JsonResponse(['success' => 'Tag deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/api/task-list/{id}', name: 'api_task_list_edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/task-list/{id}',
        summary: 'Updates a list by ID',
        tags: ['List']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the list',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        description: 'Updates a list by ID',
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(
                    property: 'name',
                    description: 'List name',
                    type: 'string',
                ),
                new OA\Property(
                    property: 'taskIds',
                    description: 'Array of task IDs (UUID) to assign',
                    type: 'array',
                    items: new OA\Items(type: 'integer')
                ),
            ],
            type: 'object',
            example: [
                'name' => 'My updated study list',
                'taskIds' => [
                    '550e8400-e29b-41d4-a716-446655440000',
                    '550e8400-e29b-41d4-a716-446655440001',
                    '550e8400-e29b-41d4-a716-446655440002'
                ]
            ]
        )
    )]
    public function editList(Request $request, TaskListRepositoryInterface $taskListRepository, TaskRepositoryInterface $taskRepository, ValidatorServiceInterface $validatorService, Uuid $id): Response
    {
        $user = $this->getUser();
        $taskList = $taskListRepository->find($id);

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;
        $taskIds = $data['taskIds'] ?? [];

        $tasks = $taskRepository->findBy(['id' => $taskIds]);

        if (empty($taskList)) {
            return new JsonResponse(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        if ($taskList->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $taskList->setName($name);
        foreach ($tasks as $task) {
            if ($task->getOwner()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
            }
            $taskList->addTask($task);
        }

        $validationResponse = $validatorService->validate($taskList);
        if ($validationResponse !== null) {
            return new JsonResponse($validationResponse, Response::HTTP_BAD_REQUEST);
        }

        $taskListRepository->createOrUpdate($taskList);

        return $this->redirectToRoute('api_task_list_get', ['id' => $taskList->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/task-list/{id}/tasks', name: 'api_task_list_remove_tasks', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/task-list/{id}/tasks',
        summary: 'Removes tasks by ID from a list',
        tags: ['List']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the list',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        description: 'Removes tasks from a list by ID',
        required: true,
        content: new OA\JsonContent(
            required: ['taskIds'],
            properties: [
                new OA\Property(
                    property: 'taskIds',
                    description: 'Array of task IDs (UUID) to assign',
                    type: 'array',
                    items: new OA\Items(type: 'integer')
                ),
            ],
            type: 'object',
            example: [
                'name' => 'My updated study list',
                'taskIds' => [
                    '550e8400-e29b-41d4-a716-446655440000',
                    '550e8400-e29b-41d4-a716-446655440001',
                    '550e8400-e29b-41d4-a716-446655440002'
                ]
            ]
        )
    )]
    public function removeTasksFromList(Request $request, UUid $id, TaskListRepositoryInterface $taskListRepository, TaskRepositoryInterface $taskRepository): Response
    {
        $user = $this->getUser();
        $taskList = $taskListRepository->find($id);


        if (empty($taskList)) {
            return new JsonResponse(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        if ($taskList->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $taskIds = $data['taskIds'] ?? [];
        $tasks = $taskRepository->findBy(['id' => $taskIds]);

        foreach ($tasks as $task) {
            $taskList->removeTask($task);
        }

        $taskListRepository->createOrUpdate($taskList);

        return $this->redirectToRoute('api_task_list_get', ['id' => $taskList->getId()], Response::HTTP_SEE_OTHER);
    }
}
