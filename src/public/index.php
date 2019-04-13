<?php
define("ROOT", __DIR__ . "/../../");
require_once ROOT . 'vendor/autoload.php';

use Utilities\Utilities;
use KanbanBoard\Application;

Utilities::loadEnvVariables();
$board = new Application([Application::LABEL_WAITING_FOR_FEEDBACK]);
echo $board->board();
