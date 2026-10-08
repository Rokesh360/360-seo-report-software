<?php

namespace App\Support;

/**
 * Draws charts as PNG data-URIs using PHP's GD extension.
 * DomPDF renders PNG images in every version, so this avoids any SVG issues.
 *
 * Save as: app/Support/ReportCharts.php
 */
class ReportCharts
{
    public const PALETTE = ['#2f855a', '#3182ce', '#dd6b20', '#805ad5', '#d53f8c', '#319795'];

    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    /* ---------------- helpers ---------------- */

    private static function alloc($im, string $hex)
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        return imagecolorallocate($im, $r, $g, $b);
    }

    private static function centeredText($im, int $font, float $cx, int $y, string $text, $color): void
    {
        $w = imagefontwidth($font) * strlen($text);
        imagestring($im, $font, (int) round($cx - $w / 2), $y, $text, $color);
    }

    private static function rightText($im, int $font, int $xRight, int $y, string $text, $color): void
    {
        $w = imagefontwidth($font) * strlen($text);
        imagestring($im, $font, $xRight - $w, $y, $text, $color);
    }

    private static function toDataUri($im): string
    {
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function drawGrid($im, int $left, int $top, int $plotW, int $plotH, float $max): void
    {
        $grid = self::alloc($im, '#e5e7eb');
        $axis = self::alloc($im, '#9ca3af');
        $txt  = self::alloc($im, '#6b7280');

        for ($i = 0; $i <= 4; $i++) {
            $y = (int) round($top + $plotH - ($plotH * $i / 4));
            imageline($im, $left, $y, $left + $plotW, $y, $i === 0 ? $axis : $grid);
            self::rightText($im, 3, $left - 8, $y - 6, number_format($max * $i / 4), $txt);
        }
        imageline($im, $left, $top, $left, $top + $plotH, $axis);
    }

    /* ---------------- vertical bar graph ---------------- */

    public static function barGraph(array $metrics): ?string
    {
        $metrics = array_filter($metrics, static fn ($v) => $v > 0);
        if (!$metrics || !self::available()) {
            return null;
        }

        $cnt = count($metrics);
        $w = max(900, $cnt * 120 + 100);
        $h = 380;
        $left = 70; $right = 20; $top = 40; $bottom = 60;
        $plotW = $w - $left - $right;
        $plotH = $h - $top - $bottom;
        $max = (float) max($metrics);

        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, self::alloc($im, '#ffffff'));

        self::drawGrid($im, $left, $top, $plotW, $plotH, $max);

        $slot = $plotW / $cnt;
        $bw = min(70, $slot * 0.6);
        $dark = self::alloc($im, '#111827');
        $gray = self::alloc($im, '#4b5563');

        $i = 0;
        foreach ($metrics as $label => $value) {
            $barH = $value / $max * $plotH;
            $x1 = (int) round($left + $i * $slot + ($slot - $bw) / 2);
            $x2 = (int) round($x1 + $bw);
            $y1 = (int) round($top + $plotH - $barH);
            $y2 = $top + $plotH;

            $color = self::alloc($im, self::PALETTE[$i % count(self::PALETTE)]);
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);

            $cx = ($x1 + $x2) / 2;
            self::centeredText($im, 4, $cx, $y1 - 18, number_format($value), $dark);
            self::centeredText($im, 3, $cx, $y2 + 10, (string) $label, $gray);
            $i++;
        }

        return self::toDataUri($im);
    }

    /* ---------------- pie chart ---------------- */

    public static function pie(array $slices): ?string
    {
        $slices = array_filter($slices, static fn ($v) => $v > 0);
        $total = array_sum($slices);
        if (!$slices || $total <= 0 || !self::available()) {
            return null;
        }

        $size = 400;
        $c = $size / 2;
        $d = $size - 20;

        $im = imagecreatetruecolor($size, $size);
        imagefilledrectangle($im, 0, 0, $size, $size, self::alloc($im, '#ffffff'));

        if (count($slices) === 1) {
            imagefilledellipse($im, (int) $c, (int) $c, $d, $d, self::alloc($im, self::PALETTE[0]));
            return self::toDataUri($im);
        }

        $cum = 0;
        $i = 0;
        foreach ($slices as $value) {
            $startDeg = (int) round(270 + $cum / $total * 360);
            $cum += $value;
            $endDeg = (int) round(270 + $cum / $total * 360);

            if ($endDeg > $startDeg) {
                imagefilledarc(
                    $im, (int) $c, (int) $c, $d, $d,
                    $startDeg % 360, $endDeg % 360,
                    self::alloc($im, self::PALETTE[$i % count(self::PALETTE)]),
                    IMG_ARC_PIE
                );
            }
            $i++;
        }

        return self::toDataUri($im);
    }

    /* ---------------- trend line chart ---------------- */

    public static function lineChart(array $labels, array $values): ?string
    {
        $n = count($values);
        if ($n < 2 || !self::available()) {
            return null;
        }

        $w = 1000; $h = 380;
        $left = 70; $right = 30; $top = 40; $bottom = 60;
        $plotW = $w - $left - $right;
        $plotH = $h - $top - $bottom;
        $max = (float) (max($values) ?: 1);

        $im = imagecreatetruecolor($w, $h);
        imagefilledrectangle($im, 0, 0, $w, $h, self::alloc($im, '#ffffff'));

        self::drawGrid($im, $left, $top, $plotW, $plotH, $max);

        $green = self::alloc($im, '#2f855a');
        $dark  = self::alloc($im, '#111827');
        $gray  = self::alloc($im, '#4b5563');

        $pts = [];
        foreach (array_values($values) as $i => $v) {
            $pts[] = [
                (int) round($left + $i * $plotW / ($n - 1)),
                (int) round($top + $plotH - $v / $max * $plotH),
                $v,
                $labels[$i] ?? '',
            ];
        }

        imagesetthickness($im, 3);
        for ($i = 1; $i < $n; $i++) {
            imageline($im, $pts[$i - 1][0], $pts[$i - 1][1], $pts[$i][0], $pts[$i][1], $green);
        }
        imagesetthickness($im, 1);

        $step = max(1, (int) ceil($n / max(1, floor($plotW / 80))));
        foreach ($pts as $i => [$x, $y, $v, $label]) {
            imagefilledellipse($im, $x, $y, 10, 10, $green);
            if ($i % $step === 0) {
                self::centeredText($im, 3, $x, $y - 20, number_format($v), $dark);
                self::centeredText($im, 3, $x, $top + $plotH + 10, (string) $label, $gray);
            }
        }

        return self::toDataUri($im);
    }
}