<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDearElasticSuite\Checks;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface as ElasticsuiteClientInterface;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;

class ElasticSuite implements CheckInterface
{
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private ModuleManager $moduleManager,
        private CheckResultFactory $checkResultFactory
    ) {
    }

    public function run(): CheckResultInterface
    {
        /** @var CheckResultInterface $checkResult */
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('elasticsuite');
        $checkResult->setLabel('Elasticsearch connection');

        if ($this->isElasticSuiteEnabled() === false) {
            $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
            $checkResult->setShortSummary('ElasticSuite is not enabled');
            $checkResult->setNotificationMessage('ElasticSuite is not enabled');
        }

        // Using the objectmanager here so we don't have to add a dependency on the Elasticsuite module
        /** @var ElasticsuiteClientInterface $elasticsuiteClient */
        $elasticsuiteClient = $this->objectManager->get(ElasticsuiteClientInterface::class);
        $status = $elasticsuiteClient->ping() ? CheckStatus::STATUS_OK : CheckStatus::STATUS_FAILED;
        // phpcs:ignore
        $message = $status === CheckStatus::STATUS_OK ? 'Elasticsearch connection OK' : 'Elasticsearch not responding';

        $checkResult->setStatus($status);
        $checkResult->setNotificationMessage($message);
        $checkResult->setShortSummary($message);
        $checkResult->setMeta($elasticsuiteClient->info());

        return $checkResult;
    }

    private function isElasticSuiteEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Smile_ElasticsuiteCatalog');
    }
}
