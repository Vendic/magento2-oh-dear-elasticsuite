<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDearElasticSuite\Test\Integration\Checks;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDearElasticSuite\Checks\ElasticSuite;
use Magento\Framework\Module\Manager as ModuleManager;

class ElasticSuiteTest extends TestCase
{
    public function testElasticSuiteCheck() : void
    {
        /** @var ElasticSuite $elasticSuiteCheck */
        $elasticSuiteCheck = Bootstrap::getObjectManager()->get(ElasticSuite::class);
        $checkResult = $elasticSuiteCheck->run();

        /** @var ModuleManager $moduleManager */
        $moduleManager = Bootstrap::getObjectManager()->get(ModuleManager::class);
        if ($moduleManager->isEnabled('Smile_ElasticsuiteCore')) {
            $this->elasticSuiteEnabledAssertions($checkResult);
        } else {
            $this->elasticSuiteDisabledAssertions($checkResult);
        }
    }

    private function elasticSuiteEnabledAssertions(CheckResultInterface $checkResult): void
    {
        $this->assertEquals(CheckStatus::STATUS_OK, $checkResult->getStatus());
        $this->assertEquals('Elasticsearch connection OK', $checkResult->getNotificationMessage());
        $this->assertEquals('Elasticsearch connection OK', $checkResult->getShortSummary());
        $this->assertGreaterThanOrEqual(1, count($checkResult->getMeta()));
    }

    private function elasticSuiteDisabledAssertions(CheckResultInterface $checkResult): void
    {
        $this->assertEquals(CheckStatus::STATUS_SKIPPED, $checkResult->getStatus());
        $this->assertEquals('Elasticsearch not responding', $checkResult->getNotificationMessage());
        $this->assertEquals('Elasticsearch not responding', $checkResult->getShortSummary());
    }
}
