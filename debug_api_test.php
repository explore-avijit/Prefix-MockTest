<?php
// Mock session for testing
session_start();
// Assuming user_id 1 exists and is admin, based on previous tool_check_db.php output
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
session_write_close();

// Capture output of get_profile.php
ob_start();
passthru('php c:\xampp\htdocs\Prefix-MockTest\api\admin\get_profile.php');
$output = ob_get_clean();

echo "--- RAW OUTPUT START ---\n";
echo $output;
echo "\n--- RAW OUTPUT END ---\n";

// Attempt to decode
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON Decode: SUCCESS\n";
    print_r($json);
} else {
    echo "JSON Decode: FAILED - " . json_last_error_msg() . "\n";
}
?>
