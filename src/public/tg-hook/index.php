<?php

require __DIR__ . '/../../../vendor/autoload.php';

$telegram = new Telegram(lldb\Config::BOT_TOKEN);
if (\lldb\Config::TIMEZONE) {
    date_default_timezone_set(\lldb\Config::TIMEZONE);
}

\lldb\TgHandler::handle($telegram);