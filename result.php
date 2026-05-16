<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['exam_id'])) {
    header("Location: profile.php"); 
    exit();
}

$user_id = intval($_SESSION['user_id']);
$exam_id = intval($_GET['exam_id']);

$total_q_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM questions WHERE exam_id = $exam_id");
$total_q_data = mysqli_fetch_assoc($total_q_query);
$total_questions = intval($total_q_data['total']);

if ($total_questions === 0) {
    header("Location: profile.php");
    exit();
}

$score_query = mysqli_prepare($conn, "
    SELECT COUNT(*) as correct_count 
    FROM user_answers ua 
    INNER JOIN questions q ON ua.question_id = q.id 
    WHERE ua.user_id = ? AND ua.exam_id = ? AND ua.selected_option_id = q.correct_option_id
");

mysqli_stmt_bind_param($score_query, "ii", $user_id, $exam_id);
mysqli_stmt_execute($score_query);
$score_res = mysqli_stmt_get_result($score_query);
$score_data = mysqli_fetch_assoc($score_res);
$score_achieved = intval($score_data['correct_count']);
mysqli_stmt_close($score_query);

$passing_score = $total_questions * 0.5;
$is_passed = ($score_achieved >= $passing_score);

$check_history = mysqli_prepare($conn, "SELECT id FROM results WHERE user_id = ? AND exam_id = ?");
mysqli_stmt_bind_param($check_history, "ii", $user_id, $exam_id);
mysqli_stmt_execute($check_history);
$history_result = mysqli_stmt_get_result($check_history);

if (mysqli_num_rows($history_result) > 0) {
    $save_stmt = mysqli_prepare($conn, "UPDATE results SET score = ? WHERE user_id = ? AND exam_id = ?");
    mysqli_stmt_bind_param($save_stmt, "iii", $score_achieved, $user_id, $exam_id);
} else {
    $save_stmt = mysqli_prepare($conn, "INSERT INTO results (user_id, exam_id, score) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($save_stmt, "iii", $user_id, $exam_id, $score_achieved);
}
mysqli_stmt_execute($save_stmt);
mysqli_stmt_close($save_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results Evaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .auth-card { 
            background: #ffffff; 
            padding: 2.5rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
            max-width: 480px; 
            width: 100%; 
        }
        .score-display { 
            font-size: 3.5rem; 
            font-weight: 700; 
            color: #212529; 
            margin: 1.5rem 0; 
        }
    </style>
</head>
<body class="text-center">

    <div class="auth-card">
        <?php if ($is_passed): ?>
            <h2 class="text-success fw-bold">Congratulations!</h2>
            <p class="text-muted fs-5">You successfully passed the test.</p>
        <?php else: ?>
            <h2 class="text-danger fw-bold">Keep Learning!</h2>
            <p class="text-muted fs-5">You did not clear the minimum score requirement this time.</p>
        <?php endif; ?>
        
        <div class="score-display">
            <?php echo $score_achieved; ?><span class="text-muted fs-2"> / <?php echo $total_questions; ?></span>
        </div>
        
        <div class="progress mb-4" style="height: 10px;">
            <div class="progress-bar <?php echo $is_passed ? 'bg-success' : 'bg-danger'; ?>" 
                 role="progressbar" 
                 style="width: <?php echo ($score_achieved / $total_questions) * 100; ?>%">
            </div>
        </div>
        
        <div class="mt-4 gap-2 d-flex justify-content-center">
            <a href="profile.php" class="btn btn-outline-primary px-4">Goto Profile</a>
            <a href="logout.php" class="btn btn-outline-danger px-4">Logout</a>
        </div>
    </div>

</body>
</html>