<?php
namespace Rbs\Catalog\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\Link;
use Change\Stdlib\String;

/**
 * @name \Rbs\Catalog\Documents\Product
 */
class Product extends \Compilation\Rbs\Catalog\Documents\Product implements \Rbs\Commerce\Interfaces\CartLineConfigCapable
{
	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getFirstVisual()
	{
		$visuals = $this->getVisuals();
		return $visuals->count() ? $visuals[0] : null;
	}

	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $document Product */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{

			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof Link)
			{
				$pathParts = explode('/', $selfLink->getPathInfo());
				array_pop($pathParts);
				$baseUrl = implode('/', $pathParts);
				$documentResult->addLink(new Link($um, $baseUrl . '/ProductListItems/', 'productListItems'));
				$documentResult->addLink(new Link($um, $baseUrl . '/Prices/', 'prices'));
				$image = $document->getFirstVisual();
				if ($image)
				{
					$documentResult->addLink(array('href' => $image->getPublicURL(512, 512), 'rel' => 'adminthumbnail'));
				}
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$image = $document->getFirstVisual();
			if ($image)
			{
				$restResult->setProperty('adminthumbnail', $image->getPublicURL(512, 512));
			}
		}
	}

	protected $ignoredPropertiesForRestEvents = array('model', 'declinationGroup', 'declination');

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('populatePathRule', array($this, 'onPopulatePathRule'), 5);
		$eventManager->attach(Event::EVENT_CREATED, array($this, 'onCreated'), 5);
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'onDefaultCreate'), 10);
		$eventManager->attach(Event::EVENT_UPDATE, array($this, 'onDefaultUpdate'), 10);
	}

	/**
	 * @param Event $event
	 */
	public function onPopulatePathRule(Event $event)
	{
		$pathRule = $event->getParam('pathRule');
		$product = $event->getDocument();
		if ($pathRule instanceof \Change\Http\Web\PathRule && $product instanceof Product)
		{
			$sectionId = $pathRule->getSectionId();
			$section = $this->getDocumentManager()->getDocumentInstance($sectionId, 'Rbs_Website_Section');
			if ($section)
			{
				/* @var $section \Rbs\Website\Documents\Section */
				$sectionPath = ($section->getPathPart() ? $section->getPathPart() . '.' : '') . $section->getId();
				$path = $pathRule->normalizePath(array(
					$sectionPath,
					$product->getCurrentLocalization()->getTitle() . '.' . $product->getId() . '.html'
				));
				$pathRule->setRelativePath($path);
			}
			else
			{
				$path = $pathRule->normalizePath(
					$product->getCurrentLocalization()->getTitle() . '.' . $product->getId() . '.html'
				);
				$pathRule->setRelativePath($path);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onCreated(Event $event)
	{
		// Section product list synchronization.
		if ($this->getPublicationSections()->count())
		{
			$this->synchronizeSectionDocumentLists();
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultCreate(Event $event)
	{
		/** @var $product Product */
		$product = $event->getDocument();
		$cs = $event->getServices('commerceServices');

		if ($product->isPropertyModified('attributeValues') && $cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$normalizedAttributeValues = $cs->getAttributeManager()->normalizeRestAttributeValues($product, $product->getCurrentLocalization()->getAttributeValues());
			$product->getCurrentLocalization()->setAttributeValues($normalizedAttributeValues);
		}

		if ($product->getNewSkuOnCreation())
		{
			/* @var $sku \Rbs\Stock\Documents\Sku */
			$sku = $product->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
			$sku->setCode($product->buildSkuCodeFromLabel($cs));
			$sku->save();
			$product->setSku($sku);
		}
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @return string
	 */
	protected function buildSkuCodeFromLabel(\Rbs\Commerce\CommerceServices $commerceServices = null)
	{
		$retry = 0;
		$baseCode = String::subString(preg_replace('/[^a-zA-Z0-9]+/', '-',
			String::stripAccents(String::toUpper($this->getLabel()))), 0, 80);
		$skuCode = $baseCode;
		if ($commerceServices)
		{
			$sku = $commerceServices->getStockManager()->getSkuByCode($skuCode);
			while ($sku && $retry++ < 100)
			{
				$skuCode = String::subString($baseCode, 0, 73) . '-' . String::toUpper(String::random(6, false));
				$sku = $commerceServices->getStockManager()->getSkuByCode($skuCode);
			}
		}
		return $skuCode;
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdate(Event $event)
	{
		/** @var $product Product */
		$product = $event->getDocument();
		if ($product->isPropertyModified('attributeValues'))
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$attributeValues = $product->getCurrentLocalization()->getAttributeValues();
				$normalizedAttributeValues = $cs->getAttributeManager()->normalizeRestAttributeValues($product, $attributeValues);
				$product->getCurrentLocalization()->setAttributeValues($normalizedAttributeValues);
				$cs->getAttributeManager()->setAttributeValues($product, $normalizedAttributeValues);
			}
		}

		// Section product list synchronization.
		if ($product->isPropertyModified('publicationSections'))
		{
			$product->synchronizeSectionDocumentLists();
		}
	}

	protected function synchronizeSectionDocumentLists()
	{
		$dm = $this->getDocumentManager();

		if ($this->getPublicationSectionsCount())
		{
			$dqb1 = $dm->getNewQuery('Rbs_Catalog_SectionProductList');
			$pb1 = $dqb1->getPredicateBuilder();
			$dqb1->andPredicates($pb1->in('synchronizedSection', $this->getPublicationSectionsIds()));
			$requiredListIds = $dqb1->getDocuments()->ids();
		}
		else
		{
			$requiredListIds = array();
		}

		$dqb2 = $dm->getNewQuery('Rbs_Catalog_SectionProductList');
		$d2qb2 = $dqb2->getModelBuilder('Rbs_Catalog_ProductListItem', 'productList');
		$pb2 = $d2qb2->getPredicateBuilder();
		$d2qb2->andPredicates($pb2->eq('product', $this));
		$existingListIds = $dqb2->getDocuments()->ids();

		// Item creation.
		$listIds = array_diff($requiredListIds, $existingListIds);
		foreach ($listIds as $listId)
		{
			/* @var $list \Rbs\Catalog\Documents\SectionProductList */
			$list = $dm->getDocumentInstance($listId);

			/* @var $item \Rbs\Catalog\Documents\ProductListItem */
			$item = $dm->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductListItem');
			$item->setProductList($list);
			$item->setProduct($this);
			$item->save();
		}

		// Item deletion.
		$listIds = array_diff($existingListIds, $requiredListIds);
		if (count($listIds))
		{
			$dqb3 = $dm->getNewQuery('Rbs_Catalog_ProductListItem');
			$pb3 = $dqb3->getPredicateBuilder();
			$dqb3->andPredicates($pb3->in('productList', $listIds), $pb3->eq('product', $this));
			foreach ($dqb3->getDocuments() as $item)
			{
				$item->delete();
			}
		}
	}

	/**
	 * @var boolean
	 */
	protected $newSkuOnCreation = true;

	/**
	 * @return boolean
	 */
	public function getNewSkuOnCreation()
	{
		return $this->newSkuOnCreation;
	}

	/**
	 * @param boolean $newSkuOnCreation
	 * @return $this
	 */
	public function setNewSkuOnCreation($newSkuOnCreation)
	{
		$this->newSkuOnCreation = $newSkuOnCreation;
		return $this;
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param array $parameters
	 * @return \Rbs\Catalog\Product\ProductCartLineConfig
	 */
	public function getCartLineConfig(\Rbs\Commerce\CommerceServices $commerceServices, array $parameters)
	{
		$cartLineConfig = new \Rbs\Catalog\Product\ProductCartLineConfig($this);
		$options = isset($parameters['options']) ? $parameters['options'] : array();
		if (is_array($options))
		{
			foreach ($options as $optName => $optValue)
			{
				$cartLineConfig->setOption($optName, $optValue);
			}
		}
		return $cartLineConfig;
	}

	/**
	 * @param \Rbs\Commerce\CommerceServices $commerceServices
	 * @param integer $webStoreId
	 * @return \Rbs\Catalog\Product\ProductPresentation
	 */
	public function getPresentation(\Rbs\Commerce\CommerceServices $commerceServices, $webStoreId)
	{
		return new \Rbs\Catalog\Product\ProductPresentation($commerceServices, $this, $webStoreId);
	}

	/**
	 * @param string $crossSellingType
	 * @return \Rbs\Catalog\Documents\CrossSellingProductList|null
	 */
	public function getCrossSellingListByType($crossSellingType)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_CrossSellingProductList');
		$query->andPredicates($query->eq('product', $this->getId()), $query->eq('crossSellingType', $crossSellingType));
		return $query->getFirstDocument();
	}

	/**
	 * @return Boolean
	 */
	public function hasVariants()
	{
		return !($this->getVariant()) && $this->getVariantGroup();
	}
}