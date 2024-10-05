<?php
// Include the file with database functions
require_once 'db.php';

session_start();

// Check if the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Logout logic
if (isset($_GET['logout'])) {
    // Unset all of the session variables
    $_SESSION = [];

    // Destroy the session
    session_destroy();

    // Redirect to login page after logout
    header('Location: admin_login.php');
    exit;
}

// Connect to the database
$conn = connect_db();

// Check if the connection was successful
if ($conn->connect_error) {
    // Display error message
    echo "<b>Error: Unable to connect to the database.</b>";

    // Display error on the page
    echo "<b><p>Error: " . $conn->connect_error . "</p></b>";

    // Terminate the script
    die("<b>Connection failed: </b>" . $conn->connect_error);
}

// Function to format output for better readability
function printResult($testName, $result) {
    echo "<b>$testName:</b> " . ($result ? "Passed" : "Failed") . "<br>";
}

// Test the add_gift function
$name = "Test Gift";
$image = "test_image.jpg";
$shares_count = 5;
$result_add_gift = add_gift($name, $image, $shares_count);
printResult('add_gift', $result_add_gift);

// Test the get_gifts function
echo "<hr><b>Before get_gifts:</b>\n";
$result_get_gifts_before = get_gifts();
print_r($result_get_gifts_before);

$result_get_gifts = get_gifts();
printResult('get_gifts', !empty($result_get_gifts));

// Test the reserve_gift function
$id = 257   ; // Provide an existing gift ID
$user_email = "test@example.com";
$user_phone = "123456789";
$user_name = "Test User";
$shares_to_reserve = 2;
$shared = 0; // Define the $shared variable
$reserved = 1;

echo "<hr><b>Before reserve_gift:</b>\n";
$result_reserve_gift_before = get_gift_info($id);
print_r($result_reserve_gift_before);

$result_reserve_gift = reserve_gift($id, $user_email, $user_phone, $user_name, $reserved, $shares_to_reserve, $shared);
printResult('reserve_gift', $result_reserve_gift);

echo "<hr><b>After reserve_gift:</b>\n";
$result_reserve_gift_after = get_gift_info($id);
print_r($result_reserve_gift_after);

// Test the update_gift function
$edit_gift_id = 38; // Provide an existing gift ID
$edit_gift_name = "Updated Gift Name";
$edit_gift_image = "updated_image.jpg";
$edit_gift_shared = 1; // 1 for shared, 0 for not shared
$edit_gift_shares_count = 10;

echo "<hr><b>Before update_gift:</b>\n";
$result_update_gift_before = get_gift_info($edit_gift_id);
print_r($result_update_gift_before);

$result_update_gift = update_gift($edit_gift_id, $edit_gift_name, $edit_gift_image, $edit_gift_shared, $edit_gift_shares_count);
printResult('update_gift', $result_update_gift);

echo "<hr><b>After update_gift:</b>\n";
$result_update_gift_after = get_gift_info($edit_gift_id);
print_r($result_update_gift_after);

// Test the get_reserved_gifts function
$result_get_reserved_gifts = get_reserved_gifts();
printResult('get_reserved_gifts', !empty($result_get_reserved_gifts));

// Close the database connection
$conn->close();


?>

<?php include 'footer.php'; ?>
