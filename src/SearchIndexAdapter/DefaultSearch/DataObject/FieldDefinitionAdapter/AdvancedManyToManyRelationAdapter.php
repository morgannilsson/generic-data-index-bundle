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

namespace Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter;

use InvalidArgumentException;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class AdvancedManyToManyRelationAdapter extends AbstractAdapter
{
    public function getIndexMapping(): array
    {
        $fieldDefinition = $this->getFieldDefinition();

        if (!$fieldDefinition instanceof AdvancedManyToManyRelation &&
            !$fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            throw new InvalidArgumentException(
                'FieldDefinition must be of type AdvancedManyToManyRelation or AdvancedManyToManyObjectRelation'
            );
        }

        $columnDefinition = $this->getColumnDefinition($fieldDefinition->getColumns());

        return [
            'properties' => [
                'asset' => [
                    'type' => AttributeType::LONG,
                ],
                'object' => [
                    'type' => AttributeType::LONG,
                ],
                'document' => [
                    'type' => AttributeType::LONG,
                ],
                'details' => [
                    'type' => AttributeType::NESTED,
                    'properties' => [
                        'fieldname' => [
                            'type' => AttributeType::KEYWORD,
                        ],
                        'columns' => [
                            'type' => AttributeType::KEYWORD, // Is actually an array of strings
                        ],
                        'element' => [
                            'properties' => [
                                'id' => [
                                    'type' => AttributeType::LONG,
                                ],
                                'type' => [
                                    'type' => AttributeType::KEYWORD,
                                ],
                            ],
                        ],
                        'data' => [
                            'properties' => $columnDefinition,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getColumnDefinition(array $columns): array
    {
        $type = [];
        foreach ($columns as $column) {
            if (isset($column['type'], $column['key'])) {
                $value = match ($column['type']) {
                    'number' => ['type' => AttributeType::LONG],
                    default => ['type' => AttributeType::KEYWORD],
                };
                $type[$column['key']] = $value;
            }
        }

        return $type;
    }

    public function normalize(mixed $value): ?array
    {
        $fieldDefinition = $this->getFieldDefinition();
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $returnValue = [
            'object' => [],
            'asset' => [],
            'document' => [],
        ];
        $normalizedValues = $fieldDefinition->normalize($value);

        if (is_array($normalizedValues)) {
            foreach ($normalizedValues as $normalizedValue) {
                if (isset($normalizedValue['element']['type'], $normalizedValue['element']['id'])) {
                    $returnValue[$normalizedValue['element']['type']][] = $normalizedValue['element']['id'];
                }
            }
            $returnValue['details'] = $normalizedValues;
        }

        return $returnValue;
    }
}
