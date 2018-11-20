<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 15/11/18
 */

namespace lldb;


use lldb\Commands\LocksCommand;
use lldb\Commands\StartCommand;
use lldb\Interfaces\ICommandHandler;
use lldb\Interfaces\IInlineQueryHandler;

class TgHandler
{
    private static $commandHandlers = [
        '/start' => StartCommand::class,
        '/locks' => LocksCommand::class,
    ];

    private static $inlineQueryHandlers = [
        'locks' => LocksCommand::class
    ];

    public static function handle(\Telegram $telegram)
    {
        if (self::isInlineQuery($telegram)) {
            self::confirmInlineQuery($telegram);

            $handler = self::getInlineQueryHandler($telegram);
            if (!$handler) {
                $msg = sprintf('Unable to find inline query handler for request [%s]', print_r($telegram, true));
                throw new \RuntimeException($msg);
            }

            $instance = new $handler($telegram);
            if (!$instance instanceof IInlineQueryHandler) {
                $msg = sprintf('Handler [%s] must implement IInlineQuery interface', $handler);
                throw new \RuntimeException($msg);
            }
            $instance->executeInline();
        }

        if (self::isCommand($telegram)) {
            $handler = self::getCommandHandler($telegram);
            if (!$handler) {
                $msg = sprintf('Unable to find command handler for request [%s]', print_r($telegram, true));
                throw new \RuntimeException($msg);
            }

            $instance = new $handler($telegram);
            if (!$instance instanceof ICommandHandler) {
                $msg = sprintf('Command [%s] must be implement ICommandHandler interface', $handler);
                throw new \RuntimeException($msg);
            }
            $instance->execute();
        }

        return true;
    }

    public static function getCommandHandlers()
    {
        return self::$commandHandlers;
    }

    private static function isCommand(\Telegram $telegram): bool
    {
        $text = $telegram->Text();

        return \is_string($text) && '' !== $text && $text[0] === '/';
    }

    private static function getCommandHandler(\Telegram $telegram)
    {
        $handler = null;
        $text = $telegram->Text();
        $words = explode(' ', $text);
        $command = $words[0];
        if (array_key_exists($command, self::$commandHandlers)) {
            $handler = self::$commandHandlers[$command];
        }
        return $handler;
    }

    private static function getInlineQueryHandler(\Telegram $telegram)
    {
        $handler = null;
        $inlineQueryType = 'locks';


        if (array_key_exists($inlineQueryType, self::$inlineQueryHandlers)) {
            $handler = self::$inlineQueryHandlers[$inlineQueryType];
        }
        return $handler;
    }

    private static function isInlineQuery(\Telegram $telegram)
    {
        $updateType = $telegram->getUpdateType();
        return $telegram::CALLBACK_QUERY === $updateType;
    }

    private static function confirmInlineQuery(\Telegram $telegram)
    {
        $queryId = $telegram->Callback_ID();
        $reply = ['callback_query_id' => $queryId];
        $res = $telegram->answerCallbackQuery($reply);

        return true;
    }
}