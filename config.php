<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE CONFIG
$host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "food_delivery";

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// APP URL
$app_url = "/CP3407";

// STRIPE CONFIG
$stripe_secret_key = "";

$stripe_publishable_key = "";

$webhook_secret = "";

function cp3407_require_login(): void
{
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['sess_user'])) {
        header("Location: /CP3407/registration%20%26%20login/login.php");
        exit;
    }
}

function cp3407_current_user_id(mysqli $conn): int
{
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    if (!empty($_SESSION['sess_user'])) {
        $email = (string) $_SESSION['sess_user'];
        $stmt = $conn->prepare("SELECT Id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $_SESSION['user_id'] = (int) $row['Id'];
                return (int) $row['Id'];
            }
        }
    }

    return 0;
}

function cp3407_current_user_email(): string
{
    return (string) ($_SESSION['sess_user'] ?? '');
}

function cp3407_money(float $amount): string
{
    return 'SGD ' . number_format($amount, 2);
}

function cp3407_label(string $value): string
{
    return ucwords(str_replace('_', ' ', trim($value)));
}

function cp3407_badge_class(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'paid', 'completed', 'delivered', 'confirmed', 'ready_for_pickup' => 'success',
        'pending', 'pending_payment', 'preparing', 'picked_up', 'on_the_way' => 'warning text-dark',
        'failed', 'cancelled', 'partial_failed' => 'danger',
        default => 'secondary',
    };
}