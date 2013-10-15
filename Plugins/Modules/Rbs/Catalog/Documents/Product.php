<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
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

	/**
	 * @param DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
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
			$image = $this->getFirstVisual();
			if ($image)
			{
				$documentResult->addLink(array('href' => $image->getPublicURL(512, 512), 'rel' => 'adminthumbnail'));
			}
		}

		if (is_array(($attributeValues = $documentResult->getProperty('attributeValues'))))
		{
			/* @var $product Product */
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$expandedAttributeValues =  $attributeEngine->expandAttributeValues($this, $attributeValues, $documentResult->getUrlManager());
			$documentResult->setProperty('attributeValues', $expandedAttributeValues);
		}
	}

	/**
	 * @param DocumentLink $documentLink
	 * @param array $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);

		$image = $this->getFirstVisual();
		if ($image)
		{
			$documentLink->setProperty('adminthumbnail',  $image->getPublicURL(512, 512));
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
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onCreated'), 5);
		$eventManager->attach('getMetaSubstitutions', array($this, 'onGetMetaSubstitutions'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onPopulatePathRule(\Change\Documents\Events\Event $event)
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
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onCreated(\Change\Documents\Events\Event $event)
	{
		// Section product list synchronization.
		if ($this->getPublicationSections()->count())
		{
			$this->synchronizeSectionDocumentLists();
		}
	}

	protected function onCreate()
	{
		if ($this->isPropertyModified('attributeValues'))
		{
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$normalizedAttributeValues =  $attributeEngine->normalizeAttributeValues($this, $this->getAttributeValues());
			$this->setAttributeValues($normalizedAttributeValues);
		}
		if ($this->getNewSkuOnCreation())
		{
			$tm = $this->getApplicationServices()->getTransactionManager();

			/* @var $sku \Rbs\Stock\Documents\Sku */
			$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
			try
			{
				$tm->begin();
				$sku->setCode($this->buildSkuCodeFromLabel());
				$sku->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$this->setSku($sku);
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onGetMetaSubstitutions(\Change\Documents\Events\Event $event)
	{
		$variables = $event->getParam('variables');
		$substitutions = [];
		foreach ($variables as $variable)
		{
			switch ($variable)
			{
				case 'document.title':
					$substitutions['document.title'] = $this->getCurrentLocalization()->getTitle();
					break;
				case 'document.description':
					//TODO: cleanup the raw text from markdown
					$description = \Change\Stdlib\String::shorten($this->getCurrentLocalization()->getDescription()->getRawText(), 80);
					$substitutions['document.description'] = $description;
					break;
				case 'document.brand':
					$substitutions['document.brand'] = $this->getBrand()->getCurrentLocalization()->getTitle();
					break;
			}
		}
		$event->setParam('substitutions', $substitutions);
	}

	/**
	 * @return string
	 */
	protected function buildSkuCodeFromLabel()
	{
		$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$retry = 0;
		$baseCode = String::subString(preg_replace('/[^a-zA-Z0-9]+/', '-', String::stripAccents(String::toUpper($this->getLabel()))), 0, 80);
		$skuCode = $baseCode;
		$sku = $cs->getStockManager()->getSkuByCode($skuCode);
		while ($sku && $retry++ < 100)
		{
			$skuCode = String::subString($baseCode, 0, 73) . '-' . String::toUpper(String::random(6, false));
			$sku = $cs->getStockManager()->getSkuByCode($skuCode);
		}
		return $skuCode;
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('attributeValues'))
		{
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$normalizedAttributeValues =  $attributeEngine->normalizeAttributeValues($this, $this->getAttributeValues());

			//DB Stat
			$attributeEngine->setAttributeValues($this, $normalizedAttributeValues);

			$this->setAttributeValues($normalizedAttributeValues);
		}

		// Section product list synchronization.
		if ($this->isPropertyModified('publicationSections'))
		{
			$this->synchronizeSectionDocumentLists();
		}
	}

	protected function synchronizeSectionDocumentLists()
	{
		$ds = $this->getDocumentServices();
		$dm = $this->getDocumentManager();

		if ($this->getPublicationSectionsCount())
		{
			$dqb1 = new \Change\Documents\Query\Query($ds, 'Rbs_Catalog_SectionProductList');
			$pb1 = $dqb1->getPredicateBuilder();
			$dqb1->andPredicates($pb1->in('synchronizedSection', $this->getPublicationSectionsIds()));
			$requiredListIds = $dqb1->getDocuments()->ids();
		}
		else
		{
			$requiredListIds = array();
		}

		$dqb2 = new \Change\Documents\Query\Query($ds, 'Rbs_Catalog_SectionProductList');
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
			$dqb3 = new \Change\Documents\Query\Query($ds, 'Rbs_Catalog_ProductListItem');
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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param array $parameters
	 * @return \Rbs\Catalog\Std\ProductCartLineConfig
	 */
	public function getCartLineConfig(\Rbs\Commerce\Services\CommerceServices $commerceServices, array $parameters)
	{
		$cartLineConfig = new \Rbs\Catalog\Std\ProductCartLineConfig($this);
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
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param integer $webStoreId
	 * @return \Rbs\Catalog\Std\ProductPresentation
	 */
	public function getPresentation(\Rbs\Commerce\Services\CommerceServices $commerceServices, $webStoreId)
	{
		return new \Rbs\Catalog\Std\ProductPresentation($commerceServices, $this, $webStoreId);
	}

	/**
	 * @param string $crossSellingType
	 * @return \Rbs\Catalog\Documents\CrossSellingProductList|null
	 */
	public function getCrossSellingListByType($crossSellingType)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Catalog_CrossSellingProductList');
		$query->andPredicates($query->eq('product', $this->getId()), $query->eq('crossSellingType', $crossSellingType));
		return $query->getFirstDocument();
	}
}