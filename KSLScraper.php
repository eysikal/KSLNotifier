<?php

use Goutte\Client;
use SendGrid\Mail\Mail;

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
            $resultsList = $crawler->filter('.item-info-title-link a')->extract(['href']);
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

            sleep(random_int(5, 10) * 60);
        } while (1);
    }

    private function sendNotification($newResults = null)
    {
	try {
	    $this->mail->addContent('text/plain', $newResults);
	    $this->mail->addContent('text/html', $newResults);
	    $this->sendgrid->send($this->mail);
        } catch (Exception $e) {
            echo 'Error sending email: ' . $e->getMessage() . "\n";
	    }
    }

    private function setUpMail()
    {
        $emailSettings = require_once './email-settings.php';    
        $this->mail = new Mail(); 
        $this->mail->setFrom('no-reply@russell.net', 'KSL Notifier');
        $this->mail->setSubject('New KSL Classifieds Result(s) for "' . rawurldecode($this->searchString) . '"');
        $this->mail->addTo(
            is_null($this->email)
                ? $emailSettings['toAddress']
                : $this->email,
            'KSL Notifier User'
        );
        $this->sendgrid = new SendGrid($emailSettings['sendgridApiKey']);
    }
}
