<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in as a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('Please log in as a customer to view your appointments.', 'error');
    redirect('../login.php');
}

$customer_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Get customer information
$customerQuery = "SELECT FirstName, LastName FROM Customers WHERE UserID = ?";
$customerStmt = $conn->prepare($customerQuery);
$customerStmt->bind_param("i", $customer_id);
$customerStmt->execute();
$customerResult = $customerStmt->get_result();
$customer = $customerResult->fetch_assoc();

// Get upcoming appointments
// TODO: Add SQL query to fetch upcoming appointments
$upcomingAppointments = [];

// Get past appointments
// TODO: Add SQL query to fetch past appointments
$pastAppointments = [];

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointmentId = (int) $_POST['appointment_id'];
    
    // TODO: Add SQL query to cancel appointment
    // Should update the appointment status to 'cancelled'
    
    if ($success) {
        setFlashMessage('Appointment cancelled successfully.', 'success');
    } else {
        setFlashMessage('Failed to cancel appointment. Please try again.', 'error');
    }
    
    redirect('appointments.php');
}

// Define page title
$page_title = "My Appointments";
include '../includes/header.php';
?>

<main>
    <section class="appointments-section">
        <div class="container">
            <h1 class="section-title">My Appointments</h1>
            
            <?php if (!empty($errors['system'])): ?>
                <div class="alert alert-error">
                    <?php echo $errors['system']; ?>
                </div>
            <?php endif; ?>
            
            <div class="appointments-container">
                <!-- Upcoming Appointments -->
                <div class="appointments-section">
                    <h2>Upcoming Appointments</h2>
                    
                    <?php if (empty($upcomingAppointments)): ?>
                        <div class="no-appointments">
                            <p>You have no upcoming appointments.</p>
                            <a href="../booking.php" class="btn btn-primary">Book Now</a>
                        </div>
                    <?php else: ?>
                        <div class="appointments-grid">
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <div class="appointment-card">
                                    <div class="appointment-header">
                                        <h3><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                        <span class="status <?php echo strtolower($appointment['status']); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="appointment-details">
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('F j, Y', strtotime($appointment['date'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo date('g:i A', strtotime($appointment['time'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($appointment['barber_name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $appointment['duration']; ?> minutes</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-tag"></i>
                                            <span>BDT <?php echo $appointment['price']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($appointment['status'] === 'scheduled'): ?>
                                        <div class="appointment-actions">
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                                                  onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="cancel_appointment" class="btn btn-danger">Cancel Appointment</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Past Appointments -->
                <div class="appointments-section">
                    <h2>Past Appointments</h2>
                    
                    <?php if (empty($pastAppointments)): ?>
                        <div class="no-appointments">
                            <p>You have no past appointments.</p>
                        </div>
                    <?php else: ?>
                        <div class="appointments-grid">
                            <?php foreach ($pastAppointments as $appointment): ?>
                                <div class="appointment-card">
                                    <div class="appointment-header">
                                        <h3><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                        <span class="status <?php echo strtolower($appointment['status']); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="appointment-details">
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('F j, Y', strtotime($appointment['date'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo date('g:i A', strtotime($appointment['time'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($appointment['barber_name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $appointment['duration']; ?> minutes</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-tag"></i>
                                            <span>BDT <?php echo $appointment['price']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($appointment['status'] === 'completed' && !$appointment['has_review']): ?>
                                        <div class="appointment-actions">
                                            <a href="../review.php?appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-primary">Leave a Review</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<style>
.appointments-section {
    padding: var(--space-6) 0;
    margin-top: 80px;
}

.section-title {
    text-align: center;
    margin-bottom: var(--space-5);
}

.appointments-container {
    max-width: 1200px;
    margin: 0 auto;
}

.appointments-section {
    margin-bottom: var(--space-6);
}

.appointments-section h2 {
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-2);
    border-bottom: 1px solid var(--color-border);
}

.appointments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-4);
}

.appointment-card {
    background-color: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    padding: var(--space-3);
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-3);
}

.appointment-header h3 {
    margin: 0;
    font-size: 1.8rem;
}

.status {
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    font-size: 1.2rem;
    font-weight: 500;
}

.status.scheduled {
    background-color: var(--color-primary-light);
    color: var(--color-primary);
}

.status.completed {
    background-color: var(--color-success-light);
    color: var(--color-success);
}

.status.cancelled {
    background-color: var(--color-danger-light);
    color: var(--color-danger);
}

.appointment-details {
    margin-bottom: var(--space-3);
}

.detail-item {
    display: flex;
    align-items: center;
    margin-bottom: var(--space-2);
    color: var(--color-text-light);
}

.detail-item i {
    width: 20px;
    margin-right: var(--space-2);
    color: var(--color-primary);
}

.appointment-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

.no-appointments {
    text-align: center;
    padding: var(--space-4);
    background-color: var(--color-bg-alt);
    border-radius: var(--radius-md);
}

.no-appointments p {
    margin-bottom: var(--space-3);
    color: var(--color-text-light);
}

@media (max-width: 768px) {
    .appointments-grid {
        grid-template-columns: 1fr;
    }
    
    .appointment-actions {
        flex-direction: column;
    }
    
    .appointment-actions button,
    .appointment-actions a {
        width: 100%;
    }
}
</style> 