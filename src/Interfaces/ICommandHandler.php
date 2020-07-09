<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 15/11/18
 */

namespace lldb\Interfaces;


interface ICommandHandler
{
    /**
     * Command handler
     *
     * @return bool
     */
    public function execute();

    /**
     * Returns description of command
     *
     * @return string
     */
    public function getDescription();
}