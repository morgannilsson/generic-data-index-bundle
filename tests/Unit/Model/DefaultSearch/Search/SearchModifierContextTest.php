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

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\Model\DefaultSearch\Search;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\DefaultSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\DefaultSearch\Modifier\SearchModifierContext;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;

/**
 * @internal
 */
final class SearchModifierContextTest extends Unit
{
    public function testGetSearch(): void
    {
        $searchMock = $this->makeEmpty(DefaultSearchInterface::class);
        $assetSearchMock = $this->makeEmpty(SearchInterface::class);
        $searchModifierContext = new SearchModifierContext($searchMock, $assetSearchMock);

        $this->assertSame($searchMock, $searchModifierContext->getSearch());
        $this->assertSame($assetSearchMock, $searchModifierContext->getOriginalSearch());
    }
}
