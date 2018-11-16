<?php

namespace lldb;


class DeploymentLock
{
    public $id;
    public $owner;
    public $tgUserId;
    public $created;

    public function __construct()
    {
        $this->id = str_replace('.', '', uniqid('', true));
        $this->created = time();
    }

    /**
     * Creates DeploymentLock instance by associated array
     *
     * @param array $data
     * @return DeploymentLock
     */
    public static function createFromArray(array $data)
    {
        $lock = new self();
        foreach ($data as $attr => $value) {
            if (property_exists($lock, $attr)) {
                $lock->{$attr} = $value;
            }
        }

        return $lock;
    }
}