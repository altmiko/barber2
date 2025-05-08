<?php
// Get the base path for assets
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/barber/') !== false || strpos($_SERVER['PHP_SELF'], '/customer/') !== false) {
    $base_path = '../';
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-info">
                <div class="footer-logo">
                    <i class="fas fa-cut"></i>
                    <span>Barberbook</span>
                </div>
                <p>Professional haircuts and styling services from expert barbers. Book your appointment today!</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo $base_path; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $base_path; ?>services.php">Services</a></li>
                    <li><a href="<?php echo $base_path; ?>barbers.php">Barbers</a></li>
                    <li><a href="<?php echo $base_path; ?>booking.php">Book Now</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h3>Services</h3>
                <ul>
                    <?php
                    // Get top 5 services
                    $sql = "SELECT ServiceID, Name FROM Services LIMIT 5";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<li><a href="' . $base_path . 'services.php?id=' . $row['ServiceID'] . '">' . htmlspecialchars($row['Name']) . '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> info@barberbook.com</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Barberbook. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $base_path; ?>assets/js/main.js"></script>
</body>
</html>