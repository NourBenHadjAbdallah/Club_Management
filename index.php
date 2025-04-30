<?php

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Home</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
    }

    a {
      text-decoration: none;
      color: #333;
    }

    a:hover {
      color: #007bff;
    }

    ul li {
      margin: 10px 0;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      padding: 8px;
      text-align: left;
    }
  </style>
</head>

<body>

  <?php include './include/sidebar.php'; ?>

  <div style="margin-left: 240px; padding: 20px;">
    <h2>Welcome,
      <?php echo htmlspecialchars($_SESSION['firstname']) . ' ' . htmlspecialchars($_SESSION['lastname']); ?>!
    </h2>

    <p>You are a <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></strong>.</p>
  </div>

</body>

</html>