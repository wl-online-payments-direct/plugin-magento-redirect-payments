<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\Test\Unit\Gateway\Config;

use Magento\Payment\Gateway\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Worldline\RedirectPayment\Gateway\Config\TranslatedTitleValueHandler;

class TranslatedTitleValueHandlerTest extends TestCase
{
    /**
     * @var ConfigInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $configInterface;

    /**
     * @var TranslatedTitleValueHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->configInterface = $this->createMock(ConfigInterface::class);
        $this->handler = new TranslatedTitleValueHandler($this->configInterface);
    }

    /**
     * @dataProvider storeIdDataProvider
     */
    public function testHandleReturnsConfiguredValueForField(?int $storeId): void
    {
        $this->configInterface->expects($this->once())
            ->method('getValue')
            ->with('title', $storeId)
            ->willReturn('Pay in installments');

        $this->assertSame('Pay in installments', $this->handler->handle(['field' => 'title'], $storeId));
    }

    public static function storeIdDataProvider(): array
    {
        return [
            'no store id' => [null],
            'explicit store id' => [1],
        ];
    }

    public function testHandleReturnsAStringEvenWhenConfigValueIsNotOne(): void
    {
        $this->configInterface->method('getValue')->willReturn(12345);

        $this->assertIsString($this->handler->handle(['field' => 'title']));
    }

    public function testHandleThrowsWhenFieldIsMissingFromSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle([]);
    }
}
