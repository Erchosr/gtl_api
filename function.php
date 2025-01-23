<?php

// Classe pour charger la configuration à partir d"un fichier
class EmailConfig {
    private static $config;

    // Méthode pour charger la configuration à partir d"un fichier
    public static function loadConfig($file) {
        self::$config = include $file;
    }

    // Méthode pour obtenir une valeur de configuration
    public static function get($key) {
        return self::$config[$key] ?? null;
    }
}

// Classe pour gérer la connexion à la boîte de réception
class EmailConnection {
    private static $mailbox;

    // Méthode pour se connecter à la boîte de réception
    public static function connectMailbox() {
        $hostname = EmailConfig::get("hostname");
        $username = EmailConfig::get("username");
        $password = EmailConfig::get("password");

        self::$mailbox = imap_open($hostname, $username, $password);

        if (!self::$mailbox) {
            die("Connection error: " . imap_last_error());
        }
        return self::$mailbox;
    }

    // Méthode pour vérifier si la connexion est ouverte et la rouvrir si nécessaire
    public static function ensureMailboxOpen() {
        if (!self::$mailbox || imap_errors() !== []) {
            self::connectMailbox();
        }
    }

    // Méthode pour fermer la connexion à la boîte de réception
    public static function closeMailbox() {
        if (self::$mailbox) {
            imap_close(self::$mailbox);
            self::$mailbox = null;
        }
    }
}

// Classe pour décoder et nettoyer le contenu des emails
class EmailContent {
    // Méthode pour décoder le contenu en fonction de l"encodage
    public static function decodeContent($message, $encoding) {
        return match ($encoding) {
            3 => base64_decode($message), // Encodage BASE64
            4 => quoted_printable_decode($message), // Encodage QUOTED-PRINTABLE
            default => $message
        };
    }

    // Méthode pour décoder les en-têtes MIME
    public static function decodeMimeHeader($header) {
        return mb_decode_mimeheader($header);
    }

    // Méthode pour nettoyer le contenu HTML
    public static function cleanHtmlContent($html) {
        return preg_replace("/<(style|script|head|meta|link|html|body)[^>]*>.*?<\/\\1>/si", "", $html);
    }

    // Méthode pour nettoyer le contenu brut
    public static function cleanPlainContent($text) {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
        $text = mb_convert_encoding($text, "UTF-8", "auto");
        return preg_replace("/[\r\n]+/", "\n", $text);
    }

    // Méthode pour générer un nombre aléatoire entre 1000 et 9999
    public static function generateRandomNumber() {
        return rand(1000, 9999);
    }
}

