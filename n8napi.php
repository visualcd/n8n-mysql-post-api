<?php
// Include config visualcd IWI
$config = include __DIR__ . '/n8napiconfig.php';

// API key validation
// Security Recommendation: While this script allows API key via GET, POST body, or HTTP headers for flexibility,
// the preferred and most secure method is to use a dedicated HTTP header (e.g., 'X-API-KEY').
// Transmitting keys via URL parameters (GET) can lead to them being logged in server access logs or stored in browser history.
// Transmitting keys in the request body for non-POST/PUT/PATCH methods is unconventional.
// Always prioritize using a dedicated header like 'X-API-KEY'.
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
    $config['database'],
    (int)$config['port'] // Ensure port is cast to integer
);
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("Database connection error: " . $mysqli->connect_error);
    echo json_encode(['error' => 'An internal server error occurred.']);
    exit;
}

// General Security Advisory: Input and Output Encoding
// 1. Input Validation: All incoming data (from $_GET, $_POST, php://input, headers, etc.) should be
//    rigorously validated. This includes checking types, lengths, formats, and allowed characters.
//    While some validation is present (e.g., for 'valoare'), a comprehensive approach is crucial.
// 2. Output Encoding: When returning data, ensure it's correctly encoded for the context in which it will be used.
//    For this JSON API, `json_encode()` handles much of the output encoding for JSON structure. However, if any
//    data returned could be interpreted as HTML or JavaScript by any client (even if not intended),
//    ensure appropriate contextual encoding (e.g., `htmlspecialchars` for HTML contexts) to prevent XSS.
//    Since `Content-Type: application/json` is set, modern browsers should not render the content as HTML,
//    but defense in depth is a good practice.

// Security Advisory: HTTP Security Headers
// Consider implementing security-enhancing HTTP headers. These are typically set at the web server level
// (e.g., Apache, Nginx) or in a central part of an application for broader coverage, but can also be added via PHP's header() function.
// Examples:
// - X-Content-Type-Options: nosniff (Prevents browsers from MIME-sniffing the content-type)
// - Referrer-Policy: strict-origin-when-cross-origin (Controls how much referrer information is sent)
// - Strict-Transport-Security: max-age=31536000; includeSubDomains (If HTTPS is enforced, tells browsers to only use HTTPS)
// - Content-Security-Policy: (More complex to configure, but powerful for XSS prevention. Example: header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';"))
// Choose headers appropriate for your application's needs and security posture.
header('Content-Type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Exemplu: preia date dintr-un tabel numit 'data'
        // Security & Performance Advisory: The query 'SELECT * FROM data' fetches all columns and all rows.
        // If the 'data' table contains sensitive information, many columns, or a very large number of rows,
        // this can lead to security vulnerabilities (exposing unnecessary data) and performance issues.
        // In a production environment, consider:
        // 1. Selecting only necessary columns (e.g., 'SELECT col1, col2 FROM data').
        // 2. Implementing pagination (e.g., using LIMIT and OFFSET).
        // 3. Adding WHERE clauses to filter data if this endpoint is meant for specific subsets of data.
        $result = $mysqli->query('SELECT * FROM data');
        if ($result === false) {
            http_response_code(500);
            error_log("GET request query error: " . $mysqli->error);
            echo json_encode(['error' => 'Error processing your request.']);
            exit;
        }
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
            $params = [];
            // Inlocuire variabile de forma {{ $json.cale }} sau {{ $('Nod').item.json.cale }} cu valorile din input
            $query = preg_replace_callback('/\{\{\s*(\$json((?:\.[a-zA-Z0-9_]+)+)|\$\(\'([^)]+)\'\)\.item\.json((?:\.[a-zA-Z0-9_]+)+))\s*\}\}/', function($matches) use ($input, &$params) {
                $value = null;
                if (!empty($matches[2])) { // $json.cale
                    $path = explode('.', trim($matches[2], '.'));
                    $current_value = $input;
                    foreach ($path as $key) {
                        if (isset($current_value[$key])) {
                            $current_value = $current_value[$key];
                        } else {
                            return $matches[0]; // Placeholder not found, return original
                        }
                    }
                    $value = $current_value;
                } elseif (!empty($matches[3]) && !empty($matches[4])) { // $('Nod').item.json.cale
                    $node = $matches[3];
                    $path = explode('.', trim($matches[4], '.'));
                    $current_value = $input[$node]['item']['json'] ?? null;
                    if ($current_value === null) return $matches[0]; // Placeholder not found, return original
                    foreach ($path as $key) {
                        if (isset($current_value[$key])) {
                            $current_value = $current_value[$key];
                        } else {
                            return $matches[0]; // Placeholder not found, return original
                        }
                    }
                    $value = $current_value;
                }

                if (isset($value)) {
                    $params[] = $value;
                    return '?';
                }
                return $matches[0]; // Return original match if value extraction failed
            }, $query);

            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                http_response_code(500);
                error_log("MySQL Prepare Error for query (" . $query . "): " . $mysqli->error);
                echo json_encode(['error' => 'Error processing your request.']);
                exit;
            }

            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                if (!$stmt->bind_param($types, ...$params)) {
                    http_response_code(500);
                    error_log("MySQL Bind Param Error: " . $stmt->error);
                    echo json_encode(['error' => 'Error processing your request.']);
                    $stmt->close();
                    exit;
                }
            }

            if (!$stmt->execute()) {
                http_response_code(400);
                error_log("MySQL Execute Error: " . $stmt->error);
                echo json_encode(['error' => 'Error processing your request.']);
                $stmt->close();
                exit;
            }

            $result = $stmt->get_result();
            $rows = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            } elseif ($stmt->affected_rows > -1) { // For INSERT, UPDATE, DELETE
                 $rows = ['affected_rows' => $stmt->affected_rows];
            }

            echo json_encode($rows);
            $stmt->close();
            break;
        }
        // Exemplu: inserează date în tabelul 'data'
        define('MAX_VALOARE_LENGTH', 255); // Define maximum length for 'valoare'

        if (!isset($input['valoare'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Lipseste campul valoare']);
            exit;
        }

        // Validate 'valoare' type
        if (!is_string($input['valoare'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Campul valoare trebuie sa fie un string.']);
            exit;
        }

        // Validate 'valoare' not empty (after trimming whitespace)
        if (trim($input['valoare']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Campul valoare nu poate fi gol.']);
            exit;
        }

        // Validate 'valoare' length
        if (strlen($input['valoare']) > MAX_VALOARE_LENGTH) {
            http_response_code(400);
            echo json_encode(['error' => 'Campul valoare depaseste lungimea maxima permisa (' . MAX_VALOARE_LENGTH . ' caractere).']);
            exit;
        }

        $stmt = $mysqli->prepare('INSERT INTO data (valoare) VALUES (?)');
        if ($stmt === false) { // Check if prepare() failed
            http_response_code(500);
            error_log("Insert prepare failed: " . $mysqli->error);
            echo json_encode(['error' => 'Could not complete the operation.']);
            exit;
        }
        if (!$stmt->bind_param('s', $input['valoare'])) { // Check if bind_param() failed
            http_response_code(500);
            error_log("Insert bind_param failed: " . $stmt->error);
            echo json_encode(['error' => 'Could not complete the operation.']);
            $stmt->close();
            exit;
        }
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            http_response_code(500);
            error_log("Insert execute failed: " . $stmt->error);
            echo json_encode(['error' => 'Could not complete the operation.']);
        }
        $stmt->close(); // Ensure statement is closed in all paths
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
$mysqli->close();
?>
