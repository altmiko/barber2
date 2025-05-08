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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    
    // Validate input
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($errors)) {
        // Update barber information
        $updateQuery = "UPDATE Barbers SET 
                       FirstName = ?, 
                       LastName = ?, 
                       Email = ?, 
                       Phone = ?, 
                       Bio = ? 
                       WHERE UserID = ?";
        
        $params = [$first_name, $last_name, $email, $phone, $bio, $barber_id];
        $types = "sssssi";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param($types, ...$params);
        
        if ($updateStmt->execute()) {
            setFlashMessage('Profile updated successfully', 'success');
            redirect('profile.php');
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

// Fetch current barber information
$barberQuery = "SELECT b.*, b.Email as UserEmail 
                FROM Barbers b 
                WHERE b.UserID = ?";

$barberStmt = $conn->prepare($barberQuery);
$barberStmt->bind_param("i", $barber_id);
$barberStmt->execute();
$barber = $barberStmt->get_result()->fetch_assoc();

// Define page title
$page_title = "My Profile";
include '../includes/header.php';
?>

<main>
    <section class="profile-section">
        <div class="container">
            <div class="section-header">
                <h1>My Profile</h1>
            </div>

            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-image">
                            <img src="https://images.pexels.com/photos/15613465/pexels-photo-15613465/free-photo-of-man-with-beard-holding-scissors.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Profile Image">
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($barber['FirstName'] . ' ' . $barber['LastName']); ?></h2>
                            <p class="hire-date">Hired on <?php echo date('F j, Y', strtotime($barber['HireDate'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="profile-main">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($barber['FirstName']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($barber['LastName']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($barber['UserEmail']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($barber['Phone']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($barber['Bio']); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<style>
.profile-section {
    padding: 2rem 0;
    margin-top: 60px;
    min-height: calc(100vh - 60px);
    background-color: #f5f7fa;
}

.section-header {
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.section-header h1 {
    margin: 0;
    font-size: 2.4rem;
}

.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}

.profile-sidebar {
    height: fit-content;
}

.profile-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
    text-align: center;
}

.profile-image {
    width: 150px;
    height: 150px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    overflow: hidden;
    background-color: #1a365d;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info h2 {
    margin: 0 0 0.5rem;
    font-size: 2rem;
}

.hire-date {
    color: #666;
    font-size: 1.4rem;
    margin: 0;
}

.profile-main {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
}

.profile-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 1.6rem;
}

.form-group textarea {
    resize: vertical;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
}

.alert {
    padding: 1.5rem;
    border-radius: 4px;
    margin-bottom: 2rem;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.alert ul {
    margin: 0;
    padding-left: 2rem;
}

.alert li {
    margin-bottom: 0.5rem;
}

.alert li:last-child {
    margin-bottom: 0;
}

@media (max-width: 991px) {
    .profile-container {
        grid-template-columns: 1fr;
    }

    .profile-card {
        max-width: 400px;
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .profile-image {
        width: 120px;
        height: 120px;
    }

    .profile-info h2 {
        font-size: 1.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Any other initialization code can go here
});
</script> 