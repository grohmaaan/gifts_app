<?php

require_once 'db.php';
$conn = connect_db();


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

// Funkce pro nahrání obrázku
function uploadImage($file) {
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        error_log("No file uploaded for " . $file['name']);
        return null; // No file uploaded
    }

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        error_log("File is not an image for " . $file['name']);
        return null; // File is not an image
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        error_log("Unsupported file format for " . $file['name']);
        return null; // Unsupported file format
    }

    // Handle existing file
    if (file_exists($target_file)) {
        $filename = pathinfo($target_file, PATHINFO_FILENAME);
        $extension = pathinfo($target_file, PATHINFO_EXTENSION);
        $target_file = $target_dir . $filename . '_' . time() . '.' . $extension;
    }

    // Attempt to move the uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        error_log("File successfully uploaded: " . $target_file);
        return $target_file;
    } else {
        error_log("Failed to move uploaded file: " . $file['name']);
        return null; // Failed to move file
    }
}




// Přidání nového dárku
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_gift'])) {
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $shared = isset($_POST['shared']) ? 1 : 0;
    $shares_count = $shared ? (int)$_POST['shares_to_reserve'] : 1; // Default to 1 if not shared
    $eshop_link = isset($_POST['eshop_link']) ? $_POST['eshop_link'] : ''; // Set a default value if not provided


    // Upload Image
    $image = uploadImage($_FILES['image']);
    if (!$image) {
        $image = 'no_image.png'; // Default image if no image was uploaded
    }

    // Upload Greeting Card
    $greeting_card_url = uploadImage($_FILES['greeting_card_url']); // Could be null if not uploaded

    if (empty($name)) {
        echo '<p class="error">Prosím, vyplňte název dárku.</p>';
    } else {
        $result = add_gift($conn, $name, $image, $shared, $shares_count, $greeting_card_url, $eshop_link);
        if ($result) {
            echo '<p class="success">Nový dárek byl úspěšně přidán.</p>';
        } else {
            echo '<p class="error">Nepodařilo se přidat nový dárek.</p>';
        }
    }
    
}


// Handling the editing of a gift

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_gift'])) {
    $edit_gift_id = filter_input(INPUT_POST, 'edit_gift_id', FILTER_VALIDATE_INT);
    $edit_gift_name = htmlspecialchars(strip_tags($_POST['edit_gift_name']));
    $edit_gift_shared = isset($_POST['edit_gift_shared']) ? 1 : 0;
    $edit_image = $_FILES['edit_file']['error'] == 0 ? uploadImage($_FILES['edit_file']) : $_POST['current_image'];
    $edit_greeting_card_url = $_FILES['edit_greeting_card_url']['error'] == 0 ? uploadImage($_FILES['edit_greeting_card_url']) : $_POST['current_greeting_card_url'];
    $edit_eshop_link = filter_input(INPUT_POST, 'edit_eshop_link', FILTER_SANITIZE_URL);

    $result = update_gift($edit_gift_id, $edit_gift_name, $edit_image, $edit_gift_shared, $edit_greeting_card_url, $edit_eshop_link);
    if ($result) {
        echo '<p class="success">Dárek byl úspěšně aktualizován.</p>';
    } else {
        echo '<p class="error">Nepodařilo se aktualizovat dárek.</p>';
    }
}

// Funkce pro přidání poznámek k rezervaci
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_notes'])) {
    $id = $_POST['id']; // Změna z reservation_id na id
    $user_notes = $_POST['user_notes'];

    // Přidání poznámek do databáze
    $result = add_user_notes($id, $user_notes);

    if ($result) {
        echo '<p class="success">Poznámky byly úspěšně přidány.</p>';
    } else {
        echo '<p class="error">Nepodařilo se přidat poznámky.</p>';
    }
}

$reserved_gifts = get_reserved_gifts();
$_SESSION['token'] = bin2hex(random_bytes(32));

// Získání seznamu dárků
$gifts = get_gifts();

?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <title>Dárky - Administrace</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
</head>

<body>
    <h1>Dárky - Administrace</h1>
    <p>Vítejte na stránce pro administrátora, kde můžete přidávat nové dárky a zobrazovat seznam rezervovaných dárků.
        Pro přidání nového dárku vyplňte formulář níže. Pro zobrazení seznamu rezervovaných dárků klikněte na tlačítko
        Zobrazit.</p>
    <p>Welcome, Admin! <a href="?logout">Logout</a></p>
    <!-- Formulář pro přidání nového dárku -->
    <?php
