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

// Get date filter from URL, default to today
$date = isset($_GET['date']) ? $_GET['date'] : 'today';
$start_date = '';
$end_date = '';

switch ($date) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+6 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));
        break;
    default:
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
}

// Fetch appointments
$appointmentsQuery = "SELECT a.AppointmentID, a.StartTime, a.EndTime, a.Status, s.Name as ServiceName, 
                     c.FirstName as CustomerFirstName, c.LastName as CustomerLastName, p.Amount 
                     FROM Appointments a 
                     JOIN Payments p ON a.PaymentID = p.PaymentID 
                     JOIN Customers c ON a.CustomerID = c.UserID 
                     JOIN ApptContains ac ON a.AppointmentID = ac.AppointmentID 
                     JOIN Services s ON ac.ServiceID = s.ServiceID 
                     JOIN BarberHas bh ON a.AppointmentID = bh.AppointmentID 
                     WHERE bh.BarberID = ? 
                     AND DATE(a.StartTime) BETWEEN ? AND ?
                     AND a.Status != 'cancelled'
                     ORDER BY a.StartTime ASC";

$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("iss", $barber_id, $start_date, $end_date);
$appointmentsStmt->execute();
$appointmentsResult = $appointmentsStmt->get_result();

// Define page title
$page_title = "My Appointments";
include '../includes/header.php';
?>

<main>
    <section class="appointments-section">
        <div class="container">
            <div class="section-header">
                <h1>My Appointments</h1>
                <div class="date-filter">
                    <a href="?date=today" class="btn <?php echo $date === 'today' ? 'btn-primary' : 'btn-outline'; ?>">Today</a>
                    <a href="?date=week" class="btn <?php echo $date === 'week' ? 'btn-primary' : 'btn-outline'; ?>">This Week</a>
                    <a href="?date=month" class="btn <?php echo $date === 'month' ? 'btn-primary' : 'btn-outline'; ?>">This Month</a>
                </div>
            </div>

            <div class="appointments-container">
                <?php if ($appointmentsResult->num_rows > 0): ?>
                    <div class="appointments-list">
                        <?php while ($appointment = $appointmentsResult->fetch_assoc()): ?>
                            <div class="appointment-card">
                                <div class="appointment-date">
                                    <div class="date-display">
                                        <span class="month"><?php echo date('M', strtotime($appointment['StartTime'])); ?></span>
                                        <span class="day"><?php echo date('d', strtotime($appointment['StartTime'])); ?></span>
                                    </div>
                                    <span class="time"><?php echo date('g:i A', strtotime($appointment['StartTime'])); ?></span>
                                </div>
                                
                                <div class="appointment-details">
                                    <h3><?php echo htmlspecialchars($appointment['ServiceName']); ?></h3>
                                    <p class="customer-name">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($appointment['CustomerFirstName'] . ' ' . $appointment['CustomerLastName']); ?>
                                    </p>
                                    <div class="appointment-status">
                                        <span class="status-badge status-<?php echo strtolower($appointment['Status']); ?>">
                                            <?php echo ucfirst($appointment['Status']); ?>
                                        </span>
                                        <span class="price">$<?php echo $appointment['Amount']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="appointment-actions">
                                    <a href="appointment-details.php?id=<?php echo $appointment['AppointmentID']; ?>" class="btn btn-outline btn-sm">Details</a>
                                    
                                    <?php if ($appointment['Status'] === 'scheduled'): ?>
                                        <a href="mark-completed.php?id=<?php echo $appointment['AppointmentID']; ?>" class="btn btn-primary btn-sm">Complete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>No Appointments Found</h3>
                        <p>You don't have any appointments scheduled for this period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<style>
.appointments-section {
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

.date-filter {
    display: flex;
    gap: 1rem;
}

.appointments-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.appointment-card {
    display: flex;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease;
}

.appointment-card:hover {
    transform: translateY(-2px);
}

.appointment-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #1a365d;
    color: white;
    padding: 1.5rem;
    min-width: 120px;
    text-align: center;
}

.date-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 0.5rem;
}

.month {
    font-size: 1.4rem;
    text-transform: uppercase;
    font-weight: 500;
}

.day {
    font-size: 2.4rem;
    font-weight: 700;
    line-height: 1;
}

.time {
    font-size: 1.6rem;
    font-weight: 600;
}

.appointment-details {
    flex: 1;
    padding: 1.5rem;
}

.appointment-details h3 {
    margin: 0 0 0.5rem;
    font-size: 1.8rem;
}

.customer-name {
    margin: 0 0 1rem;
    color: #666;
}

.customer-name i {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
}

.appointment-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 1.2rem;
    font-weight: 500;
}

.status-scheduled {
    background-color: rgba(92, 158, 173, 0.1);
    color: #5c9ead;
}

.status-completed {
    background-color: rgba(67, 160, 71, 0.1);
    color: #43a047;
}

.price {
    font-weight: 600;
    color: #1a365d;
}

.appointment-actions {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.8rem;
    padding: 1.5rem;
    background-color: #f8fafc;
}

.btn-sm {
    font-size: 1.4rem;
    padding: 0.6rem 1.2rem;
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

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .date-filter {
        width: 100%;
        justify-content: center;
    }

    .appointment-card {
        flex-direction: column;
    }

    .appointment-date {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
        padding: 1rem;
    }

    .date-display {
        flex-direction: row;
        gap: 0.8rem;
        align-items: center;
        margin-bottom: 0;
    }

    .appointment-actions {
        flex-direction: row;
    }
}

@media (max-width: 576px) {
    .date-filter {
        flex-wrap: wrap;
    }

    .date-filter .btn {
        flex: 1;
        min-width: 100px;
    }
}
</style> 