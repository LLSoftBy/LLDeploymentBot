<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 19/10/18
 */

namespace lldb;


class LocksManager
{

    private const LLDO_PATH = '/home/guki/lldo/lldo.php';

    public function getLocksByPlatform($platform): array
    {
        $lldoParams = '-p '.$platform;
        //$rawLocksData = $this->lldoCall('deploy:list-locks', $lldoParams);
        $rawLocksData = '[{"id":"5bc90244000f8337889810","owner":"guki","tgUserId":null,"created":1539899971},{"id":"5bc90253c2614604544950","owner":"guki","tgUserId":null,"created":1539899987}]';

        $locks = json_decode($rawLocksData, true);

        if (!\is_array($locks)) {
            $message = sprintf('Unable to list locks for platform %s. Unexpected result: %s',
                $platform,
                $rawLocksData
            );
            throw new \RuntimeException($message);
        }

        $results = [];
        foreach ($locks as $lockData) {
            $results[] = DeploymentLock::createFromArray($lockData);
        }

        return $results;
    }

    public function setLock($platform, $tgId, $name): bool
    {
        $platformLocks = $this->getLocksByPlatform($platform);
        if (!$this->getOwnLockId($platformLocks, $tgId)) {
            $lldoParams = sprintf('-p %s --owner %s --tg-id %d',
                $platform,
                escapeshellarg($name),
                (int)$tgId
            );

            $this->lldoCall('deploy:set-lock', $lldoParams);
        }

        return true;
    }

    public function removeLock($platform, $lockId)
    {
        $lldoParams = sprintf('-p %s %s',
            $platform,
            $lockId
        );

        $this->lldoCall('deploy:set-lock', $lldoParams);

        return true;
    }

    public function getOwnLockId($locks, $tgId)
    {
        $ownLockId = null;
        /** @var DeploymentLock $lock */
        foreach ($locks as $lock) {
            if ($lock->tgUserId === $tgId) {
                $ownLockId = $lock->id;
                break;
            }
        }

        return $ownLockId;
    }
    protected function lldoCall($command, $params = '')
    {
        if (!file_exists(self::LLDO_PATH)) {
            $msg = sprintf('Unable to locate lldo at path %s', self::LLDO_PATH);
            throw new \RuntimeException($msg);
        }
        $lldoCall = self::LLDO_PATH . ' ' . $command . ' ' . $params;

        $result = exec($lldoCall);

        return $result;
    }

}