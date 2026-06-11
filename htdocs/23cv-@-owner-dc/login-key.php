<?php session_start(); 
if (isset($_SESSION['email'])) {
    $email  = $_SESSION['email'];


include 'asserts/src/connection.php';
if (isset($_POST['submit-key'])) {
    $key    = $_POST['key'];

    $random   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token    = '';
    for($i = 0; $i < 6; $i++){
        $token .= $random[random_int(0, strlen($random) - 1)];
    }

    if(empty($_POST['key'])){
        $_SESSION['start'] = "Enter a Key!";
        header('location:login-key');
        exit();
    }

    $select = "SELECT * FROM `owners` WHERE token='$key' AND email='$email' LIMIT 1";
    $execut = mysqli_query($connect, $select);
    $fetch  = mysqli_fetch_array($execut, MYSQLI_ASSOC);
    $rows   = mysqli_num_rows($execut);

    if ($rows) {
        $update = "UPDATE `owners` SET `token`= '$token' WHERE token='$key' LIMIT 1";
        $query  = mysqli_query($connect, $update);

        if ($query) {
            $_SESSION['key'] = $_POST['key'];
            header('location:index');
            exit();

        }else{
            $_SESSION['start'] = "New Login key Fail!";
            header('location:login-key');
            exit();
        }

    }else{
        $_SESSION['start'] = "Invalid Key!";
        header('location:login-key');
        exit();
    }

}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../assets/image/logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheOneMovies - Admin Panel Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="asserts/css/admin-login.css">
</head>
<body>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Add Movie Form (Example) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Login Key Verification</h3>
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
                        <P>Enter a key we sent to your email address to verify if you are a real Admin, If you are not seeing the key email Please check your spam box.</P> <br>
                        <form id="movieForm" action="#" method="POST">
                            <div class="form-group">
                                <label class="form-label">Key</label>
                                <input type="text" name="key" class="form-control" placeholder="Enter Key">
                            </div>
                            <button type="submit" name="submit-key" class="btn btn-primary">
                                <i class="fas fa-right-to-bracket"></i> Submit
                            </button>
                        </form>
                    </div>
            </div>
        </div>
</body>
</html>
<?php }else{
    header('location:way-to-go');
    exit();
}?>                  