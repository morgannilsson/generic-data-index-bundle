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
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\DateFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Search;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Asset\AssetMetaDataFilter;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\Asset\FieldDefinitionAdapter\DateAdapter;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;

/**
 * @internal
 */
final class DateAdapterTest extends Unit
{
    public function _before(): void
    {
        date_default_timezone_set('UTC');
    }

    public function testGetIndexMapping(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);

        $adapter = new DateAdapter(
            $searchIndexConfigServiceInterfaceMock,
        );

        $mapping = $adapter->getIndexMapping();
        $this->assertSame([
            'type' => 'date',
        ], $mapping);
    }

    public function testNormalize(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);

        $adapter = new DateAdapter(
            $searchIndexConfigServiceInterfaceMock,
        );

        $result = $adapter->normalize(null);
        $this->assertNull($result);

        $result = $adapter->normalize(strtotime('2000-01-01T12:00:00Z'));

        $this->assertSame('2000-01-01T12:00:00+00:00', $result);

    }

    public function testApplySearchFilterWrongMetaDataType(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new DateAdapter(
            $searchIndexConfigServiceInterfaceMock,
        ))->setType('date');

        $filter = new AssetMetaDataFilter('test', 'checkbox', 1);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());
    }

    public function testApplySearchFilterWrongType()
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new DateAdapter(
            $searchIndexConfigServiceInterfaceMock,
        ))->setType('date');

        $filter = new AssetMetaDataFilter('test', 'date', 1);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());

        $filter = new AssetMetaDataFilter('test', 'date', [null]);
        $this->expectException(InvalidArgumentException::class);
        $adapter->applySearchFilter($filter, new Search());
    }

    public function testApplySearchFilter()
    {

        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $adapter = (new DateAdapter(
            $searchIndexConfigServiceInterfaceMock,
        ))->setType('date');

        $filter = new AssetMetaDataFilter('test', 'date', [DateFilter::PARAM_ON => strtotime('2000-01-01T12:00:00Z')]);
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'range' => [
                    'standard_fields.test.default' => [
                        'format' => "yyyy-MM-dd'T'HH:mm:ssz",
                        'gte' => '2000-01-01T00:00:00+00:00',
                        'lte' => '2000-01-01T23:59:59+00:00',
                    ],
                ],
            ],
        ], $search->toArray());

        $filter = new AssetMetaDataFilter('test', 'date', [DateFilter::PARAM_START => strtotime('2000-01-01T12:00:00Z')], 'en');
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'range' => [
                    'standard_fields.test.en' => [
                        'format' => "yyyy-MM-dd'T'HH:mm:ssz",
                        'gte' => '2000-01-01T00:00:00+00:00',
                    ],
                ],
            ],
        ], $search->toArray());

        $filter = new AssetMetaDataFilter('test', 'date', [DateFilter::PARAM_END => strtotime('2000-01-01T12:00:00Z')], 'en');
        $search = new Search();
        $adapter->applySearchFilter($filter, $search);

        $this->assertSame([
            'query' => [
                'range' => [
                    'standard_fields.test.en' => [
                        'format' => "yyyy-MM-dd'T'HH:mm:ssz",
                        'lte' => '2000-01-01T23:59:59+00:00',
                    ],
                ],
            ],
        ], $search->toArray());
    }
}
