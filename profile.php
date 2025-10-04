<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle profile update
if ($_POST) {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $error = 'Full name is required';
    } elseif ($new_password && strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } elseif ($new_password && $new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif ($new_password && !verifyPassword($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } else {
        // Update user data
        if ($new_password) {
            $hashed_password = hashPassword($new_password);
            $stmt = $pdo->prepare("
                UPDATE users SET full_name = ?, phone = ?, location = ?, password = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $result = $stmt->execute([$full_name, $phone, $location, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET full_name = ?, phone = ?, location = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $result = $stmt->execute([$full_name, $phone, $location, $user_id]);
        }
        
        if ($result) {
            $success = 'Profile updated successfully!';
            $_SESSION['full_name'] = $full_name;
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Get user's ads count
$stmt = $pdo->prepare("SELECT COUNT(*) as total_ads FROM ads WHERE user_id = ?");
$stmt->execute([$user_id]);
$ads_count = $stmt->fetch()['total_ads'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - OLX Clone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --background: #ffffff;
            --foreground: #475569;
            --card: #f1f5f9;
            --card-foreground: #475569;
            --primary: #059669;
            --primary-foreground: #ffffff;
            --secondary: #10b981;
            --secondary-foreground: #ffffff;
            --muted: #f1f5f9;
            --muted-foreground: #475569;
            --accent: #10b981;
            --accent-foreground: #ffffff;
            --destructive: #f44336;
            --destructive-foreground: #ffffff;
            --border: #e1e1e1;
            --input: #f1f5f9;
            --ring: rgba(5, 150, 105, 0.5);
            --radius: 0.5rem;
        }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: var(--card);
            color: var(--foreground);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="circles" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="2" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23circles)"/></svg>');
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .profile-content {
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .profile-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .profile-sidebar {
            background: var(--background);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 1rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: var(--foreground);
            text-decoration: none;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary);
            color: var(--primary-foreground);
            transform: translateX(5px);
        }

        .profile-main {
            background: var(--background);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--foreground);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            background: var(--input);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--ring);
            transform: translateY(-2px);
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--primary-foreground);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--destructive);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.1);
            color: var(--primary);
            border: 1px solid rgba(5, 150, 105, 0.2);
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-home:hover {
            transform: translateX(-5px);
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="profile-header">
            <div class="profile-content">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
                <p>@<?= htmlspecialchars($user['username']) ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $ads_count ?></div>
                        <div class="stat-label">Total Ads</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= date('M Y', strtotime($user['created_at'])) ?></div>
                        <div class="stat-label">Member Since</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <div class="profile-sidebar">
                <ul class="sidebar-menu">
                    <li><a href="#" class="active"><i class="fas fa-user"></i> Profile Settings</a></li>
                    <li><a href="my-ads.php"><i class="fas fa-list"></i> My Ads</a></li>
                    <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                    <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>

            <div class="profile-main">
                <h2 class="section-title">Profile Settings</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-input" 
                                   value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-input" 
                               value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                   value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" id="location" name="location" class="form-input" 
                                   value="<?= htmlspecialchars($user['location']) ?>">
                        </div>
                    </div>

                    <h3 style="margin: 2rem 0 1rem; color: var(--foreground);">Change Password (Optional)</h3>

                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" 
                               placeholder="Enter current password">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                   placeholder="Enter new password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Confirm new password">
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        confirmPassword.addEventListener('input', function() {
            if (this.value && this.value !== newPassword.value) {
                this.style.borderColor = 'var(--destructive)';
            } else {
                this.style.borderColor = 'var(--border)';
            }
        });

        // Input focus effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
