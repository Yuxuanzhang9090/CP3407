<html>
    <head>
        <title>NomNow</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
        
          <div class="container">
          
            <h2 class="text-center mb-4">Reset Pasword</h2>
                            <form class="row mb-2" method="post" action="">
                            
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-2 col-form-label">New Password</label>
                                    <div class="col-sm-10">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter your new password" required>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                <button type="submit" name="submit" class="btn btn-primary w-100">Update Password</button>
                                <p class="text-center mt-3">
                            
                                    
                    </form>
                          <?php
$con=mysqli_connect('localhost','root','','food_delivery');

if(!isset($_GET['id']))
{
    die("Invalid request");
}

$id=$_GET['id'];

if(isset($_POST['submit']))
{
    $password=$_POST['password'];

    $update=mysqli_query($con,"UPDATE users SET password='$password' WHERE id='$id'");

    if($update)
    {
        echo "<script>('Password updated successfully'); window.location='login.php';</script>";
    }
    else
    {
        echo "Update failed";
    }
}
?>

                    
          </div>

          <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
          <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </body>
</html>