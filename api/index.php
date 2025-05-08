<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Home";
include 'includes/header.php';
?>

<main>
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">Professional Haircuts & Styling</h1>
        <p class="hero-subtitle">Experience the best haircuts and styling services from our expert barbers.</p>
        <div class="cta-buttons">
          <a href="booking.php" class="btn btn-primary">Book Now</a>
        </div>
      </div>
    </div>
  </section>

  <section class="services section-padding">
    <div class="container">
      <h2 class="section-title">Our Services</h2>
      <div class="services-grid">
        <?php
        $sql = "SELECT * FROM Services LIMIT 3";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo '<div class="service-card">';
            echo '  <div class="service-card-content">';
            echo '    <div class="service-icon"><i class="fas fa-cut"></i></div>';
            echo '    <h3>' . $row['Name'] . '</h3>';
            echo '    <p>' . htmlspecialchars($row['Description']) . '</p>';
            echo '    <div class="service-details">';
            echo '      <span class="duration"><i class="far fa-clock"></i> ' . htmlspecialchars($row['Duration']) . ' min</span>';
            echo '      <span class="price">BDT ' . htmlspecialchars($row['Price']) . '</span>';
            echo '    </div>';
            echo '  </div>';
            echo '  <a href="booking.php?service=' . $row['ServiceID'] . '" class="btn btn-outline btn-block">Book Now</a>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>
  </section>

  <section class="barbers section-padding">
    <div class="container">
      <h2 class="section-title">Meet Our Expert Barbers</h2>
      <div class="barbers-grid">
        <?php
        $sql = "SELECT UserID, FirstName, LastName, Bio FROM Barbers LIMIT 3";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo '<div class="barber-card">';
            echo '  <div class="barber-image"><img src="https://images.pexels.com/photos/3998429/pexels-photo-3998429.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Barber ' . htmlspecialchars($row['FirstName']) . ' ' . htmlspecialchars($row['LastName']) . '" class="barber-profile-image"></div>';
            echo '  <div class="barber-card-content">';
            echo '    <h3>' . htmlspecialchars($row['FirstName']) . ' ' . htmlspecialchars($row['LastName']) . '</h3>';
            echo '    <p class="barber-bio">' . htmlspecialchars(substr($row['Bio'], 0, 100)) . '...</p>';
            
            $barberID = $row['UserID'];
            $ratingSQL = "SELECT AVG(Rating) as AverageRating FROM Reviews WHERE BarberID = $barberID";
            $ratingResult = $conn->query($ratingSQL);
            $ratingRow = $ratingResult->fetch_assoc();
            $averageRating = round($ratingRow['AverageRating'], 1);
            
            echo '<div class="barber-rating">';
            echo '<span class="stars">';
            for ($i = 1; $i <= 5; $i++) {
              if ($i <= $averageRating) {
                echo '<i class="fas fa-star"></i>';
              } elseif ($i - 0.5 <= $averageRating) {
                echo '<i class="fas fa-star-half-alt"></i>';
              } else {
                echo '<i class="far fa-star"></i>';
              }
            }
            echo '</span>';
            echo '<span class="rating-value">' . $averageRating . ' (' . $ratingResult->num_rows . ' reviews)</span>';
            echo '</div>';
            
            echo '</div>';
            echo '<a href="barber.php?id=' . $row['UserID'] . '" class="btn btn-outline btn-block">View Profile</a>';
            echo '</div>';
          }
        }
        ?>
      </div>
      <div class="text-center mt-4">
        <a href="barbers.php" class="btn btn-secondary">View All Barbers</a>
      </div>
    </div>
  </section>

  <section class="testimonials section-padding">
    <div class="container">
      <h2 class="section-title">What Our Customers Say</h2>
      <div class="testimonials-slider">
        <?php
        $sql = "SELECT r.Rating, r.Comments, c.FirstName, c.LastName, b.FirstName as BarberFirstName, b.LastName as BarberLastName 
                FROM Reviews r 
                JOIN Customers c ON r.CustomerID = c.UserID 
                JOIN Barbers b ON r.BarberID = b.UserID 
                ORDER BY RAND() LIMIT 5";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo '<div class="testimonial-card">';
            echo '  <div class="testimonial-card-content">';
            echo '    <div class="testimonial-rating">';
            for ($i = 1; $i <= 5; $i++) {
              if ($i <= $row['Rating']) {
                echo '<i class="fas fa-star"></i>';
              } else {
                echo '<i class="far fa-star"></i>';
              }
            }
            echo '</div>';
            echo '    <p class="testimonial-comment">"' . htmlspecialchars($row['Comments']) . '"</p>';
            echo '    <div class="testimonial-author">';
            echo '      <p class="author-name">' . htmlspecialchars($row['FirstName']) . ' ' . htmlspecialchars(substr($row['LastName'], 0, 1)) . '.</p>';
            echo '      <p class="author-info">Review for ' . htmlspecialchars($row['BarberFirstName']) . ' ' . htmlspecialchars($row['BarberLastName']) . '</p>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>
  </section>

  <section class="cta section-padding">
    <div class="container">
      <div class="cta-content text-center">
        <h2 class="cta-title">Ready for a Fresh Look?</h2>
        <p class="cta-text">Book your appointment today and experience the best barbering services.</p>
        <a href="booking.php" class="btn btn-primary btn-lg">Book Now</a>
      </div>
    </div>
  </section>
</main>

<?php
include 'includes/footer.php';
?>