<?php
require __DIR__ . '/db.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required';
    }
    if ($email === '') {
        $errors[] = 'Email is required';
    }
    if ($password === '') {
        $errors[] = 'Password is required';
    }
    if ($confirm === '') {
        $errors[] = 'Confirm password is required';
    }
    if ($password !== '' && $confirm !== '' && $password !== $confirm) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, name, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$email, $name, $hash]);

            // log the user in: the id comes from MySQL
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['name'] = $name;

            header('Location: todos.php');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                $errors[] = 'Email Already Exists';
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5" style="max-width: 420px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4 text-center">Register 🌷</h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" id="register-form" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>

                <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>

    <script src="validate.js"></script>
</body>
</html>