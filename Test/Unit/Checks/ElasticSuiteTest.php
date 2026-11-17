<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDearElasticSuite\Test\Unit\Checks;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResult;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDearElasticSuite\Checks\ElasticSuite;

class ElasticSuiteTest extends TestCase
{
    public function testSkippedWithoutResolvingClientWhenElasticSuiteIsDisabled(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects($this->never())->method('get');

        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->expects($this->once())
            ->method('isEnabled')->with('Smile_ElasticsuiteCatalog')->willReturn(false);

        $checkResultFactory = $this->createStub(CheckResultFactory::class);
        $checkResultFactory->method('create')->willReturn(new CheckResult());

        $checkResult = (new ElasticSuite($objectManager, $moduleManager, $checkResultFactory))->run();

        $this->assertSame(CheckStatus::STATUS_SKIPPED, $checkResult->getStatus());
        $this->assertSame('ElasticSuite is not enabled', $checkResult->getShortSummary());
        $this->assertSame('ElasticSuite is not enabled', $checkResult->getNotificationMessage());
    }
}
