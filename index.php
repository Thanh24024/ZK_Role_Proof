<?php
session_start();

// Kết nối CSDL
$servername = "localhost";
$port = "3306";
$database = "zk_proof";
$username = "root";
$password = "";

try {
    $dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        // Băm mật khẩu người dùng nhập để so với password_hash đã lưu
        $hashedPassword = hash('sha256', $password); // Giả sử bạn dùng SHA-256 để lưu password

        // Truy vấn: email + mật khẩu + vai trò
        $sql = "
            SELECT u.id, u.email, r.name AS role_name
            FROM user u
            JOIN userrole ur ON u.id = ur.user_id
            JOIN role r ON ur.role_id = r.id
            WHERE u.email = ? AND u.password = ? AND r.name = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$email, $hashedPassword, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Lưu session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role_name'];

            // Điều hướng theo vai trò
            switch ($role) {
                case 'Admin':   header('Location: admin-dashboard.html');   break;
                case 'IT':      header('Location: it_department.html');     break;
                case 'HR':      header('Location: hr-dashboard.html');      break;
                case 'Finance': header('Location: finance-dashboard.html'); break;
                default:        header('Location: index.html');             break;
            }
            exit;
        } else {
            $error = 'Sai email, mật khẩu hoặc vai trò!';
        }
    }
}

// In thông báo lỗi nếu có
if (!empty($error)) {
    echo "<script>alert('❌ $error'); window.history.back();</script>";
}
?>

<!-- HTML UI (như bạn gửi) -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập với ZKP + PHP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="zkp-animation inline-block">
            <i class="fas fa-shield-alt text-white text-5xl mb-4"></i>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">ZKP Authentication</h1>
        <p class="text-gray-200">Zero-Knowledge Proof với RBAC</p>
    </div>

    <div class="glass-effect rounded-2xl p-8 shadow-2xl">
        <?php if (!empty($error)): ?>
            <div class="bg-red-500 bg-opacity-30 border border-red-400 text-white rounded p-3 mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-white mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" name="email" id="email" required placeholder="Nhập email"
                       class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-gray-300 focus:outline-none">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-white mb-2">
                    <i class="fas fa-lock mr-2"></i>Mật khẩu
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" required placeholder="Nhập mật khẩu"
                           class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-gray-300 pr-12 focus:outline-none">
                    <button type="button" id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-white">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-white mb-3">
                    <i class="fas fa-users mr-2"></i>Chọn vai trò
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <?php
                    $roles = ['IT' => 'fa-laptop-code', 'HR' => 'fa-user-tie', 'Finance' => 'fa-calculator', 'Admin' => 'fa-crown'];
                    foreach ($roles as $r => $icon):
                    ?>
                        <label class="role-option cursor-pointer">
                            <input type="radio" name="role" value="<?= $r ?>" class="sr-only">
                            <div class="p-3 bg-white bg-opacity-20 border-2 border-white border-opacity-30 rounded-lg text-center text-white hover:bg-opacity-30 transition-all">
                                <i class="fas <?= $icon ?> text-xl mb-1"></i>
                                <div class="text-sm font-medium"><?= $r ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập với ZKP
            </button>
        </form>
    </div>

    <div class="text-center mt-6 text-white text-sm opacity-75">
        <p>&copy; <a href="https://github.com/trannhatbuilder" target="_blank" rel="noopener noreferrer">trannhatbuilder</a></p>
    </div>
</div>

<script>
    // Hiển thị mật khẩu
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>
</body>
</html>
