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

        $this->assertGreaterThan(0.98, $imgFing->matchScore($f1, $f2));
    }

    public function testRotateCompare()
    {
        $imgFing = new ImgFing();
        $f1 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v1.png');
        $f2 = $imgFing->identifyFile(__DIR__ . '/data/multigradient-v2.png');

        $this->assertLessThan(0.75, $imgFing->matchScore($f1, $f2));
    }
}
