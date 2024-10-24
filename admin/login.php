<?php
session_start();

$admin_username = "admin";
$admin_password = password_hash("eva", PASSWORD_DEFAULT);
$admin_page = "admin.php";

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = sanitize_input($_POST["username"]);
    $password = $_POST["password"];

    if ($username === $admin_username && password_verify($password, $admin_password)) {
        $_SESSION["admin_logged_in"] = true;
        header("Location: " . $admin_page);
        exit();
    } else {
        $error_message = "Invalid username or password";
    }
}

// Handle logout
if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

// Check if user is logged in
function is_admin_logged_in() {
    return isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true;
}

// Protect admin page
if (basename($_SERVER["PHP_SELF"]) === "admin.php" && !is_admin_logged_in()) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>
<body>
    <?php if (!is_admin_logged_in()): ?>
        <h2>Admin Login</h2>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" name="login" value="Login">
        </form>
    <?php else: ?>
        <p>You are logged in. <a href="?logout">Logout</a></p>
    <?php endif; ?>
</body>
</html>