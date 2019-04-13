<?php

use Utilities\Utilities;
use KanbanBoard\Application;

if (file_exists("config.php"))
{
    require_once("config.php"); // Load parameters for run
}
require_once ROOT . 'vendor/autoload.php';

Utilities::loadEnvVariables();

$board = new Application(array('waiting-for-feedback'));
echo $board->board();
