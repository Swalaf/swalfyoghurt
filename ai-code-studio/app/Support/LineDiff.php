<?php

namespace App\Support;

class LineDiff
{
    /**
     * Count added / removed lines between two texts (LCS on lines).
     *
     * @return array{0: int, 1: int}
     */
    public static function stats(?string $old, ?string $new): array
    {
        $a = $old === null || $old === '' ? [] : explode("\n", $old);
        $b = $new === null || $new === '' ? [] : explode("\n", $new);
        $n = count($a);
        $m = count($b);
        if ($n * $m > 4_000_000) {
            // Too large for LCS — fall back to a cheap estimate.
            $common = count(array_intersect($a, $b));

            return [max(0, $m - $common), max(0, $n - $common)];
        }
        $prev = array_fill(0, $m + 1, 0);
        for ($i = 1; $i <= $n; $i++) {
            $cur = [0];
            for ($j = 1; $j <= $m; $j++) {
                $cur[$j] = $a[$i - 1] === $b[$j - 1] ? $prev[$j - 1] + 1 : max($prev[$j], $cur[$j - 1]);
            }
            $prev = $cur;
        }
        $lcs = $prev[$m];

        return [$m - $lcs, $n - $lcs];
    }
}
