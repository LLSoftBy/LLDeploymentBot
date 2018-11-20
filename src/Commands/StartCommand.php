<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 18/11/18
 */

namespace lldb\Commands;


use lldb\Interfaces\ICommandHandler;
use lldb\TgHandler;
use Telegram;

class StartCommand implements ICommandHandler
{
    private $bot;

    public function __construct(Telegram $bot)
    {
        $this->bot = $bot;
    }

    public function execute()
    {
        $chatId = $this->bot->ChatID();
        $handlers = TgHandler::getCommandHandlers();

        $message = 'Welcome to LLSOFT Deployment helper bot. Here is the list of available commands:';
        foreach ($handlers as $command => $handlerClass) {
            try {
                /** @var ICommandHandler $handler */
                $handler = new $handlerClass($this->bot);
                $description = $handler->getDescription();
            } catch (\Exception $e) {
                $description = '';
            }

            $message .= PHP_EOL . $command;
            if ($description) {
                $message .= ' — ' . $description;
            }
        }

        $content = [
            'chat_id' => $chatId,
            'text' => $message
        ];
        $this->bot->sendMessage($content);

        return true;
    }

    public function getDescription()
    {
        return 'Returns list of available commands';
    }
}