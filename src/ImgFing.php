<?php

namespace ImgFing;

class ImgFing
{
    protected array $options = [
        'bitSize' => 300,
        'avgColorAdjust' => 5,
        'cropFit' => false,
        'adapters' => [
            'Imagick',
            'GD',
        ],
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @throws \Exception
     */
    public function identifyFile(string $path): string
    {
        if (false === is_readable($path)) {
            throw new \Exception('Could not read file: ' . $path);
        }
        return $this->identify(file_get_contents($path));
    }

    protected function identify(string $imageString): string
    {
        [$r, $g, $b] = $this->getRgbFromAdapter($imageString);

        // Some magic
        $rAvg = $gAvg = $bAvg = 127;
        foreach (['r', 'g', 'b'] as $channel) {
            $avgName = $channel . 'Avg';
            $$avgName = array_sum($$channel) / count($$channel);

            if ($$avgName - 127 > $this->options['avgColorAdjust']) {
                $$avgName = 127 + $this->options['avgColorAdjust'];
            } elseif (abs($$avgName - 127) > $this->options['avgColorAdjust']) {
                $$avgName = 127 - $this->options['avgColorAdjust'];
            }
        }

        $fingerprint = '';
        for ($i = 0; $i < ceil($this->options['bitSize'] / 3); $i++) {
            $fingerprint .= $r[$i] > $rAvg ? '1' : '0';
            $fingerprint .= $g[$i] > $gAvg ? '1' : '0';
            $fingerprint .= $b[$i] > $bAvg ? '1' : '0';
        }

        return $fingerprint;
    }

    /**
     * @throws \Exception
     */
    protected function getRgbFromAdapter(string $imageString): array
    {
        foreach ($this->options['adapters'] as $adapter) {
            if ($adapter === 'Imagick' && true === class_exists('Imagick')) {
                return $this->identifyImagick($imageString);
            } elseif ($adapter === 'GD' && true === function_exists('imagecreatefromstring')) {
                return $this->identifyGD($imageString);
            }
        }

        throw new \Exception('Unable to read images without GD and/or Imagick');
    }

    protected function identifyImagick(string $imageString): array
    {
        $file = tempnam(sys_get_temp_dir(), 'imgfing');
        $s = $this->getPixelSize();

        file_put_contents($file, $imageString);
        $img = new \Imagick($file);

        if ($this->options['cropFit'] === true) {
            $w = $img->getImageWidth();
            $h = $img->getImageHeight();
            if ($w > $h) {
                $img->cropImage($h, $h, ($w - $h) / 2, 0);
            } else {
                $img->cropImage($w, $w, 0, ($h - $w) / 2);
            }
        }

        $img->resizeImage($s, $s, \Imagick::FILTER_CATROM, 1, false);

        $r = $g = $b = [];

        for ($x = 0; $x < $s; $x++) {
            for ($y = 0; $y < $s; $y++) {
                $rgb = $img->getImagePixelColor($x, $y)->getColor(\Imagick::COLORSPACE_RGB);
                $r[] = $rgb['r'] * 255;
                $g[] = $rgb['g'] * 255;
                $b[] = $rgb['b'] * 255;
            }
        }

        $img->destroy();
        unset($img);
        unlink($file);

        return [$r, $g, $b];
    }

    protected function identifyGD(string $string): array
    {
        $src = imagecreatefromstring($string);

        $msg = null;

        set_error_handler(
            function ($errno, $errstr) use (&$msg) {
                $msg = $errstr;
            }
        );
        if ($src === false) {
            throw new \Exception('Unable to parse image: ' . $msg);
        }

        restore_error_handler();

        $s = $this->getPixelSize();

        $cx = 0;
        $cy = 0;
        $cw = imagesx($src);
        $ch = imagesy($src);

        if ($this->options['cropFit'] === true) {
            if ($cw > $ch) {
                $cx = ($cw - $ch) / 2;
                $cw = $ch;
            } else {
                $cy = ($ch - $cw) / 2;
                $ch = $cw;
            }
        }

        $rsmpl = imagecreatetruecolor($s, $s);
        imagecopyresampled($rsmpl, $src, 0, 0, $cx, $cy, $s, $s, $cw, $ch);

        $r = $g = $b = [];

        for ($x = 0; $x < $s; $x++) {
            for ($y = 0; $y < $s; $y++) {
                $rgb = imagecolorat($rsmpl, $x, $y);
                $r[] = ($rgb >> 16) & 0xFF;
                $g[] = ($rgb >> 8) & 0xFF;
                $b[] = ($rgb & 0xFF);
            }
        }

        imagedestroy($src);
        imagedestroy($rsmpl);
        unset($src, $rsmpl);

        return [$r, $g, $b];
    }

    protected function getPixelSize()
    {
        return ceil(pow($this->options['bitSize'] / 3, 0.5));
    }

    public function createIdentityImageFromString(string $imgString): string
    {
        $s = $this->getPixelSize();
        $gd = imagecreatetruecolor($s, $s);

        $fingerprint = $this->identifyString($imgString);

        for ($i = 0; $i < $this->options['bitSize']; $i += 3) {
            $r = (int)$fingerprint[$i];
            $g = (int)$fingerprint[$i + 1];
            $b = (int)$fingerprint[$i + 2];

            $color = imagecolorallocate($gd, $r * 255, $g * 255, $b * 255);
            imagesetpixel($gd, ($i / 3) % $s, floor($i / 3 / $s), $color);
        }
        ob_start();
        imagepng($gd, null, 0);
        $fingString = ob_get_clean();
        imagedestroy($gd);

        return $fingString;
    }

    public function identifyString(string $imgString): string
    {
        return $this->identify($imgString);
    }

    /**
     * @throws \Exception
     */
    public function matchScore(string $str1, string $str2): string
    {
        if (strlen($str1) !== strlen($str2)) {
            throw new \Exception('Strings should be same length (bitSize)');
        }

        $matchCount = 0;
        for ($i = 0; $i < strlen($str1); $i++) {
            if ($str1[$i] === $str2[$i]) {
                $matchCount++;
            }
        }

        return pow($matchCount / strlen($str1), 2);
    }
}
