<?php
// admin_login.php

require_once 'db.php';


session_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate admin credentials (replace this with your actual validation logic)
    if ($username === 'admin' && $password === 'velmihodnotnymafian') {
        // Successful login
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        // Invalid login
        echo 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>
    <!-- Add your styles or link to an external stylesheet here -->
</head>

<body>
    <h1>Admin Login</h1>

    <?php
    if (isset($login_error)) {
        echo '<p style="color: red;">' . $login_error . '</p>';
    }
    ?>

    <form method="post" action="admin_login.php">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <button type="submit" name="login">Login</button>
    </form>
</body>

</html>
<?php include 'footer.php'; ?>