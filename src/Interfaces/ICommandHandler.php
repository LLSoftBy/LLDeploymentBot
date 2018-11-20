<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 15/11/18
 */

namespace lldb\Interfaces;


interface ICommandHandler
{
    public function execute();
    public function getDescription();
}