<?php declare(strict_types=1);
/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

namespace Vendic\OhDearElasticSuite\Utils;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Client\ClientBuilder;
use Smile\ElasticsuiteCore\Client\ClientConfiguration;

class ElasticsearchClient
{
    /**
     * Used object manager to avoid dependency on Elasticsuite
     */
    public function __construct(private ObjectManagerInterface $objectManager)
    {
    }

    public function get(): \Elasticsearch\Client
    {
        /** @var ClientBuilder $clientBuilder */
        $clientBuilder = $this->objectManager->get(ClientBuilder::class);

        /** @var ClientConfiguration $clientConfiguration */
        $clientConfiguration = $this->objectManager->get(ClientConfiguration::class);

        return $clientBuilder->build($clientConfiguration->getOptions());
    }
}
