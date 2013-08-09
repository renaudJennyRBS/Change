<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;
use Change\Stdlib\String;
use Rbs\Commerce\Services\CommerceServices;

/**
 * @name \Rbs\Catalog\Documents\AbstractProduct
 */
class AbstractProduct extends \Compilation\Rbs\Catalog\Documents\AbstractProduct
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
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentResult)
			{
				/* @var $product \Rbs\Catalog\Documents\AbstractProduct */
				$product = $event->getDocument();
				$selfLinks = $result->getRelLink('self');
				$selfLink = array_shift($selfLinks);
				if ($selfLink instanceof Link)
				{
					$pathParts = explode('/', $selfLink->getPathInfo());
					array_pop($pathParts);
					$link = new Link($event->getParam('urlManager'), implode('/', $pathParts) . '/ProductCategorization/', 'productcategorizations');
					$result->addLink($link);
					$link = new Link($event->getParam('urlManager'), implode('/', $pathParts) . '/Prices/', 'prices');
					$result->addLink($link);
					$image = $product->getFirstVisual();
					if ($image)
					{
						$link = array('href' => $image->getPublicURL(512, 512), 'rel' => 'adminthumbnail');
						$result->addLink($link);
					}
				}
				if (is_array(($attributeValues = $result->getProperty('attributeValues'))))
				{
					/* @var $product AbstractProduct */
					$product = $event->getDocument();
					$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($product->getDocumentServices());
					$expandedAttributeValues =  $attributeEngine->expandAttributeValues($product, $attributeValues, $event->getParam('urlManager'));
					$result->setProperty('attributeValues', $expandedAttributeValues);
				}
			}
			elseif ($result instanceof DocumentLink)
			{
				/* @var $product \Rbs\Catalog\Documents\AbstractProduct */
				$product = $event->getDocument();
				$image = $product->getFirstVisual();
				if ($image)
				{
					$result->setProperty('adminthumbnail',  $image->getPublicURL(512, 512));
				}
				$link = $result->getProperty('sku');
				if ($link instanceof DocumentLink)
				{
					$link->setProperty('code', $link->getDocument()->getCode());
				}

			}
		}, 5);
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
		$cs = new CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$retry = 0;
		$baseCode = String::subString(preg_replace('/\s+/', '-', String::stripAccents(String::toUpper($this->getLabel()))), 0, 80);
		$skuCode = $baseCode;
		$sku = $cs->getStockManager()->getSkuByCode($skuCode);
		while($sku && $retry < 100)
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
}