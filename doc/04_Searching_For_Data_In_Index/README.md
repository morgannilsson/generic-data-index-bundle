# Searching For Data In Index

The Generic Data Index bundle adds standardized and flexible services to search data from the search indices.

Each search is based on a search service (depending on the element type) and a search model defining the search query. The search models can be created with the [SearchProviderInterface](https://github.com/pimcore/generic-data-index-bundle/blob/1.x/src/Service/Search/SearchService/SearchProviderInterface.php)

The regular way to search for assets, data objects or documents is to use the related search service.

## Asset Search Service

### Example usage

- Example: This example loads all assets from the root folder (parent ID 1) and orders them by their full path.
```php
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;

public function searchAction(SearchProviderInterface $searchProvider, AssetSearchServiceInterface $asserSearchService)
{
    $assetSearch = $searchProvider->createAssetSearch()
                ->addModifier(new ParentIdFilter(1))
                ->addModifier(new OrderByFullPath())
                ->setPageSize(50)
                ->setPage(1);

   $searchResult = $asserSearchService->search($assetSearch);
}
```

## Data Object Search Service

- Example: This example loads all data objects from the root folder (parent ID 1) with a specific class definition and orders them by their full path.
```php
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\DataObject\DataObjectSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;

public function searchAction(SearchProviderInterface $searchProvider, DataObjectSearchServiceInterface $dataObjectSearchService)
{
    $dataObjectSearch = $searchProvider->createDataObjectSearch()
                ->addModifier(new ParentIdFilter(1))
                ->addModifier(new OrderByFullPath())
                ->setClassDefinition($carClassDefinition)
                ->setPageSize(50)
                ->setPage(1);

    $searchResult = $dataObjectSearchService->search($dataObjectSearch);
}
```


## Document Search Service

- Example: This example loads all documents from the root folder (parent ID 1) and orders them by their full path.
```php
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Document\DocumentSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;

public function searchAction(SearchProviderInterface $searchProvider, DocumentSearchServiceInterface $documentSearchService)
{
    $documentSearch = $searchProvider->createDocumentSearch()
                ->addModifier(new ParentIdFilter(1))
                ->addModifier(new OrderByFullPath())
                ->setPageSize(50)
                ->setPage(1);

    $searchResult = $documentSearchService->search($documentSearch);
}
```

## Element Search Service

The element search service can be used to search for assets, data objects and documents at the same time.

**Hint:** the element search does not support the calculation of the `hasChildren` attributes. This means that the `hasChildren` attribute will always be `false` for all elements.

- Example: This example loads all elements which are required by the asset ID 123 and orders them by their full path.
```php
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Dependency\RequiredByFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;

public function searchAction(SearchProviderInterface $searchProvider, ElementSearchServiceInterface $elementSearchService)
{
    $elementSearch = $searchProvider->createElementSearch()
                ->addModifier(new RequiredByFilter(123, ElementType::ASSET))
                ->addModifier(new OrderByFullPath())
                ->setPageSize(50)
                ->setPage(1);

    $searchResult = $elementSearchService->search($elementSearch);
}
```

## Search Modifiers

To influence the data which gets fetched its possible to use so-called search modifiers.
Find out details about search modifiers in the [search modifiers documentation](05_Search_Modifiers/README.md). There you will also find information on how to create your own custom search modifiers.

## Retrieve IDs only instead of full objects

If you only want to retrieve your search results as list of IDs for your `AssetSearch`, `DataObjectSearch` or `DocumentSearch` you can use the `SearchResultIdListServiceInterface` to retrieve the IDs only.

```php
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Tree\ParentIdFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchResultIdListServiceInterface;

public function searchAction(SearchProviderInterface $searchProvider, SearchResultIdListServiceInterface $searchResultIdListService)
{
    $dataObjectSearch = $searchProvider->createDataObjectSearch()
                ->addModifier(new ParentIdFilter(1))
                ->addModifier(new OrderByFullPath())
                ->setPageSize(50)
                ->setPage(1);

    $allIds = $searchResultIdListService->getAllIds($dataObjectSearch); // returns an array of IDs for the full search result without pagination
    $idsOnPage = $searchResultIdListService->getIdsForCurrentPage($dataObjectSearch); // returns an array of IDs for the current page
}
```

## Default Search Models
The search services mentioned above offer a flexible and structured way to search for assets, data objects and documents. Nevertheless, if there are requirements which are not covered by the search services it might be needed to develop your own customized search queries. Default search models offer a streamlined way for executing such customized search queries. They are also used by the search services internally to create the executed search queries.

Take a look at the dedicated [Default search models documentation](06_Default_Search_Models/README.md) to find out more.

## Permissions
The search service respects the user permissions and user workspaces in connection to his roles.

Details about permissions and workspaces can be found in the [permissions and workspaces documentation](08_Permissions_Workspaces/README.md).

## Pimcore Query Language (PQL)
The [Pimcore Query Language (PQL)](./09_Pimcore_Query_Language/README.md) is a query language which can be used to provide the user a flexible way to define search criteria for data objects, assets and documents.


## Debug Search Queries
To debug the Search queries which are created by the search service, it is possible to use the following magic parameter in the URL (when debug mode is enabled):

| Get Parameter             | Description                                                                                                                                                                                                          |
|---------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `debug-search-queries` | Will change the response of the called URL and outputs all executed search queries. <br/><br/>It is possible to influence the output verbosity with the value of the parameter (1=normal, 2=verbose, 3=very verbose) |
