<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nat's Blog - Sign In</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="ascii-art">
<?php
echo <<<EOD
 _   _       _   
| \ | | __ _| |_
|  \| |/ _` | __|
| |\  | (_| | |_
|_| \_|\__,_|\__|
                                            
EOD;
?>
        </div>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error">'.htmlspecialchars($_SESSION['error']).'</div>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="authenticate.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="username">Username</label><br>
            <input type="text" id="username" name="username" required><br>

            <label for="password">Password</label><br>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
