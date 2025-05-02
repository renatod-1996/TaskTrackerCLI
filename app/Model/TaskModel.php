<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use RuntimeException;

/**
 * Handles task data persistence and operations
 */
class TaskModel {
  private const FILE_NAME = 'tasks.json';
  private const array VALID_STATUSES = ['todo', 'in-progress', 'done'];

  /**
   * Initialize task storage
   * @throws RuntimeException If file creation fails
   */
  public function __construct() {
    if(!file_exists(self::FILE_NAME)) {
      $this->initializeStorage();
    }
  }

  /**
   * Retrieve all tasks from storage
   * @return array<int, array{
   *   id: int,
   *   description: string,
   *   status: string,
   *   createdAt: string,
   *   updateAt: string
   * }>
   * @throws JsonException If JSON decoding fails
   */
  public function getAllTasks() : array {

    if (!file_exists(self::FILE_NAME)) {
      return [];
    }

    $content = file_get_contents(self::FILE_NAME);

    if($content === false) {
      throw new RuntimeException('Unable to read file ' . self::FILE_NAME);
    }

    try {
      $tasks = json_decode($content, true,512, JSON_THROW_ON_ERROR);

    } catch (JsonException) {
      return [];
    }

    return array_filter($tasks, function($task) {

      //Validate array structure
      if(!is_array($task)) {
        return false;
      }

      //Check required keys exist
      $requiredKeys = ['id', 'description', 'status', 'createdAt', 'updatedAt'];
      $taskKeys = array_keys($task);
      if(count(array_intersect($requiredKeys, $taskKeys)) !== count($requiredKeys)) {
        return false;
      }

      $validTypes = is_int($task['id'])
        && is_string($task['description'])
        && is_string($task['status']);

      $validStatuses = in_array($task['status'], self::VALID_STATUSES, true);

      $validCreatedAt = DateTimeImmutable::createFromFormat(
        DateTimeImmutable::ATOM,
        $task['createdAt']
      ) !== false;

      $validUpdatedAt = DateTimeImmutable::createFromFormat(
          DateTimeImmutable::ATOM,
          $task['updatedAt']
        ) !== false;
      return $validTypes && $validStatuses && $validCreatedAt && $validUpdatedAt;

    });
  }

  /**
   * Save tasks to storage
   * @param array<int, array> $tasks
   * @throws JsonException If JSON encoding fails
   */
  public function saveTasks(array $tasks): void {
    $result = file_put_contents(
      self::FILE_NAME,
      json_encode(
        value: array_values($tasks),
        flags: JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
      )
    );

    if($result === false) {
      throw new RuntimeException('Unable to write file ' . self::FILE_NAME);
    }
  }

  /**
   * Find a task by ID
   * @return array{
   *  id: int,
   *  description: string,
   *  status: string,
   *  createdAt: string,
   *  updatedAt: string
   * }|null
   * @throws JsonException
   */
  public function getTaskById(int $id): ?array {
    $tasks = $this->getAllTasks();
    foreach ($tasks as $task) {
      if ($task['id'] === $id) {
        return $task;
      }
    }
    return null;
  }

  /**
   * Add new task
   * @throws JsonException|RuntimeException
   */
  public function addTask(string $description): array {

    $description = trim((string) $description);
    if ($description === '') {
      throw new RuntimeException('Description cannot be empty');
    }

    $tasks = $this->getAllTasks();
    $newTask = [
      'id' => $this->getNextId($tasks),
      'description' => $description,
      'status' => 'todo',
      'createdAt' => $this->currentDate(),
      'updatedAt' => $this->currentDate(),
    ];

    $tasks[] = $newTask;
    $this->saveTasks($tasks);

    return $newTask;
  }

  /**
   * Update an existing task
   * @throws \JsonException
   */

  public function updateTask(int $id, string $description): array {
    $tasks = $this->getAllTasks();
    $found = false;

    foreach ($tasks as &$task) {
      if ($task['id'] === $id) {
        $task['description'] = $description;
        $task['updatedAt'] = $this->currentDate();
        $found = true;
        break;
      }
    }

    if(!$found) {
      throw new RuntimeException("Task $id not found");
    }

    $this->saveTasks($tasks);
    return $this->getTaskById($id);
  }

  /**
   * Delete a task by ID
   * @throws JsonException|RuntimeException
   */
  public function deleteTask(int $id): void {
    $tasks = array_filter(
      $this->getAllTasks(),
      fn($tasks) => $tasks['id'] !== $id
    );
    if (count($tasks) === count($this->getAllTasks())) {
      throw new RuntimeException("Task $id not found");
    }

    $this->saveTasks($tasks);
  }

  /**
   * Update task status
   * @throws \JsonException
   */
  public function maskTaskStatus(int $id, string $status): array {
    if(!in_array($status, self::VALID_STATUSES, true)) {
      throw new RuntimeException("Invalid status: $status");
    }

    $tasks = $this->getAllTasks();
    $found = false;

    foreach ($tasks as &$task) {
      if ($task['id'] === $id) {
        $task['status'] = $status;
        $task['updatedAt'] = $this->currentDate();
        $found = true;
        break;
      }
    }
    if(!$found) {
      throw new RuntimeException("Task $id not found");
    }
    $this->saveTasks($tasks);
    return $this->getTaskById($id);
  }

  /**
   * Calculate next available ID
   */
  private function getNextId(array $tasks) : int {
    if (empty($tasks)) {
      return 1;
    }
    return max(array_column($tasks, 'id')) + 1;
  }

  /**
   * Get current date in ISO 8601 format
   */
  private function currentDate(): string {
    return (new DateTimeImmutable())->format(\DateTime::ATOM);
  }

  /**
   * Initialize task storage file
   * @throws RuntimeException If file creation fails
   */
  private function initializeStorage(): void {
    $result = file_put_contents(self::FILE_NAME,'[]');
    if($result === false) {
      throw new RuntimeException(
        "Cannot write to file " . self::FILE_NAME
      );
    }
  }
}