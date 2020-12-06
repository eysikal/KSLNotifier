<?php

use Goutte\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class KSLScraper
{

    private $mail;
    private $searchTerm;

    public function go()
    {
        $client = new Client();
        $previousResultsList = [];
        $counter = 0;
        $this->setUpMail();
        $this->searchTerm = 'purple%20pillow';

        do {
            $crawler = $client->request('GET', "https://classifieds.ksl.com/search/keyword/$this->searchTerm");
            $resultsList = $crawler->filter('.listing-item-link')->extract(['href']);
            $newResults = array_diff($resultsList, $previousResultsList);
            if (count($newResults) > 0 && $counter > 0) {
                $message = '';
                foreach ($newResults as $result) {
                    $message .= 'https://classifieds.ksl.com' . $result . "\n\n";
                }
                $this->sendNotification($message);
            }

            $previousResultsList = $resultsList;
            echo '.';
            $counter++;

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
        $this->mail = new PHPMailer(true);

        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.mail.yahoo.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'eysikal@yahoo.com';
            $this->mail->Password   = 'zhgcshomhxqgwzlc';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            $this->mail->setFrom('eysikal@yahoo.com', 'KSL Notifier');
            $this->mail->addAddress('8016029128@vtext.com');
            $this->mail->Subject = 'New KSL Classifieds Result(s)';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
