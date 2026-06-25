<?php

namespace app\admin\service;

use app\admin\service\game\CpGame;
use app\admin\service\game\WingoGame;
use app\admin\service\game\PgGame;
use app\admin\service\game\GgGame;
use app\admin\service\game\KessGame;
use app\admin\service\game\WgGame;
use app\admin\service\game\PpGame;
use app\admin\service\game\UuGame;
use app\admin\service\game\G759Game;

/**
 * 游戏工厂
 */
class GameFactory
{

    const GAME_TYPE_WINGO = 1;
    const GAME_TYPE_PG = 2;
    const GAME_TYPE_GG = 3;
    const GAME_TYPE_JDB = 4;
    const GAME_TYPE_KESS = 5;
    const GAME_TYPE_ZY = 6;
    const GAME_TYPE_WG = 7;
    const GAME_TYPE_AMBS = 8;
    const GAME_TYPE_CP = 9;
    const GAME_TYPE_UU = 10;
    const GAME_TYPE_PP = 11;
    const GAME_TYPE_G759 = 12;

    const GAME_TYPE_WINGO_STR = 'wingo';
    const GAME_TYPE_PG_STR = 'pg';
    const GAME_TYPE_GG_STR = 'gg';
    const GAME_TYPE_JDB_STR = 'jdb';
    const GAME_TYPE_KESS_STR = 'kess';
    const GAME_TYPE_ZY_STR = 'zy';
    const GAME_TYPE_WG_STR = 'wg';
    const GAME_TYPE_AMBS_STR = 'ambs';
    const GAME_TYPE_CP_STR = 'cp';
    const GAME_TYPE_UU_STR = 'uu';
    const GAME_TYPE_PP_STR = 'pp';
    const GAME_TYPE_G759_STR = 'g759';

    private static $games = [
        // self::GAME_TYPE_WINGO => WingoGame::class,
        self::GAME_TYPE_PG => PgGame::class,
        // self::GAME_TYPE_GG => GgGame::class,
        // self::GAME_TYPE_JDB => JdbGame::class,
        self::GAME_TYPE_KESS => KessGame::class,
        self::GAME_TYPE_WG => WgGame::class,
        self::GAME_TYPE_CP => CpGame::class,
        self::GAME_TYPE_UU => UuGame::class,
        self::GAME_TYPE_PP => PpGame::class,
        self::GAME_TYPE_G759 => G759Game::class,
    ];

    /**
     * 根据类型 ID 获取游戏实例
     */
    public static function create($typeId)
    {
        if (!isset(self::$games[$typeId])) {
            throw new \Exception("不支持的游戏类型: $typeId");
        }
        return new self::$games[$typeId]();
    }

    /**
     * 根据类型名称获取游戏实例
     */
    public static function createByName($typeName)
    {
        $types = config('constant.game_types') ?? [];
        $typeId = array_search($typeName, $types);
        if ($typeId === false) {
            throw new \Exception("游戏类型不存在: $typeName");
        }
        return self::create($typeId);
    }
}
