//test_db.php
//database connection test


<?php
// načtení souboru s funkcemi pro práci s databází
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

// připojení k databázi
$conn = connect_db();

// vytvoření SQL dotazu
$sql = "INSERT INTO gifts (name, image) VALUES ('$name', '$image')";

// provedení SQL dotazu
$result = $conn->query($sql);

// kontrola, zda se dotaz podařil
if (!$result) {
  // zobrazení chybové zprávy

  print("Chyba: Nepodařilo se připojit k databázi.");

  // výpis chyby na stránku
echo "<p>Chyba: " . $conn->connect_error . "</p>";
// nebo
print "<p>Chyba: " . $conn->connect_error . "</p>";

  echo "Chyba: " . $conn->error;
} elseif ($result){
  print("Vše je v pořádku.");
}

?>

<?php include 'footer.php'; ?>