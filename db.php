
<?php

function isLocalRequest(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $host = explode(':', $host)[0];

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}


function isHttpsRequest(): bool
{
    return !empty($_SERVER['HTTPS']) &&
           strtolower((string) $_SERVER['HTTPS']) !== 'off';
}


// HTTPS REDIRECT
if (!isLocalRequest() && !isHttpsRequest()) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    header('Location: https://' . $host . $uri, true, 301);
    exit();
}


// DATABASE CONFIGURATION (credentials live in config.php / environment)
$dbConfig = require __DIR__ . '/config.php';
$dbEnv    = isLocalRequest() ? $dbConfig['local'] : $dbConfig['live'];

$dbHost = getenv('DB_HOST') ?: $dbEnv['host'];
$dbUser = getenv('DB_USER') ?: $dbEnv['user'];
$dbPass = getenv('DB_PASS') ?: $dbEnv['pass'];
$dbName = getenv('DB_NAME') ?: $dbEnv['name'];

unset($dbConfig, $dbEnv);


// DATABASE CONNECTION
try {
    $conn = new mysqli(
        $dbHost,
        $dbUser,
        $dbPass,
        $dbName
    );

    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }

    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}


// SECURE SESSION
function configureSecureSession(): void
{
    session_set_cookie_params([
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}


// PASSWORD VERIFICATION AND UPGRADE
function verifyPasswordAndUpgrade(
    string $password,
    string $storedPassword,
    mysqli $conn,
    string $table,
    string $idColumn,
    $idValue
): bool {

    if (password_get_info($storedPassword)['algo'] !== 0) {
        return password_verify($password, $storedPassword);
    }

    if (!hash_equals($storedPassword, $password)) {
        return false;
    }

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $sql = "UPDATE {$table} SET password = ? WHERE {$idColumn} = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (is_int($idValue)) {
            $stmt->bind_param('si', $hashedPassword, $idValue);
        } else {
            $stmt->bind_param('ss', $hashedPassword, $idValue);
        }

        $stmt->execute();
        $stmt->close();
    }

    return true;
}

// HTML ESCAPING
function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


// CSRF PROTECTION
function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
           escapeHtml(csrfToken()) . '">';
}

function csrfVerify(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals(csrfToken(), $sent)) {
        http_response_code(403);
        die("Invalid or missing security token. Go back, refresh the page and try again.");
    }
}

// Requires a POST request with a valid CSRF token
function requirePostWithCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die("Invalid request method.");
    }

    csrfVerify();
}


// ADMIN CHECK
function requireAdmin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['admin_username'])) {
        header("Location: admin-login.php");
        exit();
    }
}


// Turns every <a data-post> link into a POST request with a CSRF token.
// Print it just before </body> on pages that have such links.
function csrfPostScript(): string
{
    $token = json_encode(csrfToken());

    return '<script>
document.addEventListener("click", function (event) {
    var link = event.target.closest("a[data-post]");
    if (!link || event.defaultPrevented) { return; }
    event.preventDefault();

    var url  = new URL(link.getAttribute("href"), window.location.href);
    var form = document.createElement("form");
    form.method = "POST";
    form.action = url.pathname;

    url.searchParams.forEach(function (value, name) {
        var input = document.createElement("input");
        input.type = "hidden"; input.name = name; input.value = value;
        form.appendChild(input);
    });

    var csrf = document.createElement("input");
    csrf.type = "hidden"; csrf.name = "csrf_token"; csrf.value = ' . $token . ';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
});
</script>';
}

?>
