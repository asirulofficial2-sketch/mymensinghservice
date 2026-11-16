<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $service = mysqli_real_escape_string($conn, $_POST['service_type']);
    $problem = mysqli_real_escape_string($conn, $_POST['problem']);

    $sql = "INSERT INTO orders (customer_name, phone, address, service_type, problem) 
            VALUES ('$name', '$phone', '$address', '$service', '$problem')";

    if (mysqli_query($conn, $sql)) {
        echo "<div style='text-align:center;padding:50px;font-family:Arial;'>
                <h2 style='color:green;'>✅ আপনার অর্ডার সফলভাবে জমা হয়েছে!</h2>
                <p>আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।</p>
                <p><a href='index.php'>← হোমে ফিরে যান</a></p>
              </div>";
    } else {
        echo "সমস্যা: " . mysqli_error($conn);
    }
}
?>