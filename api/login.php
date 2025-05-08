<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isBarber()) {
        redirect('barber/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$errors = [];
$email = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitize($_POST['user_type']);
    
    // Simple validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($user_type) || !in_array($user_type, ['customer', 'barber'])) {
        $errors['user_type'] = 'Invalid user type';
    }
    
    // Proceed if no errors
    if (empty($errors)) {
        $table = ($user_type === 'customer') ? 'Customers' : 'Barbers';
        
        $sql = "SELECT UserID, FirstName, LastName, Email, PassHash FROM {$table} WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (verifyPassword($password, $user['PassHash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['user_type'] = $user_type;
                $_SESSION['user_name'] = $user['FirstName'] . ' ' . $user['LastName'];
                $_SESSION['user_email'] = $user['Email'];
                
                // Redirect to appropriate dashboard
                if ($user_type === 'barber') {
                    redirect('barber/dashboard.php');
                } else {
                    redirect('customer/dashboard.php');
                }
            } else {
                $errors['login'] = 'Invalid email or password';
            }
        } else {
            $errors['login'] = 'Invalid email or password';
        }
    }
}

// Define page title
$page_title = "Login";
include 'includes/header.php';
?>

<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue</p>
                </div>
                
                <?php if (!empty($errors['login'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['login']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="auth-form" data-validate="true">
                    <div class="form-group">
                        <label for="user_type">I am a:</label>
                        <div class="user-type-select">
                            <label class="user-type-option">
                                <input type="radio" name="user_type" value="customer" <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] === 'customer') ? 'checked' : ''; ?>>
                                <span class="user-type-label">
                                    <i class="fas fa-user"></i>
                                    <span>Customer</span>
                                </span>
                            </label>
                            <label class="user-type-option">
                                <input type="radio" name="user_type" value="barber" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'barber') ? 'checked' : ''; ?>>
                                <span class="user-type-label">
                                    <i class="fas fa-cut"></i>
                                    <span>Barber</span>
                                </span>
                            </label>
                        </div>
                        <?php if (!empty($errors['user_type'])): ?>
                            <div class="error-message"><?php echo $errors['user_type']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required data-label="Email">
                        <?php if (!empty($errors['email'])): ?>
                            <div class="error-message"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" required data-label="Password">
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="error-message"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
include 'includes/footer.php';
?>

<style>
/* Auth Specific Styles */
.auth-section {
    padding: var(--space-6) 0;
    min-height: calc(100vh - 80px - 80px);
    display: flex;
    align-items: center;
    margin-top: 80px;
}

.auth-container {
    max-width: 450px;
    margin: 0 auto;
    background-color: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    padding: var(--space-4);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--space-4);
}

.auth-header h1 {
    margin-bottom: var(--space-1);
}

.auth-header p {
    color: var(--color-text-light);
}

.auth-form {
    margin-bottom: var(--space-3);
}

.form-group {
    margin-bottom: var(--space-3);
}

.form-group label {
    display: block;
    margin-bottom: var(--space-1);
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 12px var(--space-2);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: var(--font-body);
    font-size: 1.6rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(26, 54, 93, 0.1);
}

.form-group input.is-invalid {
    border-color: var(--color-error);
}

.error-message {
    color: var(--color-error);
    font-size: 1.4rem;
    margin-top: 4px;
}

.password-field {
    position: relative;
}

.toggle-password {
    position: absolute;
    top: 50%;
    right: var(--space-2);
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--color-text-light);
    cursor: pointer;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-3);
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
    margin-right: 8px;
}

.forgot-password {
    font-size: 1.4rem;
    color: var(--color-primary);
}

.btn-block {
    width: 100%;
    padding: var(--space-2);
    font-size: 1.6rem;
}

.auth-footer {
    text-align: center;
    margin-top: var(--space-3);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border);
}

.user-type-select {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}

.user-type-option {
    flex: 1;
    position: relative;
}

.user-type-option input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.user-type-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--space-2);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.user-type-label i {
    font-size: 2.4rem;
    margin-bottom: var(--space-1);
    color: var(--color-text-light);
}

.user-type-option input:checked + .user-type-label {
    border-color: var(--color-primary);
    background-color: rgba(26, 54, 93, 0.05);
}

.user-type-option input:checked + .user-type-label i {
    color: var(--color-primary);
}

@media (max-width: 576px) {
    .auth-container {
        padding: var(--space-3);
    }
}
</style>

<script>
// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const passwordInput = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            icon.className = 'fas fa-eye';
        }
    });
});
</script>