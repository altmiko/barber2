<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Define page title
$page_title = "Barbers";
include 'includes/header.php';
?>

<main>
    <section class="services-hero">
        <div class="container">
            <h1 style="color: white;">Our Barbers</h1>
            <p>Meet our team of experienced barbers</p>
        </div>
    </section>

    <section class="barbers-section">
        <div class="container">
            <div class="barbers-grid">
                <div class="barber-card">
                    <div class="barber-image">
                        <img src="https://images.pexels.com/photos/15613465/pexels-photo-15613465/free-photo-of-man-with-beard-holding-scissors.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Barber 1">
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<style>
.services-hero {
    background-color:rgb(0, 24, 58);
    color: white;
    padding: 2em;
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