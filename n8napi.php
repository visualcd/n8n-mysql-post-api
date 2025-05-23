<?php
// Include config
$config = include __DIR__ . '/n8napiconfig.php';

// API key validation
$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] ??
    ($_SERVER['HTTP_X_API_KEY'] ??
    ($_GET['api_key'] ?? ''));

// Dacă nu s-a găsit cheia, caută și în body (pentru POST/PUT/PATCH)
if (!$apiKey && in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['api_key'])) {
        $apiKey = $input['api_key'];
    }
}

// DEBUG: decomenteaza linia de mai jos pentru a vedea header-ele primite
// file_put_contents('/tmp/n8n_api_debug.log', print_r($headers, true) . print_r($_SERVER, true));
if ($apiKey !== $config['api_key']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'received_api_key' => $apiKey]);
    exit;
}

// Connect to MySQL/MariaDB
$mysqli = new mysqli(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['database']
);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

header('Content-Type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Exemplu: preia date dintr-un tabel numit 'data'
        $result = $mysqli->query('SELECT * FROM data');
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
        break;
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        // Daca se trimite action=query si exista query in body sau in query string, executa query-ul primit
        if ((isset($input['action']) && $input['action'] === 'query') && (isset($input['query']) || isset($_GET['query']))) {
            $query = $input['query'] ?? $_GET['query'];
            // Inlocuire variabile de forma {{ $json.cale }} sau {{ $('Nod').item.json.cale }} cu valorile din input
            $query = preg_replace_callback('/\{\{\s*(\$json((?:\.[a-zA-Z0-9_]+)+)|\$\(\'([^)]+)\'\)\.item\.json((?:\.[a-zA-Z0-9_]+)+))\s*\}\}/', function($matches) use ($input) {
                if (!empty($matches[2])) { // $json.cale
                    $path = explode('.', trim($matches[2], '.'));
                    $value = $input;
                    foreach ($path as $key) {
                        if (isset($value[$key])) {
                            $value = $value[$key];
                        } else {
                            return '';
                        }
                    }
                    return is_string($value) ? addslashes($value) : $value;
                } elseif (!empty($matches[3]) && !empty($matches[4])) { // $('Nod').item.json.cale
                    $node = $matches[3];
                    $path = explode('.', trim($matches[4], '.'));
                    $value = $input[$node]['item']['json'] ?? null;
                    if ($value === null) return '';
                    foreach ($path as $key) {
                        if (isset($value[$key])) {
                            $value = $value[$key];
                        } else {
                            return '';
                        }
                    }
                    return is_string($value) ? addslashes($value) : $value;
                }
                return '';
            }, $query);
            $result = $mysqli->query($query);
            if ($result === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Query error', 'details' => $mysqli->error]);
                exit;
            }
            $rows = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            echo json_encode($rows);
            break;
        }
        // Exemplu: inserează date în tabelul 'data'
        if (!isset($input['valoare'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Lipseste campul valoare']);
            exit;
        }
        $stmt = $mysqli->prepare('INSERT INTO data (valoare) VALUES (?)');
        $stmt->bind_param('s', $input['valoare']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Insert failed']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
$mysqli->close();
?>
