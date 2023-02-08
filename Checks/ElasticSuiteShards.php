<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDearElasticSuite\Checks;

use Magento\Framework\Module\Manager as ModuleManager;
use Vendic\OhDear\Api\CheckInterface;
use Vendic\OhDear\Api\Data\CheckResultInterface;
use Vendic\OhDear\Api\Data\CheckStatus;
use Vendic\OhDear\Model\CheckResultFactory;
use Vendic\OhDearElasticSuite\Utils\ElasticsearchClient;

class ElasticSuiteShards implements CheckInterface
{
    public function __construct(
        private int $maximumShards,
        private ElasticsearchClient $esClient,
        private ModuleManager $moduleManager,
        private CheckResultFactory $checkResultFactory,
    ) {
    }

    /**
     * Will trigger an status error if more than 90% of the elasticsearch shards are used.
     */
    public function run(): CheckResultInterface
    {
        /** @var CheckResultInterface $checkResult */
        $checkResult = $this->checkResultFactory->create();
        $checkResult->setName('elasticsuite_shards');
        $checkResult->setLabel('Elasticsearch free shards');

        if ($this->isElasticSuiteEnabled() === false) {
            $checkResult->setStatus(CheckStatus::STATUS_SKIPPED);
            $checkResult->setShortSummary('ElasticSuite is not enabled');
            $checkResult->setNotificationMessage('ElasticSuite is not enabled');
            return $checkResult;
        }

        try {
            $numberOfShards = $this->getNumberOfShardsUsed();
        } catch (\Exception $e) {
            $checkResult->setStatus(CheckStatus::STATUS_CRASHED);
            $checkResult->setShortSummary('Elasticsearch shards check crashed');
            $checkResult->setNotificationMessage('Elasticsearch shards check crashed');
            return $checkResult;
        }

        $percentageUsed = $numberOfShards / $this->maximumShards * 100;
        $status = $percentageUsed > 90 ? CheckStatus::STATUS_WARNING : CheckStatus::STATUS_OK;

        $checkResult->setStatus($status);
        $checkResult->setShortSummary(
            $status === CheckStatus::STATUS_WARNING ?
                sprintf('Elasticsearch shards used: %s%%', $percentageUsed) :
                'Elastic shards OK'
        );
        $checkResult->setMeta(
            [
                'shards_used' => $numberOfShards,
                'shards_total' => $this->maximumShards,
                'shards_percentage_used' => $percentageUsed
            ]
        );
        $checkResult->setNotificationMessage(
        // phpcs:ignore
            $status === CheckStatus::STATUS_WARNING ?
                sprintf('WARNING: Elastic shards almost full (%s%%)', $percentageUsed) :
                ''
        );

        return $checkResult;
    }

    private function getNumberOfShardsUsed(): int
    {
        $esClient = $this->esClient->get();
        $shards = $esClient->cat()->shards();
        return count($shards);
    }

    private function isElasticSuiteEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Smile_ElasticsuiteCatalog');
    }
}
