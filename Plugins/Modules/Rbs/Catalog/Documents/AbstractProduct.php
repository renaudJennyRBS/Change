<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;

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
				if (is_array(($attributeValues = $result->getProperty('attributeValues'))))
				{
					/* @var $product AbstractProduct */
					$product = $event->getDocument();
					$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($product->getDocumentServices());
					$expandedAttributeValues =  $attributeEngine->expandAttributeValues($product, $attributeValues, $event->getParam('urlManager'));
					$result->setProperty('attributeValues', $expandedAttributeValues);
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


}