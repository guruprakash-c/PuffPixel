<?php
require 'vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$platform = $_POST['platform'] ?? 'custom';
$aspect = $_POST['aspect'] ?? '16:9';
$upscale = filter_var($_POST['upscale'] ?? false, FILTER_VALIDATE_BOOLEAN);
$title = $_POST['title'] ?? '';
$link = $_POST['link'] ?? '';

// Main image required
if (!isset($_FILES['mainImage']) || $_FILES['mainImage']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Main image is required']);
    exit;
}
$mainPath = $_FILES['mainImage']['tmp_name'];

$watermarkPath = null;
if (isset($_FILES['watermark']) && $_FILES['watermark']['error'] === UPLOAD_ERR_OK) {
    $watermarkPath = $_FILES['watermark']['tmp_name'];
}

// Define dimensions based on platform
list($targetWidth, $targetHeight) = [1280, 720]; // default

switch ($platform) {
    case 'youtube':
        $targetWidth = 1280; $targetHeight = 720; break;
    case 'instagram':
        $targetWidth = 1080; $targetHeight = 1080; break;
    case 'instagram-story':
        $targetWidth = 1080; $targetHeight = 1920; break;
    case 'facebook':
        $targetWidth = 1200; $targetHeight = 630; break;
    case 'twitter':
        $targetWidth = 1200; $targetHeight = 675; break;
    default:
        // Custom aspect ratio
        $parts = explode(':', $aspect);
        if (count($parts) === 2) {
            $ratio = $parts[0] / $parts[1];
            $targetWidth = 1280;
            $targetHeight = round($targetWidth / $ratio);
        }
        break;
}

$manager = new ImageManager(new Driver());
$image = $manager->read($mainPath);

// Resize to cover (crop to fit)
$image->cover($targetWidth, $targetHeight);

// Optional upscale
if ($upscale) {
    $image->resize($targetWidth, $targetHeight);
}

// Title overlay (centered)
if (!empty($title)) {
    $image->text($title, $targetWidth / 2, $targetHeight / 2, function ($font) use ($targetWidth) {
        $font->file('arial.ttf'); // Place arial.ttf in backend folder
        $font->size(min($targetWidth / 15, 80));
        $font->color('#ffffff');
        $font->align('center');
        $font->valign('middle');
        // Optional shadow
        $font->stroke('#000000', 3);
    });
}

// Link overlay (top-left)
if (!empty($link)) {
    $image->text($link, 20, 40, function ($font) use ($targetWidth) {
        $font->file('arial.ttf');
        $font->size($targetWidth / 40);
        $font->color('#ffffff');
        $font->stroke('#000000', 2);
    });
}

// Watermark (bottom-right)
if ($watermarkPath) {
    $wm = $manager->read($watermarkPath);
    $wmSize = $targetWidth * 0.15;
    $wm->resize($wmSize, null, function ($c) { $c->aspectRatio(); });
    $image->place($wm, 'bottom-right', 20, 20);
}

try {
    $encoded = $image->toAvif(80)->toDataUri(); // AVIF output
    echo json_encode(['thumbnail' => $encoded]);
} catch (\Exception $e) {
    // Fallback to JPEG if AVIF fails (graceful degradation)
    $encoded = $image->toJpeg(90)->toDataUri();
    echo json_encode(['thumbnail' => $encoded]);
}