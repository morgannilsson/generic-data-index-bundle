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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
final class ObjectBrickAdapter extends AbstractAdapter
{
    private DefinitionResolverInterface $objectBrickDefinition;

    public function getIndexMapping(): array
    {
        $objectBricks = $this->getFieldDefinition();
        $mapping = [];
        if (!$objectBricks instanceof Objectbricks) {
            throw new InvalidArgumentException(
                'FieldDefinition must be of type Data\Objectbricks'
            );
        }

        foreach ($objectBricks->getAllowedTypes() as $type) {
            $mapping[$type]['properties'] = $this->getMappingForObjectBrick($type);
        }

        return [
            'properties' => $mapping,
        ];
    }

    public function normalize(mixed $value): ?array
    {
        if (!$value instanceof Objectbrick) {
            return null;
        }

        $resultItems = [];
        $items = $value->getObjectVars();
        foreach ($items as $item) {
            if (!$item instanceof AbstractData) {
                continue;
            }

            $type = $item->getType();
            $resultItems[$type] = [];
            $definition = $this->objectBrickDefinition->getByKey($type);
            if ($definition === null) {
                continue;
            }

            $resultItems[$type] = [];
            foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                $getter = 'get' . ucfirst($fieldDefinition->getName());
                $value = $item->$getter();
                $resultItems[$fieldDefinition->getName()] = $this->fieldDefinitionService->normalizeValue(
                    $fieldDefinition,
                    $value
                );
            }
        }

        return $resultItems;
    }

    private function getMappingForObjectBrick(string $objectBrickType): array
    {
        $fieldDefinitions = $this->objectBrickDefinition->getByKey($objectBrickType)?->getFieldDefinitions();
        $mapping = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            $adapter = $this->getFieldDefinitionService()->getFieldDefinitionAdapter($fieldDefinition);
            if ($adapter) {
                $mapping[$adapter->getIndexAttributeName()] = $adapter->getIndexMapping();
            }
        }

        return $mapping;
    }

    #[Required]
    public function setObjectBrickDefinition(DefinitionResolverInterface $definitionResolver): void
    {
        $this->objectBrickDefinition = $definitionResolver;
    }
}
