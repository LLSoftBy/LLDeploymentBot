<?php

require __DIR__ . '/../../../vendor/autoload.php';

$botToken = '596175715:AAGeXYi1XUMGFGYht5lfCtyd2lCaztOLBrE';
$telegram = new Telegram($botToken);

\lldb\TgHandler::handle($telegram);
echo 'zz';