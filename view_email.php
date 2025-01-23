<?php
header("Content-Type: application/json");
require_once "function.php";

$email_number = isset($_GET["email_number"]) ? intval($_GET["email_number"]) : null;

try {
    if ($email_number !== null) {
        $emailDetails = EmailDetails::getEmailDetails($email_number);
        echo json_encode($emailDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $unreadEmails = EmailDetails::getUnreadEmails();
        echo json_encode($unreadEmails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
