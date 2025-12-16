<?php
// auth.php - Authentication handler
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    
    if (empty($role)) {
        header('Location: index.php');
        exit();
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // For publisher login, we check against the publishers table using email and password
        if ($role === 'publisher') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                header('Location: publisher_login.php?error=Please fill in all fields');
                exit();
            }
            
            $stmt = $conn->prepare("SELECT id, name, email, password FROM publishers WHERE email = ?");
            $stmt->execute([$email]);
            $publisher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($publisher && $password === $publisher['password']) {
                $_SESSION['user_id'] = $publisher['id'];
                $_SESSION['username'] = $publisher['name'];
                $_SESSION['email'] = $publisher['email'];
                $_SESSION['role'] = 'publisher';
                header('Location: publisher_dashboard.php');
                exit();
            } else {
                header('Location: publisher_login.php?error=Invalid credentials');
                exit();
            }
        } 
        // For admin/super_admin login, we check against the users table
        else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                header('Location: login.php?error=Please fill in all fields');
                exit();
            }
            
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'super_admin') {
                    header('Location: super_admin/dashboard.php');
                } else if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php?error=Invalid role');
                }
                exit();
            } else {
                header('Location: login.php?error=Invalid credentials');
                exit();
            }
        }
    } catch (PDOException $e) {
        if ($role === 'publisher') {
            header('Location: publisher_login.php?error=Authentication error');
        } else {
            header('Location: login.php?error=Authentication error');
        }
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>