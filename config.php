<?php
// DATABASE CONFIG
$conn = new mysqli("localhost", "root", "", "food_delivery");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// STRIPE CONFIG
$stripe_secret_key = getenv("STRIPE_SECRET_KEY") ?: "YOUR_STRIPE_SECRET_KEY";
$stripe_publishable_key = getenv("STRIPE_PUBLISHABLE_KEY") ?: "YOUR_STRIPE_PUBLISHABLE_KEY";
$webhook_secret = getenv("STRIPE_WEBHOOK_SECRET") ?: "YOUR_STRIPE_WEBHOOK_SECRET";
$stripe_webhook_secret = $webhook_secret;
$app_url = "/CP3407";

if (!function_exists('cp3407_current_user_email')) {
    function cp3407_current_user_email(): string
    {
        return trim((string)($_SESSION['sess_user'] ?? ''));
    }
}

if (!function_exists('cp3407_current_user_id')) {
    function cp3407_current_user_id(mysqli $conn): int
    {
        $session_user_id = (int)($_SESSION['user_id'] ?? 0);
        if ($session_user_id > 0) {
            return $session_user_id;
        }

        $email = cp3407_current_user_email();
        if ($email === '') {
            return 0;
        }

        $stmt = $conn->prepare("SELECT Id FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        if (!$row) {
            return 0;
        }

        $_SESSION['user_id'] = (int)$row['Id'];
        return (int)$row['Id'];
    }
}

if (!function_exists('cp3407_require_login')) {
    function cp3407_require_login(string $login_url = "/CP3407/registration%20%26%20login/login.php"): void
    {
        if (cp3407_current_user_email() === '') {
            header("Location: " . $login_url);
            exit();
        }
    }
}
