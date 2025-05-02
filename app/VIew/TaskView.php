<?php

declare(strict_types=1);

namespace App\View;

use Throwable;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * Handles command line output formatting and display
 */
class TaskView {

  private const string SEPARATOR = "-------------------------\n";
  private const string HELP_TEMPLATE = <<<HELP
        Usage: task-cli <command> [arguments]
        
        Task Management Commands:
          add <description>        Add new task
          update <id> "<desc>"     Update task description (use quotes for spaces)
          delete <id>              Permanently remove task
          mark-in-progress <id>   Mark task as being worked on
          mark-done <id>          Mark task as completed
          list [filter]            Display tasks with optional filter
        
        Listing Filters:
          all (default)          Show all tasks
          todo                   Show unstarted tasks
          in-progress           Show active tasks
          done                   Show completed tasks
        
        Examples:
          task-cli add "Review project docs"
          task-cli update 3 "Final document review"
          task-cli list in-progress
        
        HELP;

  /**
   * Display single task details
   * @param array{
   *     id: int,
   *     description: string,
   *     status: string,
   *     createdAt: string,
   *     updatedAt: string
   * } $task
   */
  public function displayTask(array $task): void
  {
    try {
      printf(
        "ID:          %d\n" .
        "Description: %s\n" .
        "Status:      %s\n" .
        "Created:     %s\n" .
        "Updated:     %s\n" .
        self::SEPARATOR,
        $task['id'],
        $task['description'],
        $this->formatStatus($task['status']),
        $this->formatDate($task['createdAt']),
        $this->formatDate($task['updatedAt'])
      );
    } catch (Throwable $e) {
      $this->renderMessage("Invalid task format: " . $e->getMessage(), '⚠️');
    }
  }

  /**
   * Display multiple tasks
   * @param array<int, array> $tasks
   */
  public function displayTasks(array $tasks) : void {
    if (empty($tasks)) {
      $this->renderMessage("No tasks found:", 'ℹ️ ');
      return;
    }

    foreach ($tasks as $task) {
      $this->displayTask($task);
    }

    $this->renderSummary(count($tasks));
  }

  /**
   * Display success message
   */
  public function success(string $message) : void {
    $this->renderMessage($message, '✅');
  }

  /**
   * Display error message and exit
   */
  public function error(string $message) : void {
    $this->renderMessage($message, '❌', true);
    exit(1);
  }

  public function help() : void {
    echo self::HELP_TEMPLATE;
    exit(1);
  }

  /**
   * Format status for display
   */
  private function formatStatus(string $status) : string {
    return match ($status ?? '') {
      'todo' => 'To Do',
      'in-progress' => 'In Progress',
      'done' => 'Done',
      default => ucfirst($status) ?? 'Unknown Status',
    };
  }

  /**
   * Format ISO date for human readability
   */
  private function formatDate(string $iso_date) : string {
    try {
      $date = new DateTimeImmutable($iso_date);
      return $date->format('M j, Y H:i');
    } catch (Throwable) {
      return $this->currentDateFormatted();
    }
  }

  private function currentDateFormatted() : string {
    return (new DateTimeImmutable())->format('M j, Y H:i');
  }

  private function currentDateISO(): string {
    return (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
  }

  /**
   * Render standardized message with icon
   */
  private function renderMessage(
    string $message,
    string $icon,
    bool $is_error = false
  ): void {
    $stream = $is_error ? STDERR : STDOUT;
    fprintf($stream, "%s %s\n",$icon, trim($message));
  }

  /**
   * Display task count summary
   */
  private function renderSummary(int $count) : void {
    $this->renderMessage(
      sprintf("Displaying %d %s", $count, $count === 1 ? 'task' : 'tasks'),
      '📋'
    );
  }
}