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
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DataObject\FieldDefinitionServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\IndexMappingServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\SearchIndexConfigServiceInterface;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class ImageGalleryAdapter extends AbstractAdapter
{
    public function __construct(
        protected SearchIndexConfigServiceInterface $searchIndexConfigService,
        protected FieldDefinitionServiceInterface $fieldDefinitionService,
        private readonly IndexMappingServiceInterface $indexMappingService,
    ) {
        parent::__construct(
            $searchIndexConfigService,
            $fieldDefinitionService
        );
    }

    public function getIndexMapping(): array
    {
        return [
            'properties' => [
                'assets' => [
                    'type' => AttributeType::LONG->value,
                ],
                'details' => $this->indexMappingService->getMappingForAdvancedImage(
                    $this->searchIndexConfigService->getSearchAnalyzerAttributes()
                ),
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
            'assets' => [],
        ];

        $normalizedValues = $fieldDefinition->normalize($value);
        if (is_array($normalizedValues)) {
            foreach ($normalizedValues as $normalizedValue) {
                if ($normalizedValue['image']['id']) {
                    $returnValue['assets'][] = $normalizedValue['image']['id'];
                }
            }
            $returnValue['details'] = $normalizedValues;
        }

        return $returnValue;
    }
}
