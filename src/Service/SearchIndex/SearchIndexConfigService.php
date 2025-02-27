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

namespace Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex;

use Psr\Log\LoggerAwareTrait;

/**
 * @internal
 */
final class SearchIndexConfigService implements SearchIndexConfigServiceInterface
{
    use LoggerAwareTrait;

    private const SYSTEM_FIELD_GENERAL = 'general';

    public const SYSTEM_FIELD_ASSET = 'asset';

    public const SYSTEM_FIELD_DOCUMENT = 'document';

    public const SYSTEM_FIELD_DATA_OBJECT = 'data_object';

    public function __construct(
        private readonly string $clientType,
        private readonly string $indexPrefix,
        private readonly array $indexSettings,
        private readonly array $searchSettings,
        private readonly array $systemFieldsSettings,
    ) {
    }

    public function getClientType(): string
    {
        return $this->clientType;
    }

    /**
     * returns index name for given class name
     */
    public function getIndexName(string $name): string
    {
        return $this->getIndexPrefix() . strtolower($name);
    }

    public function prefixIndexName(string $indexName): string
    {
        return $this->getIndexPrefix() . $indexName;
    }

    public function getIndexPrefix(): string
    {
        return $this->indexPrefix;
    }

    public function getIndexSettings(): array
    {
        return $this->indexSettings;
    }

    public function getSearchSettings(): array
    {
        return $this->searchSettings;
    }

    public function getSearchAnalyzerAttributes(): array
    {
        return $this->searchSettings['search_analyzer_attributes'] ?? [];
    }

    public function getMaxSynchronousChildrenRenameLimit(): int
    {
        return $this->searchSettings['max_synchronous_children_rename_limit'] ?? 0;
    }

    public function getSystemFieldsSettings(string $elementType): array
    {
        $systemFieldsSettings = array_merge(
            $this->systemFieldsSettings[self::SYSTEM_FIELD_GENERAL],
            $this->systemFieldsSettings[$elementType] ?? []
        );

        foreach ($systemFieldsSettings as &$systemFieldsSetting) {
            if (!count($systemFieldsSetting['properties'])) {
                unset($systemFieldsSetting['properties']);
            }
            if (!count($systemFieldsSetting['fields'])) {
                unset($systemFieldsSetting['fields']);
            }
        }

        return $systemFieldsSettings;
    }
}