echo '<form method="post" action="admin.php" enctype="multipart/form-data">';
echo '<h2>Přidání nového dárku</h2>';
echo '<label for="name">Název dárku:</label>';
echo '<input type="text" name="name" id="name" required>';
echo '<label for="image">Obrázek:</label>';
echo '<input type="file" name="image" id="image">';
echo '<label for="shared">Sdílený dárek:</label>';
echo '<input type="checkbox" name="shared" id="shared">';
echo '<label for="greeting_card_url">Přáníčko:</label>';
echo '<input type="file" name="greeting_card_url" id="greeting_card_url">';
// ... Additional fields if needed
echo '<input type="hidden" name="token" value="' . $_SESSION['token'] . '">';
echo '<button type="submit" name="add_gift">Přidat</button>';
echo '</form>';
?>
    <?php

// Seznam dárků
echo '<div class="gifts-container">';
foreach ($gifts as $gift) {
    echo '<div class="gift-item">';
    
    // Zobrazení obrázku dárku
    $imageSrc = isset($gift['image']) ? (strpos($gift['image'], 'uploads/') === 0 ? '/' . $gift['image'] : $gift['image']) : 'no_image.png';
    echo '<img src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($gift['name']) . '" class="gift-image">';
    
    
    echo '<h2 class="gift-title wrap">' . htmlspecialchars($gift['name']) . '</h2>';

    // Informace, zda je dárek sdílený
    $sharedText = $gift['shared'] ? 'Tento dárek je sdílený.' : 'Tento dárek není sdílený.';
    echo '<p>' . $sharedText . '</p>';
// Form for editing a gift
// Assume $gift is the current gift being edited
echo '<form method="post" action="admin.php" enctype="multipart/form-data">';
echo '<input type="hidden" name="edit_gift_id" value="' . $gift['id'] . '">';
echo '<label for="edit_gift_name_' . $gift['id'] . '">Název dárku:</label>';
echo '<input type="text" name="edit_gift_name" id="edit_gift_name_' . $gift['id'] . '" value="' . htmlspecialchars($gift['name']) . '" required>';
echo '<label for="edit_file_' . $gift['id'] . '">Změnit obrázek (volitelné):</label>';
echo '<input type="file" name="edit_file" id="edit_file_' . $gift['id'] . '">';
echo '<label for="edit_greeting_card_url_' . $gift['id'] . '">Přáníčko (optional):</label>';
echo '<input type="file" name="edit_greeting_card_url" id="edit_greeting_card_url_' . $gift['id'] . '">';
// ... Additional fields if needed

// ADD THE E-SHOP LINK FIELD HERE
echo '<label for="edit_eshop_link_' . $gift['id'] . '">E-shop link:</label>';

// Using null coalescing operator
echo '<input type="text" name="edit_eshop_link" id="edit_eshop_link_' . $gift['id'] . '" value="' . htmlspecialchars($gift['eshop_link'] ?? '') . '">';

// END


// Checkbox for shared gift
$checked = $gift['shared'] ? 'checked' : '';
echo '<label for="edit_gift_shared_' . $gift['id'] . '">Sdílený dárek:</label>';
echo '<input type="checkbox" name="edit_gift_shared" id="edit_gift_shared_' . $gift['id'] . '" ' . $checked . ' onchange="toggleSharesCountField(this, ' . $gift['id'] . ')">';

// Field for shares count, if the gift is shared
$displayStyle = $gift['shared'] ? 'block' : 'none';
echo '<div id="sharesCountField_' . $gift['id'] . '" style="display:' . $displayStyle . ';">';
echo '<label for="edit_shares_count_' . $gift['id'] . '">Počet rezervací:</label>';
echo '<input type="number" name="edit_shares_count" id="edit_shares_count_' . $gift['id'] . '" value="' . $gift['shares_count'] . '" min="1">';
echo '</div>';

echo '<button type="submit" name="edit_gift">Upravit</button>';
echo '</form>';


    echo '</div>';
}
echo '</div>';

echo '<script>
function toggleSharesCountField(checkbox, giftId) {
    var sharesCountField = document.getElementById("sharesCountField_" + giftId);
    sharesCountField.style.display = checkbox.checked ? "block" : "none";
}
</script>';

    ?>
    </div>

    <!-- Formulář pro přidání poznámek k rezervaci 
<form method="post" action="admin.php">
    <h2>Přidání poznámek k rezervaci</h2>
    <label for="id">Vyberte rezervaci:</label>
    <select name="id" id="id" required> -->
    <?php
    /* // Získání seznamu rezervovaných dárků
     $reserved_gifts = get_reserved_gifts();
     foreach ($reserved_gifts as $gift) {
         echo '<option value="' . $gift['id'] . '">' . $gift['name'] . ' - ' . $gift['user_email'] . '</option>';
     }*/
    ?>
    <!-- </select>
    <label for="user_notes">Poznámky:</label>
    <textarea name="user_notes" id="user_notes" required></textarea>
    <button type="submit" name="add_notes">Přidat poznámky</button>
