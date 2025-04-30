<?php
session_start();
include('../../config/db_connect.php'); 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $birthday = $_POST['birthday'];

    try {

        $checkQuery = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $pdo->prepare($checkQuery);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);

        if ($stmt->rowCount() > 0) {
            $error_message = "Username or Email already exists.";
        } else {

            $insertQuery = "INSERT INTO users (firstname, lastname, username, email, password, birthday, role, status) 
                            VALUES (:firstname, :lastname, :username, :email, :password, :birthday, 'member', 'pending')";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':username' => $username,
                ':email' => $email,
                ':password' => $password,
                ':birthday' => $birthday
            ]);

            $success_message = "Registration successful! Please wait for approval.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="bg-white border rounded-lg px-8 py-6 mx-auto my-8 max-w-2xl">
        <h2 class="text-2xl font-medium mb-4">Join Our Club</h2>
        <p class="text-gray-600 mb-6">
            Become a member to enjoy exclusive benefits, including access to premium equipment, 
            community events, and personalized support. Fill out the form below to get started!
        </p>
        <form method="POST" action="register.php">
            <input type="hidden" name="action" value="Register">
            
            <div class="mb-4">
                <label for="firstname" class="block text-gray-700 font-medium mb-2">First Name</label>
                <input type="text" id="firstname" name="firstname"
                    class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400"
                    required>
            </div>

            <div class="mb-4">
                <label for="lastname" class="block text-gray-700 font-medium mb-2">Last Name</label>
                <input type="text" id="lastname" name="lastname"
                    class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400"
                    required>
            </div>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                <input type="text" id="username" name="username"
                    class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400"
                    required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" id="email" name="email"
                    class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400"
                    required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" id="password" name="password"
                    class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400"
                    required>
            </div>
            <div class="mb-4">
            <label>Birthday</label><br>
            <input type="date" name="birthday" class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400" required><br><br>

            </div>


            <div>
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Register</button>
            </div>
        </form>
        <?php
    if (isset($error_message)) {
        echo "<p style='color:red;'>$error_message</p>";
    }

    if (isset($success_message)) {
        echo "<p style='color:green;'>$success_message</p>";
    }
    ?>
        <p class="mt-4 text-gray-600">Already have an account? <a href="/FINALPHP/login.php" class="text-blue-500 hover:underline">Login</a></p>
    </div>
</body>
</html>
