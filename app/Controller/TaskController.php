<?php

declare(strict_types=1);

namespace App\Controller;

use App\View\TaskView;
use App\Model\TaskModel;
use RuntimeException;
use Throwable;

/**
 * Orchestrates task operations between user input and data layer
 */

class TaskController {
  private const COMMAND_USAGE = [
    'add' => '<description>',
    'update' => '<id> "<description>"',
    'delete' => '<id>',
    'mark-in-progress' => '<id>',
    'mark-done' => '<id>',
    'list' => '[filter]'
  ];
  public function __construct(
    private readonly TaskModel $model,
    private readonly TaskView $view,
  ) {
  }

  /**
   * Main command routing method
   * @param array<int, string> $argv CLI arguments
   */
  public function handleCommand(array $argv): void {
    try {
      $command = $argv[1] ?? null;

      match ($command) {
        'add' => $this->handleAdd($argv),
        'update' => $this->handleUpdate($argv),
        'delete' => $this->handleDelete($argv),
        'mark-in-progress', 'mark-done' => $this->handleStatusChange($argv),
        'list' => $this->handleList($argv),
        default => $this->view->help(),
      };
    } catch (Throwable $e) {
      $this->view->error($e->getMessage());
    }
  }

  /**
   * Handle task creation
   * @param array<int, string> $argv
   * @throws RuntimeException
   */
  private function handleAdd(array $argv): void {
    $this->validateArgumentCount($argv, 3);
    $description = $this->sanitizeDescription($argv[2]);

    $task = $this->model->addTask($description);
    $this->view->success("Task added (ID: {$task['id']})");
  }

  /**
   * Handle task update
   * @param array<int, string> $argv
   * @throws RuntimeException
   */
  private function handleUpdate(array $argv): void {
    $this->validateArgumentCount($argv, 4);
    $id = $this->validateAndResolveId($argv[2]);
    $description = $this->sanitizeDescription($argv[3]);

    $task = $this->model->updateTask($id, $description);
    $this->view->success("Task updated (ID: {$task['id']})");
  }

  /**
   * Handle task deletion
   * @param array<int, string> $argv
   * @throws RuntimeException
   */
  private function handleDelete(array $argv): void {
    $this->validateArgumentCount($argv, 3);
    $id = $this->validateAndResolveId($argv[2]);

    $this->model->deleteTask($id);
    $this->view->success("Task deleted (ID: {$id})");
  }

  /**
   * Handle status changes
   * @param array<int, string> $argv
   * @throws RuntimeException
   */
  private function handleStatusChange(array $argv): void {
    $this->validateArgumentCount($argv, 3);
    $id = $this->validateAndResolveId($argv[2]);
    $status = $this->normalizeStatusCommand($argv[1]);

    $task = $this->model->maskTaskStatus($id, $status);
    $this->view->success("Task (ID: {$task['id']}) marked as {$status}");
  }

  /**
   * Handle task listing
   * @param array<int, string> $argv
   */
  private function handleList(array $argv): void {
    $filter = $this->resolveListFilter($argv[2] ?? 'all');
    try {
      $allTasks = $this->model->getAllTasks();

      $filteredTasks = $this->filterTaskByStatus($allTasks, $filter);

      $this->view->displayTasks($filteredTasks);
    } catch (RuntimeException $e) {
      $this->view->error($e->getMessage());
    }
  }

  /**
   * Validate CLI argument count for commands
   * @throws RuntimeException
   */
  private function validateArgumentCount(array $argv, int $required): void {
    if(count($argv) < $required) {
      $command = $argv[1];
      $this->view->error(
        "Invalid arguments for '{$command}'\nUsage: {$command} "
        . self::COMMAND_USAGE[$command]
      );
    }
  }

  /**
   * Convert status command to normalized status value
   */
  private function normalizeStatusCommand(string $command): string {
    return match($command) {
      'mark-in-progress' => 'in-progress',
      'mark-done' => 'done',
      default => throw new RuntimeException("Unknown command '$command'"),
    };
  }

  private function resolveListFilter(?string $filter): string {
    $validFilters = ['all', 'todo', 'in-progress', 'done'];

    if($filter && !in_array($filter, $validFilters, true)) {
      throw new RuntimeException(
        "Invalid filter. Valid options: ". implode(', ', $validFilters)
      );
    }
    return $filter ?? 'all';
  }

  private function filterTaskByStatus(array $tasks, string $status): array {
    if ($status === 'all') {
      return $tasks;
    }

    return array_filter(
      $tasks,
      fn(array $task): bool => $task['status'] === $status
    );
  }

  /***
   * Validate and convert string ID to interger
   * @throws RuntimeException
   */
  private function validateAndResolveId(string $id): int {
    if (!$id || !ctype_digit($id) || (int)$id < 1) {
      throw new RuntimeException("Invalid task ID format");
    }

    $id_int = (int) $id;
    if (!$this->model->getTaskById($id_int)) {
      throw new RuntimeException("Task with id {$id_int} not found");
    }
    var_dump($id_int);
    return $id_int;
  }

  /**
   * Sanitize and validate task description
   * @throws RuntimeException
   */
  private function sanitizeDescription(string $description): string {
    $clean = trim($description);
    if ($clean === '') {
      throw new RuntimeException("Task description can't be empty");
    }
    return $clean;
  }
}