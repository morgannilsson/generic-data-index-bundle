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

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\SearchIndexAdapter\DataObject\FieldDefinitionAdapter;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DataObject\FieldDefinitionServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter\AdvancedImageAdapter;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage;

/**
 * @internal
 */
final class AdvancedImageAdapterTest extends Unit
{
    public function testGetSearchIndexMapping(): void
    {
        $searchIndexConfigServiceInterfaceMock = $this->makeEmpty(SearchIndexConfigServiceInterface::class);
        $fieldDefinitionServiceInterfaceMock = $this->makeEmpty(FieldDefinitionServiceInterface::class);
        $indexMappingServiceInterfaceMock = $this->makeEmpty(IndexMappingServiceInterface::class,
            ['getMappingForTextKeyword' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword',
                    ],
                ],
            ]]
        );

        $adapter = new AdvancedImageAdapter(
            $searchIndexConfigServiceInterfaceMock,
            $fieldDefinitionServiceInterfaceMock,
            $indexMappingServiceInterfaceMock
        );

        $image = new Hotspotimage();
        $adapter->setFieldDefinition($image);
        $this->assertSame([
            'properties' => [
                'image' => [
                    'type' => AttributeType::LONG->value,
                ],
                'details' => [],
            ],
        ], $adapter->getIndexMapping());
    }
}
