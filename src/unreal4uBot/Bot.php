<?php

namespace unreal4u\unreal4uBot;

use unreal4u\Telegram\Types\Update;
use unreal4u\Telegram\Types\InlineQueryResultArticle;
use unreal4u\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TgLog;

class Bot
{
    private $logger = null;

    private $token = '';

    public function __construct($logger, string $token) 
    {
        $this->logger = $logger;
        $this->token = $token;
    }

    public function run(array $postData=[])
    {
        try {
            $update = new Update($_POST);
            $this->logger->addDebug('Incoming post data', $_POST);
            if (!empty($update->message->chat->id)) {
                $this->logger->addInfo(sprintf(
                    'Received message. Chat id: %d, user id: %d, user name: %s', 
                    $update->message->chat->id, 
                    $update->message->from->id, 
                    $update->message->from->username
                ));
            }
            if (!empty($update->chosen_inline_result)) {
                $this->logger->addDebug(print_r($update->chosen_inline_result, true));
            }
            if (!empty($update->inline_query)) {
                $this->logger->addInfo(sprintf(
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
                $tgLog = new TgLog($this->token);
                $tgLog->logger = $this->logger;
                $result = $tgLog->performApiRequest($answerInlineQuery);
                $this->logger->addInfo(sprintf('Sent API response to Telegram, all done'));
            }
        } catch (\Exception $e) {
            $this->logger->addError(sprintf('Captured exception: "%s"',$e->getMessage()));
        }
    }
}
