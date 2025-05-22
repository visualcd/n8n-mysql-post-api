<?php
// Încarcă configurația din fișierul n8napiconfig.php
$config = require_once __DIR__ . '/n8napiconfig.php';

$dbHost     = $config['DB_HOST'];
$dbUser     = $config['DB_USER'];
$dbPassword = $config['DB_PASSWORD'];
$dbName     = $config['DB_NAME'];
$apiKey     = $config['API_KEY'];

// Activează modul debug doar pentru dezvoltare/test
define("DEBUG_MODE", true);

// Setăm header-ul ca fiind JSON
header('Content-Type: application/json');

// Verificăm dacă metoda cererii este POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se acceptă doar cereri POST.'
    ]);
    exit();
}

// Citim conținutul cererii – se poate primi ca JSON sau ca URL-encoded POST
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);
if (!$data) {
    $data = $_POST;
}

// Verificăm API key-ul transmis
if (!isset($data['api_key']) || $data['api_key'] !== $apiKey) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Acces neautorizat.'
    ]);
    exit();
}

// Stabilim acțiunea ce va fi efectuată
$action = isset($data['action']) ? $data['action'] : '';
$response = [];

if ($action === 'query') {
    if (!isset($data['query'])) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Lipsește parametrul "query".'
        ]);
        exit();
    }
    $query = $data['query'];

    // Conectăm la baza de date
    $mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
    if ($mysqli->connect_error) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Conexiunea la baza de date a eșuat: ' . $mysqli->connect_error
        ]);
        exit();
    }

    // Executăm interogarea
    $result = $mysqli->query($query);
    if (!$result) {
        $response['status']  = 'error';
        $response['message'] = 'Eroare la interogare: ' . $mysqli->error;
    } else {
        // Dacă interogarea returnează un set de rezultate (ex: SELECT sau SHOW)
        if ($result instanceof mysqli_result) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $response['status'] = 'success';
            $response['data']   = $rows;
        } else {
            // Pentru interogări care nu returnează un set de date (INSERT, UPDATE, etc.)
            $response['status']  = 'success';
            $response['message'] = 'Interogarea a fost executată cu succes.';
        }
    }
    $mysqli->close();
} else {
    $response['status']  = 'error';
    $response['message'] = 'Acțiune necunoscută.';
}

// Partea de debug este inclusă doar dacă modul debug este activ
// if (defined("DEBUG_MODE") && DEBUG_MODE === true) {
//     // Aici poți adăuga orice informații de debug dorești
//     $response['debug'] = [
//         'received_post' => $data
//     ];
// }

// Răspunsul final se trimite în format JSON
echo json_encode($response);
?>
