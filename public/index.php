<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

spl_autoload_register(function ($classname) {
    require ("../classes/" . $classname . ".php");
});

// CONFIG --------------------------------------------------------------------------------------------------------------

$config['displayErrorDetails'] = true;
require '../config.php';

// APP -----------------------------------------------------------------------------------------------------------------

$app = new \Slim\App(["settings" => $config]);
$user = $_SERVER['REMOTE_USER'];
$container = $app->getContainer();

// DB CONNECTION -------------------------------------------------------------------------------------------------------

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=" . $db['charset'], $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// TWIG ----------------------------------------------------------------------------------------------------------------

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../templates', [
        'cache' => false // set to '../cache' to active cache
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));
    return $view;
};

// ROUTES -------------------------------------------------------------------------------------------------- Hello world

$app->get('/hello/{name}', function (Request $request, Response $response, $args) {

    // alias for : $args['name']
    $name = $request->getAttribute('name');

    $input = $request->getQueryParams(); // $_GET
    $toto = $input['toto'];

//    $response->getBody()->write("Hello, $name");
//    return $response;

    return $this->view->render($response, 'demo.html.twig', [
        'name' => $name,
        'toto' => $toto
    ]);
})->setName('hello_world');

// ROUTES -------------------------------------------------------------------------------------------------------- Index

$app->get('/', function (Request $request, Response $response, $args) use ($user) {
    return $response->withHeader('Location', $this->router->pathFor('attendance'));
})->setName('home');

// ROUTES --------------------------------------------------------------------------------------------------- Attendance

$app->get('/attendance', function (Request $request, Response $response, $args) use ($user) {

    $st = $this->db->prepare("SELECT id, name, name_canonical, attendance FROM people");
    $st->execute();
    $peoples = $st->fetchAll(PDO::FETCH_CLASS, "People");

    setlocale(LC_TIME, 'fr_FR');
//    $lundi = date('Y-m-d', strtotime('monday'));
    $lundi = $month_name = strftime('%e %B', strtotime('monday'));
    return $this->view->render($response, 'attendance.html.twig', [
        'peoples' => $peoples,
        'user' => $user,
        'lundi' => $lundi
    ]);
})->setName('attendance');

$app->get('/attendance_update', function (Request $request, Response $response, $args) use ($user) {

    $input = $request->getQueryParams(); // $_GET
    $st = $this->db->prepare("UPDATE people SET attendance = ".$input['attendance']." WHERE id = ".$input['id']);
    $st->execute();
    return $response;
})->setName('attendance_udpate');

// ROUTES ------------------------------------------------------------------------------------------------------- Mousse

$app->get('/voter-pour-ma-mousse', function (Request $request, Response $response) use ($user) {

    $st = $this->db->prepare("SELECT id, name, name_canonical FROM people where name_canonical = '$user'");
    $st->execute();
    $people = $st->fetchAll(PDO::FETCH_CLASS, "People")[0];

    $st = $this->db->prepare("
        SELECT COALESCE(COUNT(1), 0) + rand() * 5 as occurrence, id, name, description
        FROM mousse m
        left join rating r on (r.mousseA_id = m.id or r.mousseB_id = m.id)
        and r.people_id = ".$people->id."
        group by m.id
        order by 1 asc");
    $st->execute();
    $mousses = $st->fetchAll(PDO::FETCH_CLASS, "Mousse");
    // shuffle($mousses);

    return $this->view->render($response, 'mousse-voter.html.twig', [
        'mousseA' => $mousses[rand(0,10)], // less rated mousses
        'mousseB' => $mousses[rand(11, count($mousses)-1)], // other mousses
        'people' => $people,
        'user' => $user
    ]);
})->setName('mousse_voter');

$app->get('/post-vote', function (Request $request, Response $response, $args) {

    $input = $request->getQueryParams();
    $rating = explode('-', $input['vote']);

    $people_id  = $rating[0];
    $mousseA_id = $rating[1];
    $mousseB_id = $rating[2];
    $mousse_id  = $rating[3];

    $st = $this->db->prepare("
        INSERT INTO rating (people_id, mousseA_id, mousseB_id, mousse_id)
        VALUES ($people_id, $mousseA_id, $mousseB_id, $mousse_id)
        ON DUPLICATE KEY UPDATE mousse_id=$mousse_id
        ");
    $st->execute();

    return $response->withHeader('Location', $this->router->pathFor('mousse_voter'));
})->setName('mousse_post_voter');

$app->get('/le-classement-des-mousses', function (Request $request, Response $response, $args) use ($user) {

    $sql = "
        SELECT
            m.id,
            m.name,
            m.description,
        COALESCE(sum(if(r.mousseA_id = m.id or r.mousseB_id = m.id, 1, 0)), 0) as occurrence,
        COALESCE(sum(if(r.mousse_id = m.id, 1, 0)), 0) as vote
        FROM mousse m, rating r
        group by m.id";
    $res_mousses = $this->db->query($sql, PDO::FETCH_CLASS, 'Mousse');
    $mousses = [];
    foreach ($res_mousses as $mousse) {
        $mousses[] = $mousse;
    }
    usort($mousses, function($a, $b) {
        return $a->getRating() < $b->getRating();
    });

    $sql = "
        SELECT
            m.id,
            m.name,
            m.description,
        COALESCE(sum(if(r.mousseA_id = m.id or r.mousseB_id = m.id, 1, 0)), 0) as occurrence,
        COALESCE(sum(if(r.mousse_id = m.id, 1, 0)), 0) as vote
        FROM mousse m, rating r, people p
        WHERE p.id = r.people_id
        AND p.name = '$user'
        group by m.id";
    $res_mousses = $this->db->query($sql, PDO::FETCH_CLASS, 'Mousse');
    $mousses_user = [];
    foreach ($res_mousses as $mousse) {
        $mousses_user[] = $mousse;
    }
    usort($mousses_user, function($a, $b) {
        return $a->getRating() < $b->getRating();
    });

    return $this->view->render($response, 'mousse-classement.html.twig', [
        'mousses' => $mousses,
        'mousses_user' => $mousses_user,
        'user' => $user
    ]);
})->setName('mousse_classement');

// RUN -----------------------------------------------------------------------------------------------------------------

$app->run();

