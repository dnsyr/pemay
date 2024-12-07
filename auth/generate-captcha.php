<?php
session_start();
header('Content-Type: image/png');

// Check if captcha text is already set in the session
if (!isset($_SESSION['captcha'])) {
  // Generate a new CAPTCHA text if not set
  $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
  $_SESSION['captcha'] = $captchaText;
} else {
  $captchaText = $_SESSION['captcha'];
}

// Create the CAPTCHA image
$image = imagecreate(120, 40); // Increase width for more space
$bgColor = imagecolorallocate($image, 255, 255, 255); // White background
$textColor = imagecolorallocate($image, 0, 0, 0);     // Black text

// Add distracting lines
for ($i = 0; $i < 5; $i++) {
  imageline(
    $image,
    rand(0, 120),
    rand(0, 40),
    rand(0, 120),
    rand(0, 40),
    imagecolorallocate(
      $image,
      rand(0, 255),  // Random red value
      rand(0, 255),  // Random green value
      rand(0, 255)   // Random blue value
    )
  );
}

// Add CAPTCHA text
imagestring($image, 5, 10, 10, $captchaText, $textColor);

// Output the image
imagepng($image);
imagedestroy($image);
