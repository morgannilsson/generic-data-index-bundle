<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\SearchIndexAdapter\Asset\FieldDefinitionAdapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\InvalidArgumentException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Search;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Asset\AssetMetaDataFilter;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\Asset\FieldDefinitionAdapter\TextKeywordAdapter;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;

/**
 * @internal
 */
final class TextKeywordAdapterTest extends Unit
{
    public function testGetSearchIndexMapping(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $indexMappingServiceInterfaceMock = $this->getMappingService(
            ['getMappingForTextKeyword' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword',
                    ],
                ],
            ]]
        );
        $adapter = new TextKeywordAdapter(
            $searchIndexConfigServiceInterfaceMock,
            $indexMappingServiceInterfaceMock
        );

        $mapping = $adapter->getIndexMapping();
        $this->assertSame([
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                ],
            ],
        ], $mapping);
    }

    public function testApplySearchFilterWrongMetaDataType(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new TextKeywordAdapter(
            $searchIndexConfigServiceInterfaceMock,
            $this->getMappingService()
        ))->setType('input');

        $filter = new AssetMetaDataFilter('test', 'checkbox', 1);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());
    }

    public function testApplySearchFilterWrongType()
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new TextKeywordAdapter(
            $searchIndexConfigServiceInterfaceMock,
            $this->getMappingService()
        ))->setType('input');

        $filter = new AssetMetaDataFilter('test', 'input', 1);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());

        $filter = new AssetMetaDataFilter('test', 'input', [null]);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());
    }

    public function testApplySearchFilter()
    {

        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new TextKeywordAdapter(
            $searchIndexConfigServiceInterfaceMock,
            $this->getMappingService()
        ))->setType('input');

        $filter = new AssetMetaDataFilter('test', 'input', 'value');
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'filter' => [
                        'wildcard' => [
                            'standard_fields.test.default.keyword' => [
                                'value' => '*value*',
                                'case_insensitive' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $search->toArray());

        $filter = new AssetMetaDataFilter('test', 'input', 'value*', 'en');
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'filter' => [
                        'wildcard' => [
                            'standard_fields.test.en.keyword' => [
                                'value' => 'value*',
                                'case_insensitive' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $search->toArray());

        $filter = new AssetMetaDataFilter('test', 'input', 'val*ue', 'en');
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'bool' => [
                    'filter' => [
                        'wildcard' => [
                            'standard_fields.test.en.keyword' => [
                                'value' => 'val*ue',
                                'case_insensitive' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ], $search->toArray());
    }

    private function getMappingService(array $arguments = []): IndexMappingServiceInterface
    {
        return $this->makeEmpty(IndexMappingServiceInterface::class, $arguments);
    }
}
