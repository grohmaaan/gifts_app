<?php
// Include the database connection code and capture the connection object
include 'db.php';
$conn = connect_db();

// Display debug messages only for administrators
function displayDebugMessage($message) {
    global $is_admin;
    if ($is_admin) {
        echo $message . '<br>';
    }
}

$maintain = false;

if ($maintain == true && $_SERVER['HTTP_HOST'] == "cb.cz") {
    header("Location: https://cb.cz/neratovice/jeziskova_vnoucata/maintanance");
    exit;
} elseif ($maintain == true && $_SERVER['HTTP_HOST'] == "localhost") {
    header("Location: http://localhost/gifts_app/maintanance");
    exit;
}

session_start();

// Check if the user is an administrator
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
        $gift_id = $_POST['gift_id'];
        $user_email = $_POST['user_email'];
        $user_phone = $_POST['user_phone'];
        $shares_to_reserve = isset($_POST['shared']) ? intval($_POST['shared']) : 0;
        $shared = isset($_POST['shared']) ? 1 : 0;
        $reserved = 1; // Set to the appropriate value

        // Retrieve the original gift status
        $original_gift = get_gift_info($conn, $gift_id);

        // If the gift is not originally shared, set shared to 0
        if ($original_gift && !$original_gift['shared']) {
            $shared = 0;
        }

        if (empty($user_phone)) {
            echo '<p class="error">Prosím, vyplňte všechna pole.</p>';
        } else {
            // Pass $conn to reserve_gift function
            $result = reserve_gift($conn, $gift_id, $user_email, $user_phone, $user_name, $reserved, $shares_to_reserve, $shared);

            if ($result) {
                echo '<p class="success">Dárek byl úspěšně rezervován.</p>';
                echo '<script>';
                echo 'document.getElementById("successModal").style.display = "block";';
                echo '</script>';
            } else {
                echo '<p class="error">Nepodařilo se rezervovat dárek.</p>';
                displayDebugMessage('<p class="error">Chyba: ' . mysqli_error($conn) . '</p>');
            }
        }
    }
}

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

