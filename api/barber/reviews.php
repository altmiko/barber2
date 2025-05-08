<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in as a barber
if (!isLoggedIn() || !isBarber()) {
    setFlashMessage('You must be logged in as a barber to access this page.', 'error');
    redirect('../login.php');
}

$barber_id = $_SESSION['user_id'];

// Get rating filter from URL
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Build the query based on rating filter
$reviewsQuery = "SELECT r.ReviewID, r.Rating, r.Comments, r.CreatedAt, 
                c.FirstName as CustomerFirstName, c.LastName as CustomerLastName,
                a.AppointmentID, s.Name as ServiceName
                FROM Reviews r 
                JOIN Customers c ON r.CustomerID = c.UserID 
                JOIN Appointments a ON r.AppointmentID = a.AppointmentID
                JOIN ApptContains ac ON a.AppointmentID = ac.AppointmentID
                JOIN Services s ON ac.ServiceID = s.ServiceID
                WHERE r.BarberID = ? ";

if ($rating > 0) {
    $reviewsQuery .= "AND r.Rating = ? ";
}

$reviewsQuery .= "ORDER BY r.CreatedAt DESC";

$reviewsStmt = $conn->prepare($reviewsQuery);

if ($rating > 0) {
    $reviewsStmt->bind_param("ii", $barber_id, $rating);
} else {
    $reviewsStmt->bind_param("i", $barber_id);
}

$reviewsStmt->execute();
$reviewsResult = $reviewsStmt->get_result();

// Get average rating and total reviews
$statsQuery = "SELECT 
                ROUND(AVG(Rating), 1) as avgRating,
                COUNT(*) as totalReviews,
                SUM(CASE WHEN Rating = 5 THEN 1 ELSE 0 END) as fiveStar,
                SUM(CASE WHEN Rating = 4 THEN 1 ELSE 0 END) as fourStar,
                SUM(CASE WHEN Rating = 3 THEN 1 ELSE 0 END) as threeStar,
                SUM(CASE WHEN Rating = 2 THEN 1 ELSE 0 END) as twoStar,
                SUM(CASE WHEN Rating = 1 THEN 1 ELSE 0 END) as oneStar
                FROM Reviews 
                WHERE BarberID = ?";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $barber_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Define page title
$page_title = "My Reviews";
include '../includes/header.php';
?>

<main>
    <section class="reviews-section">
        <div class="container">
            <div class="section-header">
                <h1>My Reviews</h1>
                <div class="rating-filter">
                    <a href="?rating=0" class="btn <?php echo $rating === 0 ? 'btn-primary' : 'btn-outline'; ?>">All</a>
                    <a href="?rating=5" class="btn <?php echo $rating === 5 ? 'btn-primary' : 'btn-outline'; ?>">5 Stars</a>
                    <a href="?rating=4" class="btn <?php echo $rating === 4 ? 'btn-primary' : 'btn-outline'; ?>">4 Stars</a>
                    <a href="?rating=3" class="btn <?php echo $rating === 3 ? 'btn-primary' : 'btn-outline'; ?>">3 Stars</a>
                    <a href="?rating=2" class="btn <?php echo $rating === 2 ? 'btn-primary' : 'btn-outline'; ?>">2 Stars</a>
                    <a href="?rating=1" class="btn <?php echo $rating === 1 ? 'btn-primary' : 'btn-outline'; ?>">1 Star</a>
                </div>
            </div>

            <div class="reviews-container">
                <div class="reviews-stats">
                    <div class="overall-rating">
                        <div class="rating-number"><?php echo $stats['avgRating'] ?: 'N/A'; ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($stats['avgRating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="total-reviews"><?php echo $stats['totalReviews']; ?> reviews</div>
                    </div>

                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar">
                                <div class="rating-label"><?php echo $i; ?> stars</div>
                                <div class="bar-container">
                                    <div class="bar" style="width: <?php echo $stats['totalReviews'] ? ($stats[$i . 'Star'] / $stats['totalReviews'] * 100) : 0; ?>%"></div>
                                </div>
                                <div class="rating-count"><?php echo $stats[$i . 'Star']; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php if ($reviewsResult->num_rows > 0): ?>
                        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?php
                                            $initials = strtoupper(substr($review['CustomerFirstName'], 0, 1) . substr($review['CustomerLastName'], 0, 1));
                                            echo $initials;
                                            ?>
                                        </div>
                                        <div class="customer-details">
                                            <h4><?php echo htmlspecialchars($review['CustomerFirstName'] . ' ' . $review['CustomerLastName']); ?></h4>
                                            <span class="service-name"><?php echo htmlspecialchars($review['ServiceName']); ?></span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['Rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-body">
                                    <p><?php echo htmlspecialchars($review['Comments']); ?></p>
                                </div>
                                <div class="review-footer">
                                    <span class="review-date"><?php echo date('F j, Y', strtotime($review['CreatedAt'])); ?></span>
                                    <a href="appointment-details.php?id=<?php echo $review['AppointmentID']; ?>" class="btn btn-outline btn-sm">View Appointment</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3>No Reviews Found</h3>
                            <p>You haven't received any reviews yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<style>
.reviews-section {
    padding: 2rem 0;
    margin-top: 60px;
    min-height: calc(100vh - 60px);
    background-color: #f5f7fa;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.section-header h1 {
    margin: 0;
    font-size: 2.4rem;
}

.rating-filter {
    display: flex;
    gap: 0.5rem;
}

.reviews-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}

.reviews-stats {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
    height: fit-content;
}

.overall-rating {
    text-align: center;
    margin-bottom: 2rem;
}

.rating-number {
    font-size: 4.8rem;
    font-weight: 700;
    color: #1a365d;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.rating-stars {
    color: #f59e0b;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.total-reviews {
    color: #666;
    font-size: 1.4rem;
}

.rating-bars {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.rating-label {
    width: 60px;
    font-size: 1.4rem;
    color: #666;
}

.bar-container {
    flex: 1;
    height: 8px;
    background-color: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.bar {
    height: 100%;
    background-color: #f59e0b;
    border-radius: 4px;
}

.rating-count {
    width: 40px;
    text-align: right;
    font-size: 1.4rem;
    color: #666;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #1a365d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.8rem;
}

.customer-details h4 {
    margin: 0 0 0.5rem;
    font-size: 1.8rem;
}

.service-name {
    color: #666;
    font-size: 1.4rem;
}

.review-rating {
    color: #f59e0b;
    font-size: 1.6rem;
}

.review-body {
    margin-bottom: 1.5rem;
}

.review-body p {
    margin: 0;
    color: #333;
    line-height: 1.6;
    font-size: 1.6rem;
}

.review-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.review-date {
    color: #666;
    font-size: 1.4rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(26, 54, 93, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
}

.empty-state-icon i {
    font-size: 3rem;
    color: #1a365d;
}

.empty-state h3 {
    margin: 0 0 1rem;
    font-size: 2rem;
}

.empty-state p {
    color: #666;
    margin: 0;
}

@media (max-width: 991px) {
    .reviews-container {
        grid-template-columns: 1fr;
    }

    .reviews-stats {
        margin-bottom: 2rem;
    }
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .rating-filter {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .review-header {
        flex-direction: column;
        gap: 1rem;
    }

    .review-rating {
        margin-left: 6.5rem;
    }
}

@media (max-width: 576px) {
    .rating-filter .btn {
        flex: 1;
        min-width: 80px;
    }

    .review-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style> 