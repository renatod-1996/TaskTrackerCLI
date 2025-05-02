
# Task Tracker
Task tracker is a project used to track and manage your tasks. In this project I built a simple command line interface (CLI) to track what you need to do, what you have done, and what you are currently working on. This project helped me practice my programming skills, including working with the file system, handling user input, and building a simple CLI application.

## Requirements
The application runs from the command line, accepts actions and user input as arguments and stores the tasks in a JSON file. The user is able to:
- Add, update and delete tasks
- Mark a task as in progress or done
- List all tasks
- List all completed tasks
- List all pending tasks
- List all tasks in progress

## Example
The list of commands and their usage is given below:

```bash
  # Adding a new task
    php task-cli.php add "Buy groceries"
  # Output: Task added successfully (ID: 1)

  # Updating and deleting tasks
    php task-cli.php update 1 "Buy groceries and cook dinner"
    php task-cli.php delete 1

  # Marking a task as in progress or done
    php task-cli.php mark-in-progress 1
    php task-cli.php mark-done 1

  # Listing all tasks
    php task-cli.php list

  # Listing tasks by status
    php task-cli.php list done
    php task-cli.php list todo
    php task-cli.php list in-progress
```

## Task properties
Each task has the following properties:
- id: A unique identifier for the task
- description: A brief description of the task
- status: The status of the task (pending, in progress, done)
- createdAt: Date and time the task was created
- updatedAt: Date and time of the last update of the task

## 
At the end of this project, I developed a practical tool that can help you or others manage tasks efficiently. This project lays a solid foundation for more advanced programming projects and real-world applications.

This project was done with PHP 8.3.20.

