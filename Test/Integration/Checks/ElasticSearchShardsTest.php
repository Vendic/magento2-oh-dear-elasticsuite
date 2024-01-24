<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDear\Test\Integration\Checks;

use Elasticsearch\Namespaces\CatNamespace as ElasticsearchCatNamespace;
use Magento\TestFramework\Helper\Bootstrap;
use OpenSearch\Namespaces\CatNamespace as OpenSearchCatnamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Client\ClientBuilder;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDearElasticSuite\Checks\ElasticSuiteShards;

class ElasticSearchShardsTest extends TestCase
{
    public function testShardsOk(): void
    {
        /** @var ElasticSuiteShards $shardsCheck */
        $shardsCheck = Bootstrap::getObjectManager()->get(ElasticSuiteShards::class);
        $checkRun = $shardsCheck->run();

        // Assert that check staus is OK
        $this->assertEquals(CheckStatus::STATUS_OK, $checkRun->getStatus());
        $this->assertEquals('Elastic shards OK', $checkRun->getShortSummary());
    }

    public function testShardsWarning(): void
    {
        // For loop that will create 1000 empty arrays
        $shards = [];
        for ($i = 0; $i < 999; $i++) {
            $shards[] = [];
        }

        /** @var MockObject & ElasticsearchCatNamespace|OpenSearchCatnamespace $catMock */
        $catMock = $this->getMockBuilder($this->getCatNamespaceClassName())
            ->disableOriginalConstructor()
            ->onlyMethods(['shards'])
            ->getMock();
        $catMock->method('shards')->willReturn($shards);

        /** @var MockObject & \Elasticsearch\Client $mockClient */
        $mockClient = $this->getMockBuilder(
            $this->getClientClassName()
        )
            ->disableOriginalConstructor()
            ->onlyMethods(['cat'])
            ->getMock();
        $mockClient->method('cat')->willReturn($catMock);

        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $mockClientBuilder = $this->createMock(ClientBuilder::class);
        $mockClientBuilder->method('build')->willReturn($mockClient);
        $objectManager->addSharedInstance($mockClientBuilder, ClientBuilder::class);

        /** @var ElasticSuiteShards $shardsCheck */
        $shardsCheck = $objectManager->get(ElasticSuiteShards::class);
        $checkRun = $shardsCheck->run();

        $this->assertEquals(CheckStatus::STATUS_WARNING, $checkRun->getStatus());
        $this->assertMatchesRegularExpression(
            '/WARNING: Elastic shards almost full \(\d{2}.\d{1}%\)/',
            $checkRun->getNotificationMessage()
        );
        $this->assertMatchesRegularExpression(
            '/Elasticsearch shards used: \d{2}.\d{1}%/',
            $checkRun->getShortSummary()
        );
    }

    public function testCannotGetShardsFromEs(): void
    {
        /** @var MockObject & ElasticsearchCatNamespace $catMock */
        $catMock = $this->getMockBuilder(
            $this->getCatNamespaceClassName()
        )
            ->disableOriginalConstructor()
            ->onlyMethods(['shards'])
            ->getMock();
        $catMock->method('shards')->willThrowException(new \Exception('Cannot get shards'));

        /** @var MockObject & \Elasticsearch\Client $mockClient */
        $mockClient = $this->getMockBuilder($this->getClientClassName())
            ->disableOriginalConstructor()
            ->onlyMethods(['cat'])
            ->getMock();
        $mockClient->method('cat')->willReturn($catMock);

        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $mockClientBuilder = $this->createMock(ClientBuilder::class);
        $mockClientBuilder->method('build')->willReturn($mockClient);
        $objectManager->addSharedInstance($mockClientBuilder, ClientBuilder::class);

        /** @var ElasticSuiteShards $shardsCheck */
        $shardsCheck = $objectManager->get(ElasticSuiteShards::class);
        $checkRun = $shardsCheck->run();

        $this->assertEquals(CheckStatus::STATUS_CRASHED, $checkRun->getStatus());
        $this->assertEquals('Elasticsearch shards check crashed', $checkRun->getShortSummary());
        $this->assertEquals('Elasticsearch shards check crashed', $checkRun->getNotificationMessage());
    }

    private function isOpenSearchInstalled(): bool
    {
        return class_exists(\OpenSearch\Client::class);
    }

    private function getCatNamespaceClassName(): string
    {
        return $this->isOpenSearchInstalled() ? OpenSearchCatnamespace::class : ElasticsearchCatNamespace::class;
    }

    private function getClientClassName(): string
    {
        return $this->isOpenSearchInstalled() ? \OpenSearch\Client::class : \Elasticsearch\Client::class;
    }
}
