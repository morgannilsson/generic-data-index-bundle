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

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class VideoAdapter extends AbstractAdapter
{
    public function getIndexMapping(): array
    {
        return [
            'properties' => [
                'id' => [
                    'type' => AttributeType::TEXT,
                ],
                'type' => [
                    'type' => AttributeType::KEYWORD,
                ],
                'details' => [
                    'type' => AttributeType::NESTED,
                    'properties' => [
                        'type' => [
                            'type' => AttributeType::KEYWORD,
                        ],
                        'title' => [
                            'type' => AttributeType::KEYWORD,
                        ],
                        'description' => [
                            'type' => AttributeType::KEYWORD,
                        ],
                        'poster' => [
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
                            'properties' => [
                                'id' => [
                                    'type' => AttributeType::LONG,
                                ],
                                'type' => [
                                    'type' => AttributeType::KEYWORD,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function normalize(mixed $value): ?array
    {
        $fieldDefinition = $this->getFieldDefinition();
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $returnValue = [
            'type' => null,
            'id' => null,
            'details' => null,
        ];

        $normalizedValues = $fieldDefinition->normalize($value);

        if (is_array($normalizedValues)) {
            $returnValue['type'] = $normalizedValues['type'];
            $returnValue['id'] = $normalizedValues['data'];

            if ($normalizedValues['type'] === 'asset') {
                $returnValue['details'] = $normalizedValues;
                $returnValue['id'] = $normalizedValues['data']['id'];
            }
        }

        return $returnValue;
    }
}
