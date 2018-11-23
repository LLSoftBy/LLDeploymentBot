<?php

require __DIR__ . '/../../../vendor/autoload.php';

$telegram = new Telegram(lldb\Config::BOT_TOKEN);

\lldb\TgHandler::handle($telegram);