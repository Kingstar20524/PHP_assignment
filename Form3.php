<?php
session_start();

if (isset($_SESSION['student_id'])) {
    header("Location: marks.php");
    exit();
}
if (isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['student_id']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        // Check if admin
        $admin_user = "admin";
        $admin_pass = "admin123";

        if ($username == $admin_user && $password == $admin_pass) {
            $_SESSION['admin'] = true;
            header("Location: admin.php");
            exit();
        }

        // Otherwise check student in DB
        $conn = mysqli_connect("localhost", "root", "", "school_db");

        if (!$conn) {
            $error = "Database connection failed.";
        } else {
            $username = mysqli_real_escape_string($conn, $username);
            $password = mysqli_real_escape_string($conn, $password);

            $query  = "SELECT * FROM students WHERE student_id = '$username' AND password = '$password'";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $_SESSION['student_id']   = $row['student_id'];
                $_SESSION['student_name'] = $row['name'];
                header("Location: marks.php");
                exit();
            } else {
                $error = "Invalid ID or password.";
            }

            mysqli_close($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>
    <link rel="stylesheet" href="style3.css">
</head>
<body>

    <div class="login-box">

        <div class="login-header">
            <img src="https://cdn-icons-png.flaticon.com/512/201/201614.png" alt="Logo" class="logo">
            <h2>Student Portal</h2>
            <p>Sign in with your Student ID or Admin credentials</p>
        </div>

        <?php if ($error != ""): ?>
            <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="student_id">Student ID / Username</label>
                <input type="text" id="student_id" name="student_id" placeholder="Enter your ID or username"
                    value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>

        </form>

        <p class="footer-text">Having trouble? Contact your class teacher.</p>

    </div>

</body>
</html>