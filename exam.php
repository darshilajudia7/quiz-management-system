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

$exam_id = intval($_GET['exam_id']);
$user_id = intval($_SESSION['user_id']);

if (isset($_GET['q_index']) && intval($_GET['q_index']) === 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    mysqli_query($conn, "DELETE FROM user_answers WHERE user_id = $user_id AND exam_id = $exam_id");
}

$exam_query = mysqli_query($conn, "SELECT * FROM exams WHERE id = $exam_id");
$exam_details = mysqli_fetch_assoc($exam_query);
if (!$exam_details) {
    header("Location: profile.php"); // Updated redirect path
    exit();
}
$duration_seconds = intval($exam_details['duration_minutes']) * 60;

// Save answers 
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $posted_q_id = intval($_POST['current_q_id']);
    
    if (isset($_POST['selected_option']) && !empty($_POST['selected_option'])) {
        $selected_option = isset($_POST['selected_option']) ? intval($_POST['selected_option']) : NULL;

        $save_stmt = mysqli_prepare($conn, "
            INSERT INTO user_answers 
            (user_id, exam_id, question_id, selected_option_id) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id)
        ");

        mysqli_stmt_bind_param(
            $save_stmt,
            "iiii",
            $user_id,
            $exam_id,
            $posted_q_id,
            $selected_option
        );

        if (!mysqli_stmt_execute($save_stmt)) {
            die(mysqli_stmt_error($save_stmt));
        }

        mysqli_stmt_close($save_stmt);
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'submit_exam') {

        $score_query = mysqli_query($conn, "
            SELECT COUNT(*) AS correct_answers 
            FROM user_answers ua
            JOIN question_options qo 
            ON ua.selected_option_id = qo.id
            WHERE ua.user_id = $user_id
            AND ua.exam_id = $exam_id
            AND qo.is_correct = 1
        ");

        if (!$score_query) {
            die(mysqli_error($conn));
        }

        $score_data = mysqli_fetch_assoc($score_query);

        $total_score = intval($score_data['correct_answers']);

        
        $save_score_stmt = mysqli_prepare($conn, "
            INSERT INTO results (user_id, exam_id, score)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            score = VALUES(score),
            submitted_at = NOW()
        ");

        if (!$save_score_stmt) {
            die(mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $save_score_stmt,
            "iii",
            $user_id,
            $exam_id,
            $total_score
        );

        if (!mysqli_stmt_execute($save_score_stmt)) {
            die(mysqli_stmt_error($save_score_stmt));
        }
        header("Location: result.php?exam_id=" . $exam_id);
        exit();
    }
}

$current_index = isset($_GET['q_index']) ? intval($_GET['q_index']) : 0;

$questions_query = mysqli_query($conn, "SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id ASC");
$all_questions = [];
while ($row = mysqli_fetch_assoc($questions_query)) {
    $all_questions[] = $row;
}
$total_questions = count($all_questions);

if ($current_index < 0) {
    $current_index = 0;
} elseif ($current_index >= $total_questions && $total_questions > 0) {
    $current_index = $total_questions - 1;
}

$current_question = isset($all_questions[$current_index]) ? $all_questions[$current_index] : null;


if ($current_question) {
    $cq_id = $current_question['id'];
    $check_ans = mysqli_query($conn, "SELECT selected_option_id FROM user_answers WHERE user_id = $user_id AND exam_id = $exam_id AND question_id = $cq_id");
    if (mysqli_num_rows($check_ans) === 0) {
        mysqli_query($conn, "INSERT INTO user_answers (user_id, exam_id, question_id, selected_option_id) VALUES ($user_id, $exam_id, $cq_id, NULL)");
    }
}

$options = [];
if ($current_question) {
    $options_query = mysqli_query($conn, "SELECT * FROM question_options WHERE question_id = {$current_question['id']} ORDER BY id ASC");
    while ($opt = mysqli_fetch_assoc($options_query)) {
        $options[] = $opt;
    }
}

$saved_answers = [];
$answers_query = mysqli_query($conn, "SELECT question_id, selected_option_id FROM user_answers WHERE user_id = $user_id AND exam_id = $exam_id");
while ($ans_row = mysqli_fetch_assoc($answers_query)) {
    $saved_answers[$ans_row['question_id']] = $ans_row['selected_option_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Module Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<div class="container-fluid px-4 mt-4">
    <div class="row">
        
        <!-- LEFT PANEL: RUNNER FORM ENGINE -->
        <div class="col-md-8 mb-4">
            <div class="card p-4 shadow-sm border-0">
                <?php if ($current_question): ?>
                    <span class="badge bg-secondary mb-2">Question <?php echo ($current_index + 1); ?> of <?php echo $total_questions; ?></span>
                    <h4 class="mb-4 text-dark"><?php echo htmlspecialchars($current_question['question_text']); ?></h4> 
                    
                    <form action="exam.php?exam_id=<?php echo $exam_id; ?>&q_index=<?php echo $current_index; ?>" method="POST" id="quizForm">
                        <input type="hidden" name="current_q_id" value="<?php echo $current_question['id']; ?>">
                        <input type="hidden" name="action" id="formActionField" value="save_answer">
                        
                        <div class="options-container mb-4">
                            <?php foreach ($options as $opt): 
                                $user_choice = isset($saved_answers[$current_question['id']]) ? $saved_answers[$current_question['id']] : null;
                                $checked = ($user_choice == $opt['id']) ? 'checked' : '';
                            ?>
                                <div class="form-check">
                                    <input type="radio" name="selected_option" id="opt_<?php echo $opt['id']; ?>" value="<?php echo $opt['id']; ?>" class="form-check-input" <?php echo $checked; ?>> 
                                    <label class="form-check-label d-block w-100" for="opt_<?php echo $opt['id']; ?>">
                                        <?php echo htmlspecialchars($opt['option_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-3">
                            <button type="submit" onclick="document.getElementById('quizForm').action='exam.php?exam_id=<?php echo $exam_id; ?>&q_index=<?php echo $current_index - 1; ?>';" class="btn btn-secondary px-4" <?php echo ($current_index == 0) ? 'disabled' : ''; ?>>Previous</button>
                            <button type="submit" onclick="document.getElementById('quizForm').action='exam.php?exam_id=<?php echo $exam_id; ?>&q_index=<?php echo $current_index + 1; ?>';" class="btn btn-primary px-4" <?php echo ($current_index == $total_questions - 1) ? 'disabled' : ''; ?>>Next Question</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANEL: TIMEOUT & NAVIGATION MATRIX -->
        <div class="col-md-4">
            <div class="sidebar-status border-0 p-4 bg-white rounded shadow-sm">
                <div class="text-center">
                    <h6 class="text-uppercase text-muted tracking-wider">Time Remaining</h6> 
                    <h2 class="text-danger fw-bold my-2" id="timer">--:--</h2> 
                </div>
                <hr class="my-3">
                
                <div class="mb-3 small p-2 bg-light rounded">
                    <div class="mb-1"><span class="legend-dot bg-white border border-secondary"></span> Remaining (Unvisited)</div>
                    <div class="mb-1"><span class="legend-dot bg-warning"></span> Not Answered (Skipped)</div>
                    <div><span class="legend-dot bg-success"></span> Answered (Saved response)</div>
                </div>
                
                <p class="text-dark fw-semibold mb-2">Question Navigation Matrix:</p>
                <div class="d-flex flex-wrap justify-content-start mb-4">
                    <?php 
                    for($i = 0; $i < $total_questions; $i++): 
                        $target_q_id = $all_questions[$i]['id'];
                        $status_class = 'status-remaining'; 
                        
                        if (array_key_exists($target_q_id, $saved_answers)) {
                            if (is_null($saved_answers[$target_q_id])) {
                                $status_class = 'status-skipped';
                            } else {
                                $status_class = 'status-answered';
                            }
                        }
                        
                        $active_focus = ($i === $current_index) ? 'active-focus' : '';
                    ?>
                        <button type="button" onclick="document.getElementById('quizForm').action='exam.php?exam_id=<?php echo $exam_id; ?>&q_index=<?php echo $i; ?>'; document.getElementById('quizForm').submit();"
                                class="q-box m-1 p-2 rounded align-middle <?php echo $status_class; ?> <?php echo $active_focus; ?>">
                            <?php echo ($i + 1); ?>
                        </button>
                    <?php endfor; ?>
                </div>
                
                <button type="button" id="btnSubmitTest" class="btn btn-success w-100 py-2 fw-bold" onclick="triggerFinalSubmit();">
                    Finish & Submit Test
                </button> 
            </div>
        </div>

    </div>
</div>

<script>
const storageKey = "exam_timer_<?php echo $exam_id; ?>_user_<?php echo $user_id; ?>";
const totalDuration = <?php echo $duration_seconds; ?>;
let timeRemaining = localStorage.getItem(storageKey) ? parseInt(localStorage.getItem(storageKey)) : totalDuration;
const timerElement = document.getElementById('timer');

function updateTimer() {
    if (timeRemaining <= 0) {
        clearInterval(countdownInterval);
        localStorage.removeItem(storageKey);
        
        document.getElementById('formActionField').value = 'submit_exam';
        document.getElementById('quizForm').submit();
        return;
    }
    timeRemaining--;
    localStorage.setItem(storageKey, timeRemaining);
    let mins = Math.floor(timeRemaining / 60);
    let secs = timeRemaining % 60;
    timerElement.innerHTML = (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs : secs);
}

function triggerFinalSubmit() {
    if (confirm('Are you sure you want to finalize and submit your exam?')) {
        localStorage.removeItem(storageKey);
        document.getElementById('formActionField').value = 'submit_exam';
        document.getElementById('quizForm').submit();
    }
}

updateTimer();
const countdownInterval = setInterval(updateTimer, 1000);
</script>
</body>
</html>
