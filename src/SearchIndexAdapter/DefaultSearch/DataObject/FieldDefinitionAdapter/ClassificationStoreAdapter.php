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

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\SearchIndexAdapter\MappingProperty;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\LanguageServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Traits\LoggerAwareTrait;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore as ClassificationstoreModel;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupListing;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing as KeyGroupRelationListing;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
final class ClassificationStoreAdapter extends AbstractAdapter
{
    use LoggerAwareTrait;

    private ServiceResolverInterface $classificationService;

    private LanguageServiceInterface $languageService;

    #[Required]
    public function setClassificationService(ServiceResolverInterface $serviceResolver): void
    {
        $this->classificationService = $serviceResolver;
    }

    public function getIndexMapping(): array
    {
        $classificationStore = $this->getFieldDefinition();
        if (!$classificationStore instanceof Classificationstore) {
            throw new InvalidArgumentException(
                'Field definition must be an instance of ' . Classificationstore::class
            );
        }
        $mapping = [];

        $groups = $this->getClassificationStoreGroups($classificationStore->getStoreId());
        foreach ($groups as $group) {
            $keys = $this->getClassificationStoreKeysFromGroup($group);
            $mapping[$group->getName()]['properties'] = $this->getMappingForGroupConfig($keys);
        }

        return [
            'type' => AttributeType::NESTED,
            'properties' => $mapping,
        ];
    }

    public function normalize(mixed $value): ?array
    {
        if (!$value instanceof ClassificationstoreModel) {
            return null;
        }

        $validLanguages = $this->getValidLanguages();
        $resultItems = [];

        foreach ($this->getActiveGroups($value) as $groupId => $groupConfig) {
            $resultItems[$groupConfig->getName()] = [];
            $keys = $this->getClassificationStoreKeysFromGroup($groupConfig);
            foreach ($validLanguages as $validLanguage) {
                foreach ($keys as $key) {
                    $normalizedValue = $this->getNormalizedValue($value, $groupId, $key, $validLanguage);

                    if ($normalizedValue !== null) {
                        $resultItems[$groupConfig->getName()][$validLanguage][$key->getName()] = $normalizedValue;
                    }
                }
            }
        }

        return $resultItems;
    }

    /**
     * @return GroupConfig[]
     */
    private function getActiveGroups(ClassificationstoreModel $value): array
    {
        $groups = [];
        foreach ($value->getActiveGroups() as $groupId => $active) {
            if ($active) {
                $groupConfig = GroupConfig::getById($groupId);
                if ($groupConfig) {
                    $groups[$groupId] = $groupConfig;
                }
            }
        }

        return $groups;
    }

    /**
     * @param KeyGroupRelation[] $groupConfigs
     */
    private function getMappingForGroupConfig(array $groupConfigs): array
    {
        $groupMapping = [];
        foreach ($groupConfigs as $key) {
            try {
                $definition = $this->classificationService->getFieldDefinitionFromKeyConfig($key);
            } catch (Exception) {
                $this->logger->warning(
                    'Could not get field definition for type ' . $key->getType() . ' in group ' . $key->getGroupId()
                );

                continue;
            }

            if ($definition instanceof Data) {
                $adapter = $this->getFieldDefinitionService()->getFieldDefinitionAdapter($definition);

                if ($adapter) {
                    $groupMapping['default']['properties'][$key->getName()] = $adapter->getIndexMapping();
                }
            }
        }

        return $groupMapping;
    }

    /**
     * @return GroupConfig[]
     */
    private function getClassificationStoreGroups(int $id): array
    {
        $listing = new GroupListing();
        $listing->setCondition('storeId = :storeId', ['storeId' => $id]);

        return $listing->getList();
    }

    /**
     * @return KeyGroupRelation[]
     */
    private function getClassificationStoreKeysFromGroup(GroupConfig $groupConfig): array
    {
        $listing = new KeyGroupRelationListing();
        $listing->addConditionParam('groupId = ?', $groupConfig->getId());

        return $listing->getList();
    }

    private function getNormalizedValue(
        ClassificationstoreModel $classificationstore,
        int $groupId,
        KeyGroupRelation $key,
        string $language
    ): mixed {
        try {
            $value = $classificationstore->getLocalizedKeyValue(
                $groupId,
                $key->getKeyId(),
                $language,
                true,
                true
            );
        } catch (Exception $exception) {
            $this->logger->warning(sprintf(
                'Could not get localized value for key %s in group %s: %s',
                $key->getKeyId(),
                $groupId,
                $exception->getMessage()
            ));

            return null;
        }

        $keyConfig = DefinitionCache::get($key->getKeyId());
        if ($keyConfig === null) {
            return null;
        }

        $fieldDefinition = $this->classificationService->getFieldDefinitionFromKeyConfig($keyConfig);

        return $this->fieldDefinitionService->normalizeValue($fieldDefinition, $value);
    }

    private function getValidLanguages(): array
    {
        return array_merge([MappingProperty::NOT_LOCALIZED_KEY], $this->languageService->getValidLanguages());
    }

    #[Required]
    public function setLanguageService(LanguageServiceInterface $languageService): void
    {
        $this->languageService = $languageService;
    }
}
