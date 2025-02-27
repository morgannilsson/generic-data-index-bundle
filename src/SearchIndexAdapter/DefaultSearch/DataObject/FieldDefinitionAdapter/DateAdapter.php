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

use Carbon\Carbon;
use DateTimeInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;

/**
 * @internal
 */
final class DateAdapter extends AbstractAdapter
{
    public function getIndexMapping(): array
    {
        return [
            'type' => AttributeType::DATE->value,
            'format' => $this->respectTimezone() ? 'strict_date_time_no_millis' : 'strict_date',
        ];
    }

    public function normalize(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format($this->respectTimezone() ? DateTimeInterface::ATOM : 'Y-m-d');
        }

        return null;
    }

    private function respectTimezone(): bool
    {
        $fieldDefinition = $this->getFieldDefinition();
        if (!$fieldDefinition instanceof Date) {
            return false;
        }

        return $fieldDefinition->getColumnType() === 'bigint(20)';
    }
}
