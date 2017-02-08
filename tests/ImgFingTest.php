<?php

namespace ImgFing;

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 7-2-2017
 * Time: 14:06
 */
class ImgFingTest extends \PHPUnit\Framework\TestCase
{
    public function testBlackJpgGd()
    {
        $imgFing = new ImgFing([
            'adapters' => ['GD'],
        ]);

        $this->assertSame(str_repeat('0', 10 * 10 * 3), $imgFing->identifyFile(__DIR__ . '/data/black.jpg'));
    }

    public function testBlackJpgImagick()
    {
        $imgFing = new ImgFing([
            'adapters' => ['Imagick'],
        ]);

        $this->assertSame(str_repeat('0', 10 * 10 * 3), $imgFing->identifyFile(__DIR__ . '/data/black.jpg'));
    }

    public function testBlackJpg10Bit()
    {
        $imgFing = new ImgFing([
            'bitSize' => 10
        ]);

        $this->assertSame(str_repeat('0', 12), $imgFing->identifyFile(__DIR__ . '/data/black.jpg'));
    }

    public function testWhitePngGd()
    {
        $imgFing = new ImgFing([
            'adapters' => ['GD'],
        ]);
        $this->assertSame(str_repeat('1', 10 * 10 * 3), $imgFing->identifyFile(__DIR__ . '/data/white.png'));
    }

    public function testWhitePngImagick()
    {
        $imgFing = new ImgFing([
            'adapters' => ['Imagick'],
        ]);
        $this->assertSame(str_repeat('1', 10 * 10 * 3), $imgFing->identifyFile(__DIR__ . '/data/white.png'));
    }

    public function testResizeCompare()
    {
        $imgFing = new ImgFing();
        $f1 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $f2 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1_100_67.jpg');

        $this->assertGreaterThan(0.99, $imgFing->matchScore($f1, $f2));
    }

    public function testLighterCompare()
    {
        $imgFing = new ImgFing();
        $f1 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $f2 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1-light+15.png');

        $this->assertGreaterThan(0.94, $imgFing->matchScore($f1, $f2));
    }

    public function testRotateCompare()
    {
        $imgFing = new ImgFing();
        $f1 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $f2 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v2.png');

        $this->assertLessThan(0.55, $imgFing->matchScore($f1, $f2));
    }

    public function testCroppedCompareGD()
    {
        $imgFingCrop = new ImgFing([
            'cropFit' => true,
            'adapters' => ['GD'],
        ]);
        $imgFingNoCrop = new ImgFing([
            'cropFit' => false,
            'adapters' => ['GD'],
        ]);

        $cropped = $imgFingCrop->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $noCrop = $imgFingNoCrop->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $cropSquare = $imgFingCrop->identifyFile(__DIR__ . '/data/multigradient-v1-square.png');
        $noCropSquare = $imgFingNoCrop->identifyFile(__DIR__ . '/data/multigradient-v1-square.png');

        $this->assertSame(1, $imgFingCrop->matchScore($cropSquare, $noCropSquare));
        $this->assertSame(1, $imgFingCrop->matchScore($cropped, $noCropSquare));
        $this->assertLessThan(1, $imgFingCrop->matchScore($cropped, $noCrop));
    }

    public function testCroppedCompareImagick()
    {
        $imgFingCrop = new ImgFing([
            'cropFit' => true,
            'adapters' => ['Imagick'],
        ]);
        $imgFingNoCrop = new ImgFing([
            'cropFit' => false,
            'adapters' => ['Imagick'],
        ]);

        $cropped = $imgFingCrop->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $noCrop = $imgFingNoCrop->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $cropSquare = $imgFingCrop->identifyFile(__DIR__ . '/data/multigradient-v1-square.png');
        $noCropSquare = $imgFingNoCrop->identifyFile(__DIR__ . '/data/multigradient-v1-square.png');

        $this->assertSame(1, $imgFingCrop->matchScore($cropSquare, $noCropSquare));
        $this->assertSame(1, $imgFingCrop->matchScore($cropped, $noCropSquare));
        $this->assertLessThan(1, $imgFingCrop->matchScore($cropped, $noCrop));
    }
}
