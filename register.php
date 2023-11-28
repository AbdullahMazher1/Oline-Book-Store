<?php
include 'config.php';

if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = mysqli_real_escape_string($conn, md5($_POST['password']));
    $cpass = mysqli_real_escape_string($conn, md5($_POST['cpassword']));
    $user_type = $_POST['user_type'];

    // Check if the user already exists in the database
    $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('Query failed');

    if(mysqli_num_rows($select_users) > 0){
        // User exists, check their status
        $user_data = mysqli_fetch_assoc($select_users);
        $status = $user_data['status'];

        if ($status == 'Inactive') {
            // If user status is 'Inactive', update it to 'Active'
            mysqli_query($conn, "UPDATE `users` SET status = 'Active' WHERE email = '$email'") or die('Update query failed');
            $message[] = 'User status updated to Active!';
            header('location: login.php');
            exit;
        } else {
            // If user status is already 'Active', show a message
            $message[] = 'User already exists and is Active!';
        }
    } else {
        // User does not exist, proceed with registration
        if($pass != $cpass){
            $message[] = 'Confirm password not matched!';
        } else {
            mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$name', '$email', '$cpass', '$user_type')") or die('Insert query failed');
            $message[] = 'Registered successfully!';
            header('location: login.php');
            exit;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>register</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">  

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css?v=<?php echo time();?>">

</head>
<body class = "backLog">



<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>
   
<div class="form-container">

   <form action="" method="post">
      <h3 class="logintext">Register Now</h3>
      <input type="text" name="name" placeholder="enter your name" required class="logbox">
      <input type="email" name="email" placeholder="enter your email" required class="logbox">
      <input type="password" name="password" placeholder="enter your password" required class="logbox">
      <input type="password" name="cpassword" placeholder="confirm your password" required class="logbox">
      <select name="user_type" class="box" id="selector">
         <option value="user">user</option>
         <option value="admin">admin</option>
      </select>
      <input type="submit" name="submit" value="Register Now" class="btn">
      <p class= "ques">already have an account? <a href="login.php" class="link">login now </a></p>
   </form>

</div>

</body>
</html>