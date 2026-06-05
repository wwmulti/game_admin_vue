<?php

namespace app\admin\service;

/**
 * 游戏接口 - 所有游戏类型必须实现
 */
interface IGame
{
    public function verify($params);
    public function submitGameData($data);
    public function submitCheckGame($data);
    // public function getVerifyUrl();
    // public function getGameUrl();
}
