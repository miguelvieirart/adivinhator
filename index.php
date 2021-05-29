<?php
if (session_id() == "") session_start();
include("class_game.php");

    new itemGame();


?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adivinhator - Guesses what you're thinking of!</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js" integrity="sha384-+YQ4JLhjyBLPDQt//I+STsc9iw4uQqACwlvpslubQzn4u2UU2UFM80nGisd026JF" crossorigin="anonymous"></script>
</head>
<body>

<div class="container">
    <div class="row">
    
        <div class="col-6 offset-3 my-5">
            <h1>Adivinhator</h1>
            <h3>Guesses what you're thinking of!</h3>

                <div id="game_area" class="my-5">
                    <div class="text-center">
                        <button class="btn btn-primary" onclick="game_start()">Start Game</button>
                    </div>
                </div>
                <div>
        </div>
    
    </div>
</div>

<script>

function game_start() {
    call_game({"action":"start"});
}

function send_answer() {
    call_game({"action":"answer","answer":$("#answer").val()});
}
function send_property() {
    call_game({"action":"details","property":$("#property").val()});
}

function call_game(action) {
    
    
    $.ajax({
        type: 'POST',
        url: 'requests.php',
        data: action,
        success: function(data)
        {   console.log(data)
            $('#game_area').html(data);
        }
    });

}
</script>
</body>
</html>