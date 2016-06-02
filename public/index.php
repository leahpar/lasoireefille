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

// ROUTES ------------------------------------------------------------------------------------------------------- Mousse


$app->get('/', function (Request $request, Response $response, $args) {
    return $response->withHeader('Location', $this->router->pathFor('mousse_classement'));
})->setName('home');


$app->get('/voter-pour-ma-mousse', function (Request $request, Response $response, $args) {

    $st = $this->db->prepare("SELECT id, name, description FROM mousse");
    $st->execute();
    $mousses = $st->fetchAll(PDO::FETCH_CLASS, "Mousse");
    shuffle($mousses);

    $user = $_SERVER['REMOTE_USER'];
    $st = $this->db->prepare("SELECT id, name FROM people where name = '$user'");
    $st->execute();
    $people = $st->fetchAll(PDO::FETCH_CLASS, "People")[0];
    var_dump($people);

    return $this->view->render($response, 'mousse-voter.html.twig', [
        'mousses' => array_slice($mousses, 0, 2),
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


$app->get('/le-classement-des-mousses', function (Request $request, Response $response, $args) {

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

    $user = $_SERVER['REMOTE_USER'];
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

