<?php
/**
 * Header Template - All Pages
 * Includes navigation, logo, and user menu
 */

// Check if user is logged in
$customerId = $_SESSION['customer_id'] ?? null;
$customerName = $_SESSION['customer_name'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GlamByMariga - Luxury Beauty Studio. Premium lashes, wigs, and beauty services in Kenya.">
    <meta name="theme-color" content="#B76E79">

    <title><?php echo isset($pageTitle) ? $pageTitle . ' - GlamByMariga' : 'GlamByMariga - Luxury Beauty Studio'; ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">

    <?php if (isset($customCSS)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($customCSS); ?>">
    <?php endif; ?>
</head>

<body>

    <!-- Header Navigation -->
    <header>
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <a href="/">
                        <i class="fas fa-spa"></i> GlamByMariga
                    </a>
                </div>

                <!-- Main Navigation -->
                <nav class="main-nav">
                    <ul>
                        <li><a href="/" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                        <li><a href="/services.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>">Services</a></li>
                        <li><a href="/shop.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>">Shop</a></li>
                        <li><a href="/gallery.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'gallery.php' ? 'active' : ''; ?>">Gallery</a></li>
                        <li><a href="/about.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>">About</a></li>
                        <li><a href="/contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                    </ul>
                </nav>

                <!-- Right Actions -->
                <div class="header-actions">
                    <!-- Search -->
                    <button class="btn-icon search-btn" id="searchBtn" title="Search">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Wishlist -->
                    <a href="/customer/wishlist.php" class="btn-icon" title="Wishlist">
                        <i class="fas fa-heart"></i>
                        <span class="badge" id="wishlistCount">0</span>
                    </a>

                    <!-- Shopping Cart -->
                    <a href="/shop/cart.php" class="btn-icon" title="Shopping Cart">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="badge" id="cartCount">0</span>
                    </a>

                    <!-- User Menu -->
                    <?php if ($customerId): ?>
                        <div class="user-menu">
                            <button class="btn-user" id="userMenuBtn">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars(substr($customerName, 0, 10)); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>

                            <div class="dropdown-menu user-dropdown" id="userDropdown">
                                <a href="/customer/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                                <a href="/customer/bookings.php" class="dropdown-item">
                                    <i class="fas fa-calendar"></i> Bookings
                                </a>
                                <a href="/customer/orders.php" class="dropdown-item">
                                    <i class="fas fa-shopping-cart"></i> Orders
                                </a>
                                <a href="/customer/profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <hr class="dropdown-divider">
                                <a href="/auth/logout.php" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/auth/login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Search Modal -->
    <div class="modal" id="searchModal">
        <div class="modal-content">
            <button class="modal-close" id="searchClose">
                <i class="fas fa-times"></i>
            </button>

            <div class="search-form">
                <input type="text" id="searchInput" placeholder="Search services, products, articles..." class="search-input">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <nav>
            <a href="/">Home</a>
            <a href="/services.php">Services</a>
            <a href="/shop.php">Shop</a>
            <a href="/gallery.php">Gallery</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
            <hr>
            <?php if ($customerId): ?>
                <a href="/customer/dashboard.php">Dashboard</a>
                <a href="/auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="/auth/login.php">Login</a>
                <a href="/auth/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="main-content">
