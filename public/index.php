<?php 

include('../vendor/autoload.php');
include('../conf.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use unreal4u\Telegram\Types\Update;
use unreal4u\Telegram\Types\InlineQueryResultArticle;
use unreal4u\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TgLog;

$parsedRequestUri = trim($_SERVER['REQUEST_URI'], '/');
if (array_key_exists($parsedRequestUri, BOT_TOKENS)) {
    $currentBot = BOT_TOKENS[$parsedRequestUri];

    $logger = new Logger($currentBot);
    $logger->pushHandler(new StreamHandler('../telegramApiLogs/main.log'));

    $logger->addDebug('--------------------------------');
    $logger->addInfo(sprintf('New request on bot %s', $currentBot));
    $rest_json = file_get_contents("php://input");
    $_POST = json_decode($rest_json, true);

    try {
        $update = new Update($_POST);
        $logger->addDebug('Incoming post data', $_POST);
        if (!empty($update->message->chat->id)) {
            $logger->addInfo(sprintf(
                'Received message. Chat id: %d, user id: %d, user name: %s', 
                $update->message->chat->id, 
                $update->message->from->id, 
                $update->message->from->username
            ));
        }

        if (!empty($update->chosen_inline_result)) {
            $logger->addDebug(print_r($update->chosen_inline_result, true));
        }

        if (!empty($update->inline_query)) {
            $logger->addInfo(sprintf(
                'Received inline query. User id %d (username: %s). Query: "%s", inline query id: %s',
                $update->inline_query->from->id,
                $update->inline_query->from->username,
                $update->inline_query->query,
                $update->inline_query->id
            ));

            $query = $update->inline_query->query;
            if (empty($query)) {
                $query = 'What is lmgtfy?';
            }

            $inlineQueryResultArticle = new InlineQueryResultArticle();
            $inlineQueryResultArticle->url = 'http://lmgtfy.com/?q='.urlencode($query);
            $inlineQueryResultArticle->title = $inlineQueryResultArticle->url; //'Forward this message to anyone you would like (Title)';
            $inlineQueryResultArticle->message_text = $inlineQueryResultArticle->url; //'Forward this message to anyone you would like (Message)';
            $inlineQueryResultArticle->disable_web_page_preview = true;
            $inlineQueryResultArticle->hide_url = true;
            $inlineQueryResultArticle->id = md5(uniqid());

            $answerInlineQuery = new AnswerInlineQuery();
            $answerInlineQuery->inline_query_id = $update->inline_query->id;
            $answerInlineQuery->results[] = $inlineQueryResultArticle;

            $tgLog = new TgLog($parsedRequestUri);
            $tgLog->logger = $logger;
            $result = $tgLog->performApiRequest($answerInlineQuery);
            $logger->addInfo(sprintf('Sent API response to Telegram, all done'));
        }
    } catch (\Exception $e) {
        $logger->addError(sprintf('Captured exception: "%s"',$e->getMessage()));
    }
} else {
    header('Location: https://github.com/unreal4u?tab=repositories', true, 302);
}

