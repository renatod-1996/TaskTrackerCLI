#!/usr/bin/env php
<?php

declare(ticks = 1);

/**
 * Task CLI - Command Line Task Manager
 *
 * @version 1.0.0
 * @author J. Renato De La Rosa Mtz.
 * @license MIT
 */

require __DIR__ . '/vendor/autoload.php';

use App\View\TaskView;
use App\Model\TaskModel;
use App\Controller\TaskController;

// Validate execution enviroment
if (PHP_SAPI !== 'cli') {
  die('CLI only');
}

try {
  // Bootstrap aplication
  $dependencies = bootstrap();
  $controller = $dependencies['controller'];

  if ($argc < 2) {
    $controller->handleCommand(['', 'help']);
    exit(0);
  }

  $controller->handleCommand($argv);
  exit(0);
} catch (Throwable $e) {
  handleFatalError($e);
}

/**
 * Initialize application dependencies
 * @return array{
 *   model: TaskModel,
 *   view: TaskView,
 *   controller: TaskController
 * }
 * @throws RuntimeException
 */
function bootstrap(): array {
  try {
    $model = new TaskModel();
    $view = new TaskView();
    return [
      'model' => $model,
      'view' => $view,
      'controller' => new TaskController($model, $view),
    ];
  } catch(Throwable $e) {
    throw new RuntimeException(
      'Failed to initialize application: ' . $e->getMessage(),
      0,
      $e
    );
  }
}

/**
 * Handle critical application errors
 */
function handleFatalError(Throwable $e): never
{
  $message = sprintf(
    "CRITICAL ERROR: %s\nFile: %s:%d\n",
    $e->getMessage(),
    $e->getFile(),
    $e->getLine()
  );

  fwrite(STDERR, $message);
  exit(1);
}
