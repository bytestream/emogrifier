<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\Utilities;

use Pelago\Emogrifier\Utilities\HtmlNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Class HtmlNormalizerTest
 *
 * @covers Pelago\Emogrifier\Utilities\HtmlNormalizer
 *
 * @author Kieran Brahney <kieran@supportpal.com>
 */
class HtmlNormalizerTest extends TestCase
{
    /**
     * The aim of the HtmlNormalizer is to add missing root elements to HTML. It needs to be able
     * to handle badly formatted HTML without throwing an error so this is what we're testing here.
     *
     * @return string[][]
     */
    public function invalidHtmlDataProvider(): array
    {
        return [
            [
                '<head>',
                '<html><head></head><body></body></html>'
            ],
            [
                '</head>',
                '<html><head></head><body></body></html>'
            ],
            [
                '<head><meta charset="utf8" /></head>',
                '<html><head><meta charset="utf8" /></head><body></body></html>'
            ],
            [
                '<meta charset="utf8" /></head>',
                '<html><head></head><body><meta charset="utf8" /></body></html>'
            ],
            [
                '<meta charset="utf8" />',
                '<html><head></head><body><meta charset="utf8" /></body></html>'
            ],
            [
                '<body>',
                '<html><head></head><body></body></html>'
            ],
            [
                '<body>Hi</body>',
                '<html><head></head><body>Hi</body></html>'
            ],
            [
                'Hi</body>',
                '<html><head></head><body>Hi</body></html>'
            ],
            [
                'Hi',
                '<html><head></head><body>Hi</body></html>'
            ],
            [
                '<b',
                '<html><head></head><body><b></body></html>'
            ],
            [
                '<html>',
                '<html><head></head><body></body></html>'
            ],
            [
                '<html>Hi</html>',
                '<html><head></head><body>Hi</body></html>'
            ],
            [
                'Hi</html>',
                '<html><head></head><body>Hi</body></html>'
            ],
            [
                "  <html>\n  Hi</html>   <body></body>",
                "<html>\n  <head></head><body>  Hi</body></html>   "
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param string $expectedHtml
     *
     * @dataProvider invalidHtmlDataProvider
     */
    public function renderRepairsBrokenHtml(string $input, string $expectedHtml)
    {
        $parser = new HtmlNormalizer;
        $parser->loadHtml($input);

        $this->assertEquals($expectedHtml, $parser->saveHtml());
    }
}
