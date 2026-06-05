<?php

namespace app\common\library;

class BinaryStreamWriter
{
    private $buffer = '';

    // 1. 写入 1 字节的 Boolean (1字节不涉及字节序)
    public function writeBool($value)
    {
        $this->buffer .= pack("C", $value ? 1 : 0);
        return $this;
    }

    // 2. 写入 8 字节的 Int64 (小端序)
    public function writeInt64($value)
    {
        // P 为无符号 64 位小端序 (PHP 5.6.3+)
        $this->buffer .= pack("P", $value);
        return $this;
    }

    // 3. 写入 2 字节长度 + 字符串内容 (Unsigned Short 小端)
    public function writeString($str)
    {
        $len = mb_strlen($str, '8bit');
        // v 为 2 字节无符号小端短整型
        $this->buffer .= pack("v", $len) . $str;
        return $this;
    }

    // 4. 写入 2 字节元素个数 + [int32...] 数组 (全小端)
    public function writeIntArray($arr)
    {
        $count = count($arr);
        // v 为数组长度的小端序
        $this->buffer .= pack("v", $count);
        foreach ($arr as $val) {
            // V 为 4 字节无符号小端整型
            $this->buffer .= pack("V", $val);
        }
        return $this;
    }

    // 5. 写入原始二进制
    public function writeRaw($raw)
    {
        $this->buffer .= $raw;
        return $this;
    }

    // 6. 写入 4 字节的 Unsigned Int (小端序)
    public function writeInt($value)
    {
        // V 为无符号 32 位小端序
        $this->buffer .= pack("V", $value);
        return $this;
    }

    // 获取最终生成的二进制流
    public function getBuffer()
    {
        return $this->buffer;
    }

    // 清空缓冲区
    public function clear()
    {
        $this->buffer = '';
    }
}
