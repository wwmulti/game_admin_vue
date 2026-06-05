<?php

namespace app\common\library;

class BinaryStreamReader
{
    private $data;
    private $offset = 0;

    public function __construct($binaryData)
    {
        $this->data = $binaryData;
    }

    // 1. 取 1 字节的 Boolean (1字节不涉及字节序)
    public function readBool()
    {
        $byte = substr($this->data, $this->offset, 1);
        $this->offset += 1;
        $val = unpack("C", $byte)[1];
        return $val !== 0;
    }

    // 2. 取 8 字节的 Int64 (小端序)
    public function readInt64()
    {
        $bytes = substr($this->data, $this->offset, 8);
        $this->offset += 8;
        // P 是小端 64 位无符号格式符 (PHP 5.6.3+)
        return unpack("P", $bytes)[1];
    }

    // 3. 取 2 字节长度 + 字符串内容 (长度改为小端序)
    public function readString()
    {
        $lenBytes = substr($this->data, $this->offset, 2);
        $this->offset += 2;
        // v 是无符号短整型小端序
        $len = unpack("v", $lenBytes)[1];

        $str = substr($this->data, $this->offset, $len);
        $this->offset += $len;
        return $str;
    }

    // 4. 取 2 字节长度 + [int32...] 数组 (长度和元素均为小端序)
    public function readIntArray()
    {
        $lenBytes = substr($this->data, $this->offset, 2);
        $this->offset += 2;
        // v 是长度的小端序
        $len = unpack("v", $lenBytes)[1];

        $arr = [];
        for ($i = 0; $i < $len; $i++) {
            $valBytes = substr($this->data, $this->offset, 4);
            $this->offset += 4;
            // V 是无符号整型小端序
            $arr[] = unpack("V", $valBytes)[1];
        }
        return $arr;
    }

    // 5.读取 4 字节的 Unsigned Int (小端序)
    public function readInt()
    {
        $bytes = substr($this->data, $this->offset, 4);
        $this->offset += 4;
        // V 为无符号 32 位小端序
        $val = unpack("V", $bytes)[1];
        return $val;
    }
}
