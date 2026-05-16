<?php
// session_start();

include 'db.php'; 

// Basic login check guardrail
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark p-3">
    <span class="navbar-brand">Welcome,</span>
    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</nav>

<div class="container mt-4">
    <h4>Select an exam</h4>
    
    <form action="exam.php" method="GET" class="w-50">
        <select name="exam_id" class="form-select mb-3" required>
            <option value="">-- Choose an Exam --</option>
            <?php
            $exams_query = mysqli_query($conn, "SELECT id, title, duration_minutes FROM exams");
            while ($exam = mysqli_fetch_assoc($exams_query)) {
                echo "<option value='{$exam['id']}'>{$exam['title']} ({$exam['duration_minutes']} Mins)</option>";
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary">Start Exam</button>
    </form>

    <h5 class="mt-5">Exams submitted</h5>
<table class="table table-bordered table-striped mt-2">
    <thead>
        <tr>
            <th>Exam Title</th>
            <th>Score Achieved</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = mysqli_prepare($conn, "SELECT r.score, e.title, e.total_marks 
                                       FROM results r 
                                       INNER JOIN exams e ON r.exam_id = e.id 
                                       WHERE r.user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($res) > 0) {
            while($row = mysqli_fetch_assoc($res)){
                echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td><strong>{$row['score']}</strong> / {$row['total_marks']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='2' class='text-muted text-center'>You haven't attempted any exams yet.</td></tr>";
        }
        mysqli_stmt_close($stmt);
        ?>
    </tbody>
</table>
    
    <div class="alert alert-info mt-4">
        <strong>How the exam will be conducted?</strong>
        <p class="mb-0">Exam will be conducted for a certain time duration. Use NEXT & PREV to see questions.</p>
    </div>
</div>
</body>
</html>