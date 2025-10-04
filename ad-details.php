<?php
session_start();
require_once 'db.php';

$ad_id = $_GET['id'] ?? '';
$error = '';

if (empty($ad_id)) {
    header('Location: index.php');
    exit();
}

// Fetch ad details with user and category info
$stmt = $pdo->prepare("
    SELECT a.*, u.username, u.full_name, u.phone, u.location as user_location, u.created_at as user_since,
           c.name as category_name, c.icon as category_icon
    FROM ads a 
    JOIN users u ON a.user_id = u.user_id 
    JOIN categories c ON a.category_id = c.category_id
    WHERE a.ad_id = ? AND a.status = 'active'
");
$stmt->execute([$ad_id]);
$ad = $stmt->fetch();

if (!$ad) {
    $error = 'Ad not found or no longer available';
}

// Fetch ad images
$images = [];
if ($ad) {
    $stmt = $pdo->prepare("SELECT * FROM ad_images WHERE ad_id = ? ORDER BY is_primary DESC, sort_order");
    $stmt->execute([$ad_id]);
    $images = $stmt->fetchAll();
    
    // Update view count
    $pdo->prepare("UPDATE ads SET views_count = views_count + 1 WHERE ad_id = ?")->execute([$ad_id]);
}

// Fetch related ads
$related_ads = [];
if ($ad) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.username,
               (SELECT image_path FROM ad_images WHERE ad_id = a.ad_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM ads a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.category_id = ? AND a.ad_id != ? AND a.status = 'active' 
        ORDER BY a.created_at DESC LIMIT 4
    ");
    $stmt->execute([$ad['category_id'], $ad_id]);
    $related_ads = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ad ? htmlspecialchars($ad['title']) . ' - OLX Clone' : 'Ad Not Found' ?></title>
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

        .back-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .back-nav:hover {
            transform: translateX(-5px);
            color: var(--secondary);
        }

        .ad-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .ad-main {
            background: var(--background);
            border-radius: var(--radius);
            overflow: hidden;
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

        .image-gallery {
            position: relative;
            height: 400px;
            overflow: hidden;
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .image-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .image-nav:hover {
            background: rgba(0,0,0,0.7);
        }

        .image-nav.prev {
            left: 1rem;
        }

        .image-nav.next {
            right: 1rem;
        }

        .image-indicators {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
        }

        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
        }

        .ad-content {
            padding: 2rem;
        }

        .ad-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--foreground);
        }

        .ad-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .ad-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }

        .ad-description {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .ad-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .seller-card,
        .contact-card {
            background: var(--background);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            animation: slideInRight 0.8s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--foreground);
        }

        .seller-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .seller-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .seller-details h3 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .seller-details p {
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }

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
            margin-bottom: 1rem;
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

        .related-ads {
            margin-top: 3rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--foreground);
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .related-card {
            background: var(--background);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.15);
        }

        .related-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .related-content {
            padding: 1rem;
        }

        .related-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .related-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .error-message {
            text-align: center;
            padding: 3rem;
            background: var(--background);
            border-radius: var(--radius);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .error-icon {
            font-size: 4rem;
            color: var(--muted-foreground);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .ad-container {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .ad-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-nav">
            <i class="fas fa-arrow-left"></i> Back to Listings
        </a>

        <?php if ($error): ?>
            <div class="error-message">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2><?= htmlspecialchars($error) ?></h2>
                <p>The ad you're looking for might have been removed or is no longer available.</p>
            </div>
        <?php else: ?>
            <div class="ad-container">
                <div class="ad-main">
                    <?php if (!empty($images)): ?>
                        <div class="image-gallery">
                            <img src="uploads/<?= $images[0]['image_path'] ?>" alt="<?= htmlspecialchars($ad['title']) ?>" class="main-image" id="mainImage">
                            
                            <?php if (count($images) > 1): ?>
                                <button class="image-nav prev" onclick="changeImage(-1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="image-nav next" onclick="changeImage(1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                
                                <div class="image-indicators">
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="indicator <?= $index === 0 ? 'active' : '' ?>" onclick="showImage(<?= $index ?>)"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="image-gallery">
                            <img src="/placeholder.svg?height=400&width=600" alt="No image" class="main-image">
                        </div>
                    <?php endif; ?>

                    <div class="ad-content">
                        <h1 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h1>
                        <div class="ad-price">$<?= number_format($ad['price'], 2) ?></div>
                        
                        <div class="ad-meta">
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($ad['location']) ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($ad['category_name']) ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-star"></i>
                                <?= ucfirst($ad['condition_type']) ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <?= $ad['views_count'] ?> views
                            </div>
                        </div>
                        
                        <div class="ad-description">
                            <?= nl2br(htmlspecialchars($ad['description'])) ?>
                        </div>
                    </div>
                </div>

                <div class="ad-sidebar">
                    <div class="seller-card">
                        <h3 class="card-title">Seller Information</h3>
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <?= strtoupper(substr($ad['full_name'], 0, 1)) ?>
                            </div>
                            <div class="seller-details">
                                <h3><?= htmlspecialchars($ad['full_name']) ?></h3>
                                <p>Member since <?= date('M Y', strtotime($ad['user_since'])) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-card">
                        <h3 class="card-title">Contact Seller</h3>
                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== $ad['user_id']): ?>
                            <a href="chat.php?ad_id=<?= $ad['ad_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-comment"></i> Send Message
                            </a>
                            
                            <?php if ($ad['contact_phone']): ?>
                                <a href="tel:<?= $ad['contact_phone'] ?>" class="btn btn-outline">
                                    <i class="fas fa-phone"></i> Call Now
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline" onclick="toggleFavorite()">
                                <i class="fas fa-heart"></i> Add to Favorites
                            </button>
                        <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $ad['user_id']): ?>
                            <a href="edit-ad.php?id=<?= $ad['ad_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Ad
                            </a>
                            <button class="btn btn-outline" onclick="markAsSold()">
                                <i class="fas fa-check"></i> Mark as Sold
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Contact
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($related_ads)): ?>
                <div class="related-ads">
                    <h2 class="section-title">Related Ads</h2>
                    <div class="related-grid">
                        <?php foreach ($related_ads as $related): ?>
                            <a href="ad-details.php?id=<?= $related['ad_id'] ?>" class="related-card">
                                <img src="<?= $related['primary_image'] ? 'uploads/' . $related['primary_image'] : '/placeholder.svg?height=150&width=250' ?>" 
                                     alt="<?= htmlspecialchars($related['title']) ?>" class="related-image">
                                <div class="related-content">
                                    <h3 class="related-title"><?= htmlspecialchars($related['title']) ?></h3>
                                    <div class="related-price">$<?= number_format($related['price'], 2) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Image gallery functionality
        const images = <?= json_encode(array_column($images, 'image_path')) ?>;
        let currentImageIndex = 0;

        function showImage(index) {
            currentImageIndex = index;
            document.getElementById('mainImage').src = 'uploads/' + images[index];
            
            // Update indicators
            document.querySelectorAll('.indicator').forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        }

        function changeImage(direction) {
            currentImageIndex += direction;
            
            if (currentImageIndex >= images.length) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = images.length - 1;
            }
            
            showImage(currentImageIndex);
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') changeImage(-1);
            if (e.key === 'ArrowRight') changeImage(1);
        });

        // Favorite functionality
        function toggleFavorite() {
            // Implementation for adding/removing favorites
            alert('Favorite functionality will be implemented');
        }

        // Mark as sold functionality
        function markAsSold() {
            if (confirm('Are you sure you want to mark this ad as sold?')) {
                // Implementation for marking as sold
                alert('Mark as sold functionality will be implemented');
            }
        }
    </script>
</body>
</html>
