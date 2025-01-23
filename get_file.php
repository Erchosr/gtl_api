<?php
require_once "function.php";

$email_number = isset($_GET["email_number"]) ? intval($_GET["email_number"]) : null;


try {
    if ($email_number !== null) {
        $result = EmailAttachmentHandler::installAttachments($email_number);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        die("Email number is required.");
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>