<?php

namespace app\admin\service\game;

/**
 * Wingo 游戏
 */
class WingoGame extends BaseGame
{
    protected $gameType = 'wingo';
    protected $verifyUri = '/auth/verify';
    protected $gameUri = '/game/enter';

    public function verify($params)
    {
        // 调用 Wingo 验证接口
        // $url = $this->getVerifyUrl();
        $url = "";
        $result = wget($url, json_encode($params));
        return json_decode($result, true);
    }
}
