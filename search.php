<?php
session_start();
require_once 'db.php';

// Get search parameters
$query = sanitize($_GET['q'] ?? '');
$category_id = sanitize($_GET['category'] ?? '');
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 0);
$condition = sanitize($_GET['condition'] ?? '');
$location = sanitize($_GET['location'] ?? '');
$sort_by = sanitize($_GET['sort'] ?? 'newest');

// Build search query
$where_conditions = ["a.status = 'active'"];
$params = [];

if (!empty($query)) {
    $where_conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

if (!empty($category_id)) {
    $where_conditions[] = "a.category_id = ?";
    $params[] = $category_id;
}

if ($min_price > 0) {
    $where_conditions[] = "a.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "a.price <= ?";
    $params[] = $max_price;
}

if (!empty($condition)) {
    $where_conditions[] = "a.condition_type = ?";
    $params[] = $condition;
}

if (!empty($location)) {
    $where_conditions[] = "a.location LIKE ?";
    $params[] = "%$location%";
}

// Sort options
$sort_options = [
    'newest' => 'a.created_at DESC',
    'oldest' => 'a.created_at ASC',
    'price_low' => 'a.price ASC',
    'price_high' => 'a.price DESC',
    'popular' => 'a.views_count DESC'
];

$order_by = $sort_options[$sort_by] ?? $sort_options['newest'];

// Execute search
$where_clause = implode(' AND ', $where_conditions);
$sql = "
    SELECT a.*, u.username, u.location as user_location,
           (SELECT image_path FROM ad_images WHERE ad_id = a.ad_id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name
    FROM ads a 
    JOIN users u ON a.user_id = u.user_id 
    JOIN categories c ON a.category_id = c.category_id
    WHERE $where_clause 
    ORDER BY $order_by
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get search statistics
$total_results = count($results);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - OLX Clone</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            position: relative;
            overflow: hidden;
        }

        .search-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="search" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="3" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23search)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .header-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .search-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .search-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            border-radius: var(--radius);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            color: white;
            font-size: 1rem;
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .search-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 1rem 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .search-btn:hover {
            background: rgba(255,255,255,0.3);
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

        .search-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .filters-sidebar {
            background: var(--background);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .filter-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .filter-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .filter-title {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group {
            margin-bottom: 1rem;
        }

        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--foreground);
            font-size: 0.9rem;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: var(--input);
            transition: all 0.3s ease;
            outline: none;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--ring);
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0.5rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
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
        }

        .results-section {
            background: var(--background);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .results-info {
            font-size: 1.1rem;
            color: var(--foreground);
        }

        .results-count {
            font-weight: 700;
            color: var(--primary);
        }

        .sort-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sort-label {
            font-weight: 600;
            color: var(--foreground);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .result-card {
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.15);
        }

        .result-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .result-card:hover .result-image {
            transform: scale(1.05);
        }

        .result-content {
            padding: 1.5rem;
        }

        .result-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--foreground);
            line-height: 1.4;
        }

        .result-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .result-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }

        .result-location {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .result-date {
            font-size: 0.8rem;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
        }

        .no-results-icon {
            font-size: 4rem;
            color: var(--muted-foreground);
            margin-bottom: 1rem;
        }

        .no-results-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .no-results-text {
            color: var(--muted-foreground);
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .search-layout {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: static;
            }
            
            .results-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .sort-controls {
                justify-content: center;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
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

        <div class="search-header">
            <div class="header-content">
                <h1 class="search-title">
                    <?= !empty($query) ? 'Search Results for "' . htmlspecialchars($query) . '"' : 'Browse Listings' ?>
                </h1>
                <form class="search-form" method="GET">
                    <input type="text" name="q" class="search-input" 
                           placeholder="Search for anything..." value="<?= htmlspecialchars($query) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="search-layout">
            <div class="filters-sidebar">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                    
                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-tag"></i> Category
                        </h3>
                        <div class="filter-group">
                            <select name="category" class="filter-select" onchange="submitFilters()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>" 
                                            <?= $category_id === $cat['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-dollar-sign"></i> Price Range
                        </h3>
                        <div class="price-range">
                            <input type="number" name="min_price" class="filter-input" 
                                   placeholder="Min" value="<?= $min_price > 0 ? $min_price : '' ?>">
                            <span>-</span>
                            <input type="number" name="max_price" class="filter-input" 
                                   placeholder="Max" value="<?= $max_price > 0 ? $max_price : '' ?>">
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-star"></i> Condition
                        </h3>
                        <div class="filter-group">
                            <select name="condition" class="filter-select" onchange="submitFilters()">
                                <option value="">Any Condition</option>
                                <option value="new" <?= $condition === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="like_new" <?= $condition === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>Good</option>
                                <option value="fair" <?= $condition === 'fair' ? 'selected' : '' ?>>Fair</option>
                                <option value="poor" <?= $condition === 'poor' ? 'selected' : '' ?>>Poor</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </h3>
                        <div class="filter-group">
                            <input type="text" name="location" class="filter-input" 
                                   placeholder="Enter location" value="<?= htmlspecialchars($location) ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    
                    <a href="search.php" class="btn btn-outline" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-times"></i> Clear All
                    </a>
                </form>
            </div>

            <div class="results-section">
                <div class="results-header">
                    <div class="results-info">
                        <span class="results-count"><?= $total_results ?></span> 
                        <?= $total_results === 1 ? 'result' : 'results' ?> found
                    </div>
                    
                    <div class="sort-controls">
                        <label class="sort-label">Sort by:</label>
                        <select name="sort" class="filter-select" style="width: auto;" onchange="updateSort(this.value)">
                            <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_low" <?= $sort_by === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sort_by === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="popular" <?= $sort_by === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h2 class="no-results-title">No results found</h2>
                        <p class="no-results-text">
                            Try adjusting your search criteria or browse our categories to find what you're looking for.
                        </p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($results as $index => $result): ?>
                            <a href="ad-details.php?id=<?= $result['ad_id'] ?>" class="result-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                                <img src="<?= $result['primary_image'] ? 'uploads/' . $result['primary_image'] : '/placeholder.svg?height=200&width=300' ?>" 
                                     alt="<?= htmlspecialchars($result['title']) ?>" class="result-image">
                                <div class="result-content">
                                    <h3 class="result-title"><?= htmlspecialchars($result['title']) ?></h3>
                                    <div class="result-price">$<?= number_format($result['price'], 2) ?></div>
                                    <div class="result-meta">
                                        <div class="result-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($result['location']) ?>
                                        </div>
                                        <div class="result-date">
                                            <?= date('M j', strtotime($result['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function submitFilters() {
            document.getElementById('filterForm').submit();
        }

        function updateSort(sortValue) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sortValue);
            window.location.href = url.toString();
        }

        // Auto-submit price filters after user stops typing
        let priceTimeout;
        document.querySelectorAll('input[name="min_price"], input[name="max_price"]').forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(priceTimeout);
                priceTimeout = setTimeout(() => {
                    submitFilters();
                }, 1000);
            });
        });

        // Location filter auto-submit
        let locationTimeout;
        document.querySelector('input[name="location"]').addEventListener('input', function() {
            clearTimeout(locationTimeout);
            locationTimeout = setTimeout(() => {
                submitFilters();
            }, 1000);
        });

        // Animate cards on load
        document.querySelectorAll('.result-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>
