<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepositoryInterface;
use App\Service\ValidatorServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

// TODO: Add response documentation.
final class TaskController extends AbstractController
{
    #[Route('/api/task', name: 'api_task', methods: ['GET'])]
    #[OA\Get(
        path: '/api/task',
        summary: 'Get all tasks from an user',
        tags: ['Task']
    )]
    public function getTasks(TaskRepositoryInterface $taskRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        $tasks = $taskRepository->findBy(['owner' => $user->getId()]);
        $response = $serializer->serialize($tasks, 'json', ['groups' => ['task', 'task_owner']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/task/{id}', name: 'api_task_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/task/{id}',
        summary: 'Get a task by ID',
        tags: ['Task']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the task',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    public function getTask(TaskRepositoryInterface $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $user = $this->getUser();
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], status: Response::HTTP_FORBIDDEN);
        }

        $response = $serializer->serialize($task, 'json', ['groups' => ['task_single', 'task_owner']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/task', name: 'api_task_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/task',
        summary: 'Creates a task',
        tags: ['Task']
    )]
    #[OA\QueryParameter(
        name: 'parent',
        description: 'ID of the task parent, this creates a subtask',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Creates a task',
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title' ,type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'deadline', type: 'string', format: 'date-time'),
            ],
            type: 'object',
            example: [
                'title' => 'My new task',
                'description' => 'A new task that is quite a lot to do.',
                'deadline' => '2000-01-01T20:31:27+02:00',
            ]
        )
    )]
    public function createTask(TaskRepositoryInterface $taskRepository, ValidatorServiceInterface $validatorService, Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');
        $parentId = $request->query->get('parent');

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setDeadline($deadline ? new \DateTime($deadline) : null);
        $task->setCreatedAt(new \DateTimeImmutable('now'));
        $task->setOwner($user);

        $validationResponse = $validatorService->validate($task, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        if (!empty($parentId)) {
            $parent = $taskRepository->find($parentId);
            if ($parent === null) {
                return new JsonResponse(['error' => 'Parent task not found'], Response::HTTP_NOT_FOUND);
            }
            if ($parent->getOwner()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
            }
            $task->setParent($parent);
        }

        $taskRepository->createOrUpdate($task);

        return $this->redirectToRoute('api_task_get', ['id' => $task->getId()], 303);
    }

    #[Route('/api/task/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/task/{id}',
        summary: 'Deletes a task by ID',
        tags: ['Task']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the task',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    public function deleteTask(TaskRepositoryInterface $taskRepository, int $id): JsonResponse
    {
        $user = $this->getUser();

        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $taskRepository->delete($task);

        return new JsonResponse(['success' => 'Task deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/api/task/{id}', name: 'api_task_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/task/{id}',
        summary: 'Updates a task by ID',
        tags: ['Task']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID of the task',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Update a task',
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title' ,type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'deadline', type: 'string', format: 'date-time'),
                new OA\Property(property: 'completed', type: 'boolean'),
            ],
            type: 'object',
            example: [
                'title' => 'My updated task',
                'description' => 'An updated task that is quite a lot to do.',
                'deadline' => '2000-01-01T20:31:27+02:00',
                'completed' => 'true',
            ],
        )
    )]
    public function editTask(TaskRepositoryInterface $taskRepository, ValidatorServiceInterface $validatorService, int $id, Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');
        $complete = $request->getPayload()->get('completed');

        if (!empty($title)) {
            $task->setTitle($title);
        }
        if (!empty($description)) {
            $task->setDescription($description);
        }
        if (!empty($deadline)) {
            $task->setDeadline(new \DateTime($deadline));
        }
        if (!empty($complete)) {
            $task->setCompleted($complete);
        }

        $validationResponse = $validatorService->validate($task, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $task->setUpdatedAt(new \DateTimeImmutable('now'));
        $taskRepository->createOrUpdate($task);

        return $this->redirectToRoute('api_task_get', ['id' => $task->getId()], 303);
    }

}
