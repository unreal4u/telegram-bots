<?php 

include('../conf.php');

if (trim($_SERVER['REQUEST_URI'], '/') === BOT_TOKEN) {
    echo 'Request comes from Telegram itself';
} else {
    echo 'Hello world!';
}

