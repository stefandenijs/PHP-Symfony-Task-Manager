<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TaskController extends AbstractController
{
    #[Route('/task', name: 'app_task', methods: ['GET', 'HEAD'])]
    public function index(TaskRepository $taskRepository, SerializerInterface $serializer): JsonResponse
    {
        $tasks = $taskRepository->findAll();

        $response = $serializer->serialize($tasks, 'json');

        return JsonResponse::fromJsonString($response, status: 200);
    }

    #[Route('/task/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(TaskRepository $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(["error" => "Task not found"], Response::HTTP_NOT_FOUND);
        }

        $response = $serializer->serialize($task, 'json');


        return JsonResponse::fromJsonString($response, Response::HTTP_OK);
    }

    #[Route('/task', name: 'app_task_create', methods: ['POST'])]
    public function new(TaskRepository $taskRepository, ValidatorInterface $validator, Request $request): Response|RedirectResponse
    {
        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');
        $deadline = $request->getPayload()->get('deadline');

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setDeadline($deadline);
        $task->setCreatedAt(new \DateTimeImmutable('now'));

        $errors = $validator->validate($task, null, ['task']);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new Response($errorsString, Response::HTTP_BAD_REQUEST);
        }

        $taskRepository->create($task);

        return $this->redirect("/task/{$task->getId()}");
    }

    #[Route('/task/{id}', name: 'app_task_delete', methods: ['DELETE'])]
    public function delete(TaskRepository $taskRepository, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(["error" => "Task not found"], Response::HTTP_NOT_FOUND);
        }

        $taskRepository->delete($task);

        return new JsonResponse(["success" => "Task deleted successfully"], Response::HTTP_OK);
    }

    #[Route('/task/{id}', name: 'app_task_update', methods: ['PUT'])]
    public function edit(TaskRepository $taskRepository, ValidatorInterface $validator, int $id, Request $request): Response|RedirectResponse
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

        $errors = $validator->validate($task, null);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new Response($errorsString, Response::HTTP_BAD_REQUEST);
        }

        $task->setUpdatedAt(new \DateTimeImmutable('now'));
        $taskRepository->update($task);

        return $this->redirect("/task/{$task->getId()}");
    }

}
