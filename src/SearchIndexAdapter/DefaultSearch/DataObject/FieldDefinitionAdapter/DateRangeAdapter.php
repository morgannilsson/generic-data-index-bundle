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

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\DefaultSearch\AttributeType;

/**
 * @internal
 */
final class DateRangeAdapter extends AbstractAdapter
{
    public function getIndexMapping(): array
    {
        return [
            'type' => AttributeType::OBJECT->value,
            'properties' => [
                'start' => [
                    'type' => AttributeType::DATE->value,
                    'format' => 'strict_date_time_no_millis',
                ],
                'end' => [
                    'type' => AttributeType::DATE->value,
                    'format' => 'strict_date_time_no_millis',
                ],
            ],
        ];
    }

    public function normalize(mixed $value): ?array
    {
        if ($value instanceof CarbonPeriod) {
            if ($value->getEndDate()) {
                return [
                    'start' => $this->formatDate($value->getStartDate()),
                    'end' => $this->formatDate($value->getEndDate()),
                ];
            }

            return [
                'start' => $this->formatDate($value->getStartDate()),
            ];
        }

        return null;
    }

    private function formatDate(?CarbonInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
