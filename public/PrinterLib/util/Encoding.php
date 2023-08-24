<?php

namespace Xpyun\util;

class Encoding
{
    public static function CalcGbkLenForPrint($data)
{
    // 移除 <le> 和 <lc>
    $data = str_replace(array('<le>', '<lc>'), '', $data);

    $chineseChars = preg_match_all("/[\x{4e00}-\x{9fa5}]/u", $data);
    $otherChars = mb_strlen($data, "UTF8") - $chineseChars;

    return $chineseChars * 2 + $otherChars;
}

    public static function CalcAsciiLenForPrint($data)
    {
        return strlen($data);
    }
}

?>