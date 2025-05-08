<?php
// Database connection parameters
$servername = "barberbookdb-barberbook.j.aivencloud.com";
$username = "avnadmin";
$password = "AVNS_EHS3bUWR3_7dcdFu9Ow";
$dbname = "barberbookdb";
$port = "14282";

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected successfully to the database!<br>";
    
    // Test query to get all services
    $query = "SELECT * FROM Services";
    $result = $conn->query($query);
    
    if ($result) {
        echo "Number of services found: " . $result->num_rows . "<br>";
        
        // Display services
        while ($row = $result->fetch_assoc()) {
            echo "Service: " . $row['Name'] . " - Price: $" . $row['Price'] . "<br>";
        }
    } else {
        echo "Error executing query: " . $conn->error;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
        echo "<br>Database connection closed.";
    }
}
?> 