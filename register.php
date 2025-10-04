<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_POST) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            // Create new user
            $user_id = generateUniqueId();
            $hashed_password = hashPassword($password);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (user_id, username, email, password, full_name, phone, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $username, $email, $hashed_password, $full_name, $phone, $location])) {
                $success = 'Account created successfully! You can now sign in.';
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OLX Clone</title>
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
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            background: var(--background);
            border-radius: var(--radius);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 700px;
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-visual {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .auth-visual::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hexagons" width="30" height="30" patternUnits="userSpaceOnUse"><polygon points="15,5 25,12 25,22 15,29 5,22 5,12" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23hexagons)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(1deg); }
        }

        .visual-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .visual-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .visual-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .visual-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .auth-form {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: var(--muted-foreground);
            font-size: 1rem;
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

        .required {
            color: var(--destructive);
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

        .form-input.error {
            border-color: var(--destructive);
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-weak { color: var(--destructive); }
        .strength-medium { color: #ff9800; }
        .strength-strong { color: var(--primary); }

        .btn {
            width: 100%;
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
            justify-content: center;
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

        .btn-primary:active {
            transform: translateY(0);
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

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--secondary);
        }

        .back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .back-home:hover {
            transform: translateX(-5px);
            color: rgba(255,255,255,0.8);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .auth-visual {
                display: none;
            }
            
            .auth-form {
                padding: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="auth-container">
        <div class="auth-visual">
            <div class="visual-content">
                <div class="visual-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2 class="visual-title">Join Our Community!</h2>
                <p class="visual-subtitle">Create your account and start buying and selling amazing products with thousands of trusted users.</p>
            </div>
        </div>

        <div class="auth-form">
            <div class="form-header">
                <h1 class="form-title">Create Account</h1>
                <p class="form-subtitle">Fill in your details to get started</p>
            </div>

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

            <form method="POST" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input" 
                               placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-input" 
                               placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Create a password" required>
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                               placeholder="Confirm your password" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               placeholder="Your phone number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" id="location" name="location" class="form-input" 
                               placeholder="Your city/area" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and enhancement
        const form = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    message = '<span class="strength-weak">Weak password</span>';
                    break;
                case 2:
                case 3:
                    message = '<span class="strength-medium">Medium password</span>';
                    break;
                case 4:
                case 5:
                    message = '<span class="strength-strong">Strong password</span>';
                    break;
            }
            
            passwordStrength.innerHTML = message;
        });

        // Password confirmation checker
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validate required fields
            const requiredFields = ['full_name', 'username', 'email', 'password', 'confirm_password'];
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                }
            });
            
            // Validate password match
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.classList.add('error');
                isValid = false;
            }
            
            // Validate password length
            if (passwordInput.value.length < 6) {
                passwordInput.classList.add('error');
                isValid = false;
            }
            
            if (isValid) {
                registerBtn.innerHTML = '<div class="loading"></div> Creating Account...';
                registerBtn.disabled = true;
            } else {
                e.preventDefault();
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

        // Auto-focus first field
        document.getElementById('full_name').focus();
    </script>
</body>
</html>
