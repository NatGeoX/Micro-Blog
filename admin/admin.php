<?php
session_start();
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: ../login.php"); // Redirect to login page if not logged in
    exit();
}
$servername = "localhost";
$username = "root";
$password = "eva";
$dbname = "diary";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deletion of entries
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM diary_entries WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Entry deleted');</script>";
    } else {
        echo "Error deleting entry: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all entries
$stmt = $conn->prepare("SELECT * FROM diary_entries ORDER BY entry_date DESC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Manage Entries</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Manage Diary Entries</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Mood</th>
                <th>Entry</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo date("l, d F Y H:i", strtotime($row['entry_date'])); ?></td>
                <td><?php echo $row['mood_emoji']; ?></td>
                <td><?php echo $row['text_entry']; ?></td>
                <td>
                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
