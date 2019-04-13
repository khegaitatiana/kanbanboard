<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Utilities\Utilities;
use KanbanBoard\Authentication;
use KanbanBoard\Github;
use KanbanBoard\Application;

#TODO move to .env
include 'credentials.php';
putenv("GH_CLIENT_ID=" . $GH_CLIENT_ID);
putenv("GH_CLIENT_SECRET=" . $GH_CLIENT_SECRET);
putenv("GH_ACCOUNT=" . $GH_ACCOUNT);
putenv("GH_REPOSITORIES=" . $GH_REPOSITORIES);

$repositories = explode('|', Utilities::env('GH_REPOSITORIES'));
$authentication = new Authentication();
$token = $authentication->login();
$github = new Github($token, Utilities::env('GH_ACCOUNT'));
$board = new Application($github, $repositories, array('waiting-for-feedback'));
$data = $board->board();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader('../views'),
));
echo $m->render('index', array('milestones' => $data));
