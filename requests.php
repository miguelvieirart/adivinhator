<?php

if (session_id() == "") session_start();
include("class_game.php");

if (!isset($_SESSION['game'])) {
    new itemGame();
}

$game = unserialize($_SESSION['game']);

$game = new itemGame($game);



if (isset($_POST['action'])) {


    echo $game->askForInput($_POST);
}


?>


