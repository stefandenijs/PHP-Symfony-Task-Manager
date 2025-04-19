<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\ValidatorService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class TaskController extends AbstractController
{
    #[Route('/api/task', name: 'api_task', methods: ['GET', 'HEAD'])]
    #[OA\Get(
        path: '/api/task',
        summary: 'Get all tasks from a User',
        tags: ['Task']
    )]
    public function getTasks(TaskRepository $taskRepository, SerializerInterface $serializer): JsonResponse
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
    public function getTask(TaskRepository $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $user = $this->getUser();
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], status: Response::HTTP_UNAUTHORIZED);
        }

        $response = $serializer->serialize($task, 'json', ['groups' => ['task', 'task_owner']]);

        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/api/task', name: 'api_task_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/task',
        summary: 'Creates a task',
        tags: ['Task']
    )]
    #[OA\RequestBody(
        description: 'Creates a task',
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            type: 'object',
            example: [
                'title' => 'title',
                'description' => 'description',
                'deadline' => 'deadline',
            ]
        )
    )]
    public function createTask(TaskRepository $taskRepository, ValidatorService $validatorService, Request $request): Response|JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setDeadline($deadline);
        $task->setCreatedAt(new \DateTimeImmutable('now'));
        $task->setOwner($user);

        $validationResponse = $validatorService->validate($task, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $taskRepository->createOrUpdate($task);

        return $this->redirect("/api/task/{$task->getId()}");
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
    public function deleteTask(TaskRepository $taskRepository, int $id): JsonResponse
    {
        $user = $this->getUser();

        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_UNAUTHORIZED);
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
            type: 'object',
            example: [
                'title' => 'title',
                'description' => 'description',
                'deadline' => 'deadline',
                'completed' => 'completed',
            ]
        )
    )]
    public function editTask(TaskRepository $taskRepository, ValidatorService $validatorService, int $id, Request $request): Response|RedirectResponse
    {
        $user = $this->getUser();

        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        if ($task->getOwner()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden to access this resource'], Response::HTTP_UNAUTHORIZED);
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
            $task->setComplete($complete);
        }

        $validationResponse = $validatorService->validate($task, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $task->setUpdatedAt(new \DateTimeImmutable('now'));
        $taskRepository->createOrUpdate($task);

        return $this->redirect("/api/task/{$task->getId()}");
    }

}
