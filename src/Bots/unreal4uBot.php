<?php

namespace unreal4u\Bots;

use unreal4u\Telegram\Types\Update;
use unreal4u\Telegram\Types\Message;
use unreal4u\Telegram\Types\InlineQueryResultArticle;
use unreal4u\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TgLog;
use Psr\Log\LoggerInterface;

class unreal4uBot implements BotsInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    private $token;

    public function __construct(LoggerInterface $logger, string $token)
    {
        $this->logger = $logger;
        $this->token = $token;
    }

    public function run(array $postData = [])
    {
        try {
            $update = new Update($postData, $this->logger);
            $this->logger->debug('Incoming post data', $_POST);
            $this->performAction($update);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Captured exception: "%s"', $e->getMessage()));
        }

        return $this;
    }

    public function performAction(Update $update)
    {
        if (!empty($update->message->chat->id)) {
            $this->handleIncomingMessage($update->message);
        }

        if (!empty($update->chosen_inline_result)) {
            $this->logger->debug('We have a chosen_inline_result back, result id: '.$update->chosen_inline_result->result_id);
        }


        if (!empty($update->inline_query)) {
            $this->inlineQuery($update);
        }

        return $this;
    }

    public function handleIncomingMessage(Message $message): unreal4uBot
    {
        $this->logger->info(sprintf(
            'Received message. Chat id: %d, user id: %d, user name: %s',
            $message->chat->id,
            $message->from->id,
            $message->from->username
        ));

        return $this;
    }

    public function inlineQuery(Update $update): unreal4uBot
    {
        $this->logger->info(sprintf(
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

        // Number of results
        $i = 1;

        $inlineQueryResultArticle = new InlineQueryResultArticle();
        $inlineQueryResultArticle->url = 'http://lmgtfy.com/?q=' . urlencode($query);
        $inlineQueryResultArticle->title = $inlineQueryResultArticle->url; //'Forward this message to anyone you would like (Title)';
        $inlineQueryResultArticle->message_text = $inlineQueryResultArticle->url; //'Forward this message to anyone you would like (Message)';
        $inlineQueryResultArticle->disable_web_page_preview = true;
        $inlineQueryResultArticle->hide_url = true;
        // @TODO find a way to compress this all into an identifiable 64bit ascii string, maybe with pack()?
        $inlineQueryResultArticle->id = md5(json_encode(['uid' => $update->inline_query->from->id, 'iqid' => $update->inline_query->id, 'rid' => $i]));
        $answerInlineQuery = new AnswerInlineQuery();
        $answerInlineQuery->inline_query_id = $update->inline_query->id;
        $answerInlineQuery->results[] = $inlineQueryResultArticle;

        $tgLog = new TgLog($this->token, $this->logger);
        //$tgLog->logger = $this->logger;
        $tgLog->performApiRequest($answerInlineQuery);
        $this->logger->info(sprintf('Sent API response to Telegram, all done'));

        return $this;
    }
}
