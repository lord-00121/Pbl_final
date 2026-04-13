<?php
// includes/cloudinary_helper.php
require_once __DIR__ . '/../config/cloudinary.php';

/**
 * Uploads a file to Cloudinary and returns the secure URL.
 * @param array $file The $_FILES['name'] array
 * @param string $folder Optional folder name in Cloudinary
 * @return string|null The secure URL or null on failure
 */
function uploadToCloudinary($file, $folder = 'sportify') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return null;

    $timestamp = time();
    $api_key = CLOUDINARY_API_KEY;
    $api_secret = CLOUDINARY_API_SECRET;
    $cloud_name = CLOUDINARY_CLOUD_NAME;

    // Create signature
    $params = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
    ];
    ksort($params);
    $sign_string = "";
    foreach ($params as $key => $value) {
        $sign_string .= "$key=$value&";
    }
    $sign_string = rtrim($sign_string, '&') . $api_secret;
    $signature = sha1($sign_string);

    // Prepare CURL
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    $postData = [
        'file' => new CURLFile($file['tmp_name']),
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return null;

    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}
