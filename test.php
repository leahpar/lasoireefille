<?php

require __DIR__ . '/vendor/autoload.php';
use \Ovh\Api;

$applicationKey = "trjCypIhYIDjZkWA";
$applicationSecret = "jk9vbAj6egaHmGI4tWSGJQrvOxVum0qP";
$consumer_key = "Kg2SerIcc0RiOxJILXZyB4N8ciocbkmL";
$smsService = "sms-br5890-1";
$endpoint = 'ovh-eu';

$conn = new Api(
    $applicationKey,
    $applicationSecret,
    $endpoint,
    $consumer_key);

// Get SMS Service
//$smsService = getSmsService($conn);

// Allow new sender
//print_r($conn->post("/sms/".$smsService."/senders", ["sender" => "soireefille", "reason" => "some reason"]));

// Get allowed senders
//$senders = $conn->get("/sms/".$smsService."/senders");
//print_r($senders);
//print_r($conn->put("/sms/".$smsService."/senders/soireefille", ["status" => "enable"]));
print_r($conn->get("/sms/".$smsService."/senders/soireefille"));

// Send SMS
//sendSms($conn, $smsService, "soireefille", "Yolo les filles !", ["+33626661097"]);

// Get outgoing sms
//getOutgoingSms($conn, $smsService);

// Get incoming sms
getIncomingSms($conn, $smsService);


function getSmsService($conn)
{
    $smsServices = $conn->get('/sms/');
//    print_r($smsServices);
    return $smsServices[0];
}

function sendSms($conn, $smsServices, $sender, $message, $receivers)
{
    $content = (object) array(
        "charset"=> "UTF-8",
        "class"=> "phoneDisplay",
        "coding"=> "7bit",
        "message"=> $message,
        "sender" => $sender,
        "noStopClause"=> true,
        "priority"=> "high",
        "receivers"=> $receivers,
        "senderForResponse"=> true,
        "validityPeriod"=> 2880
    );
    $resultPostJob = $conn->post('/sms/'. $smsServices . '/jobs/', $content);
    print_r($content);
    print_r($resultPostJob);
}

function getOutgoingSms($conn, $smsService)
{
    $outgoing = $conn->get("/sms/".$smsService."/outgoing");
    foreach ($outgoing as $sms) {
        print_r($conn->get("/sms/".$smsService."/outgoing/".$sms));
    }
}


function getIncomingSms($conn, $smsService)
{
    $incoming = $conn->get("/sms/".$smsService."/incoming");
    foreach ($incoming as $sms) {
        print_r($sms."\n");
        print_r($conn->get("/sms/".$smsService."/incoming/".$sms));
    }
}

