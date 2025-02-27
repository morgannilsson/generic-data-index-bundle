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

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\Model\DefaultSearch\Query;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\BoolQuery;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\TermFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Query\TermsFilter;

/**
 * @internal
 */
final class BoolQueryTest extends Unit
{
    public function testIsEmpty(): void
    {
        $boolQuery = new BoolQuery();
        self::assertTrue($boolQuery->isEmpty());

        $boolQuery = new BoolQuery([
            'must' => [
                ['term' => ['field' => 'value']],
            ],
        ]);
        self::assertFalse($boolQuery->isEmpty());
    }

    public function testAddCondition(): void
    {
        $boolQuery = new BoolQuery();
        $boolQuery->addCondition('must', ['term' => ['field' => 'value']]);
        self::assertFalse($boolQuery->isEmpty());
        self::assertSame([
            'must' => [
                ['term' => ['field' => 'value']],
            ],
        ], $boolQuery->getParams());
    }

    public function testMerge(): void
    {
        $boolQueryA = new BoolQuery();
        $boolQueryA->addCondition('must', ['term' => ['field1' => 'value']]);
        $boolQueryA->addCondition('must', ['term' => ['field2' => 'value2']]);

        $boolQueryB = new BoolQuery();
        $boolQueryB->addCondition('must', ['term' => ['field3' => 'value3']]);
        $boolQueryB->addCondition('must', ['term' => ['field4' => 'value4']]);

        $boolQueryA->merge($boolQueryB);

        self::assertSame([
            'must' => [
                ['term' => ['field1' => 'value']],
                ['term' => ['field2' => 'value2']],
                ['term' => ['field3' => 'value3']],
                ['term' => ['field4' => 'value4']],
            ],
        ], $boolQueryA->getParams());
    }

    public function testToArray(): void
    {
        $boolQuery = new BoolQuery();
        $boolQuery->addCondition('must', ['term' => ['field' => 'value']]);

        self::assertSame([
            'bool' => [
                'must' => [
                    'term' => ['field' => 'value'],
                ],
            ],
        ], $boolQuery->toArray(true));

        $boolQuery = new BoolQuery([
            'must' => [
                ['term' => ['field' => 'value']],
            ],
        ]);

        self::assertSame([
            'bool' => [
                'must' => [
                    'term' => ['field' => 'value'],
                ],
            ],
        ], $boolQuery->toArray(true));

        $boolQuery = new BoolQuery([
            'should' => [
                ['term' => ['field' => 'value']],
                ['term' => ['field2' => 'value2']],
            ],
        ]);

        self::assertSame([
            'bool' => [
                'should' => [
                    ['term' => ['field' => 'value']],
                    ['term' => ['field2' => 'value2']],
                ],
            ],
        ], $boolQuery->toArray(true));

        self::assertSame([
            'should' => [
                ['term' => ['field' => 'value']],
                ['term' => ['field2' => 'value2']],
            ],
        ], $boolQuery->toArray());
    }

    public function testQueryObjectsToArray(): void
    {
        $boolQuery = new BoolQuery([
            'should' => [
                new TermFilter('field', 'value'),
                new TermFilter('field2', 'value2'),
            ],
        ]);

        self::assertSame([
            'should' => [
                ['term' => ['field' => 'value']],
                ['term' => ['field2' => 'value2']],
            ],
        ], $boolQuery->toArray());

        $boolQuery = new BoolQuery([
            'should' => [
                new TermsFilter('field', ['value', 'value2']),
                new TermsFilter('field2', ['value3', 'value4']),
            ],
        ]);

        self::assertSame([
            'should' => [
                ['terms' => ['field' => ['value', 'value2']]],
                ['terms' => ['field2' => ['value3', 'value4']]],
            ],
        ], $boolQuery->toArray());
    }
}
