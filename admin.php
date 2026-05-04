<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: Form3.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "admin");
if (!$conn) {
    die("Database connection failed.");
}

$success = "";
$error   = "";

// ---- ADD STUDENT ----
if (isset($_POST['add_student'])) {
    $sid  = mysqli_real_escape_string($conn, trim($_POST['student_id']));
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $pass = mysqli_real_escape_string($conn, trim($_POST['password']));

    $check = mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$sid'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Student ID already exists.";
    } else {
        $q = "INSERT INTO students (student_id, name, password) VALUES ('$sid', '$name', '$pass')";
        if (mysqli_query($conn, $q)) $success = "Student added successfully.";
        else $error = "Failed to add student.";
    }
}

// ---- DELETE STUDENT ----
if (isset($_GET['delete_student'])) {
    $sid = mysqli_real_escape_string($conn, $_GET['delete_student']);
    mysqli_query($conn, "DELETE FROM marks WHERE student_id = '$sid'");
    mysqli_query($conn, "DELETE FROM students WHERE student_id = '$sid'");
    $success = "Student deleted successfully.";
}

// ---- ADD MARKS ----
if (isset($_POST['add_marks'])) {
    $sid      = mysqli_real_escape_string($conn, trim($_POST['student_id']));
    $subject  = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $obtained = (int)$_POST['marks_obtained'];
    $total    = (int)$_POST['total_marks'];

    // Auto calculate grade
    $pct = ($total > 0) ? ($obtained / $total) * 100 : 0;
    if ($pct >= 90)      $grade = "A+";
    elseif ($pct >= 80)  $grade = "A";
    elseif ($pct >= 70)  $grade = "B";
    elseif ($pct >= 60)  $grade = "C";
    elseif ($pct >= 50)  $grade = "D";
    else                 $grade = "F";

    $check = mysqli_query($conn, "SELECT * FROM marks WHERE student_id='$sid' AND subject='$subject'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Marks for this subject already exist. Use edit instead.";
    } else {
        $q = "INSERT INTO marks (student_id, subject, marks_obtained, total_marks, grade)
              VALUES ('$sid', '$subject', $obtained, $total, '$grade')";
        if (mysqli_query($conn, $q)) $success = "Marks added successfully.";
        else $error = "Failed to add marks.";
    }
}

// ---- UPDATE MARKS ----
if (isset($_POST['update_marks'])) {
    $id       = (int)$_POST['mark_id'];
    $obtained = (int)$_POST['marks_obtained'];
    $total    = (int)$_POST['total_marks'];

    $pct = ($total > 0) ? ($obtained / $total) * 100 : 0;
    if ($pct >= 90)      $grade = "A+";
    elseif ($pct >= 80)  $grade = "A";
    elseif ($pct >= 70)  $grade = "B";
    elseif ($pct >= 60)  $grade = "C";
    elseif ($pct >= 50)  $grade = "D";
    else                 $grade = "F";

    $q = "UPDATE marks SET marks_obtained=$obtained, total_marks=$total, grade='$grade' WHERE id=$id";
    if (mysqli_query($conn, $q)) $success = "Marks updated successfully.";
    else $error = "Failed to update marks.";
}

// ---- DELETE MARKS ----
if (isset($_GET['delete_mark'])) {
    $id = (int)$_GET['delete_mark'];
    if (mysqli_query($conn, "DELETE FROM marks WHERE id=$id"))
        $success = "Mark entry deleted.";
    else
        $error = "Failed to delete mark.";
}

// ---- FETCH ALL STUDENTS ----
$students_result = mysqli_query($conn, "SELECT * FROM students ORDER BY student_id");

// ---- FETCH MARKS FOR SELECTED STUDENT ----
$selected_student = null;
$marks_result     = null;
if (isset($_GET['view'])) {
    $sid = mysqli_real_escape_string($conn, $_GET['view']);
    $sr  = mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$sid'");
    if (mysqli_num_rows($sr) == 1) {
        $selected_student = mysqli_fetch_assoc($sr);
        $marks_result     = mysqli_query($conn, "SELECT * FROM marks WHERE student_id = '$sid'");
    }
}

