<?php
require __DIR__ . '/db.php';

// R3: only logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];   // always from the session, never from a form
$userName = $_SESSION['name'];

$errors = [];

// Add a to-do
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $title = trim($_POST['title'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Title is too long (200 characters at most)';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO todos (user_id, title) VALUES (?, ?)');
        $stmt->execute([$userId, $title]);

        header('Location: todos.php');
        exit;
    }
}

// Edit the title
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $todoId = (int) ($_POST['todo_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Title is too long (200 characters at most)';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE todos SET title = ? WHERE todo_id = ? AND user_id = ?'
        );
        $stmt->execute([$title, $todoId, $userId]);

        header('Location: todos.php');
        exit;
    }
}

// Mark done / not done
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $todoId = (int) ($_POST['todo_id'] ?? 0);
    $isDone = ($_POST['is_done'] ?? '0') === '1' ? 1 : 0;

    $stmt = $pdo->prepare(
        'UPDATE todos SET is_done = ? WHERE todo_id = ? AND user_id = ?'
    );
    $stmt->execute([$isDone, $todoId, $userId]);

    header('Location: todos.php');
    exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $todoId = (int) ($_POST['todo_id'] ?? 0);

    $stmt = $pdo->prepare('DELETE FROM todos WHERE todo_id = ? AND user_id = ?');
    $stmt->execute([$todoId, $userId]);

    header('Location: todos.php');
    exit;
}

// Show the list of the logged-in user, newest first
$stmt = $pdo->prepare(
    'SELECT todo_id, title, is_done, created_at
     FROM todos WHERE user_id = ? ORDER BY todo_id DESC'
);
$stmt->execute([$userId]);
$todos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>To-Do</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4" style="max-width: 720px;">

        <!-- Header: title, user name, logout -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">My To-Do ✨</h1>
            <div class="d-flex align-items-center gap-2">
                <span>Hi, <?= htmlspecialchars($userName) ?></span>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm">Log out</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Add form -->
                <form method="POST" action="todos.php" class="title-form" novalidate>
                    <input type="hidden" name="action" value="add">
                    <div class="input-group mb-4">
                        <input type="text" class="form-control" name="title"
                               placeholder="What needs to be done?">
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>

                <!-- The list -->
                <?php if (empty($todos)): ?>
                    <p class="text-center text-muted my-4">No to-dos yet. Add your first one!</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($todos as $todo): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex flex-wrap align-items-start gap-2">

                                    <!-- done / not done -->
                                    <form method="POST" action="todos.php">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="todo_id" value="<?= (int) $todo['todo_id'] ?>">
                                        <input type="hidden" name="is_done" value="<?= $todo['is_done'] ? '0' : '1' ?>">
                                        <button type="submit"
                                                class="btn btn-sm <?= $todo['is_done'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                                                title="<?= $todo['is_done'] ? 'Mark as not done' : 'Mark as done' ?>">
                                            <?= $todo['is_done'] ? '✔' : '○' ?>
                                        </button>
                                    </form>

                                    <!-- edit the title -->
                                    <form method="POST" action="todos.php" class="title-form flex-grow-1" novalidate>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="todo_id" value="<?= (int) $todo['todo_id'] ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="title"
                                                   class="form-control <?= $todo['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>"
                                                   value="<?= htmlspecialchars($todo['title']) ?>">
                                            <button type="submit" class="btn btn-outline-primary">Save</button>
                                        </div>
                                    </form>

                                    <!-- delete (asks first) -->
                                    <form method="POST" action="todos.php"
                                          onsubmit="return confirm('Are you sure you want to delete this to-do?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="todo_id" value="<?= (int) $todo['todo_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>

                                </div>
                                <small class="text-muted d-block mt-1">
                                    Added: <?= htmlspecialchars(date('d M Y, H:i', strtotime($todo['created_at']))) ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="validate.js"></script>
</body>
</html>