<?php
require __DIR__ . '/../../../vendor/autoload.php';

$post = file_get_contents('php://input');

file_put_contents('requests','========'.PHP_EOL.$post.PHP_EOL.'----'.PHP_EOL.print_r($_REQUEST,true), FILE_APPEND);

$deploymentData = json_decode($post);

if (!$deploymentData) {
    file_put_contents('requests','+++ Unable to parse $deployment data', FILE_APPEND);
    die();
}

$subject = $deploymentData->Subject;
if (false !== strpos($subject, '#deployment')) {
    $subject .= '<pre>' . PHP_EOL . $deploymentData->Message . PHP_EOL . '</pre>';
    $subject = str_replace(' ', ' ', $subject);
}

$telegram = new Telegram(lldb\Config::BOT_TOKEN);

$locksCommand = new \lldb\Commands\LocksCommand($telegram);
$message = ['text' => $subject];
$locksCommand->notifyGroup($message);