// Retrieve the list of gifts
$gifts = get_gifts($conn);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Ježíškovo neratovická vnoučata</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"/>

    <style>
        /* Přidání průhlednosti do navbaru */
        .navbar {
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Přidání přechodů na tlačítka */
        button,
        input[type="submit"],
        input[type="email"],
        input[type="tel"] {
            transition: background-color 0.3s ease;
        }

        button.reserve:hover,
        input[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Nastavení průhledného pozadí pro modální okno */
        .modal-content {
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Přidání stínu k obrázkům */
        .gift img {
            border: 1px solid #cccccc;
            width: 200px;
            height: 200px;
            margin: 10px;
            border-radius: 8px; /* Zaoblené rohy */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Stíny */
        }

        .gift img.no-image {
            display: block;
            margin: 10px auto;
            width: 200px;
            height: 200px;
            border: 1px solid #cccccc;
            border-radius: 8px; /* Zaoblené rohy */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Stíny */
        }

        /* Upravený styl pro názvy dárků */
        .gift h2 {
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Zkouška různých barev pro pozadí */
        body:not(#logo, footer) {
            background-color: #f5f5f5; /* Světle šedá */
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="https://cb.cz/neratovice" target="_blank" style="float: left;">
        <img src="logo4.png" alt="Logo" width="30" height="24" class="logo">
    </a>
    <h2 class="navbar_h2" style="margin-top:10px; padding: 17px !important;">Ježíškova neratovická vnoučata | 2023</h2>
</nav>

<p>Děkujeme všem ochotným dárcům, kteří chtějí udělat ukrajinským dětem trochu hezčí Vánoce. Všechny vybrané dárky budou předány na dopolední bohoslužbě (od 10:00) v Jistotě na Štědrý den. Zabalené a jménem označené dárky můžete donést do Jistoty nebo do obchodu školních potřeb v budově Základní umělecké školy. <b>Kontakt: +420 603 868 798</b></p>


 <!-- Procházení seznamu dárků -->
<div class="gift-container">

<?php
// Inicializace proměnné $count mimo podmínku
$count = 0;

// Kontrola odeslání formuláře
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit'])) {
        // Zpracování rezervace
        $gift_id = $_POST['gift_id'];
        $user_email = $_POST['user_email'];
        $user_phone = $_POST['user_phone'];

        // ... (zbytek kódu pro zpracování rezervace)
    }
}

foreach ($gifts as $gift) {
    echo '<div class="gift">';
    if (!empty($gift['image'])) {
        echo '<img src="' . $gift['image'] . '" alt="' . $gift['name'] . '" class="gift-image">';
    } else {
        echo '<img src="no_image.png" alt="No Image" class="gift-image no-image">';
    }
    echo '<h2 class="gift-title wrap">' . $gift['name'] . '</h2>';
    // Zobrazení informace o sdíleném dárku
    echo '<p class="gift-shared">' . ($gift['shared'] ? 'Sdílený dárek' : '') . '</p>';
    // Kontrola, zda je dárek rezervován
    if ($gift['reserved']) {
        // Zobrazení zprávy, že je dárek rezervován
        echo '<p class="reserved">Tento dárek je již rezervován.</p>';
    } else {
        // Zobrazení tlačítka pro rezervaci dárku
        echo '<button class="reserve" data-id="' . $gift['id'] . '" data-shared="' . ($gift['shared'] ? 1 : 0) . '">Rezervovat</button>';
    }
    echo '</div>';

    $count++;
    // Po každém třetím dárku ukončit řádek a začít nový
    if ($count % 3 == 0) {
        echo '</div><div class="gift-container">';
    }
}
?>


<!-- Formulář pro rezervaci dárku -->
<form id="form" method="post" action="index.php">
    <h2>Rezervace dárku</h2>
    <p>Pro rezervaci dárku vyplňte následující údaje a klikněte na tlačítko Rezervovat.</p>
    <input type="hidden" name="gift_id" id="gift_id_modal">
    <!-- PŘIDAT ZDE -->
    <input type="hidden" name="shared" id="shared_modal" value="0">
    <!-- KONEC -->
    <label for="user_email_modal">E-mail:</label>
    <input type="email" name="user_email" id="user_email_modal" required>
    <label for="user_phone_modal">Telefonní číslo:</label>
    <input type="tel" name="user_phone" id="user_phone_modal" required>
    <button type="submit" name="submit" class="reserve">Rezervovat</button>
</form>


<!-- Modální okno pro rezervaci -->
<div id="reservationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Rezervace dárku</h2>
        <p>Pro rezervaci dárku vyplňte následující údaje a klikněte na tlačítko Rezervovat.</p>
        <div id="dynamicFormContainer"></div>
    </div>
</div>

<!-- Modální okno pro úspěšnou rezervaci -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSuccessModal()">&times;</span>
        <h2>Úspěšná rezervace</h2>
        <p>Dárek byl úspěšně rezervován. Děkujeme!</p>
    </div>
</div>

<script>
    var buttons = document.querySelectorAll('.reserve');

    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function () {
            var gift_id = this.getAttribute('data-id');
            var shared = this.getAttribute('data-shared');
            displayReservationForm(gift_id, shared);

        });
    }

    function closeModal() {
        document.getElementById('reservationModal').style.display = 'none';
    }

    function closeSuccessModal() {
        document.getElementById('successModal').style.display = 'none';
    }

    function displayReservationForm(gift_id, shared) {
    var formContainer = document.getElementById('dynamicFormContainer');
    formContainer.innerHTML = `
    <form method="post" action="index.php" id="form_modal">
        <input type="hidden" name="gift_id" value="${gift_id}">
        <input type="hidden" name="shared" value="${shared ? 1 : 0}">
        <label for="user_email_modal">E-mail:</label>
        <input type="email" name="user_email" id="user_email_modal" required>
        <label for="user_phone_modal">Telefonní číslo:</label>
        <input type="tel" name="user_phone" id="user_phone_modal" required>
        <!-- Add a condition to include user_name field only if the gift is shared -->
        ${shared ? '<label for="user_name_modal">Jméno:</label><input type="text" name="user_name" id="user_name_modal">' : ''}
        <button type="submit" name="submit" class="reserve">Rezervovat</button>
    </form>
    `;
    document.getElementById('reservationModal').style.display = 'block';
}




<?php
if (isset($result) && $result) {
    // Display debug messages only when in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo 'Reservation successful\n';
    }

    echo 'document.getElementById("successModal").style.display = "block";';
}
?>

    window.onclick = function (event) {
        var modal = document.getElementById('reservationModal');
        var successModal = document.getElementById('successModal');
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === successModal) {
            closeSuccessModal();
        }
    }
</script>
</body>
</html>
<?php include 'footer.php'; ?>



