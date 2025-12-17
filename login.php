<?php
include_once 'database.php';
session_start();

// ----------- SIGN IN -----------
if (isset($_POST['signin'])) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM tbl_user_ezparcel WHERE fld_user_email = :email ");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

        $email = $_POST['email_signin'];
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['fld_user_password'] === sha1($_POST['password_signin'])) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['user_email'] = $user['fld_user_email'];
            $_SESSION['user_name'] = $user['fld_user_name'];
            $_SESSION['user_level'] = (int)$user['fld_user_level'];

            // Redirect based on user level
            if ($_SESSION['user_level'] === 1) {
                header("Location: orderhistory.php");
                exit;
            } elseif ($_SESSION['user_level'] === 2) {
                header("Location: parcelstatus.php");
                exit;
            } else {
                // Unknown level - fallback
                header("Location: orderhistory.php");
                exit;
            }
        } else {
            $signin_error = "Invalid Email, Phone or Password";
        }
    } catch (PDOException $e) {
        $signin_error = "Database Error: " . $e->getMessage();
    }
    $conn = null;
}

// ----------- SIGN UP -----------
if (isset($_POST['signup'])) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if email or phone already exists
        $stmt = $conn->prepare("SELECT * FROM tbl_user_ezparcel WHERE fld_user_email = :email OR fld_user_phone = :phone");
        $stmt->bindParam(':email', $_POST['email_signup']);
        $stmt->bindParam(':phone', $_POST['phone_signup']);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $signup_error = "Email or Phone already registered";
        } else {
            // FIXED: Added :level to VALUES clause
            $stmt = $conn->prepare("INSERT INTO tbl_user_ezparcel (fld_user_name, fld_user_email, fld_user_password, fld_user_phone, fld_user_level) VALUES (:name, :email, :password, :phone, :level)");
            
            // Hash password before binding
            $password_hashed = sha1($_POST['password_signup']);
            $level = 2; // Set default level to 2
            
            $stmt->bindParam(':name', $_POST['name_signup']);
            $stmt->bindParam(':email', $_POST['email_signup']);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':phone', $_POST['phone_signup']);
            $stmt->bindParam(':level', $level);
            
            $stmt->execute();
            $signup_success = "Account created successfully! You can now sign in.";
        }
    } catch (PDOException $e) {
        $signup_error = "Database Error: " . $e->getMessage();
    }
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EZPARCEL - Login/SignUp</title>
<link rel="stylesheet" href="css/style.css">
<script src="js/script.js" defer></script>
</head>
<body>
     <h2>Welcome To EZPARCEL</h2>
    <h4>Smart, Simple and Secure</h4>
<div class="container" id="container">
    <!-- SIGN UP -->
    <div class="form-container sign-up-container">
        <form method="post" action="">
            <h1>Create Account</h1>
            <?php if(isset($signup_error)) echo "<div class='alert'>$signup_error</div>"; ?>
            <?php if(isset($signup_success)) echo "<div class='success'>$signup_success</div>"; ?>
            <input type="text" name="name_signup" placeholder="Name" required />
            <input type="email" name="email_signup" placeholder="Email" required />
            <input type="text" name="phone_signup" placeholder="Phone" required />
            <input type="password" name="password_signup" placeholder="Password" required />
            <button name="signup">Sign Up</button>
        </form>
    </div>

    <!-- SIGN IN -->
    <div class="form-container sign-in-container">
        <form method="post" action="">
            <h1>Sign In</h1>
            <?php if(isset($signin_error)) echo "<div class='alert'>$signin_error</div>"; ?>
            <input type="email" name="email_signin" placeholder="Email" required />
            <input type="password" name="password_signin" placeholder="Password" required />
            <a href="#">Forgot your password?</a>
            <button name="signin">Sign In</button>
        </form>
    </div>

    <!-- OVERLAY -->
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1>Welcome Back!</h1>
                <p>To keep connected with us please login with your personal info</p>
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1>Hello, Friend!</h1>
                <p>Enter your personal details and start journey with us</p>
                <button class="ghost" id="signUp">Sign Up</button>
            </div>
        
        </div>
    </div>
</div>
</body>
</html>
