<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=post-ad.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();

if ($_POST) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = sanitize($_POST['category_id']);
    $condition_type = sanitize($_POST['condition_type']);
    $location = sanitize($_POST['location']);
    $contact_phone = sanitize($_POST['contact_phone']);
    $contact_email = sanitize($_POST['contact_email']);
    
    // Validation
    if (empty($title) || empty($description) || empty($category_id) || empty($location)) {
        $error = 'Please fill in all required fields';
    } elseif ($price <= 0) {
        $error = 'Please enter a valid price';
    } elseif (strlen($title) < 5) {
        $error = 'Title must be at least 5 characters long';
    } elseif (strlen($description) < 20) {
        $error = 'Description must be at least 20 characters long';
    } else {
        // Create ad
        $ad_id = generateUniqueId();
        
        $stmt = $pdo->prepare("
            INSERT INTO ads (ad_id, user_id, category_id, title, description, price, condition_type, location, contact_phone, contact_email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$ad_id, $user_id, $category_id, $title, $description, $price, $condition_type, $location, $contact_phone, $contact_email])) {
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $uploaded_images = 0;
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if (!empty($tmp_name)) {
                        $file_name = $_FILES['images']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = $ad_id . '_' . time() . '_' . $uploaded_images . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($tmp_name, $upload_path)) {
                                $image_id = generateUniqueId();
                                $is_primary = ($uploaded_images === 0) ? 1 : 0;
                                
                                $stmt = $pdo->prepare("
                                    INSERT INTO ad_images (image_id, ad_id, image_path, image_name, is_primary, sort_order) 
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([$image_id, $ad_id, $new_filename, $file_name, $is_primary, $uploaded_images]);
                                $uploaded_images++;
                            }
                        }
                    }
                }
            }
            
            $success = 'Ad posted successfully!';
            // Clear form data
            $_POST = [];
        } else {
            $error = 'Failed to post ad. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post an Ad - OLX Clone</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="plus" width="25" height="25" patternUnits="userSpaceOnUse"><path d="M12.5,5 L12.5,20 M5,12.5 L20,12.5" stroke="white" stroke-width="1" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23plus)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .form-container {
            background: var(--background);
            border-radius: var(--radius);
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
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

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            background: var(--input);
            transition: all 0.3s ease;
            outline: none;
            font-family: inherit;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--ring);
            transform: translateY(-2px);
        }

        .image-upload {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .image-upload:hover {
            border-color: var(--primary);
            background: rgba(5, 150, 105, 0.05);
        }

        .image-upload.dragover {
            border-color: var(--secondary);
            background: rgba(16, 185, 129, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--muted-foreground);
            margin-bottom: 1rem;
        }

        .upload-text {
            color: var(--muted-foreground);
            margin-bottom: 1rem;
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            aspect-ratio: 1;
        }

        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-image {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--destructive);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .remove-image:hover {
            transform: scale(1.1);
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

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="page-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h1 class="page-title">Post Your Ad</h1>
                <p class="page-subtitle">Share your item with thousands of potential buyers</p>
            </div>
        </div>

        <div class="form-container">
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

            <form method="POST" enctype="multipart/form-data" id="adForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </h2>
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Ad Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-input" 
                               placeholder="What are you selling?" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-textarea" 
                                  placeholder="Describe your item in detail..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category <span class="required">*</span></label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>" 
                                            <?= (($_POST['category_id'] ?? '') === $category['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="condition_type" class="form-label">Condition</label>
                            <select id="condition_type" name="condition_type" class="form-select">
                                <option value="new" <?= (($_POST['condition_type'] ?? '') === 'new') ? 'selected' : '' ?>>New</option>
                                <option value="like_new" <?= (($_POST['condition_type'] ?? '') === 'like_new') ? 'selected' : '' ?>>Like New</option>
                                <option value="good" <?= (($_POST['condition_type'] ?? 'good') === 'good') ? 'selected' : '' ?>>Good</option>
                                <option value="fair" <?= (($_POST['condition_type'] ?? '') === 'fair') ? 'selected' : '' ?>>Fair</option>
                                <option value="poor" <?= (($_POST['condition_type'] ?? '') === 'poor') ? 'selected' : '' ?>>Poor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Price ($) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" class="form-input" 
                               placeholder="0.00" step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Images -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-images"></i> Photos
                    </h2>
                    
                    <div class="image-upload" id="imageUpload">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <strong>Click to upload</strong> or drag and drop images here
                        </div>
                        <div style="font-size: 0.9rem; color: var(--muted-foreground);">
                            Maximum 5 images, JPG/PNG only
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                    </div>
                    
                    <div class="image-preview" id="imagePreview"></div>
                </div>

                <!-- Location & Contact -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Location & Contact
                    </h2>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" class="form-input" 
                               placeholder="City, State" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-input" 
                                   placeholder="Your phone number" value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="contact_email" class="form-label">Email</label>
                            <input type="email" id="contact_email" name="contact_email" class="form-input" 
                                   placeholder="Your email" value="<?= htmlspecialchars($_POST['contact_email'] ?? $_SESSION['email']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-plus"></i> Post Ad
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image upload handling
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        let selectedFiles = [];

        imageUpload.addEventListener('click', () => imageInput.click());

        imageUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUpload.classList.add('dragover');
        });

        imageUpload.addEventListener('dragleave', () => {
            imageUpload.classList.remove('dragover');
        });

        imageUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUpload.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        imageInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            for (let file of files) {
                if (selectedFiles.length >= 5) {
                    alert('Maximum 5 images allowed');
                    break;
                }
                
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    displayImage(file, selectedFiles.length - 1);
                }
            }
            updateFileInput();
        }

        function displayImage(file, index) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                    <button type="button" class="remove-image" onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                imagePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
            updateFileInput();
        }

        function updatePreview() {
            imagePreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                displayImage(file, index);
            });
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imageInput.files = dt.files;
        }

        // Form validation
        const form = document.getElementById('adForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = document.getElementById('price').value;
            const category = document.getElementById('category_id').value;
            const location = document.getElementById('location').value.trim();

            if (!title || !description || !price || !category || !location) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }

            if (title.length < 5) {
                e.preventDefault();
                alert('Title must be at least 5 characters long');
                return;
            }

            if (description.length < 20) {
                e.preventDefault();
                alert('Description must be at least 20 characters long');
                return;
            }

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
            submitBtn.disabled = true;
        });

        // Input focus effects
        document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(input => {
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
