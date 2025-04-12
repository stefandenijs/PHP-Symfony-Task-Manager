<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
final class TaskController extends AbstractController
{
    #[Route('/task', name: 'app_task', methods: ['GET', 'HEAD'])]
    public function getTasks(TaskRepository $taskRepository, SerializerInterface $serializer): JsonResponse
    {
        $tasks = $taskRepository->findAll();

        $response = $serializer->serialize($tasks, 'json');

        return JsonResponse::fromJsonString($response, status: 200);
    }

    #[Route('/task/{id}', name: 'app_task_show', methods: ['GET'])]
    public function getTask(TaskRepository $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(["error" => "Task not found"], Response::HTTP_NOT_FOUND);
        }

        $response = $serializer->serialize($task, 'json');


        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/task', name: 'app_task_create', methods: ['POST'])]
    public function createTask(TaskRepository $taskRepository, ValidatorService $validatorService, Request $request): Response|JsonResponse|RedirectResponse
    {
        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setDeadline($deadline);
        $task->setCreatedAt(new \DateTimeImmutable('now'));

        $validationResponse = $validatorService->validate($task, null, ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $taskRepository->createOrUpdate($task);

        return $this->redirect("/task/{$task->getId()}");
    }

    #[Route('/task/{id}', name: 'app_task_delete', methods: ['DELETE'])]
    public function deleteTask(TaskRepository $taskRepository, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(["error" => "Task not found"], Response::HTTP_NOT_FOUND);
        }

        $taskRepository->delete($task);

        return new JsonResponse(["success" => "Task deleted successfully"], Response::HTTP_OK);
    }

    #[Route('/task/{id}', name: 'app_task_update', methods: ['PUT'])]
    public function editTask(TaskRepository $taskRepository, ValidatorService $validatorService, int $id, Request $request): Response|RedirectResponse
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(["error" => "Task not found"], Response::HTTP_NOT_FOUND);
        }

        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');

        if (!is_null($title) && $title !== '') {
            $task->setTitle($title);
        }
        if (!is_null($description) && $description !== '') {
            $task->setDescription($description);
        }
        if (!is_null($deadline) && $deadline !== '') {
            $task->setDeadline(new \DateTime($deadline));
        }

        $validationResponse = $validatorService->validate($task, null,  ['task']);
        if ($validationResponse !== null) {
            return $validationResponse;
        }

        $task->setUpdatedAt(new \DateTimeImmutable('now'));
        $taskRepository->createOrUpdate($task);

        return $this->redirect("/task/{$task->getId()}");
    }

}
