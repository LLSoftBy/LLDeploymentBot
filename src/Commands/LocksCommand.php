<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 22/10/18
 */

namespace lldb\Commands;


use lldb\DeploymentLock;
use lldb\Interfaces\ICommandHandler;
use lldb\Interfaces\IInlineQueryHandler;
use lldb\LocksManager;
use Telegram;

class LocksCommand implements ICommandHandler, IInlineQueryHandler
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
        $this->locksManager = new LocksManager();
    }

    public function getDescription()
    {
        return 'View and edit platform locks';
    }

    public function execute()
    {
        $chatId = $this->bot->ChatID();
        $typingAction = ['chat_id' => $chatId, 'action' => 'typing'];

        foreach ($this->availablePlatforms as $platform => $platformAlias) {
            $this->bot->sendChatAction($typingAction);
            $message = $this->getFormattedPlatformMessage($platform);

            $reply = $this->bot->sendMessage($message);
        }

        return true;
    }

    public function executeInline()
    {
        $rawCallbackData = $this->bot->Callback_Data();
        $callbackData = json_decode($rawCallbackData, true);
        $action = $callbackData['a'];
        $platform = $callbackData['p'];
        $chatId = $this->bot->ChatID();
        $messageId = $this->bot->MessageID();

        $waitMessage = $this->getWaitMessageContent();
        $waitMessage['message_id'] = $messageId;
        $waitMessage['chat_id'] = $chatId;
        $result = $this->bot->editMessageText($waitMessage);

        $typingAction = ['chat_id' => $chatId, 'action' => 'typing'];
        $this->bot->sendChatAction($typingAction);

        switch ($action) {
            case '+':
                $tgId = $this->bot->UserID();
                $name = trim($this->bot->FirstName() . ' ' . $this->bot->LastName());
                $this->locksManager->setLock($platform, $tgId, $name);
                break;
            case '-':
                $ownLockId = $callbackData['l'];
                if ($ownLockId) {
                    $this->locksManager->removeLock($platform, $ownLockId);
                }
                break;
            default:
                throw new \RuntimeException('Unknown callback action ' . $action);
        }

        $newMessage = $this->getPlatformMessageContent($platform);
        $newMessage['message_id'] = $messageId;
        $newMessage['chat_id'] = $chatId;
        $result = $this->bot->editMessageText($newMessage);

        return true;
    }

    public function getFormattedPlatformMessage($platform)
    {
        $chatId = $this->bot->ChatID();
        $content = $this->getPlatformMessageContent($platform);
        $content['chat_id'] = $chatId;

        return $content;
    }

    public function getPlatformMessageContent($platform)
    {
        $platformAlias = $this->getPlatformAlias($platform);
        $messageText = sprintf('*%s*', $platformAlias) . PHP_EOL;

        $platformLocks = $this->locksManager->getLocksByPlatform($platform);
        $messageText .= ($platformLocks ? '🔒 Platform is locked by:' : '🎉 Platform is not locked') . PHP_EOL;

        $messageText .= $this->getLocksDescription($platformLocks);

        $isGroupMessage = $this->bot->messageFromGroup();
        $content = ['text' => $messageText, 'parse_mode' => 'Markdown'];
        if (!$isGroupMessage) {
            $tgId = $this->bot->UserID();
            $myLockId = $this->locksManager->getOwnLockId($platformLocks, $tgId);

            $button = $myLockId ?
                $this->getUnlockButton($platform, $myLockId) :
                $this->getLockButton($platform);

            $markup = $this->bot->buildInlineKeyBoard([[$button]]);

            $content['reply_markup'] = $markup;
        }

        return $content;
    }

    public function getWaitMessageContent()
    {
        $btn = $this->getWaitButton();
        $content = [
            'text' => 'Confirming...',
            'reply_markup' => $this->bot->buildInlineKeyBoard([[$btn]])
        ];

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
        return sprintf('%s at %s',
            $lock->owner,
            date('Y-m-d H:i:s', $lock->created)
        );
    }

    private function getUnlockButton(string $platform, string $lockId)
    {
        $value = json_encode(['t' => 'lck', 'a' => '-', 'p' => $platform, 'l' => $lockId]);
        $platformAlias = $this->getPlatformAlias($platform);
        $btn = $this->bot->buildInlineKeyboardButton('Unlock ' . $platformAlias, '', $value);
        return $btn;
    }

    private function getLockButton(string $platform)
    {
        $value = json_encode(['t' => 'lck', 'a' => '+', 'p' => $platform]);
        $platformAlias = $this->getPlatformAlias($platform);
        $btn = $this->bot->buildInlineKeyboardButton('Lock ' . $platformAlias, '', $value);
        return $btn;
    }

    private function getWaitButton()
    {
        $value = json_encode(['t' => 'nop']);
        $btn = $this->bot->buildInlineKeyboardButton('⏳', '', $value);
        return $btn;
    }

}