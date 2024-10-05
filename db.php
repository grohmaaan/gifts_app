<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DEBUG_MODE', true);

// Function for connecting to the database
function connect_db() {
    // Database connection details
    // Add your database details here

    if ($_SERVER['HTTP_HOST'] == "cb.cz") {
        $servername = "octo-service";
        $username = "neratovice";
        $password = "ahmo4aku";
        $dbname = "neratovice_db";
    } elseif ($_SERVER['HTTP_HOST'] == "localhost") {
        $servername = "localhost:3306";
        $username = "root";
        $password = "";
        $dbname = "gifts_app_db";
    } else {
        die("Error: Failed to connect to the database.");
    }

    // Create a database connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the connection was successful
    if ($conn->connect_error) {
        // Display an error message
        die("Error: Failed to connect to the database. " . $conn->connect_error);
    }

    // Return the database connection object
    return $conn;
}

// Function for getting a list of all gifts from the database
function get_gifts() {
    // Database connection
    $conn = connect_db();

    // SQL query
    $sql = "SELECT * FROM gifts";

    // Execute the SQL query
    $result = $conn->query($sql);

    // Check if the query returned any results
    if ($result->num_rows > 0) {
        // Create an empty array to store gifts
        $gifts = array();

        // Loop through all rows in the result
        while ($row = $result->fetch_assoc()) {
            // Add the row to the gifts array
            $gifts[] = $row;
        }

        // Close the database connection
        $conn->close();

        // Return the gifts array
        return $gifts;
    } else {
        // Close the database connection
        $conn->close();

        // Return an empty array
        return array();
    }
}

// Function to add
function add_gift($conn, $name, $image, $shared, $shares_count, $greeting_card_url, $eshop_link) {
    $sql = "INSERT INTO gifts (name, image, shared, shares_count, reserved, reserved_shares, greeting_card_url, eshop_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $reserved = 0; // Initially, the gift is not reserved
    $reserved_shares = 0; // Initially, no shares are reserved

    // Bind parameters
    $stmt->bind_param("ssiiisss", $name, $image, $shared, $shares_count, $reserved, $reserved_shares, $greeting_card_url, $eshop_link);

    $result = $stmt->execute();
    if (!$result) {
        error_log('Error in add_gift function: ' . mysqli_error($conn));
    }
    $stmt->close();
    $conn->close();

    return $result;
}


// Funkce pro rezervaci dárku
function reserve_gift($conn, $gift_id, $user_email, $user_phone, $user_name, $shared) {
    // Retrieve the current status of the gift
    $gift_info = get_gift_info($conn, $gift_id);
    if (!$gift_info) {
        return false; // Gift not found
    }

    // Check if the gift is shared and update accordingly
    if ($shared && $gift_info['shares_count'] > $gift_info['reserved_shares']) {
        // Update the reserved shares
        $sql_update = "UPDATE gifts SET reserved_shares = reserved_shares + 1";
        
        // If all shares are now reserved, update the reserved status
        if ($gift_info['shares_count'] - 1 == $gift_info['reserved_shares']) {
            $sql_update .= ", reserved = 1";
        }

        $sql_update .= " WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $gift_id);
        $result_update = $stmt_update->execute();
        $stmt_update->close();

        if (!$result_update) {
            error_log('Error updating gift reservation: ' . mysqli_error($conn));
            return false;
        }
    } elseif (!$shared) {
        // For non-shared gifts, set as reserved directly
        $sql_reserved = "UPDATE gifts SET reserved = 1 WHERE id = ?";
        $stmt_reserved = $conn->prepare($sql_reserved);
        $stmt_reserved->bind_param("i", $gift_id);
        $result_reserved = $stmt_reserved->execute();
        $stmt_reserved->close();

        if (!$result_reserved) {
            error_log('Error updating non-shared gift reservation: ' . mysqli_error($conn));
            return false;
        }
    }

    // Insert reservation information into the history
    $sql_user_info = "INSERT INTO reservation_history (gift_id, user_email, user_phone, user_name) VALUES (?, ?, ?, ?)";
    $stmt_user_info = $conn->prepare($sql_user_info);
    $stmt_user_info->bind_param("isss", $gift_id, $user_email, $user_phone, $user_name);
    $result_user_info = $stmt_user_info->execute();
    $stmt_user_info->close();

    if (!$result_user_info) {
        error_log('Error inserting user info: ' . mysqli_error($conn));
        return false;
    }

    return true;
}

// Function to update a gift in the database
// ADD THE E-SHOP LINK AS A PARAMETER
function update_gift($id, $name, $image, $shared, $greeting_card_url, $eshop_link) {
    $conn = connect_db();
    // INCLUDE THE E-SHOP LINK IN THE SQL QUERY
    $sql = "UPDATE gifts SET name = ?, image = ?, shared = ?, greeting_card_url = ?, eshop_link = ? WHERE id = ?";

    $stmt = $conn->prepare($sql);
    // BIND THE E-SHOP LINK AS A STRING
    $stmt->bind_param("ssissi", $name, $image, $shared, $greeting_card_url, $eshop_link, $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $result;
}


// Function for getting a list of reserved gifts from the database
function get_reserved_gifts() {
    // Database connection
    $conn = connect_db();

    // SQL query
    $sql = "SELECT g.name, rh.user_email, rh.user_phone, rh.user_notes FROM reservation_history rh
            JOIN gifts g ON rh.gift_id = g.id WHERE g.reserved = 1";

    // Execute the SQL query
    $result = $conn->query($sql);

    // Check if the query returned any results
    if ($result->num_rows > 0) {
        // Create an empty array for storing reserved gifts
        $reserved_gifts = array();

        // Loop through all rows in the result
        while ($row = $result->fetch_assoc()) {
            // Add the row to the reserved gifts array
            $reserved_gifts[] = $row;
        }

        // Close the database connection
        $conn->close();

        // Return the reserved gifts array
        return $reserved_gifts;
    } else {
        // Close the database connection
        $conn->close();

        // Return an empty array
        return array();
    }
}


// Function to get information about a specific gift
function get_gift_info($conn, $id) {
    // Query to get gift information
    $sql = "SELECT * FROM gifts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a row is returned
    if ($result->num_rows > 0) {
        // Fetch result as an associative array
        $gift_info = $result->fetch_assoc();

        // Close the statement
        $stmt->close();

        return $gift_info;
    } else {
        // Close the statement
        $stmt->close();

        // Return an empty array if no row is returned
        return array();
    }
}

// Funkce pro získání historie rezervovaných dárků
function get_reserved_gifts_history() {
    $conn = connect_db();
    $sql = "SELECT g.name, rh.user_email, rh.user_phone, rh.user_name FROM reservation_history rh JOIN gifts g ON rh.gift_id = g.id";
    $result = $conn->query($sql);
    $reserved_gifts = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reserved_gifts[] = $row;
        }
    }
    $conn->close();
    return $reserved_gifts;
}

// Funkce pro přidání poznámek uživatele do databáze
function add_user_notes($id, $user_notes) {
    $conn = connect_db();
    $sql = "UPDATE reservation_history SET user_notes = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $user_notes, $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}


?>
