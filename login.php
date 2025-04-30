<?php
session_start();
require './config/db_connect.php';

if (isset($_POST['username'], $_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];


    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute(params: [$username, $password]);
    $user = $stmt->fetch();

    if ($user) {

        if ($user['status'] == 'approved') {
            $_SESSION['id'] = $user['id'];
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role']; 
            $_SESSION['firstname'] = $user['firstname'];  
            $_SESSION['lastname'] = $user['lastname'];    

            header('Location: index.php');
            exit();
        } else {
            $error = "Your account is not approved yet!";
        }
    } else {
        $error = "Invalid credentials!";
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="w-screen min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-800 px-4 sm:px-6 lg:px-8">
    <div class="relative py-3 sm:max-w-xs sm:mx-auto">
        <div class="min-h-96 px-8 py-6 mt-4 text-left bg-white dark:bg-gray-900 rounded-xl shadow-lg">
            <div class="flex flex-col justify-center items-center h-full select-none">
                <div class="flex flex-col items-center justify-center gap-2 mb-8">
                    <a href="https://www.facebook.com/Club.Audivisuel.HammamSousse/?locale=ar_AR" target="_blank">
                        <img src="./assets/Logo club.png" class="w-8" alt="Logo" />
                    </a>
                    <p class="m-0 text-[16px] font-semibold dark:text-white">Login to your Account</p>
                    <span class="m-0 text-xs max-w-[90%] text-center text-[#8B8E98]">
                        Get started with our app, just start section and enjoy experience.
                    </span>
                </div>
                <form method="POST" class="w-full">
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 text-xs text-center mb-4"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <div class="w-full flex flex-col gap-2">
                        <label class="font-semibold text-xs text-gray-400">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="border rounded-lg px-3 py-2 mb-5 text-sm w-full outline-none dark:border-gray-500 dark:bg-gray-900" 
                            placeholder="Username" 
                            required 
                        />
                    </div>
                    <div class="w-full flex flex-col gap-2">
                        <label class="font-semibold text-xs text-gray-400">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="border rounded-lg px-3 py-2 mb-5 text-sm w-full outline-none dark:border-gray-500 dark:bg-gray-900" 
                            placeholder="••••••••" 
                            required 
                        />
                    </div>
                    <div class="mt-5">
                        <button 
                            type="submit" 
                            name="login" 
                            class="py-1 px-8 bg-blue-500 hover:bg-blue-800 focus:ring-offset-blue-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg cursor-pointer select-none"
                        >
                            Login
                        </button>
                    </div>
                </form>
                <p class="mt-4 text-xs text-center text-[#8B8E98]">
                    Not a member? <a href="./pages/Register/register.php" class="text-blue-500 hover:underline">Register</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>