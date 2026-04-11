<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCMS - Tortoise Conservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/security.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="logout.php" class="d-inline">
                <?php echo csrfInput(); ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
        </div>