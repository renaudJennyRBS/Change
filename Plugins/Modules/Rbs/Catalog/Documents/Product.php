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
			$documentResult->addLink(new Link($um, $baseUrl . '/ProductCategorization/', 'productcategorizations'));
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

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('populatePathRule', array($this, 'onPopulatePathRule'), 5);
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
			$section = $this->getDocumentManager()->getDocumentInstance($sectionId, 'Rbs_Catalog_Section');
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
}