<?php
session_start();

// If not logged in, send back to login
if (!isset($_SESSION['student_id'])) {
    header("Location: Form3.php");
    exit();
}

$student_id   = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Database connection
$conn = mysqli_connect("localhost", "root", "", "admin");

if (!$conn) {
    die("Database connection failed.");
}

// Fetch marks for the logged-in student
$query  = "SELECT subject, marks_obtained, total_marks, grade FROM marks WHERE student_id = '$student_id'";
$result = mysqli_query($conn, $query);

// Calculate totals
$total_obtained = 0;
$total_possible = 0;
$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total_obtained += $row['marks_obtained'];
    $total_possible += $row['total_marks'];
    $rows[] = $row;
}

$overall_percent = ($total_possible > 0) ? round(($total_obtained / $total_possible) * 100, 1) : 0;

// Overall grade based on percentage
if ($overall_percent >= 90)      $overall_grade = "A+";
elseif ($overall_percent >= 80)  $overall_grade = "A";
elseif ($overall_percent >= 70)  $overall_grade = "B";
elseif ($overall_percent >= 60)  $overall_grade = "C";
elseif ($overall_percent >= 50)  $overall_grade = "D";
else                             $overall_grade = "F";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Marks - Student Portal</title>
    <link rel="stylesheet" href="mark.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <span class="nav-title">🎓 Student Portal</span>
        <div class="nav-right">
            <span>Welcome, <strong><?php echo htmlspecialchars($student_name); ?></strong></span>
            <a href="Logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">

        <!-- Student Info Card -->
        <div class="info-card">
            <div class="info-left">
                <p class="info-label">Student Name</p>
                <p class="info-value"><?php echo htmlspecialchars($student_name); ?></p>
            </div>
            <div class="info-left">
                <p class="info-label">Student ID</p>
                <p class="info-value"><?php echo htmlspecialchars($student_id); ?></p>
            </div>
            <div class="info-left">
                <p class="info-label">Overall Grade</p>
                <p class="info-value grade-badge grade-<?php echo strtolower(str_replace('+','',$overall_grade)); ?>">
                    <?php echo $overall_grade; ?>
                </p>
            </div>
            <div class="info-left">
                <p class="info-label">Overall Score</p>
                <p class="info-value"><?php echo $total_obtained; ?> / <?php echo $total_possible; ?> (<?php echo $overall_percent; ?>%)</p>
            </div>
        </div>

        <!-- Marks Table -->
        <div class="table-card">
            <h3>Subject-wise Results</h3>

            <?php if (count($rows) == 0): ?>
                <p class="no-data">No marks found for your account.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $i => $row):
                            $percent = round(($row['marks_obtained'] / $row['total_marks']) * 100, 1);
                            $pass    = $percent >= 40;
                        ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo $row['marks_obtained']; ?></td>
                            <td><?php echo $row['total_marks']; ?></td>
                            <td>
                                <div class="progress-wrap">
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <?php echo $percent; ?>%
                            </td>
                            <td><span class="grade-badge grade-<?php echo strtolower(str_replace('+','',$row['grade'])); ?>"><?php echo htmlspecialchars($row['grade']); ?></span></td>
                            <td>
                                <?php if ($pass): ?>
                                    <span class="status pass">Pass</span>
                                <?php else: ?>
                                    <span class="status fail">Fail</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td><strong><?php echo $total_obtained; ?></strong></td>
                            <td><strong><?php echo $total_possible; ?></strong></td>
                            <td colspan="3"><strong><?php echo $overall_percent; ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>