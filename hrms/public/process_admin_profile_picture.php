<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "No file uploaded or upload error occurred.";
    header("Location: admin_profile.php");
    exit();
}

$file = $_FILES['profile_picture'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];

// Get file extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Allowed extensions
$allowed = array('jpg', 'jpeg', 'png', 'gif');

if (!in_array($file_ext, $allowed)) {
    $_SESSION['error'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    header("Location: admin_profile.php");
    exit();
}

// Check file size (max 5MB)
if ($file_size > 5242880) {
    $_SESSION['error'] = "File size must be less than 5MB.";
    header("Location: admin_profile.php");
    exit();
}

// Create unique filename
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;

// Define upload directory - CORRECTED PATH
$upload_dir = __DIR__ . '/uploads/profiles/';
$upload_path = $upload_dir . $new_filename;

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    chmod($upload_dir, 0777);
}

// Get old profile picture to delete it
$query = "SELECT profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$old_picture = $result['profile_picture'];

// Upload new file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Update database
    $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $update->bind_param("si", $new_filename, $user_id);
    
    if ($update->execute()) {
        // Delete old profile picture if exists
        if ($old_picture && file_exists($upload_dir . $old_picture)) {
            unlink($upload_dir . $old_picture);
        }
        
        $_SESSION['success'] = "Profile picture updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update database.";
    }
} else {
    $_SESSION['error'] = "Failed to upload file. Please check folder permissions.";
}

header("Location: admin_profile.php");
exit();
?>