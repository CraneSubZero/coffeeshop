<?php
// Include the phpqrcode library
include('phpqrcode-master/qrlib.php');

// URL to redirect to
$url = "http://localhost/kkc";

// Path to save the QR code image
$filePath = "qrcodes/localhost_qr.png";

// Generate the QR code
QRcode::png($url, $filePath, QR_ECLEVEL_L, 10);

// Display the QR code image
echo "<h1>QR Code for Localhost Website</h1>";
echo "<img src='$filePath' alt='QR Code' />";
?>