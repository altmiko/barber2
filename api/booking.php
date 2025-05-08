<?php
session_start();
require_once 'config/database.php'; // Adjust path as needed
require_once 'includes/functions.php'; // Adjust path as needed

if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('Please log in as a customer to book an appointment.', 'error');
    redirect('login.php');
}

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "Book Appointment - Step 2";

// --- PHP Backend Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred.'];

    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in. Please login to book an appointment.';
        echo json_encode($response);
        exit;
    }
    $customerID = $_SESSION['user_id'];

    if ($_POST['action'] === 'fetch_bookings') {
        $selectedDate = $_POST['date'] ?? date('Y-m-d');
        try {
            // Fetch appointments that start on the selected date
            // We also need to know which barber is booked for which slot
            $stmt = $conn->prepare("
                SELECT 
                    s.Time, 
                    s.BarberID,
                    a.EndTime
                FROM Slots s
                JOIN Appointments a ON s.AppointmentID = a.AppointmentID
                WHERE DATE(s.Time) = ? AND s.Status = 'Booked'
            ");
            $stmt->bind_param("s", $selectedDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $bookedSlots = [];
            while ($row = $result->fetch_assoc()) {
                $bookedSlots[] = $row;
            }
            $stmt->close();
            $response = ['success' => true, 'bookedSlots' => $bookedSlots];
        } catch (Exception $e) {
            $response['message'] = 'Error fetching bookings: ' . $e->getMessage();
        }
        echo json_encode($response);
        exit;
    } 
    elseif ($_POST['action'] === 'process_booking') {
        $selectedServices = $_POST['services'] ?? []; // Array of objects: {barberId, time, serviceId, serviceName, price, duration}
        
        if (empty($selectedServices)) {
            $response['message'] = 'No services selected.';
            echo json_encode($response);
            exit;
        }

        $conn->begin_transaction();
        try {
            $totalAmount = 0;
            foreach($selectedServices as $item) {
                $totalAmount += floatval($item['price']);
            }

            // 1. Create Payment Record
            $payMethod = 'Online'; // Or get from form
            $payStatus = 'Pending'; // Or 'Completed' if payment is processed immediately
            $stmt = $conn->prepare("INSERT INTO Payments (Amount, PayMethod, PayStatus) VALUES (?, ?, ?)");
            $stmt->bind_param("dss", $totalAmount, $payMethod, $payStatus);
            $stmt->execute();
            $paymentID = $conn->insert_id;
            $stmt->close();

            if (!$paymentID) {
                throw new Exception("Failed to create payment record.");
            }
            
            // Group services by barber and start time to create appointments
            $appointmentsToCreate = [];
            foreach ($selectedServices as $item) {
                $key = $item['barberId'] . '_' . $item['time'];
                if (!isset($appointmentsToCreate[$key])) {
                    $appointmentsToCreate[$key] = [
                        'barberId' => $item['barberId'],
                        'startTime' => $item['time'],
                        'services' => [],
                        'totalDuration' => 0
                    ];
                }
                $appointmentsToCreate[$key]['services'][] = $item;
                $appointmentsToCreate[$key]['totalDuration'] += intval($item['duration']);
            }

            $createdAppointmentIDs = [];

            foreach ($appointmentsToCreate as $apptData) {
                $startTimeStr = $apptData['startTime']; // e.g., "2023-10-27 09:00:00"
                $startDateTime = new DateTime($startTimeStr);
                $endDateTime = clone $startDateTime;
                $endDateTime->add(new DateInterval('PT' . $apptData['totalDuration'] . 'M'));
                $endTimeStr = $endDateTime->format('Y-m-d H:i:s');
                $status = 'Scheduled';

                // 2. Create Appointment Record
                $stmt = $conn->prepare("INSERT INTO Appointments (StartTime, EndTime, Status, PaymentID, CustomerID) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $startTimeStr, $endTimeStr, $status, $paymentID, $customerID);
                $stmt->execute();
                $appointmentID = $conn->insert_id;
                $stmt->close();

                if (!$appointmentID) {
                    throw new Exception("Failed to create appointment for barber " . $apptData['barberId'] . " at " . $apptData['startTime']);
                }
                $createdAppointmentIDs[] = $appointmentID;

                // 3. Create Slot Record (marking the primary 1-hour slot)
                // More complex logic might be needed if an appointment spans multiple master slots
                $slotStatus = 'Booked';
                $stmt = $conn->prepare("INSERT INTO Slots (Status, Time, BarberID, AppointmentID) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $slotStatus, $startTimeStr, $apptData['barberId'], $appointmentID);
                $stmt->execute();
                $stmt->close();
                
                // 4. Create BarberHas Record
                $stmt = $conn->prepare("INSERT INTO BarberHas (BarberID, AppointmentID) VALUES (?, ?)");
                $stmt->bind_param("ii", $apptData['barberId'], $appointmentID);
                $stmt->execute();
                $stmt->close();

                // 5. Create ApptContains Records for each service
                foreach ($apptData['services'] as $service) {
                    $stmt = $conn->prepare("INSERT INTO ApptContains (ServiceID, AppointmentID) VALUES (?, ?)");
                    $stmt->bind_param("ii", $service['serviceId'], $appointmentID);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            $conn->commit();
            $response = ['success' => true, 'message' => 'Booking successful! Payment ID: ' . $paymentID . ' Appointment IDs: ' . implode(', ', $createdAppointmentIDs), 'payment_id' => $paymentID];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Booking failed: ' . $e->getMessage();
        }
        echo json_encode($response);
        exit;
    }
}

// Fetch Barbers for the grid (can be moved to an AJAX call if many barbers)
$barbers = [];
$result = $conn->query("SELECT UserID, FirstName, LastName FROM Barbers ORDER BY FirstName, LastName");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }
}

// Fetch Services for the modal (can be moved to an AJAX call)
$services = [];
$result = $conn->query("SELECT ServiceID, Name, Description, Duration, Price FROM Services ORDER BY Name");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

include 'includes/header.php'; // Assumes you have a header file
?>

<style>
    body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f4f4; color: #333; }
    .booking-container { display: flex; max-width: 1600px; margin: 20px auto; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px; }
    .grid-controls { margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
    .grid-controls label { font-weight: bold; }
    .grid-controls input[type="date"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    
    .booking-grid-area { flex: 3; overflow-x: auto; }
    .booking-grid { border-collapse: collapse; width: 100%; table-layout: fixed; }
    .booking-grid th, .booking-grid td { border: 1px solid #ddd; padding: 0; text-align: center; vertical-align: top; }
    .booking-grid th { background-color: #f0f0f0; height: 40px; font-size: 0.9em; }
    .booking-grid td { height: 60px; }
    .barber-name-col { width: 150px; background-color: #f8f8f8; font-weight: bold; padding: 10px; font-size: 0.9em; }
    
    .slot {
        cursor: pointer;
        background-color: #e8f5e9; /* Light green for available */
        transition: background-color 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
        width: 100%;
        font-size: 0.8em;
        color: #388e3c;
    }
    .slot:hover { background-color: #c8e6c9; }
    .slot.booked {
        background-color: #ffebee; /* Light red for booked */
        color: #c62828;
        cursor: not-allowed;
        font-style: italic;
    }
    .slot.partially-booked { /* If you implement more granular checks */
        background-color: #fff9c4; /* Light yellow */
        color: #f57f17;
    }
    .slot.selected-slot {
        background-color: #bbdefb; /* Light blue for selection process */
        border: 2px solid #2196f3;
    }
    .slot.processing {
        background-color: #e3f2fd; /* Light blue for processing */
        color: #1565c0;
        font-style: italic;
    }

    .order-summary-area { flex: 1; margin-left: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
    .order-summary-area h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    #selected-services-list { list-style: none; padding: 0; }
    #selected-services-list li { 
        background-color: #fff; margin-bottom: 10px; padding: 10px; border-radius: 3px; border: 1px solid #eee;
        font-size: 0.9em; display: flex; justify-content: space-between; align-items: center;
    }
    #selected-services-list li .service-details { flex-grow: 1; }
    #selected-services-list li .service-name { font-weight: bold; }
    #selected-services-list li .service-time, #selected-services-list li .service-barber { font-size: 0.85em; color: #555; }
    #selected-services-list li .remove-service { cursor: pointer; color: red; font-weight: bold; padding: 0 5px; }

    #total-amount { font-weight: bold; font-size: 1.2em; margin-top: 15px; }
    #confirm-booking-btn { 
        background-color: #4CAF50; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer;
        font-size: 1em; width: 100%; margin-top: 20px; transition: background-color 0.3s;
    }
    #confirm-booking-btn:hover { background-color: #45a049; }
    #confirm-booking-btn:disabled { background-color: #ccc; cursor: not-allowed; }

    /* Modal Styles */
    .modal {
        display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
        overflow: auto; background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fff; margin: 10% auto; padding: 25px; border: 1px solid #ddd;
        width: 80%; max-width: 600px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
    .modal-header h4 { margin: 0; font-size: 1.5em; color: #333; }
    .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    .close-btn:hover, .close-btn:focus { color: #333; text-decoration: none; }
    
    #services-checkbox-list { max-height: 300px; overflow-y: auto; margin-bottom: 20px; }
    .service-item-label { display: block; margin-bottom: 12px; padding: 10px; background-color: #f9f9f9; border-radius: 4px; cursor: pointer; transition: background-color 0.2s; }
    .service-item-label:hover { background-color: #f0f0f0; }
    .service-item-label input[type="radio"] { margin-right: 10px; vertical-align: middle; }
    .service-item-label .service-info { display: inline-block; vertical-align: middle; }
    .service-item-label .service-name { font-weight: bold; }
    .service-item-label .service-meta { font-size: 0.9em; color: #555; }

    #add-services-to-cart-btn {
        background-color: #007bff; color: white; padding: 10px 18px; border: none; border-radius: 4px;
        cursor: pointer; font-size: 1em; transition: background-color 0.3s;
    }
    #add-services-to-cart-btn:hover { background-color: #0056b3; }
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
    .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }

</style>

<div class="booking-container">
    <div class="booking-grid-area">
        <div id="booking-message" class="alert" style="display:none;"></div>
        <h2>Select Date & Time Slot</h2>
        <div class="grid-controls">
            <label for="booking-date">Select Date:</label>
            <input type="date" id="booking-date" name="booking-date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
        </div>

        <table class="booking-grid">
            <thead>
                <tr id="time-slots-header">
                    <th class="barber-name-col" style="padding: 10px;">Barber</th>
                    <?php
                    // 12 slots from 9 AM to 8 PM (slot is start time)
                    for ($i = 9; $i <= 20; $i++) { // 9 AM to 8 PM (inclusive for start times)
                        $period = $i >= 12 ? 'PM' : 'AM';
                        $displayHour = $i > 12 ? $i - 12 : $i;
                        $nextHour = $i + 1 > 12 ? ($i + 1) - 12 : $i + 1;
                        $nextPeriod = $i + 1 >= 12 ? 'PM' : 'AM';
                        echo "<th>" . $displayHour . ":00 " . $period . " - " . $nextHour . ":00 " . $nextPeriod . "</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody id="booking-grid-body">
                <?php foreach ($barbers as $barber): ?>
                    <tr data-barber-id="<?php echo $barber['UserID']; ?>">
                        <td class="barber-name-col"><?php echo htmlspecialchars($barber['FirstName'] . ' ' . $barber['LastName']); ?></td>
                        <?php
                        for ($i = 9; $i <= 20; $i++) {
                            $timeStr = str_pad($i, 2, "0", STR_PAD_LEFT) . ":00:00";
                            echo "<td data-time-slot=\"" . $timeStr . "\" class=\"slot-cell\">";
                            echo "<div class=\"slot\" data-barber-id=\"" . $barber['UserID'] . "\" data-time=\"" . $timeStr . "\">Available</div>";
                            echo "</td>";
                        }
                        ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($barbers)): ?>
                    <tr><td colspan="13">No barbers available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="order-summary-area">
        <h3>Your Selections</h3>
        <ul id="selected-services-list">
            <!-- Selected services will be appended here by JS -->
        </ul>
        <div id="total-amount">Total: BDT 0.00</div>
        <button id="confirm-booking-btn" disabled>Confirm & Book</button>
        <p id="login-prompt" style="color:red; display:none;">Please <a href="login.php">login</a> to book.</p>
    </div>
</div>

<!-- Service Selection Modal -->
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Select Service(s)</h4>
            <span class="close-btn" id="closeServiceModal">&times;</span>
        </div>
        <p>For: <strong id="modalBarberName"></strong> at <strong id="modalSlotTime"></strong></p>
        <div id="services-checkbox-list">
            <?php foreach ($services as $service): ?>
                <label class="service-item-label">
                    <input type="radio" name="selected_service" value="<?php echo $service['ServiceID']; ?>"
                           data-name="<?php echo htmlspecialchars($service['Name']); ?>"
                           data-price="<?php echo $service['Price']; ?>"
                           data-duration="<?php echo $service['Duration']; ?>">
                    <span class="service-info">
                        <span class="service-name"><?php echo htmlspecialchars($service['Name']); ?></span><br>
                        <span class="service-meta">Duration: <?php echo $service['Duration']; ?> mins | Price: BDT <?php echo $service['Price']; ?></span>
                        <?php if (!empty($service['Description'])): ?>
                            <br><span class="service-meta" style="font-size:0.8em;"><?php echo htmlspecialchars(substr($service['Description'], 0, 70)); ?>...</span>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
                <p>No services found.</p>
            <?php endif; ?>
        </div>
        <div id="modal-message" class="alert" style="display:none; margin-bottom: 15px;"></div>
        <button id="add-services-to-cart-btn">Add to Selections</button>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingDateInput = document.getElementById('booking-date');
    const gridBody = document.getElementById('booking-grid-body');
    const serviceModal = document.getElementById('serviceModal');
    const closeServiceModalBtn = document.getElementById('closeServiceModal');
    const addServicesToCartBtn = document.getElementById('add-services-to-cart-btn');
    const servicesCheckboxList = document.getElementById('services-checkbox-list');
    const selectedServicesListUL = document.getElementById('selected-services-list');
    const totalAmountDiv = document.getElementById('total-amount');
    const confirmBookingBtn = document.getElementById('confirm-booking-btn');
    const bookingMessageDiv = document.getElementById('booking-message');
    const loginPrompt = document.getElementById('login-prompt');

    let currentSelectedSlot = null; // { barberId, barberName, time, date }
    let cart = []; // { uniqueId, barberId, barberName, date, time, serviceId, serviceName, price, duration }
    let bookedSlotsData = []; // Stores fetched booked slots for the current date

    const timeSlots = Array.from({length: 12}, (_, i) => (i + 9).toString().padStart(2, '0') + ":00:00"); // 09:00:00 to 20:00:00

    // Check login status (very basic, relies on PHP session variable)
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        confirmBookingBtn.disabled = true;
        loginPrompt.style.display = 'block';
    }

    function displayMessage(message, isSuccess) {
        const messageDiv = document.getElementById('booking-message');
        messageDiv.textContent = message;
        messageDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
        messageDiv.style.display = 'block';
        
        // Only auto-hide success messages
        if (isSuccess) {
            setTimeout(() => { messageDiv.style.display = 'none'; }, 5000);
        }
    }

    function displayModalMessage(message, isSuccess) {
        const messageDiv = document.getElementById('modal-message');
        messageDiv.textContent = message;
        messageDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
        messageDiv.style.display = 'block';
        
        // Only auto-hide success messages
        if (isSuccess) {
            setTimeout(() => { messageDiv.style.display = 'none'; }, 5000);
        }
    }

    async function fetchBookedSlots(date) {
        const formData = new FormData();
        formData.append('action', 'fetch_bookings');
        formData.append('date', date);

        try {
            const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                bookedSlotsData = data.bookedSlots;
            } else {
                displayMessage(data.message || 'Error fetching booking data.', false);
                bookedSlotsData = [];
            }
        } catch (error) {
            displayMessage('Network error fetching booking data: ' + error, false);
            bookedSlotsData = [];
        }
        updateGridUI();
    }
    
    function isSlotBooked(barberId, date, time) {
        // date part of time is YYYY-MM-DD HH:MM:SS
        const slotDateTimeStr = date + ' ' + time; // e.g., "2023-10-27 09:00:00"
        
        return bookedSlotsData.some(booked => {
            const bookedStartDateTime = new Date(booked.Time);
            const bookedEndDateTime = new Date(booked.EndTime);
            const slotStartDateTime = new Date(slotDateTimeStr);
            const slotEndDateTime = new Date(slotStartDateTime.getTime() + (60 * 60 * 1000)); // Add 1 hour for slot duration

            // Check if the barber matches and the slot overlaps with the booking
            return booked.BarberID.toString() === barberId.toString() &&
                   slotStartDateTime < bookedEndDateTime && // Slot starts before booking ends
                   slotEndDateTime > bookedStartDateTime;   // Slot ends after booking starts
        });
    }

    function updateGridUI() {
        const selectedDate = bookingDateInput.value;
        document.querySelectorAll('.slot').forEach(slotDiv => {
            const barberId = slotDiv.dataset.barberId;
            const time = slotDiv.dataset.time;

            // Remove existing classes and event listeners
            slotDiv.classList.remove('booked', 'selected-slot');
            slotDiv.textContent = 'Available';
            
            // Add click event listener
            slotDiv.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                if (!this.classList.contains('booked')) {
                    openServiceModal(barberId, time, selectedDate);
                }
            });

            if (isSlotBooked(barberId, selectedDate, time)) {
                slotDiv.classList.add('booked');
                slotDiv.textContent = 'Booked';
            }
        });
    }

    function openServiceModal(barberId, time, date) {
        if (!isLoggedIn) {
            displayMessage('Please login to select services and book.', false);
            return;
        }
        
        const barberRow = document.querySelector(`tr[data-barber-id='${barberId}']`);
        const barberName = barberRow ? barberRow.querySelector('.barber-name-col').textContent : 'Unknown Barber';
        
        currentSelectedSlot = { barberId, barberName, time, date };
        document.getElementById('modalBarberName').textContent = barberName;
        
        // Format the time for display
        const timeObj = new Date(date + 'T' + time);
        const formattedTime = timeObj.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
        document.getElementById('modalSlotTime').textContent = `${date} at ${formattedTime}`;
        
        // Reset checkboxes and modal message
        servicesCheckboxList.querySelectorAll('input[type="radio"]').forEach(cb => cb.checked = false);
        const modalMessage = document.getElementById('modal-message');
        modalMessage.style.display = 'none';
        modalMessage.textContent = '';
        
        // Show the modal
        serviceModal.style.display = 'block';
    }

    closeServiceModalBtn.onclick = function() {
        serviceModal.style.display = 'none';
        currentSelectedSlot = null;
        // Clear modal message when closing
        const modalMessage = document.getElementById('modal-message');
        modalMessage.style.display = 'none';
        modalMessage.textContent = '';
    }

    window.onclick = function(event) {
        if (event.target == serviceModal) {
            serviceModal.style.display = 'none';
            currentSelectedSlot = null;
            // Clear modal message when closing
            const modalMessage = document.getElementById('modal-message');
            modalMessage.style.display = 'none';
            modalMessage.textContent = '';
        }
    }

    addServicesToCartBtn.onclick = function() {
        if (!currentSelectedSlot) return;

        const selectedService = servicesCheckboxList.querySelector('input[type="radio"]:checked');
        const modalMessage = document.getElementById('modal-message');
        
        if (!selectedService) {
            // Force the message to be visible every time
            // modalMessage.style.display = 'none'; // First hide it
            modalMessage.offsetHeight; // Force a reflow
            modalMessage.textContent = 'Please select a service before adding to selections.';
            modalMessage.className = 'alert alert-danger';
            modalMessage.style.display = 'block';
            return;
        }

        // Create the new cart item
        const newCartItem = {
            uniqueId: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            barberId: currentSelectedSlot.barberId,
            barberName: currentSelectedSlot.barberName,
            date: currentSelectedSlot.date,
            time: currentSelectedSlot.date + ' ' + currentSelectedSlot.time,
            serviceId: selectedService.value,
            serviceName: selectedService.dataset.name,
            price: parseFloat(selectedService.dataset.price),
            duration: parseInt(selectedService.dataset.duration)
        };

        // Remove any existing items for this slot
        cart = cart.filter(item => {
            const isSameSlot = item.barberId === currentSelectedSlot.barberId && 
                             item.time.split(' ')[1] === currentSelectedSlot.time;
            return !isSameSlot;
        });

        // Add the new item
        cart.push(newCartItem);

        // Update the slot display
        const slotElement = document.querySelector(`.slot[data-barber-id="${currentSelectedSlot.barberId}"][data-time="${currentSelectedSlot.time}"]`);
        if (slotElement) {
            slotElement.classList.add('processing');
            slotElement.textContent = selectedService.dataset.name;
        }
        
        // Clear the error message only after successful selection
        modalMessage.style.display = 'none';
        modalMessage.textContent = '';
        
        updateCartUI();
        serviceModal.style.display = 'none';
        currentSelectedSlot = null;
    }

    function updateCartUI() {
        selectedServicesListUL.innerHTML = '';
        let currentTotal = 0;
        if (cart.length === 0) {
            selectedServicesListUL.innerHTML = '<li>No services selected yet.</li>';
            confirmBookingBtn.disabled = true;
        } else {
            cart.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="service-details">
                        <span class="service-name">${item.serviceName}</span><br>
                        <span class="service-barber">Barber: ${item.barberName}</span><br>
                        <span class="service-time">Time: ${item.date} ${item.time.substring(11,16)}</span>
                    </div>
                    <span>BDT ${item.price.toFixed(2)}</span>
                    <span class="remove-service" data-unique-id="${item.uniqueId}">&times;</span>
                `;
                selectedServicesListUL.appendChild(li);
                currentTotal += item.price;

                li.querySelector('.remove-service').addEventListener('click', function() {
                    removeFromCart(this.dataset.uniqueId);
                });
            });
            confirmBookingBtn.disabled = !isLoggedIn; // Only enable if logged in
        }
        totalAmountDiv.textContent = `Total: BDT ${currentTotal.toFixed(2)}`;
    }
    
    function removeFromCart(uniqueId) {
        const item = cart.find(item => item.uniqueId === uniqueId);
        if (item) {
            // Remove processing state from the slot
            const slotElement = document.querySelector(`.slot[data-barber-id="${item.barberId}"][data-time="${item.time.split(' ')[1]}"]`);
            if (slotElement) {
                slotElement.classList.remove('processing');
                slotElement.textContent = 'Available';
            }
        }
        cart = cart.filter(item => item.uniqueId !== uniqueId);
        updateCartUI();
    }

    bookingDateInput.addEventListener('change', function() {
        fetchBookedSlots(this.value);
        // Potentially clear cart or warn user if date change invalidates cart items
        // For now, we keep cart items, assuming user manages this.
    });

    confirmBookingBtn.addEventListener('click', async function() {
        if (cart.length === 0 || !isLoggedIn) {
            displayMessage('Please select services and ensure you are logged in.', false);
            return;
        }
        this.disabled = true;
        this.textContent = 'Processing...';

        const bookingData = new FormData();
        bookingData.append('action', 'process_booking');
        cart.forEach((item, index) => {
            // PHP will receive services as services[0][barberId], services[0][time] etc.
            for (const key in item) {
                bookingData.append(`services[${index}][${key}]`, item[key]);
            }
        });

        try {
            const response = await fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
                method: 'POST',
                body: bookingData
            });
            const result = await response.json();
            if (result.success) {
                displayMessage(result.message, true);
                cart = []; // Clear cart on successful booking
                updateCartUI();
                fetchBookedSlots(bookingDateInput.value); // Refresh grid
                // Optionally redirect to a success page or payment page
                // if (result.payment_id) { window.location.href = 'payment.php?id=' + result.payment_id; }
            } else {
                displayMessage(result.message || 'Booking failed. Please try again.', false);
            }
        } catch (error) {
            displayMessage('Network error during booking: ' + error, false);
        } finally {
            this.disabled = cart.length === 0 || !isLoggedIn; // Re-evaluate disabled state
            this.textContent = 'Confirm & Book';
        }
    });

    // Initial load
    fetchBookedSlots(bookingDateInput.value);
    updateCartUI(); // Initialize cart UI (e.g. "No services")
});
</script>

<?php
    include 'includes/footer.php';
?>
</body>
</html> 