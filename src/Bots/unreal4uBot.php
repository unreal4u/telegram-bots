<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Query\Result\Article;
use unreal4u\TelegramAPI\Telegram\Types\InputMessageContent\Text;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;

class unreal4uBot extends Base
{
    public function run(array $postData = []): TelegramMethods
    {
        $method = null;

        try {
            $update = new Update($postData, $this->logger);
            $this->logger->debug('Incoming post data', $_POST);
            $method = $this->performAction($update);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Captured exception: "%s"', $e->getMessage()));
        }

        return $method;
    }

    public function performAction(Update $update): TelegramMethods
    {
        $method = null;

        if (!empty($update->message->chat->id)) {
            $method = $this->handleIncomingMessage($update->message);
        }

        if (!empty($update->chosen_inline_result)) {
            $this->logger->debug('We have a chosen_inline_result back, result id: '.$update->chosen_inline_result->result_id);
        }

        if (!empty($update->inline_query)) {
            $method = $this->inlineQuery($update);
        }

        return $method;
    }

    public function handleIncomingMessage(Message $message): TelegramMethods
    {
        $this->logger->info(sprintf(
            'Received message. Chat id: %d, user id: %d, user name: %s',
            $message->chat->id,
            $message->from->id,
            $message->from->username
        ));

        if (!empty($message->sticker->file_id)) {
            return $this->downloadSticker($message);
        }

        return null;
    }

    public function inlineQuery(Update $update): TelegramMethods
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

        $inlineQueryResultArticle = new Article();
        $inlineQueryResultArticle->url = 'http://lmgtfy.com/?q=' . urlencode($query);
        $inlineQueryResultArticle->title = $inlineQueryResultArticle->url; //'Forward this message to anyone you would like (Title)';
        $inlineQueryResultArticle->hide_url = true;
        $inputMessageContentText = new Text();
        $inputMessageContentText->message_text = $inlineQueryResultArticle->url;
        $inputMessageContentText->disable_web_page_preview = true;
        $inlineQueryResultArticle->input_message_content = $inputMessageContentText;
        // @TODO find a way to compress this all into an identifiable 64bit ascii string, maybe with pack()?
        $inlineQueryResultArticle->id = md5(json_encode(['uid' => $update->inline_query->from->id, 'iqid' => $update->inline_query->id, 'rid' => $i]));
        $answerInlineQuery = new AnswerInlineQuery();
        $answerInlineQuery->inline_query_id = $update->inline_query->id;
        $answerInlineQuery->addResult($inlineQueryResultArticle);

        return $answerInlineQuery;
    }

    /**
     * @FIXME this function name will probably change in the future!
     * This will download a file and offer it to the user
     */
    private function downloadSticker(Message $message): TelegramMethods
    {
        $this->logger->debug(sprintf('Got a sticker request, downloading sticker'));

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $message->chat->id;

        try {
            $getFile = new GetFile();
            $getFile->file_id = $message->sticker->file_id;
            $tgLog = new TgLog($this->token, $this->logger);
            $file = $tgLog->performApiRequest($getFile);
            $tgDocument = $tgLog->downloadFile($file);
            $this->logger->debug('Downloaded sticker, sending it to temporary directory');
            file_put_contents(sprintf('media/%s', basename($file->file_path)), (string)$tgDocument);
            #$image = \imagecreatefromwebp(sprintf('media/%s', basename($file->file_path)));
            #\imagepng($image, 'media/'.basename($file->file_path).'.png');
            #\imagedestroy($image);

            $sendMessage->text = sprintf(
                'Download link for sticker: http://media.unreal4u.com/%s',
                basename($file->file_path)
            );
        } catch (\Exception $e) {
            $this->logger->error('Problem downloading sticker: '.$e->getMessage());
            $sendMessage->text = sprintf('There was a problem downloading your sticker, please retry later');
        }

        return $sendMessage;
    }
}
