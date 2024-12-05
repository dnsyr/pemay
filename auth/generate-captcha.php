<?php
session_start();
header('Content-Type: image/png');

$captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
$_SESSION['captcha'] = $captchaText;

$image = imagecreate(100, 40);
$bgColor = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);

imagestring($image, 5, 10, 10, $captchaText, $textColor);
imagepng($image);
imagedestroy($image);