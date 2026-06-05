<?php

namespace app\admin\service\game;

/**
 * GG 游戏
 */
class GgGame extends BaseGame
{
    protected $gameType = 'gg';
    protected $verifyUri = '/verify';
    protected $gameUri = '/game';

    public function verify($params)
    {
        // $url = $this->getVerifyUrl();
        $url = "";
        $result = wget($url, json_encode($params));
        return json_decode($result, true);
    }
}