</form>
-->

    <!-- Tlačítka pro zobrazení a tisk seznamu rezervovaných dárků -->
    <!-- Tabulka se seznamem rezervovaných dárků -->
    <table id="historyTable" style="display: none;">
        <thead>
            <tr>
                <th>Název dárku</th>
                <th>E-mail uživatele</th>
                <th>Telefonní číslo uživatele</th>
                <th>Poznámky uživatele</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $reserved_gifts = get_reserved_gifts();
            foreach ($reserved_gifts as $gift) {
                echo '<tr>';
                echo '<td>' . $gift['name'] . '</td>';
                echo '<td>' . $gift['user_email'] . '</td>';
                echo '<td>' . $gift['user_phone'] . '</td>';
                echo '<td>' . $gift['user_notes'] . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Tlačítka pro zobrazení a tisk seznamu rezervovaných dárků -->
    <button id="show">Zobrazit seznam rezervovaných dárků</button>
    <button id="print">Tisk seznamu rezervovaných dárků</button>



    <script>

        // JavaScript pro zobrazení seznamu rezervovaných dárků
        var buttonShow = document.getElementById('show');
        buttonShow.addEventListener('click', function () {
            document.getElementById('historyTable').style.display = 'table';
        });

        // JavaScript pro tisk seznamu rezervovaných dárků
        var buttonPrint = document.getElementById('print');
        buttonPrint.addEventListener('click', function () {
            printReservedGifts();
        });

        function printReservedGifts() {
            var table = document.getElementById('historyTable').cloneNode(true);
            var newWindow = window.open('', '_blank');
            newWindow.document.write('<html><head><title>Seznam rezervovaných dárků</title>');
            newWindow.document.write('<style>body{font-family: Arial, sans-serif;} table{border-collapse: collapse; width: 100%;} table, th, td{border: 1px solid #ddd;} th, td{padding: 8px; text-align: left;}</style>');
            newWindow.document.write('</head><body>');
            newWindow.document.write('<h2>Seznam rezervovaných dárků</h2>');
            newWindow.document.write(table.outerHTML);
            newWindow.document.write('</body></html>');
            newWindow.document.close();
            newWindow.print();
        }


        var buttonShow = document.getElementById('show');
        var buttonPrint = document.getElementById('print');
        var editForm = document.getElementById('editForm');

        buttonShow.addEventListener('click', function () {
            document.getElementById('historyTable').style.display = 'table';
        });

        buttonPrint.addEventListener('click', function () {
            printReservedGifts();
        });

        // Add an event listener for the print button
        var printButton = document.getElementById('print');
        printButton.addEventListener('click', function () {
            printReservedGifts();
        });

        function printReservedGifts() {
            var table = document.getElementById('historyTable').cloneNode(true);
            var newWindow = window.open('', '_blank');
            newWindow.document.write('<html><head><title>Seznam rezervovaných dárků</title>');
            newWindow.document.write('<style>body{font-family: Arial, sans-serif;} table{border-collapse: collapse; width: 100%;} table, th, td{border: 1px solid #ddd;} th, td{padding: 8px; text-align: left;}</style>');
            newWindow.document.write('</head><body>');
            newWindow.document.write('<h2>Seznam rezervovaných dárků</h2>');
            newWindow.document.write(table.outerHTML);
            newWindow.document.write('</body></html>');
            newWindow.document.close();
            newWindow.print();
        }

        // Funkce pro vyplnění formuláře úpravy dárku
        function fillEditForm(id, name, image, shared, sharesCount) {
            document.getElementById('edit_gift_id').value = id;
            document.getElementById('edit_gift_name').value = name;
            document.getElementById('edit_gift_shared').checked = shared;
            document.getElementById('edit_shares_count').value = sharesCount;
        }

        var editForms = document.querySelectorAll('.editForm');
        editForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                fillEditForm(
                    form.querySelector('[name="edit_gift_id"]').value,
                    form.querySelector('[name="edit_gift_name"]').value,
                    form.querySelector('[name="edit_gift_shared"]').value,
                    form.querySelector('[name="edit_shares_count"]').value
                );
            });
        });

        buttonShow.addEventListener('click', function () {
            document.getElementById('historyTable').style.display = 'table';
        });

        // Add JavaScript to toggle the display of the shares_count field based on the shared checkbox
        var sharedCheckbox = document.getElementById('shared');
        var sharesCountField = document.getElementById('sharesCountField');

        sharedCheckbox.addEventListener('change', function () {
            sharesCountField.style.display = this.checked ? 'block' : 'none';
        });

        // Add JavaScript to toggle the display of the shares_count field based on the edit_gift_shared checkbox
        var editGiftSharedCheckbox = document.getElementById('edit_gift_shared');
        var editSharesCountField = document.getElementById('edit_shares_count');

        editGiftSharedCheckbox.addEventListener('change', function () {
            editSharesCountField.style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>

</html>
<?php include 'footer.php'; ?>