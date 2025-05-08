<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in as a customer
if (!isLoggedIn() || isBarber()) {
    setFlashMessage('You must be logged in as a customer to access this page.', 'error');
    redirect('../login.php');
}

$customer_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Validate required fields
    if (empty($first_name)) {
        setFlashMessage('First name is required.', 'error');
    } elseif (empty($last_name)) {
        setFlashMessage('Last name is required.', 'error');
    } elseif (empty($phone)) {
        setFlashMessage('Phone number is required.', 'error');
    } elseif (!preg_match('/^[0-9]{11}+$/', $phone)) {
        setFlashMessage('Phone number must be 11 digits long.', 'error');
    } elseif (empty($email)) {
        setFlashMessage('Email is required.', 'error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('Invalid email format.', 'error');  
    } else {
        // Update customer information
        $query = "UPDATE Customers SET 
                 FirstName = ?, 
                 LastName = ?, 
                 Phone = ?, 
                 Email = ?, 
                 Address = ? 
                 WHERE UserID = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $email, $address, $customer_id);
        
        if ($stmt->execute()) {
            setFlashMessage('Profile updated successfully!', 'success');
            redirect('profile.php');
        } else {
            setFlashMessage('Error updating profile. Please try again.', 'error');
        }
    }
}

// Fetch customer information
$query = "SELECT * FROM Customers WHERE UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Define page title
$page_title = "My Profile";
include '../includes/header.php';
?>

<main>
    <section class="profile-section">
        <div class="container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your personal information and preferences</p>
            </div>
            
            <div class="profile-content">
                <div class="profile-card">
                    <div class="profile-image">
                        <img src="https://images.pexels.com/photos/15613465/pexels-photo-15613465/free-photo-of-man-with-beard-holding-scissors.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Profile Image">
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']); ?></h2>
                    </div>
                </div>
                
                <form action="profile.php" method="POST" class="profile-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['FirstName']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['LastName']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['Phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['Email']); ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer['Address']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<style>
.profile-section {
    padding: 4rem 0;
    background-color: #f5f7fa;
    min-height: calc(100vh - 80px);
    margin-top: 80px;
}

.profile-header {
    text-align: center;
    margin-bottom: 3rem;
}

.profile-header h1 {
    font-size: 3.6rem;
    color: #1a365d;
    margin-bottom: 1rem;
}

.profile-header p {
    font-size: 1.8rem;
    color: #666;
}

.profile-content {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
}

.profile-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info h2 {
    font-size: 2.4rem;
    color: #1a365d;
    margin-bottom: 0.5rem;
}

.profile-form {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 500;
    color: #333;
    font-size: 1.4rem;
}

.form-group input {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 1.4rem;
    transition: border-color 0.2s ease;
}

.form-group input:focus {
    border-color: #1a365d;
    outline: none;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

@media (max-width: 768px) {
    .profile-section {
        padding: 2rem 0;
    }
    
    .profile-header h1 {
        font-size: 2.8rem;
    }
    
    .profile-header p {
        font-size: 1.6rem;
    }
    
    .profile-card {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions button {
        width: 100%;
    }
}
</style> 