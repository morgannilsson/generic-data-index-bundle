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

namespace Pimcore\Bundle\GenericDataIndexBundle\Tests\Unit\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Event;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Permission\AssetPermissions;
use Pimcore\Bundle\GenericDataIndexBundle\Permission\DataObjectPermissions;
use Pimcore\Bundle\GenericDataIndexBundle\Permission\DocumentPermissions;
use Pimcore\Bundle\GenericDataIndexBundle\Service\EventService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class EventServiceTest extends Unit
{
    public function testDispatchAssetSearchEvent(): void
    {
        $permissions = new AssetPermissions();
        $permissions->setView(true);
        $permissions->setRename(true);

        $event = new Event\Asset\PermissionEvent(
            new AssetSearchResultItem(),
            $permissions
        );

        $eventService = $this->getEventService($event);
        $returnedEvent = $eventService->dispatchAssetSearchEvent(
            new AssetSearchResultItem(),
            $permissions
        );

        $this->assertSame($event->getPermissions(), $returnedEvent->getPermissions());
        $this->assertTrue($returnedEvent->getPermissions()->isView());
        $this->assertTrue($returnedEvent->getPermissions()->isRename());
        $this->assertFalse($returnedEvent->getPermissions()->isList());
    }

    public function testDispatchDataObjectSearchEvent(): void
    {
        $permissions = new DataObjectPermissions();
        $permissions->setView(true);
        $permissions->setRename(true);

        $event = new Event\DataObject\PermissionEvent(
            new DataObjectSearchResultItem(),
            $permissions
        );

        $eventService = $this->getEventService($event);
        $returnedEvent = $eventService->dispatchDataObjectSearchEvent(
            new DataObjectSearchResultItem(),
            $permissions
        );

        $this->assertSame($event->getPermissions(), $returnedEvent->getPermissions());
        $this->assertTrue($returnedEvent->getPermissions()->isView());
        $this->assertTrue($returnedEvent->getPermissions()->isRename());
        $this->assertFalse($returnedEvent->getPermissions()->isList());
    }

    public function testDispatchDocumentSearchEvent(): void
    {
        $permissions = new DocumentPermissions();
        $permissions->setView(true);
        $permissions->setList(true);

        $event = new Event\Document\PermissionEvent(
            new DocumentSearchResultItem(),
            $permissions
        );

        $eventService = $this->getEventService($event);
        $returnedEvent = $eventService->dispatchDocumentSearchEvent(
            new DocumentSearchResultItem(),
            $permissions
        );

        $this->assertSame($event->getPermissions(), $returnedEvent->getPermissions());
        $this->assertTrue($returnedEvent->getPermissions()->isView());
        $this->asserttrue($returnedEvent->getPermissions()->isList());
        $this->assertFalse($returnedEvent->getPermissions()->isPublish());
    }

    public function testEventDispatcherCall(): void
    {
        $eventService = $this->getEventService(
            new Event\Asset\PermissionEvent(
                new AssetSearchResultItem(),
                new AssetPermissions()
            )
        );

        $eventService->dispatchAssetSearchEvent(
            new AssetSearchResultItem(),
            new AssetPermissions()
        );
    }

    private function getEventService(
        Event\Asset\PermissionEvent|Event\DataObject\PermissionEvent|Event\Document\PermissionEvent $event
    ): EventService {
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::exactly(1, $event),
        ]);

        return new EventService($eventDispatcher);
    }
}
