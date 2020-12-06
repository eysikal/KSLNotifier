<?php

use Goutte\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class KSLScraper
{

    private $mail;

    public function go($searchString)
    {
        $client = new Client();
        $this->setUpMail();
        $previousResultsList = [];
        $firstRun = true;

        do {
            $crawler = $client->request('GET', "https://classifieds.ksl.com/search/keyword/$searchString");
            $resultsList = $crawler->filter('.listing-item-link')->extract(['href']);
            $newResults = array_diff($resultsList, $previousResultsList);
            if (count($newResults) > 0 && !$firstRun) {
                echo "\n" . 'Found new results!' . "\n";
                $newResultsString = '';
                foreach ($newResults as $result) {
                    $newResultsString .= 'https://classifieds.ksl.com' . $result . "\n\n";
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
            // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mail->isSMTP();
            $this->mail->Host       = $emailSettings['host'];
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $emailSettings['username'];
            $this->mail->Password   = $emailSettings['password'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = $emailSettings['port'];
            $this->mail->setFrom($emailSettings['fromAddress'], 'KSL Notifier');
            $this->mail->addAddress($emailSettings['toAddress']);
            $this->mail->Subject = 'New KSL Classifieds Result(s)';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
