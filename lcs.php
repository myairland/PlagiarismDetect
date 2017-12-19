<?php
require_once('char.php');

class LCSStatic
{

    public static function cutSentence($article)
    {
        $start = 0;
        $i = 0;//记录每个字符的位置

        $sentence = array();
        $splitList = CharUtils::mb_str_split(',.!?:;~，。！？：；～');
        $charList = CharUtils::mb_str_split($article);
        foreach ($charList as $char) {
            if (in_array($char, $splitList)) {
                array_push($sentence, array_slice($charList, $start, $i + 1 - $start));
                $start = $i + 1; //start标记到下一句的开头
                $i += 1;
            } else {
                $i += 1; // 若不是标点符号，则字符位置继续前移
            }
        }
        
        $len = count($charList);
        if ($start < $len) {
            array_push($sentence, array_slice($charList, $start, $len - $start + 1));//这是为了处理文本末尾没有标点符号的情况
        }
        
        return $sentence;
    }
    //lcs in php code
    // https://gist.github.com/ezzatron/1193894
    public static function lcs(array $left, array $right)
    {
        $m = count($left);
        $n = count($right);
    // $a[$i][$j] = length of LCS of $left[$i..$m] and $right[$j..$n]
        $a = array();
    // compute length of LCS and all subproblems via dynamic programming
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                if ($left[$i] == $right[$j]) {
                    $a[$i][$j] = (isset($a[$i + 1][$j + 1]) ? $a[$i + 1][$j + 1] : 0) + 1;
                } else {
                      $a[$i][$j] = max(
                        (isset($a[$i + 1][$j]) ? $a[$i + 1][$j] : 0), (isset($a[$i][$j + 1]) ? $a[$i][$j + 1] : 0)
                      );
                }
            }
        }
    // recover LCS itself
        $i = 0;
        $j = 0;
        $lcs = array();
        while ($i < $m && $j < $n) {
            if ($left[$i] == $right[$j]) {
                $lcs[] = $left[$i];
                $i++;
                $j++;
            } elseif ((isset($a[$i + 1][$j]) ? $a[$i + 1][$j] : 0)
                >= (isset($a[$i][$j + 1]) ? $a[$i][$j + 1] : 0)
              ) {
                $i++;
            } else {
                $j++;
            }
        }
        return $lcs;
    }
}