// For edit mark modal
$edit_mark = null;
if (isset($_GET['edit_mark'])) {
    $id   = (int)$_GET['edit_mark'];
    $er   = mysqli_query($conn, "SELECT * FROM marks WHERE id=$id");
    if (mysqli_num_rows($er) == 1) $edit_mark = mysqli_fetch_assoc($er);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Portal</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <span class="nav-title">🎓 Admin Dashboard</span>
    <div class="nav-right">
        <span>Logged in as <strong>Admin</strong></span>
        <a href="Logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Alerts -->
    <?php if ($success != ""): ?>
        <div class="alert success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error != ""): ?>
        <div class="alert error">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- LEFT: Student List -->
        <div class="panel">
            <div class="panel-header">
                <h3>All Students</h3>
                <button class="btn-small btn-green" onclick="toggleForm('add-student-form')">+ Add Student</button>
            </div>

            <!-- Add Student Form -->
            <div id="add-student-form" class="sub-form" style="display:none;">
                <form method="POST" action="">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="text" name="password" placeholder="Password" required>
                    <button type="submit" name="add_student" class="btn-small btn-blue">Add</button>
                </form>
            </div>

            <!-- Student Table -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $students_result = mysqli_query($conn, "SELECT * FROM students ORDER BY student_id");
                while ($s = mysqli_fetch_assoc($students_result)):
                ?>
                    <tr class="<?php echo (isset($_GET['view']) && $_GET['view'] == $s['student_id']) ? 'active-row' : ''; ?>">
                        <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td class="actions">
                            <a href="?view=<?php echo urlencode($s['student_id']); ?>" class="btn-small btn-blue">View</a>
                            <a href="?delete_student=<?php echo urlencode($s['student_id']); ?>"
                               class="btn-small btn-red"
                               onclick="return confirm('Delete this student and all their marks?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- RIGHT: Marks Panel -->
        <div class="panel">
            <?php if ($selected_student): ?>

                <div class="panel-header">
                    <h3>Marks — <?php echo htmlspecialchars($selected_student['name']); ?> (<?php echo htmlspecialchars($selected_student['student_id']); ?>)</h3>
                    <button class="btn-small btn-green" onclick="toggleForm('add-marks-form')">+ Add Marks</button>
                </div>

                <!-- Add Marks Form -->
                <div id="add-marks-form" class="sub-form" style="display:none;">
                    <form method="POST" action="?view=<?php echo urlencode($selected_student['student_id']); ?>">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($selected_student['student_id']); ?>">
                        <input type="text" name="subject" placeholder="Subject" required>
                        <input type="number" name="marks_obtained" placeholder="Marks Obtained" required>
                        <input type="number" name="total_marks" placeholder="Total Marks" required>
                        <button type="submit" name="add_marks" class="btn-small btn-blue">Add</button>
                    </form>
                </div>

                <!-- Edit Marks Form -->
                <?php if ($edit_mark): ?>
                <div class="sub-form">
                    <h4 style="margin-bottom:10px;">Edit: <?php echo htmlspecialchars($edit_mark['subject']); ?></h4>
                    <form method="POST" action="?view=<?php echo urlencode($selected_student['student_id']); ?>">
                        <input type="hidden" name="mark_id" value="<?php echo $edit_mark['id']; ?>">
                        <input type="number" name="marks_obtained" value="<?php echo $edit_mark['marks_obtained']; ?>" required>
                        <input type="number" name="total_marks" value="<?php echo $edit_mark['total_marks']; ?>" required>
                        <button type="submit" name="update_marks" class="btn-small btn-blue">Update</button>
                        <a href="?view=<?php echo urlencode($selected_student['student_id']); ?>" class="btn-small btn-red">Cancel</a>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Marks Table -->
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Obtained</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $marks_result = mysqli_query($conn, "SELECT * FROM marks WHERE student_id = '" . mysqli_real_escape_string($conn, $selected_student['student_id']) . "'");
                    if (mysqli_num_rows($marks_result) == 0):
                    ?>
                        <tr><td colspan="5" class="no-data">No marks added yet.</td></tr>
                    <?php else: ?>
                    <?php while ($m = mysqli_fetch_assoc($marks_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['subject']); ?></td>
                            <td><?php echo $m['marks_obtained']; ?></td>
                            <td><?php echo $m['total_marks']; ?></td>
                            <td><span class="grade-badge grade-<?php echo strtolower(str_replace('+','',$m['grade'])); ?>"><?php echo $m['grade']; ?></span></td>
                            <td class="actions">
                                <a href="?view=<?php echo urlencode($selected_student['student_id']); ?>&edit_mark=<?php echo $m['id']; ?>" class="btn-small btn-blue">Edit</a>
                                <a href="?view=<?php echo urlencode($selected_student['student_id']); ?>&delete_mark=<?php echo $m['id']; ?>"
                                   class="btn-small btn-red"
                                   onclick="return confirm('Delete this mark entry?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <div class="empty-state">
                    <p>👈 Select a student from the left to view and manage their marks.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    el.style.display = (el.style.display === 'none') ? 'block' : 'none';
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>
