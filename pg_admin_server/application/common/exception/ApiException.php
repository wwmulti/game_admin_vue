<?php

namespace app\common\exception;

use think\Exception\HttpResponseException;

class ApiException
{
    public function __construct($message = '')
    {
        throw new HttpResponseException(
           json(['message' => $message, 'success' => false])
        );
    }
}