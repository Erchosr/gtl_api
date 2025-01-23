<?php
require_once "function.php";

try {
    // Connection and retrieval of emails
    $mailbox = EmailConnection::connectMailbox();
    $emails = [];
    $numMessages = imap_num_msg($mailbox);

    for ($i = $numMessages; $i > 0; $i--) {
        $overview = imap_fetch_overview($mailbox, $i, 0);
        $structure = imap_fetchstructure($mailbox, $i);
        $headers = imap_headerinfo($mailbox, $i);

        // Check if the email has attachments
        $hasAttachments = false;
        if (!empty($structure->parts)) {
            foreach ($structure->parts as $part) {
                if (isset($part->disposition) && strtolower($part->disposition) === "attachment") {
                    $hasAttachments = true;
                    break;
                }
            }
        }

        // Retrieve recipients
        $to = isset($headers->to) ? $headers->to : [];
        $cc = isset($headers->cc) ? $headers->cc : [];
        $bcc = isset($headers->bcc) ? $headers->bcc : [];
        $all_recipients = array_merge($to, $cc, $bcc);

        $all_recipients = array_map(function($recipient) {
            return is_object($recipient) ? $recipient->mailbox . "@" . $recipient->host : $recipient;
        }, $all_recipients);

        $emails[] = [
            "email_number" => $i,
            "subject" => isset($overview[0]->subject) ? $overview[0]->subject : "No subject",
            "from" => isset($overview[0]->from) ? $overview[0]->from : "Unknown",
            "date" => isset($overview[0]->date) ? $overview[0]->date : "Unknown date",
            "readen" => isset($overview[0]->seen) ? "yes" : "no",
            "hasAttachments" => $hasAttachments,
            "recipients" => array_map("EmailContent::decodeMimeHeader", $all_recipients)
        ];
    }

    EmailConnection::closeMailbox();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Emails</title>
</head>
<body>
    <h1>Liste des Emails</h1>
    <table border="1">
        <tr>
            <th>Sujet</th>
            <th>Expéditeur</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Pièces jointes</th>
            <th>Destinataires</th>
            <th>Actions</th>
        </tr>
        <?php if (!empty($emails)): ?>
            <?php foreach ($emails as $email): ?>
                <tr>
                    <td><?= htmlspecialchars($email["subject"]); ?></td>
                    <td><?= htmlspecialchars($email["from"]); ?></td>
                    <td><?= htmlspecialchars($email["date"]); ?></td>
                    <td><?= $email["readen"]; ?></td>
                    <td><?= $email["hasAttachments"] ? "📎" : ""; ?></td>
                    <td><?= implode(", ", $email["recipients"]); ?></td>
                    <td>
                        <a href="view_email.php?email_number=<?= $email["email_number"]; ?>">Voir</a>
                        <?php if ($email["hasAttachments"]): ?>
                            <a href="get_file.php?email_number=<?= $email["email_number"]; ?>">Télécharger les pièces jointes</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Aucun email trouvé.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>
