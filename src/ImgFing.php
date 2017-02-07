<?php

namespace ImgFing;

class ImgFing
{
    protected $options = [
        'width' => 10,
        'height' => 10,
        'maxColorShift' => 70,
        'maxColorMultiply' => 2,
    ];
    
    public function __construct (array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }
    
    public function identifyFile($path)
    {
        if (false === is_readable($path)) {
            throw new \Exception('Could not read file: ' . $path);
        }
        return $this->identifyGD(file_get_contents($path));
    }
    
    public function identifyString($imgString)
    {
        return $this->identifyGD($imgString);
    }
    
    protected function identifyGD($string)
    {
        $src = imagecreatefromstring($string);
        
        $msg = null;
        set_error_handler(function($errno, $errstr) use (&$msg) { $msg = $errstr; });
        if ($src === false) {
            throw new \Exception('Unable to parse image: ' . $msg);
        }
        
        restore_error_handler();
        
        $w = $this->options['width'];
        $h = $this->options['height'];
        
        $rsmpl = imagecreatetruecolor($w, $h);
        imagecopyresampled($rsmpl, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        
        $r = $g = $b = [];
        
        for ($x =0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++ ) {
                $rgb = imagecolorat($rsmpl, $x, $y);
                $r[] = ($rgb >> 16) & 0xFF;
                $g[] = ($rgb >> 8) & 0xFF;
                $b[] = ($rgb & 0xFF);
            }
        }
        
        imagedestroy($src);
        imagedestroy($rsmpl);
        unset($src, $rsmpl);
        
        $rMultiplier = 255 / (max($r) - min($r));
        $gMultiplier = 255 / (max($g) - min($g));
        $bMultiplier = 255 / (max($b) - min($b));
        
        $rMultiplier = $rMultiplier > $this->options['maxColorMultiply'] ? $this->options['maxColorMultiply'] : $rMultiplier;
        $gMultiplier = $gMultiplier > $this->options['maxColorMultiply'] ? $this->options['maxColorMultiply'] : $gMultiplier;
        $bMultiplier = $bMultiplier > $this->options['maxColorMultiply'] ? $this->options['maxColorMultiply'] : $bMultiplier;
        
        $rShift = min($r);
        $gShift = min($g);
        $bShift = min($b);
        
        $rShift = $rShift > $this->options['maxColorShift'] ? $this->options['maxColorShift'] : $rShift;
        $gShift = $gShift > $this->options['maxColorShift'] ? $this->options['maxColorShift'] : $gShift;
        $bShift = $bShift > $this->options['maxColorShift'] ? $this->options['maxColorShift'] : $bShift;
        
        
        $fingerprint = '';
        for ($i=0; $i< $w*$h; $i++) {
            $fingerprint .= ($r[$i] - $rShift) * $rMultiplier;
            $fingerprint .= ($g[$i] - $gShift) * $gMultiplier;
            $fingerprint .= ($b[$i] - $bShift) * $bMultiplier;
        }
        
        return $fingerprint;
    }
}
