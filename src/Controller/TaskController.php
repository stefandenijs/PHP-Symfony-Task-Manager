<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

final class TaskController extends AbstractController
{
    #[Route('/task', name: 'app_task', methods: ['GET', 'HEAD'])]
    public function index(TaskRepository $taskRepository, SerializerInterface $serializer): JsonResponse
    {
        $tasks = $taskRepository->findAll();

        $response = $serializer->serialize($tasks, 'json');

        return JsonResponse::fromJsonString($response, status: 200);

//        return $this->json([
//            'message' => 'Welcome to your new controller!',
//            'path' => 'src/Controller/TaskController.php',
//        ]);
    }

    #[Route('/task/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(TaskRepository $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);

        if (!$task) {
            return new JsonResponse(data: ["error" => "Task not found"], status: 404);
        }

        $response = $serializer->serialize($task, 'json');


        return JsonResponse::fromJsonString($response, status: 200);
    }

    #[Route('/task', name: 'app_task_create', methods: ['POST'])]
    public function new(TaskRepository $taskRepository): RedirectResponse
    {
        $task = new Task();
        $task->setTitle('New task');
        $task->setDescription('New task');
        $deadline = new \DateTime('now');
        $deadline->modify('+1 day');
        $task->setDeadline($deadline);
        $task->setCreatedAt(new \DateTimeImmutable('now'));

        $taskRepository->create($task);

        return $this->redirect("/task/{$task->getId()}");
    }

    #[Route('/task/{id}', name: 'app_task_delete', methods: ['DELETE'])]
    public function delete(TaskRepository $taskRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(data: ["error" => "Task not found"], status: 404);
        }

        $taskRepository->delete($task);

        return new JsonResponse(data: ["success" => "Task deleted successfully"], status: 200);
    }

    #[Route('/task/{id}', name: 'app_task_update', methods: ['PUT'])]
    public function edit(TaskRepository $taskRepository, SerializerInterface $serializer, int $id, Request $request): JsonResponse|RedirectResponse
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(data: ["error" => "Task not found"], status: 404);
        }

        $title = $request->getPayload()->get('title');
        $description = $request->getPayload()->get('description');

        if (!is_null($title) && $title !== '') {
            $task->setTitle($title);
        }

        if (!is_null($description) && $description !== '') {
            $task->setDescription($description);
        }

        $task->setUpdatedAt(new \DateTimeImmutable('now'));
        $taskRepository->update($task);

        return $this->redirect("/task/{$task->getId()}");
    }

}
