<?php
require_once 'config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 text-center">
                <div class="error-template">
                    <h1 class="display-1">403</h1>
                    <h2>Access Denied</h2>
                    <p class="text-muted">You don't have permission to access this resource.</p>
                    <div class="error-details mb-4">
                        The server understood the request but refuses to authorize it.
                    </div>
                    <a href="index.php" class="btn btn-primary">Go to Home</a>
                    <a href="login.php" class="btn btn-secondary">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
