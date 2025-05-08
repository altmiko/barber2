<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Our Services";
include 'includes/header.php';

$sql = "SELECT * FROM Services ORDER BY Price";
$result = $conn->query($sql);
?>

<main>
    <div class="container">
        <h1>Our Services</h1>
        <div class="services-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="service-card">';
                    echo '<div class="service-icon"><i class="fas fa-cut"></i></div>';
                    echo '<h3>' . htmlspecialchars($row['Name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['Description']) . '</p>';
                    echo '<div class="service-details">';
                    echo '<span class="duration"><i class="far fa-clock"></i> ' . htmlspecialchars($row['Duration']) . ' min</span>';
                    echo '<span class="price">BDT ' . number_format($row['Price'], 2) . '</span>';
                    echo '</div>';
                    echo '<a href="booking.php?service=' . $row['ServiceID'] . '" class="btn btn-outline">Book Now</a>';
                    echo '</div>';
                }
            } else {
                echo '<p class="no-services">No services available at the moment.</p>';
            }
            ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<style>
.services-hero {
    background-color: #1a365d;
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.services-hero h1 {
    font-size: 3.6rem;
    margin-bottom: 1rem;
}

.services-hero p {
    font-size: 1.8rem;
    opacity: 0.9;
}

.services-section {
    padding: 4rem 0;
    background-color: #f5f7fa;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.service-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.service-card:hover {
    transform: translateY(-4px);
}

.service-image {
    height: 200px;
    overflow: hidden;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-content {
    padding: 2rem;
}

.service-content h3 {
    margin-bottom: 1rem;
    font-size: 2rem;
}

.service-description {
    color: #666;
    margin-bottom: 1.5rem;
    font-size: 1.4rem;
    line-height: 1.5;
}

.service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.service-price {
    font-size: 2rem;
    font-weight: 600;
    color: #1a365d;
}

.service-duration {
    color: #666;
    font-size: 1.4rem;
}

.no-services {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.no-services p {
    color: #666;
    font-size: 1.6rem;
}

@media (max-width: 768px) {
    .services-hero h1 {
        font-size: 2.8rem;
    }

    .services-hero p {
        font-size: 1.6rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any JavaScript functionality here
});
</script> 