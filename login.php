<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Login - Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<style>
    .login-box img {
    display: block;
    margin: 0 auto;
    margin-bottom: 20px; /* Adds spacing below the logo */
    max-width: 100%;  /* Ensures the image scales to fit within the sidebar */
    height: 80px;     /* Maintains the aspect ratio of the image */
    object-fit: contain; /* Prevents stretching or cropping */
}

</style>
<body>
    <div class="login-container">
        <div class="login-box">
        <img src="img/logo1.png" alt="Logo" class="logo">


            <!-- Display error message if any -->
            <?php
            session_start();
            if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); // Clear the error message after displaying it
            endif;
            ?>

            <!-- Login Form -->
            <form action="login_process.php" method="POST">
                <div class="input-box">
                    <label for="username">Em</label>
                    <input type="email" id="username" name="username" required>
                </div>

                <div class="input-box">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="input-box">
                    <input type="submit" value="Login">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
