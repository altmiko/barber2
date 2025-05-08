<?php
if (!isset($page_title)) {
    $page_title = "Barberbook";
}

// Get the base path for assets
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/barber/') !== false || strpos($_SERVER['PHP_SELF'], '/customer/') !== false) {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Barberbook</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $base_path; ?>assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="<?php echo $base_path; ?>index.php">
                        <i class="fas fa-cut"></i>
                        <span>Barberbook</span>
                    </a>
                </div>
                
                <nav class="navigation">
                    <ul class="nav-links">
                        <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                        <li><a href="<?php echo $base_path; ?>services.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>">Services</a></li>
                        <li><a href="<?php echo $base_path; ?>barbers.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'barbers.php') ? 'active' : ''; ?>">Barbers</a></li>
                        <li><a href="<?php echo $base_path; ?>booking.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'booking.php') ? 'active' : ''; ?>">Book Now</a></li>
                    </ul>
                </nav>
                
                <div class="auth-buttons">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isBarber()): ?>
                            <a href="<?php echo $base_path; ?>barber/dashboard.php" class="btn btn-secondary">Dashboard</a>
                        <?php else: ?>
                            <a href="<?php echo $base_path; ?>customer/dashboard.php" class="btn btn-secondary">Dashboard</a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path; ?>logout.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>login.php" class="btn btn-outline">Login</a>
                        <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>
    
    <div class="mobile-menu">
        <ul class="mobile-nav-links">
            <li><a href="<?php echo $base_path; ?>index.php">Home</a></li>
            <li><a href="<?php echo $base_path; ?>services.php">Services</a></li>
            <li><a href="<?php echo $base_path; ?>barbers.php">Barbers</a></li>
            <li><a href="<?php echo $base_path; ?>booking.php">Book Now</a></li>
            <li><a href="<?php echo $base_path; ?>contact.php">Contact</a></li>
        </ul>
        
        <div class="mobile-auth-buttons">
            <?php if (isLoggedIn()): ?>
                <?php if (isBarber()): ?>
                    <a href="<?php echo $base_path; ?>barber/dashboard.php" class="btn btn-secondary">Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>customer/dashboard.php" class="btn btn-secondary">Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php" class="btn btn-outline">Login</a>
                <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Flash Message Display -->
    <div class="flash-message-container">
        <?php echo displayFlashMessage(); ?>
    </div>