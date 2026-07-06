<?php
$conn = mysqli_connect(
    "scholarpoint-db.cha6geg2ilp0.ap-south-1.rds.amazonaws.com",
    "admin",
    "sandipraut12",
    "school_portal"
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
