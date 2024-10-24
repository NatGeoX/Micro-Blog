<?php

session_start();
require_once 'db/db_connect.php';

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: login.php');
        exit();
    }

    // Validate input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please fill in both fields.';
        header('Location: login.php');
        exit();
    }

    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a session
                session_regenerate_id(true); // Prevent session fixation

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;

                // Redirect to the main page
                header('Location: index.php');
                exit();
            } else {
                // Invalid password
                $_SESSION['error'] = 'Invalid username or password.';
                header('Location: login.php');
                exit();
            }
        } else {
            // User not found
            $_SESSION['error'] = 'Invalid username or password.';
            header('Location: login.php');
            exit();
        }
    } catch (Exception $e) {
        // In production, log the error instead of displaying
        // error_log($e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again later.';
        header('Location: login.php');
        exit();
    }
} else {
    // Invalid request method
    $_SESSION['error'] = 'Invalid request.';
    header('Location: login.php');
    exit();
}
?>
