<?php
include 'db.php';

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Fetching user data from database
    $query = "SELECT * FROM register WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        header("Location: profile.php");
    } else {
        echo "<script>alert('Invalid Credentials');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTES - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-card">
        <h3>Login</h3>
        <form method="POST">
            <!-- Email Input -->
            <input type="email" name="email" class="form-control mb-3" placeholder="Email ID" required>
            
            <!-- Password Input -->
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
            
            <!-- Navigation Link -->
            <p><a href="register.php">Register?</a></p>
            
            <!-- Action Button -->
            <button type="submit" name="login" class="green-btn-circle"> → </button>
        </form>
    </div>
</body>
</html>