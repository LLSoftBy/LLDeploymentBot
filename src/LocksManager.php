<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 19/10/18
 */

namespace lldb;


class LocksManager
{

    private const LLDO_PATH = Config::LLDO_PATH;

    /**
     * Returns platform locks
     *
     * @param $platform
     * @return array Array of DeploymentLock
     */
    public function getLocksByPlatform($platform): array
    {
        $lldoParams = '-p '.$platform;
        $rawLocksData = $this->lldoCall('deploy:list-locks', $lldoParams);
        //$rawLocksData = '[{"id":"5bc90244000f8337889810","owner":"guki","tgUserId":176696425,"created":1539899971},{"id":"5bc90253c2614604544950","owner":"guki","tgUserId":null,"created":1539899987}]';
        //$rawLocksData = '[{"id":"5bc90244000f8337889810","owner":"guki","tgUserId":null,"created":1539899971},{"id":"5bc90253c2614604544950","owner":"guki","tgUserId":null,"created":1539899987}]';

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

    /**
     * Sets lock by id
     *
     * @param string $platform
     * @param string $tgId Telegram id of lock owner
     * @param string $name Name of lock owner
     * @return bool
     */
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

    /**
     * Removes lock by id
     *
     * @param string $platform
     * @param string $lockId
     * @return bool
     */
    public function removeLock($platform, $lockId)
    {
        $lldoParams = sprintf('-p %s %s',
            $platform,
            $lockId
        );

        $this->lldoCall('deploy:delete-lock', $lldoParams);

        return true;
    }

    /**
     * Returns id of lock by telegram id
     *
     * @param array $locks Array of DeploymentLock
     * @param string $tgId telegram id
     * @return null|string
     */
    public function getOwnLockId($locks, $tgId)
    {
        $ownLockId = null;
        /** @var DeploymentLock $lock */
        foreach ($locks as $lock) {
            if ((int)$lock->tgUserId === (int)$tgId) {
                $ownLockId = $lock->id;
                break;
            }
        }

        return $ownLockId;
    }

    /**
     * Implements lldo call
     *
     * @param string $command
     * @param string $params
     * @return string
     */
    protected function lldoCall($command, $params = '')
    {
        if (!file_exists(self::LLDO_PATH)) {
            $msg = sprintf('Unable to locate lldo at path %s', self::LLDO_PATH);
            throw new \RuntimeException($msg);
        }
        $lldoCall = self::LLDO_PATH . ' ' . $command . ' ' . $params;

        $result = exec($lldoCall);
        //$result = '';

        return $result;
    }

}