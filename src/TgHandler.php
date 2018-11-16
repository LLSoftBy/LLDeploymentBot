<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 15/11/18
 */

namespace lldb;


use lldb\Commands\LocksCommand;
use lldb\Interfaces\ICommand;

class TgHandler
{
    private static $commandHandlers = [
        '/locks' => LocksCommand::class
    ];

    public static function handle(\Telegram $telegram)
    {
        if (self::isCommand($telegram)) {
            $handler = self::getCommandHandler($telegram);
            if (!$handler) {
                $msg = sprintf('Unable to find command handler for request [%s]', print_r($telegram, true));
                throw new \RuntimeException($msg);
            }

            $instance = new $handler($telegram);
            if (!$instance instanceof ICommand) {
                $msg = sprintf('Command [%s] must be implement ICommand interface', $handler);
                throw new \RuntimeException($msg);
            }
            $instance->execute();
        }

        return true;
    }

    private static function isCommand(\Telegram $telegram)
    {
        return true;
    }

    private static function getCommandHandler(\Telegram $telegram)
    {
        $handler = null;
        $command = '/locks';
        if (array_key_exists($command, self::$commandHandlers)) {
            $handler = self::$commandHandlers[$command];
        }
        return $handler;
    }

    private static function isInlineQuery(\Telegram $telegram)
    {
        return false;
    }
}