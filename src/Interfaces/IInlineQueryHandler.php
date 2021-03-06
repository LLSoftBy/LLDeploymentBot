<?php
/**
 * Created by PhpStorm.
 * Author: e.guchek
 * Date: 17/11/18
 */

namespace lldb\Interfaces;


interface IInlineQueryHandler
{
    /**
     * Inline query handler
     *
     * @return bool
     */
    public function executeInline();
}