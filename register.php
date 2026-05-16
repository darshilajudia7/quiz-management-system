
<!DOCTYPE html>
<html>
<head>
    <title>Register page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <h3>Register</h3>
        <form method="POST">
            <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="Email ID" required>
            <input type="password" name="pass" class="form-control mb-3" placeholder="Password" required>
            <p><a href="login.php">Login?</a></p>
            <button type="submit" name="register" class="green-btn-circle"> → </button>
        </form>
    </div>
</body>
</html>

<?php
include 'db.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = $_POST['pass'];

    $result = $conn->prepare("INSERT INTO register (full_name, email, password) VALUES (?,?,?)");
    $result->bind_param("sss", $name, $email, $pass);
    
    if($result->execute()){
        header("Location: login.php"); 
    }
}
?>