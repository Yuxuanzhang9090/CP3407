<html>
    <head>
        <title>NomNom</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
        
          <div class="container">
          
            <h2 class="text-center mb-4">Login to NomNom</h2>
                            <form class="row mb-2"  name="loginform" method="post">
                            
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-sm-2 col-form-label">Email</label>
                                    <div class="col-sm-10">
                                    <input type="email" class="form-control" name="Email" id="email" placeholder="Enter your email" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword3" class="col-sm-2 col-form-label">Password</label>
                                    <div class="col-sm-10">
                                    <input type="password" class="form-control" name="Password" id="password" placeholder="Enter your password" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                <button type="submit" name="submit" class="btn btn-primary w-100">Login</button>
                                <p class="text-center mt-3">
Don't have an account? <a href="Signup.php">Sign up</a></p>
                                </div>
                                
                                

                                

                    </form>
                           
                    
          </div>

          <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
          <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </body>
</html>