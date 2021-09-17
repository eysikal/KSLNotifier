<?php

use Goutte\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class KSLScraper
{
    private $mail;
    private $email;
    private $searchString;

    public function __construct($searchString, $email = null)
    {
        $this->searchString = $searchString;
        $this->email = $email;
        $this->client = new Client();
        $this->setUpMail();
    }

    public function go()
    {
        $previousResultsList = [];
        $firstRun = true;

        do {
            $crawler = $this->client->request('GET', "https://classifieds.ksl.com/search/keyword/$this->searchString");
            $resultsList = $crawler->filter('.item-info-title-link')->extract(['href']);
            $newResults = array_diff($resultsList, $previousResultsList);
            if (count($newResults) > 0 && !$firstRun) {
                echo "\n" . 'Found new results!' . "\n";
                $newResultsString = "\n\n";
                foreach ($newResults as $key => $result) {
                    $newResultsString .= '#' . ($key + 1) . ' https://classifieds.ksl.com' . $result . "\n\n";
                }
                $this->sendNotification($newResultsString);
            }

            $previousResultsList = $resultsList;
            $firstRun = false;
            echo '.';

            sleep(random_int(10, 15) * 60);
        } while (1);
    }

    private function sendNotification($newResults = null)
    {
        try {
            $this->mail->Body = $newResults;
            $this->mail->send();
        } catch (Exception $e) {
            echo 'Mailer Error: ' . $this->mail->ErrorInfo;
        }
    }

    private function setUpMail()
    {
        $emailSettings = require_once './email-settings.php';
        $this->mail = new PHPMailer(true);

        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mail->isSMTP();
            $this->mail->Host       = $emailSettings['host'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $emailSettings['username'];
            $this->mail->Password   = $emailSettings['password'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $emailSettings['port'];
            $this->mail->setFrom($emailSettings['fromAddress'], 'KSL Notifier');
            $this->mail->addAddress(
                is_null($this->email)
                    ? $emailSettings['toAddress']
                    : $this->email
            );
            $this->mail->Subject = 'New KSL Classifieds Result(s) for "' . rawurldecode($this->searchString) . '"';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
