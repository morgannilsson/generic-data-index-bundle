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

namespace Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ClientType;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\InvalidMappingException;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DataObject\FieldDefinitionServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\Mapping;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
readonly class IndexMappingService implements IndexMappingServiceInterface
{
    public function __construct(
        private FieldDefinitionServiceInterface $fieldDefinitionService,
        private SearchIndexConfigServiceInterface $searchIndexConfigService
    ) {
    }

    /**
     * @param Data[] $fieldDefinitions
     */
    public function getMappingForFieldDefinitions(array $fieldDefinitions): array
    {
        $mapping['properties'] = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            if (!$fieldDefinition->getName()) {
                continue;
            }

            try {
                $fieldMapping = $this->getMapping($fieldDefinition);
                $mapping['properties'][$fieldMapping->getMappingName()] = $fieldMapping->getMapping();
            } catch (InvalidMappingException) {
                continue;
            }
        }

        $mapping['properties'] = $this->transformLocalizedfields($mapping['properties']);

        return $mapping;
    }

    public function getMappingForTextKeyword(array $attributes): array
    {
        return [
            'type' => AttributeType::TEXT->value,
            'fields' => array_merge(
                $attributes[AttributeType::TEXT->value]['fields'] ?? [],
                [
                    'keyword' => [
                        'type' => AttributeType::KEYWORD->value,
                        'ignore_above' => 1024,
                    ],
                    'sort' => [
                        'type' => AttributeType::KEYWORD->value,
                        'ignore_above' => 8191,
                        'normalizer' => 'generic_data_index_sort_truncate_normalizer',
                    ],
                ]
            ),
        ];
    }

    public function getMappingForAdvancedImage(array $attributes): array
    {
        $markerFields = $this->getAdvancedImagePointData($attributes);
        $hotspotFields = $markerFields;
        $hotspotFields['properties']['width'] = [
            'type' => AttributeType::FLOAT->value,
        ];
        $hotspotFields['properties']['height'] = [
            'type' => AttributeType::FLOAT->value,
        ];

        return [
            'type' => AttributeType::NESTED->value,
            'properties' => [
                'crop' => [
                    'properties' => [
                        'cropWidth' => [
                            'type' => AttributeType::FLOAT->value,
                        ],
                        'cropHeight' => [
                            'type' => AttributeType::FLOAT->value,
                        ],
                        'cropLeft' => [
                            'type' => AttributeType::FLOAT->value,
                        ],
                        'cropTop' => [
                            'type' => AttributeType::FLOAT->value,
                        ],
                        'cropPercent' => [
                            'type' => AttributeType::BOOLEAN->value,
                        ],
                    ],
                ],
                'hotspots' => $hotspotFields,
                'marker' => $markerFields,
                'image' => [
                    'properties' => [
                        'id' => [
                            'type' => AttributeType::LONG->value,
                        ],
                        'type' => [
                            'type' => AttributeType::KEYWORD->value,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getAdvancedImagePointData(array $attributes): array
    {
        return [
            'type' => AttributeType::NESTED->value,
            'properties' => [
                'name' => $this->getMappingForTextKeyword($attributes),
                'data' => [
                    'type' => $this->getFlatAttributeType()->value,
                ],
                'top' => [
                    'type' => AttributeType::FLOAT->value,
                ],
                'left' => [
                    'type' => AttributeType::FLOAT->value,
                ],
            ],
        ];
    }

    /**
     * @throws InvalidMappingException
     */
    private function getMapping(Data $fieldDefinition): Mapping
    {
        $fieldDefinitionAdapter = $this->fieldDefinitionService->getFieldDefinitionAdapter($fieldDefinition);
        if (!$fieldDefinitionAdapter) {
            throw new InvalidMappingException(
                'Invalid field definition adapter for field definition: ' . $fieldDefinition->getName()
            );
        }

        $searchAttributeName =  $fieldDefinitionAdapter->getIndexAttributeName();

        return new Mapping(
            mappingName: $searchAttributeName,
            mapping: $fieldDefinitionAdapter->getIndexMapping()
        );
    }

    private function transformLocalizedfields(array $data): array
    {
        if (isset($data['localizedfields'])) {
            $localizedFields = $data['localizedfields']['properties'];
            unset($data['localizedfields']);

            foreach ($localizedFields as $locale => $attributes) {
                foreach ($attributes['properties'] as $attributeName => $attributeData) {
                    $data[$attributeName] = $data[$attributeName] ?? ['type' => 'object', 'properties' => []];
                    $data[$attributeName]['properties'][$locale] = $attributeData;
                }
            }
        }

        return $data;
    }

    private function getFlatAttributeType(): AttributeType
    {
        return $this->searchIndexConfigService->getClientType() === ClientType::OPEN_SEARCH->value ?
            AttributeType::FLAT_OBJECT :
            AttributeType::FLATTENED;
    }
}
