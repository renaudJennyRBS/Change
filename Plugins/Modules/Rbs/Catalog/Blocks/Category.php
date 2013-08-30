<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Category
 */
class Category extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('categoryId', Property::TYPE_INTEGER, true);
		$parameters->addParameterMeta('sectionId', Property::TYPE_INTEGER, true);
		$parameters->addParameterMeta('conditionId', Property::TYPE_INTEGER, false);
		$parameters->addParameterMeta('itemsPerLine', Property::TYPE_INTEGER, true);
		$parameters->addParameterMeta('itemsPerPage', Property::TYPE_INTEGER, true);

		$parameters->setLayoutParameters($event->getBlockLayout());
		if ($parameters->getParameter('categoryId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Catalog\Documents\Category)
			{
				$parameters->setParameterValue('categoryId', $document->getId());
			}
			else
			{
				/* @var $page \Change\Presentation\Interfaces\Page */
				$page = $event->getParam('page');
				$section = $page->getSection();
				$parameters->setParameterValue('sectionId', $section->getId());
				$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Catalog_Category');
				$query->andPredicates($query->eq('section', $section->getId()));
				$document = $query->getFirstDocument();
				if ($document)
				{
					$parameters->setParameterValue('categoryId', $document->getId());
				}
			}
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$categoryId = $parameters->getParameter('categoryId');
		if ($categoryId)
		{
			/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
			$commerceServices = $event->getParam('commerceServices');
			$documentManager = $event->getDocumentServices()->getDocumentManager();

			/* @var $category \Rbs\Catalog\Documents\Category */
			$category = $documentManager->getDocumentInstance($categoryId);
			$attributes['category'] = $category;
			$attributes['title'] = $category->getTitle();

			//TODO: handle pagination
			$conditionId = $parameters->getParameter('conditionId');
			$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Catalog_Product');
			$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductCategorization', 'product');
			$subQuery->andPredicates(
				$subQuery->eq('category', $categoryId), $conditionId ? $subQuery->eq('condition', $conditionId) : $subQuery->isNull('condition'));
			$subQuery->addOrder('position', true);

			$priceManager = $taxManager = null;
			if ($commerceServices->getBillingArea())
			{
				$priceManager = $commerceServices->getPriceManager();
				if ($commerceServices->getZone())
				{
					$taxManager = $commerceServices->getTaxManager();
				}
			}

			$rows = array();
			$webStore = $category->getWebStore();
			$webStoreId = $webStore ? $webStore->getId() : 0;
			$productQuery = array('webStoreId' => $webStoreId, 'categoryId' => $categoryId);
			foreach ($query->getDocuments() as $product)
			{
				/* @var $product \Rbs\Catalog\Documents\Product */
				$url = $event->getUrlManager()->getCanonicalByDocument($product, null, $productQuery)->toString();
				$row = array('id' => $product->getId(), 'url' => $url, 'price' => null,'priceTTC' => null);
				$visual = $product->getFirstVisual();
				$row['visual'] = $visual ? $visual->getPath() : null;
				$price = $priceManager ? $priceManager->getPriceByProduct($product, $webStoreId) : null;
				if ($price)
				{
					$row['price'] = $price->getValue();
					if ($taxManager)
					{
						$taxApplications = $taxManager->getTaxByValue($price->getValue(), $price->getTaxCategories());
						$row['priceTTC'] = $taxManager->getValueWithTax($price->getValue(), $taxApplications);
					}
				}
				$rows[] = (new \Rbs\Catalog\Std\ProductItem($row))->setDocumentManager($documentManager);
			}
			$attributes['rows'] = $rows;

			$attributes['itemsPerLine'] = $parameters->getItemsPerLine();
			return 'category.twig';
		}
		return null;
	}
}