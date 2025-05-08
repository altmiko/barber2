<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$page_title = "Book Appointment";
include 'includes/header.php';

$selectedDate = '';
$selectedServices = [];
$selectedBarber = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedDate = $_POST['date'];
    $selectedServices = $_POST['services'] ?? [];
    $selectedBarber = $_POST['barber'];

    if (empty($selectedServices)) {
        $error = "Please select at least one service";
    } elseif (empty($selectedBarber)) {
        $error = "Please select a barber";
    } elseif (empty($selectedDate)) {
        $error = "Please select a date";
    } else {
        $totalDuration = 0;
        $totalPrice = 0;
        $serviceNames = [];

        foreach ($selectedServices as $serviceId) {
            $sql = "SELECT * FROM Services WHERE ServiceID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            $service = $result->fetch_assoc();

            $totalDuration += $service['Duration'];
            $totalPrice += $service['Price'];
            $serviceNames[] = $service['Name'];
        }

        $appointmentTime = '09:00:00';
        $appointmentDate = date('Y-m-d', strtotime($selectedDate));

        $sql = "SELECT * FROM Appointments WHERE BarberID = ? AND AppointmentDate = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $selectedBarber, $appointmentDate);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Barber is not available on this date";
        } else {
            $conn->begin_transaction();

            try {
                $paymentSql = "INSERT INTO Payments (CustomerID, Amount, PaymentDate, PaymentStatus) VALUES (?, ?, NOW(), 'completed')";
                $stmt = $conn->prepare($paymentSql);
                $stmt->bind_param("id", $_SESSION['user_id'], $totalPrice);
                $stmt->execute();
                $paymentId = $conn->insert_id;

                $appointmentSql = "INSERT INTO Appointments (CustomerID, BarberID, AppointmentDate, AppointmentTime, Status, PaymentID) VALUES (?, ?, ?, ?, 'scheduled', ?)";
                $stmt = $conn->prepare($appointmentSql);
                $stmt->bind_param("iisss", $_SESSION['user_id'], $selectedBarber, $appointmentDate, $appointmentTime, $paymentId);
                $stmt->execute();
                $appointmentId = $conn->insert_id;

                foreach ($selectedServices as $serviceId) {
                    $serviceSql = "INSERT INTO AppointmentServices (AppointmentID, ServiceID) VALUES (?, ?)";
                    $stmt = $conn->prepare($serviceSql);
                    $stmt->bind_param("ii", $appointmentId, $serviceId);
                    $stmt->execute();
                }

                $conn->commit();
                $success = "Appointment booked successfully!";
                $selectedDate = '';
                $selectedServices = [];
                $selectedBarber = '';
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error booking appointment: " . $e->getMessage();
            }
        }
    }
}

$servicesSql = "SELECT * FROM Services ORDER BY Name";
$servicesResult = $conn->query($servicesSql);

$barbersSql = "SELECT UserID, FirstName, LastName FROM Barbers ORDER BY FirstName";
$barbersResult = $conn->query($barbersSql);
?>

<main>
    <div class="container">
        <div class="booking-form">
            <h1>Book an Appointment</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="bookingForm">
                <div class="form-group">
                    <label>Select Services</label>
                    <div class="services-grid">
                        <?php while ($service = $servicesResult->fetch_assoc()): ?>
                            <div class="service-option">
                                <input type="checkbox" id="service_<?php echo $service['ServiceID']; ?>" 
                                       name="services[]" value="<?php echo $service['ServiceID']; ?>"
                                       <?php echo in_array($service['ServiceID'], $selectedServices) ? 'checked' : ''; ?>>
                                <label for="service_<?php echo $service['ServiceID']; ?>">
                                    <h4><?php echo htmlspecialchars($service['Name']); ?></h4>
                                    <p><?php echo htmlspecialchars($service['Description']); ?></p>
                                    <div class="service-meta">
                                        <span class="price">BDT <?php echo number_format($service['Price'], 2); ?></span>
                                        <span class="duration"><?php echo $service['Duration']; ?> mins</span>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="barber">Select Barber</label>
                    <select id="barber" name="barber" required>
                        <option value="">Choose a barber</option>
                        <?php while ($barber = $barbersResult->fetch_assoc()): ?>
                            <option value="<?php echo $barber['UserID']; ?>" 
                                    <?php echo $selectedBarber == $barber['UserID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($barber['FirstName'] . ' ' . $barber['LastName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Select Date</label>
                    <input type="date" id="date" name="date" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo $selectedDate; ?>">
                </div>

                <div id="bookingSummary" class="booking-summary" style="display: none;">
                    <h3>Booking Summary</h3>
                    <div id="summaryContent"></div>
                </div>

                <button type="submit" class="btn btn-primary">Book Appointment</button>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const summary = document.getElementById('bookingSummary');
    const summaryContent = document.getElementById('summaryContent');
    const dateInput = document.getElementById('date');
    const barberSelect = document.getElementById('barber');
    const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]');

    function updateSummary() {
        const selectedServices = Array.from(serviceCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => {
                const label = cb.nextElementSibling;
                const name = label.querySelector('h4').textContent;
                const price = label.querySelector('.price').textContent;
                const duration = label.querySelector('.duration').textContent;
                return { name, price, duration };
            });

        const selectedBarber = barberSelect.options[barberSelect.selectedIndex].text;
        const selectedDate = dateInput.value;

        if (selectedServices.length > 0 && selectedBarber && selectedDate) {
            let totalPrice = 0;
            let totalDuration = 0;

            const servicesHtml = selectedServices.map(service => {
                totalPrice += parseFloat(service.price.replace('BDT ', '').replace(',', ''));
                totalDuration += parseInt(service.duration);
                return `
                    <div class="summary-item">
                        <span class="service-name">${service.name}</span>
                        <span class="service-price">${service.price}</span>
                        <span class="service-duration">${service.duration}</span>
                    </div>
                `;
            }).join('');

            summaryContent.innerHTML = `
                <div class="summary-details">
                    <p><strong>Date:</strong> ${new Date(selectedDate).toLocaleDateString()}</p>
                    <p><strong>Time:</strong> 9:00 AM</p>
                    <p><strong>Barber:</strong> ${selectedBarber}</p>
                    <div class="selected-services">
                        ${servicesHtml}
                    </div>
                    <div class="summary-total">
                        <p><strong>Total Duration:</strong> ${totalDuration} minutes</p>
                        <p><strong>Total Price:</strong> BDT ${totalPrice.toFixed(2)}</p>
                    </div>
                </div>
            `;
            summary.style.display = 'block';
        } else {
            summary.style.display = 'none';
        }
    }

    form.addEventListener('change', updateSummary);
    updateSummary();
});
</script>

<?php include 'includes/footer.php'; ?>