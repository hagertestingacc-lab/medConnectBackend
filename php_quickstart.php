<html lang="HTML5">

<head>
    <title>PHP Quick Start</title>
</head>

<body>
    <?php

require __DIR__ . '/vendor/autoload.php';

// Use the Configuration class
use Cloudinary\Configuration\Configuration;

// ✅ Correct way
$cloudinaryUrl = env('CLOUDINARY_URL');
Configuration::instance($cloudinaryUrl . '?secure=true');
