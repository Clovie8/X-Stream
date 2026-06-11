<?php 
session_start(); 
include 'asserts/src/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer-master/src/PHPMailer.php';
require_once '../PHPMailer-master/src/SMTP.php';
require_once '../PHPMailer-master/src/Exception.php';

$mail = new PHPMailer(true);




if (isset($_POST['submit-login'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['pwd'];
    $random   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token    = '';
    for($i = 0; $i < 6; $i++){
        $token .= $random[random_int(0, strlen($random) - 1)];
    }

    if(empty($_POST['name']) or empty($_POST['email']) or empty($_POST['pwd'])){
        $_SESSION['start'] = "Fill all input to Login!";
        header('location:way-to-go');
        exit();
    }

    $select = "SELECT * FROM `owners` WHERE name='$name' AND email='$email' AND password='$password' LIMIT 1";
    $execut = mysqli_query($connect, $select);
    $fetch  = mysqli_fetch_array($execut, MYSQLI_ASSOC);
    $rows   = mysqli_num_rows($execut);

    if ($rows) {
        $update = "UPDATE `owners` SET `token`='$token' WHERE name='$name' AND email='$email' AND password='$password' LIMIT 1";
        $query  = mysqli_query($connect, $update);

        if ($query) {
            try {
                // SMTP settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'theoneflims3@gmail.com';
                $mail->Password   = 'ysps fxzz lwta xufp'; 
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                
                // Email headers
                $mail->setFrom('theoneflims3@gmail.com', 'TheOneMovies');
                $mail->addReplyTo($email, $name);
                $mail->addAddress($email);
                
                // body template 
                $template = "
                <html>
                <body style='font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px;'>
                <div style='max-width: 600px; background: #ffffff; margin: auto; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'>
                
                <!-- Header -->
                <div style='background-color: #141414; padding: 20px; text-align: center;'>
                <h1 style='color: #e50914; margin: 0; font-size: 22px;'>Login Key</h1>
                </div>
                
                <!-- Body -->
                <div style='padding: 25px;'>
                <p style='font-size: 16px; color: #333333;'>
                Hello <strong>$name</strong>!
                </p>
                <p>We received a request to Login to the TheOneMovies Back Office, with the email: <strong>$email</strong> and name: <strong>$name</strong>.</p>
                <p>This is your One Time Login Key, Used only once:</p>

                
                <!-- Status Badge -->
                <p style='color: #e50914; padding: 8px; font-size: 1.2rem; text-align: center; font-weight: bold; margin: 20px 0;'>
                LOGIN KEY
                </p>
                
                <!-- Order Decision Section -->
                <div style='margin-top: 20px; padding: 15px; border-left: 4px solid #e50914; background: #f9fbff; border-radius: 6px;'>
                <p style='margin: 0;  font-size: 1.3rem; text-align: center; color: #333;'>$token</p>
                </div>

                <p>If you did not request a Login Key, you can safely Reset the hall System.</p>
            
                <hr>
                <p style='font-size: 12px; color: #777; text-align: center;'>&copy; 2026 TheOneMovies. All Rights Reserved.</p>
                </div>

                </body>
                </html>            
                ";
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'One Time Login Key';
                $mail->Body    = $template;
                
                $mail->send();
                $_SESSION['email'] = $_POST['email'];
                $_SESSION['end'] = "Check your email, We sent you a Login key!";
                header('location:login-key');
                exit();
            } catch (Exception $e) {
                $_SESSION['email'] = $_POST['email'];
                $_SESSION['start'] = "Sent email Login key Fail!";
                header('location:login-key');
                exit();
            }
        }else{
            $_SESSION['start'] = "Login key Fail!";
            header('location:way-to-go');
            exit();
        }

    }else{
        $_SESSION['start'] = "You are not Admin!";
        header('location:way-to-go');
        exit();
    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheOneMovies - Admin Panel Login</title>
    <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="asserts/css/admin-login.css">
</head>
<body>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Add Movie Form (Example) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Login</h3>
                </div>
                    <?php
                        if(isset($_SESSION['start'])){
                        ?>
                        <div class="error_start" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['start']?></span>
                        </div>
                        <?php
                        unset($_SESSION['start']);
                        }
                        ?>

                        <?php
                        if(isset($_SESSION['end'])){
                        ?>
                        <div class="error_end" style="text-align: center; font-size: 18px;">
                            <span><?php echo $_SESSION['end']?></span>
                        </div>
                        <?php
                        unset($_SESSION['end']);
                        }
                    ?>
                    <div class="card-body">
                        <form id="movieForm" action="#" method="POST">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter Email">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="pwd" class="form-control" placeholder="Enter Password">
                            </div>
                            
                            <button type="submit" name="submit-login" class="btn btn-primary">
                                <i class="fas fa-right-to-bracket"></i> LogIn
                            </button>
                        </form>
                    </div>
            </div>
        </div>
</body>
</html>           