// Classe pour obtenir les détails des emails
class EmailDetails {
    // Méthode pour obtenir les détails d"un email spécifique
    public static function getEmailDetails($email_number) {
        EmailConnection::ensureMailboxOpen();
        $mailbox = EmailConnection::connectMailbox();
        $overview = imap_fetch_overview($mailbox, $email_number, 0);
        $structure = imap_fetchstructure($mailbox, $email_number);
        $headers = imap_headerinfo($mailbox, $email_number);

        if (!$overview) {
            EmailConnection::closeMailbox();
            throw new Exception("Email with ID $email_number not found.");
        }

        $plain_text = $html_text = "";
        $attachments = [];
        $all_recipients = array_merge(
            isset($headers->to) ? $headers->to : [],
            isset($headers->cc) ? $headers->cc : [],
            isset($headers->bcc) ? $headers->bcc : []
        );
        
        $all_recipients = array_map(function($recipient) {
            return is_object($recipient) ? $recipient->mailbox . "@" . $recipient->host : $recipient;
        }, $all_recipients);

        if (!empty($structure->parts)) {
            foreach ($structure->parts as $partIndex => $part) {
                if ($part->subtype === "PLAIN") {
                    $plain_text .= EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1), $part->encoding);
                } elseif ($part->subtype === "HTML") {
                    $html_text .= EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1), $part->encoding);
                } elseif (isset($part->disposition) && strtolower($part->disposition) === "attachment") {
                    $filename = $part->dparameters[0]->value ?? "unknown_file";
                    $attachmentData = EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1), $part->encoding);
                    $date = date("Ymd_His");
                    $randomNumber = EmailContent::generateRandomNumber();
                    $attachmentPath = "attachments/" . $date . "_" . $randomNumber . "_" . $filename;
                    $attachments[] = [
                        "filename" => $filename,
                        "path" => "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]) . "/" . $attachmentPath
                    ];
                } elseif ($part->type === 1) {
                    foreach ($part->parts as $subPartIndex => $subPart) {
                        if ($subPart->subtype === "PLAIN") {
                            $plain_text .= EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1 . "." . ($subPartIndex + 1)), $subPart->encoding);
                        } elseif ($subPart->subtype === "HTML") {
                            $html_text .= EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1 . "." . ($subPartIndex + 1)), $subPart->encoding);
                        } elseif (isset($subPart->disposition) && strtolower($subPart->disposition) === "attachment") {
                            $filename = $subPart->dparameters[0]->value ?? "unknown_file";
                            $attachmentData = EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1 . "." . ($subPartIndex + 1)), $subPart->encoding);
                            $date = date("Ymd_His");
                            $randomNumber = EmailContent::generateRandomNumber();
                            $attachmentPath = "attachments/" . $date . "_" . $randomNumber . "_" . $filename;
                            $attachments[] = [
                                "filename" => $filename,
                                "path" => "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]) . "/" . $attachmentPath
                            ];
                        }
                    }
                }
            }
        } else {
            $plain_text = EmailContent::decodeContent(imap_body($mailbox, $email_number, 0), $structure->encoding);
        }

        $html_text = EmailContent::cleanHtmlContent($html_text);
        $plain_text = EmailContent::cleanPlainContent($plain_text);

        $emailDetails = [
            "email_id" => $email_number,
            "subject" => EmailContent::decodeMimeHeader($overview[0]->subject ?? ""),
            "from" => EmailContent::decodeMimeHeader($overview[0]->from ?? ""),
            "date" => $overview[0]->date ?? "",
            "read" => $overview[0]->seen ? "yes" : "no",
            "plain_content" => $plain_text,
            "html_content" => $html_text,
            "attachments" => $attachments,
            "recipients" => array_map("EmailContent::decodeMimeHeader", $all_recipients)
        ];

        EmailConnection::closeMailbox();
        return $emailDetails;
    }

    // Méthode pour obtenir tous les emails non lus
    public static function getUnreadEmails() {
        EmailConnection::ensureMailboxOpen();
        $mailbox = EmailConnection::connectMailbox();
        $emails = imap_search($mailbox, "UNSEEN");

        if (!$emails) {
            EmailConnection::closeMailbox();
            return "You have no unread emails";
        }

        $unreadEmails = [];
        foreach ($emails as $email_number) {
            $unreadEmails[] = self::getEmailDetails($email_number);
        }

        EmailConnection::closeMailbox();
        return $unreadEmails;
    }
}

// Classe pour gérer l"installation des pièces jointes
class EmailAttachmentHandler {
    // Méthode pour installer les pièces jointes d"un email spécifique
    public static function installAttachments($email_number) {
        EmailConnection::ensureMailboxOpen();
        $mailbox = EmailConnection::connectMailbox();
        $structure = imap_fetchstructure($mailbox, $email_number);

        if (!is_dir("attachments")) {
            mkdir("attachments", 0755, true);
        }

        $attachments = [];
        if (!empty($structure->parts)) {
            foreach ($structure->parts as $partIndex => $part) {
                if (isset($part->disposition) && strtolower($part->disposition) === "attachment") {
                    $filename = $part->dparameters[0]->value ?? "unknown_file";
                    $attachmentData = EmailContent::decodeContent(imap_fetchbody($mailbox, $email_number, $partIndex + 1), $part->encoding);
                    $date = date("Ymd_His");
                    $randomNumber = EmailContent::generateRandomNumber();
                    $attachmentPath = "attachments/" . $date . "_" . $randomNumber . "_" . $filename;

                    // Vérifier si le fichier existe déjà
                    if (!file_exists($attachmentPath)) {
                        file_put_contents($attachmentPath, $attachmentData);
                    }

                    $attachments[] = [
                        "filename" => $filename,
                        "path" => "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]) . "/" . $attachmentPath
                    ];
                }
            }
        }

        EmailConnection::closeMailbox();
        return [
            "message" => "The attachments of the email [$email_number] have been installed successfully.",
            "attachments" => $attachments
        ];
    }
}

// Charger la configuration
EmailConfig::loadConfig("config.php");
?>