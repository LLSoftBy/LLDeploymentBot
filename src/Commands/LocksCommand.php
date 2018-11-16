<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 22/10/18
 */

namespace lldb\Commands;


use lldb\DeploymentLock;
use lldb\Interfaces\ICommand;
use lldb\LocksManager;
use Telegram;

class LocksCommand implements ICommand
{

    protected $availablePlatforms = [
        '/QA/DEV' => 'DEV',
        '/QA/QA' => 'QA',
        '/QA/STG' => 'STG'
    ];

    protected $bot;
    protected $locksManager;

    public function __construct(Telegram $bot)
    {
        $this->bot = $bot;
        // todo: use DI
        $this->locksManager = new LocksManager();
    }

    public function execute()
    {
        $chatId = $this->bot->ChatID();
        $typingAction = ['chat_id' => $chatId, 'action' => 'typing'];

        foreach ($this->availablePlatforms as $platform => $platformAlias) {
            $this->bot->sendChatAction($typingAction);
            $message = $this->getFormatedPlatformMessage($platform);

            $reply = $this->bot->sendMessage($message);
        }

        return true;
    }

    public function getFormatedPlatformMessage($platform)
    {
        $platformAlias = $this->getPlatformAlias($platform);
        $messageText = sprintf('*%s*', $platformAlias) . PHP_EOL;

        $platformLocks = $this->locksManager->getLocksByPlatform($platform);
        $messageText .= ($platformLocks ? '🔒 Platform is locked by:' : '🎉 Platform is not locked') . PHP_EOL;

        $messageText .= $this->getLocksDescription($platformLocks);

        $tgId = $this->bot->UserID();
        $chatId = $this->bot->ChatID();
        $content = ['chat_id' => $chatId, 'text' => $messageText, 'parse_mode' => 'Markdown'];
        if ($tgId) {
            $myLockId = $this->locksManager->getOwnLockId($platformLocks, $tgId);

            $button = $myLockId ?
                $this->getUnlockButton($platform, $tgId, $myLockId) :
                $this->getLockButton($platform, $tgId);

            $markup = $this->bot->buildInlineKeyBoard([[$button]]);

            $content['reply_markup'] = $markup;
        }

        return $content;
    }

    public function getPlatformAlias($platform)
    {
        if (!array_key_exists($platform, $this->availablePlatforms)) {
            throw new \RuntimeException(sprintf('Platform %s is unknown', $platform));
        }

        return $this->availablePlatforms[$platform];
    }

    public function getLocksDescription(array $locks)
    {
        $description = '';
        foreach ($locks as $lock) {
            $description .= $this->getLockDescription($lock) . PHP_EOL;
        }

        return $description;
    }

    public function getLockDescription(DeploymentLock $lock)
    {
        return sprintf('%s, %s',
            $lock->owner,
            $lock->created
        );
    }

    private function getUnlockButton(string $platform, string $tgId, string $lockId)
    {
        $value = json_encode(['type' => 'lock', 'platform' => $platform, 'tgId' => $tgId, 'lockId' => $lockId]);
        $platformAlias = $this->getPlatformAlias($platform);
        $btn = $this->bot->buildInlineKeyboardButton('Unlock ' . $platformAlias, '', $value);
        return $btn;
    }

    private function getLockButton(string $platform, string $tgId)
    {
        $value = json_encode(['type' => 'lock', 'platform' => $platform, 'tgId' => $tgId]);
        $platformAlias = $this->getPlatformAlias($platform);
        $btn = $this->bot->buildInlineKeyboardButton('Lock ' . $platformAlias, '', $value);
        return $btn;
    }

}