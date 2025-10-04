<?php
session_start();
require_once 'db.php';

// Fetch featured ads
$featuredAds = $pdo->query("
    SELECT a.*, u.username, u.location as user_location, 
           (SELECT image_path FROM ad_images WHERE ad_id = a.ad_id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name
    FROM ads a 
    JOIN users u ON a.user_id = u.user_id 
    JOIN categories c ON a.category_id = c.category_id
    WHERE a.status = 'active' AND a.is_featured = 1 
    ORDER BY a.created_at DESC LIMIT 8
")->fetchAll();

// Fetch recent ads
$recentAds = $pdo->query("
    SELECT a.*, u.username, u.location as user_location,
           (SELECT image_path FROM ad_images WHERE ad_id = a.ad_id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name
    FROM ads a 
    JOIN users u ON a.user_id = u.user_id 
    JOIN categories c ON a.category_id = c.category_id
    WHERE a.status = 'active' 
    ORDER BY a.created_at DESC LIMIT 12
")->fetchAll();

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLX Clone - Buy & Sell Everything</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: var(--background);
            color: var(--foreground);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: var(--background);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            gap: 2rem;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            text-shadow: 0 0 20px rgba(5, 150, 105, 0.3);
        }

        .search-container {
            flex: 1;
            max-width: 600px;
            position: relative;
        }

        .search-form {
            display: flex;
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .search-form:focus-within {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(5, 150, 105, 0.2);
        }

        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            font-size: 1rem;
            outline: none;
        }

        .search-btn {
            background: var(--primary);
            color: var(--primary-foreground);
            border: none;
            padding: 1rem 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .search-btn:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
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
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--primary-foreground);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: slideInUp 1s ease-out;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: slideInUp 1s ease-out 0.2s both;
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

        /* Categories Section */
        .categories {
            padding: 3rem 0;
            background: var(--card);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 3rem;
            color: var(--foreground);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .category-card {
            background: var(--background);
            padding: 2rem 1rem;
            border-radius: var(--radius);
            text-align: center;
            text-decoration: none;
            color: var(--foreground);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.2);
            border-color: var(--primary);
        }

        .category-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.2);
            color: var(--secondary);
        }

        .category-name {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Featured Ads Section */
        .featured-ads {
            padding: 4rem 0;
            background: var(--background);
        }

        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .ad-card {
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
        }

        .ad-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
        }

        .ad-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .ad-card:hover .ad-image {
            transform: scale(1.05);
        }

        .ad-content {
            padding: 1.5rem;
        }

        .ad-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--foreground);
        }

        .ad-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .ad-location {
            color: var(--muted-foreground);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--secondary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: var(--foreground);
            color: var(--background);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: var(--background);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .search-container {
                order: 3;
                width: 100%;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .ads-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-store"></i> OLX Clone
                </a>
                
                <div class="search-container">
                    <form class="search-form" action="search.php" method="GET">
                        <input type="text" name="q" class="search-input" placeholder="Search for anything..." required>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="post-ad.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post Ad
                        </a>
                        <a href="profile.php" class="btn btn-outline">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Buy & Sell Everything</h1>
                <p>Discover amazing deals on thousands of items from trusted sellers in your area</p>
                <a href="post-ad.php" class="btn btn-primary" style="background: white; color: var(--primary); font-size: 1.1rem; padding: 1rem 2rem;">
                    <i class="fas fa-plus"></i> Start Selling Now
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Browse Categories</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="category.php?id=<?= $category['category_id'] ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?= $category['icon'] ?>"></i>
                        </div>
                        <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Ads Section -->
    <section class="featured-ads">
        <div class="container">
            <h2 class="section-title">Featured Listings</h2>
            <div class="ads-grid">
                <?php foreach ($featuredAds as $ad): ?>
                    <a href="ad-details.php?id=<?= $ad['ad_id'] ?>" class="ad-card">
                        <div class="featured-badge">
                            <i class="fas fa-star"></i> Featured
                        </div>
                        <img src="<?= $ad['primary_image'] ? 'uploads/' . $ad['primary_image'] : '/placeholder.svg?height=200&width=300' ?>" 
                             alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-image">
                        <div class="ad-content">
                            <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
                            <div class="ad-price">$<?= number_format($ad['price'], 2) ?></div>
                            <div class="ad-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($ad['location']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Recent Ads Section -->
    <section class="featured-ads" style="background: var(--card);">
        <div class="container">
            <h2 class="section-title">Latest Listings</h2>
            <div class="ads-grid">
                <?php foreach ($recentAds as $ad): ?>
                    <a href="ad-details.php?id=<?= $ad['ad_id'] ?>" class="ad-card">
                        <img src="<?= $ad['primary_image'] ? 'uploads/' . $ad['primary_image'] : '/placeholder.svg?height=200&width=300' ?>" 
                             alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-image">
                        <div class="ad-content">
                            <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
                            <div class="ad-price">$<?= number_format($ad['price'], 2) ?></div>
                            <div class="ad-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($ad['location']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About OLX Clone</h3>
                    <p>Your trusted marketplace for buying and selling everything. Connect with millions of buyers and sellers in your area.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="safety.php">Safety Tips</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Categories</h3>
                    <ul class="footer-links">
                        <li><a href="category.php?id=cat_electronics">Electronics</a></li>
                        <li><a href="category.php?id=cat_vehicles">Vehicles</a></li>
                        <li><a href="category.php?id=cat_furniture">Furniture</a></li>
                        <li><a href="category.php?id=cat_fashion">Fashion</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="#" style="color: var(--primary); font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: var(--primary); font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: var(--primary); font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: var(--primary); font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 OLX Clone. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Search form enhancement
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        const searchBtn = document.querySelector('.search-btn');

        searchForm.addEventListener('submit', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
                searchInput.style.borderColor = 'var(--destructive)';
                setTimeout(() => {
                    searchInput.style.borderColor = '';
                }, 2000);
            } else {
                searchBtn.innerHTML = '<div class="loading"></div>';
            }
        });

        // Card hover effects
        document.querySelectorAll('.ad-card, .category-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Header scroll effect
        let lastScrollTop = 0;
        const header = document.querySelector('.header');
        
        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
            
            lastScrollTop = scrollTop;
        });

        // Page redirect function using JavaScript
        function redirectTo(url) {
            window.location.href = url;
        }

        // Add loading states to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('#')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<div class="loading"></div> Loading...';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>
