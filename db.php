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


// ==========================================
// POSTGRESQL / SUPABASE ADAPTER CLASSES
// ==========================================

class PgsqlResult
{
    private array $rows;
    private int $cursor = 0;
    public int $num_rows = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array
    {
        if ($this->cursor < $this->num_rows) {
            return $this->rows[$this->cursor++];
        }
        return null;
    }

    public function fetch_all(int $mode = MYSQLI_ASSOC): array
    {
        return $this->rows;
    }
}


class PgsqlStatement
{
    private PDOStatement $stmt;
    private PDO $pdo;
    private array $params = [];
    public ?string $error = null;

    public function __construct(PDOStatement $stmt, PDO $pdo)
    {
        $this->stmt = $stmt;
        $this->pdo  = $pdo;
    }

    public function bind_param(string $types, mixed &...$vars): bool
    {
        $this->params = [];
        foreach ($vars as &$val) {
            $this->params[] = &$val;
        }
        return true;
    }

    public function execute(?array $params = null): bool
    {
        try {
            $args = $params ?? $this->params;
            return $this->stmt->execute($args);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Supabase Pgsql execute error: " . $e->getMessage());
            return false;
        }
    }

    public function get_result(): PgsqlResult|false
    {
        try {
            $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            return new PgsqlResult($rows);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function close(): bool
    {
        $this->stmt->closeCursor();
        return true;
    }
}


class PgsqlConnection
{
    private PDO $pdo;
    public ?string $error = null;
    public ?string $connect_error = null;
    public int|string $insert_id = 0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function set_charset(string $charset): bool
    {
        return true;
    }

    private function sanitizeSql(string $sql): string
    {
        // Strip MySQL backticks for PostgreSQL compatibility
        return str_replace('`', '', $sql);
    }

    public function prepare(string $sql): PgsqlStatement|false
    {
        try {
            $cleanSql = $this->sanitizeSql($sql);
            $stmt = $this->pdo->prepare($cleanSql);
            return new PgsqlStatement($stmt, $this->pdo);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Supabase Pgsql prepare error: " . $e->getMessage());
            return false;
        }
    }

    public function query(string $sql): PgsqlResult|bool
    {
        try {
            $cleanSql = $this->sanitizeSql($sql);
            $stmt = $this->pdo->query($cleanSql);
            if ($stmt && $stmt->columnCount() > 0) {
                return new PgsqlResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Supabase Pgsql query error: " . $e->getMessage());
            return false;
        }
    }

    public function close(): void
    {
    }
}


// ==========================================
// DATABASE CONNECTION SETUP
// ==========================================

$dbConfig = require __DIR__ . '/config.php';
$conn     = null;

// Check if Supabase configuration is active
$supabaseConfig = $dbConfig['supabase'] ?? null;
$useSupabase    = !empty(getenv('SUPABASE_HOST')) || 
                  (!empty($supabaseConfig['host']) && ($supabaseConfig['pass'] ?? '') !== 'CHANGE_ME_SUPABASE_PASSWORD');

if ($useSupabase) {
    // SUPABASE (POSTGRESQL) CONNECTION
    $host = getenv('DB_HOST') ?: ($supabaseConfig['host'] ?? 'db.bmwtijjrsskkbabueibd.supabase.co');
    $port = getenv('DB_PORT') ?: ($supabaseConfig['port'] ?? 5432);
    $name = getenv('DB_NAME') ?: ($supabaseConfig['name'] ?? 'postgres');
    $user = getenv('DB_USER') ?: ($supabaseConfig['user'] ?? 'postgres');
    $pass = getenv('DB_PASS') ?: ($supabaseConfig['pass'] ?? '');

    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $conn = new PgsqlConnection($pdo);
    } catch (PDOException $e) {
        error_log("Supabase connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your Supabase credentials in config.php.");
    }

} else {
    // LOCAL MYSQL CONNECTION
    $dbEnv  = isLocalRequest() ? ($dbConfig['local'] ?? []) : ($dbConfig['live'] ?? []);
    $dbHost = getenv('DB_HOST') ?: ($dbEnv['host'] ?? 'localhost');
    $dbUser = getenv('DB_USER') ?: ($dbEnv['user'] ?? 'root');
    $dbPass = getenv('DB_PASS') ?: ($dbEnv['pass'] ?? '');
    $dbName = getenv('DB_NAME') ?: ($dbEnv['name'] ?? 'campus_lost_found');

    try {
        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($conn->connect_error) {
            error_log("DB connection failed: " . $conn->connect_error);
            die("Database connection failed. Please configure MySQL or Supabase credentials in config.php.");
        }
        $conn->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        error_log("DB connection failed: " . $e->getMessage());
        die("Database connection failed. Please configure MySQL or Supabase credentials in config.php.");
    }
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
    $conn,
    string $table,
    string $idColumn,
    $idValue
): bool {

    if (!empty(password_get_info($storedPassword)['algo'])) {
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
