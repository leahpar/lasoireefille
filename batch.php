<?php

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function ($classname) {
    require ("classes/" . $classname . ".php");
});

$_SERVER['SERVER_ADDR'] = $argv[1];
require 'config.php';


$db = $config['db'];
$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=" . $db['charset'], $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($argv[2] == 'attendance') {
    $sql = "UPDATE people SET attendance = 0";
    $pdo->query($sql);
}

elseif ($argv[2] == 'incoming-sms')
{
    $conn = new \Ovh\Api(
        $config['ovh']['applicationKey'],
        $config['ovh']['applicationSecret'],
        $config['ovh']['endpoint'],
        $config['ovh']['consumer_key']);
    $smsService = $config['ovh']['smsService'];

    // Get incoming SMS
    echo "Open incomings...\n";
    $incoming = $conn->get("/sms/".$smsService."/incoming");
    foreach ($incoming as $sms_id) {

        $sms = $conn->get("/sms/".$smsService."/incoming/".$sms_id);
//        (
//            [credits] => 0
//            [sender] => +33626661097
//            [creationDatetime] => 2016-06-21T12:51:05+02:00
//            [id] => 57957204
//            [tag] =>
//            [message] => Yolo
//        )

        // Select user
        $st = $pdo->prepare("
            SELECT id, name, name_canonical, height, phone
            FROM people
            WHERE phone = '".$sms['sender']."'
        ");
        $st->execute();
        $users = $st->fetchAll(PDO::FETCH_CLASS, "People");
        if (count($users) == 0) {
            // No user
            continue;
        };
        $user = $users[0];

        echo "SMS $sms_id ($user->name)\n";

        if (false !== stripos("oui", $sms['message'])) {
            echo "=> OUI\n";
            $sql = "UPDATE people SET attendance = 1 WHERE id = $user->id";
            $pdo->query($sql);

            $done = true;
        }
        elseif (false !== stripos("non", $sms['message'])) {
            echo "=> NON\n";
            $sql = "UPDATE people SET attendance = 0 WHERE id = $user->id";
            $pdo->query($sql);

            $done = true;
        }
        elseif (is_numeric(str_replace(['kg', ' kg'], '', str_replace(',', '.', $sms['message'])))) {
            $mass = str_replace(['kg', ' kg'], '', str_replace(',', '.', $sms['message']));
            echo "=> $mass\n";

            $date = str_replace('-', '', substr($sms['creationDatetime'], 0, 10));

            $sql = "
                INSERT INTO imc (people_id, date, mass) VALUES
                ($user->id, $date, $mass)
                ON DUPLICATE KEY UPDATE mass=$mass;
            ";
            $pdo->query($sql);
            $done = true;
        }
        else {
            echo "=> undefined\n";
            $done = false;
        }

        if ($done) {
            $log = fopen('logs/sms', 'a');
            fwrite($log, json_encode($sms));
            fwrite($log, "\n");
            fclose($log);

            $sms = $conn->delete("/sms/".$smsService."/incoming/".$sms_id);

        }
    }
}

elseif ($argv[2] == 'send-sms-attendance') {
    $conn = new \Ovh\Api(
        $config['ovh']['applicationKey'],
        $config['ovh']['applicationSecret'],
        $config['ovh']['endpoint'],
        $config['ovh']['consumer_key']);
    $smsService = $config['ovh']['smsService'];

    $st = $pdo->prepare("
            SELECT id, name, name_canonical, height, phone
            FROM people
            WHERE phone is not null
        ");
    $st->execute();
    $users = $st->fetchAll(PDO::FETCH_CLASS, "People");

    $senders = [];
    foreach ($users as $user) {
            // +33681760350
            $senders[] = $user->phone;
    }
    $message = "C'est l'appel de la soirée fille ! Est-ce que tu seras là lundi ? (oui / non)";
    sendSms($conn, $smsService, "soireefille", $message, $senders);
}

elseif ($argv[2] == 'send-sms-delestage') {
    $conn = new \Ovh\Api(
        $config['ovh']['applicationKey'],
        $config['ovh']['applicationSecret'],
        $config['ovh']['endpoint'],
        $config['ovh']['consumer_key']);
    $smsService = $config['ovh']['smsService'];

    $st = $pdo->prepare("
            SELECT id, name, name_canonical, height, phone
            FROM people
            WHERE phone is not null
        ");
    $st->execute();
    $users = $st->fetchAll(PDO::FETCH_CLASS, "People");

    $senders = [];
    foreach ($users as $user) {
            // +33681760350
            $senders[] = $user->phone;
    }
    $message = "C'est la balance qui te parle ! Quel est ton poids aujourd'hui ?";
    sendSms($conn, $smsService, "soireefille", $message, $senders);
}
die('ok');


function sendSms($conn, $smsServices, $sender, $message, $receivers)
{
    $content = (object) array(
        "charset"=> "UTF-8",
        "class"=> "phoneDisplay",
        "coding"=> "8bit",
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