<?php
namespace Rbs\Catalog\Documents;

use Change\Collection\CollectionManager;
use Change\Documents\AbstractModel;
use Change\Documents\Events\Event;
use Rbs\Catalog\Std\AttributeEngine;

/**
 * @name \Rbs\Catalog\Documents\DeclinationGroup
 */
class DeclinationGroup extends \Compilation\Rbs\Catalog\Documents\DeclinationGroup
{
	/**
	 * @param AbstractModel $documentModel
	 */
	public function setDefaultValues(AbstractModel $documentModel)
	{
		parent::setDefaultValues($documentModel);
		$this->setAxesInfo(array());
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATED, array($this, 'onCreated'));
	}

	protected function onCreate()
	{
		if (!$this->getLabel() && $this->getDeclinedProduct())
		{
			$this->setLabel($this->getDeclinedProduct()->getLabel());
		}

		$this->initAxeInfo();
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('axeAttribute'))
		{
			$this->initAxeInfo();
		}

		if ($this->isPropertyModified('productMatrixInfo'))
		{
			$tm = $this->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$this->normalizeProductMatrix();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	protected function initAxeInfo()
	{
		$axesInfo = $this->getAxesInfo();
		if (count($axesInfo) === 0)
		{
			$axesInfo = array();
			$attrEngine = new AttributeEngine($this->getDocumentServices());
			$axeAttributes = $attrEngine->getAxeAttributes($this->getAxeAttribute());
			foreach ($axeAttributes as $axeAttribute)
			{
				$axe = array('id' => $axeAttribute->getId(), 'dv' => $attrEngine->getCollectionValues($axeAttribute));
				if (!is_array($axe['dv']))
				{
					$axe['dv'] = array();
				}
				$axe['cat'] = $axeAttribute->isVisibleFor('categorization');
				$axesInfo[] = $axe;
			}
			$this->setAxesInfo($axesInfo);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onCreated(Event $event)
	{
		/** @var $declinationGroup DeclinationGroup */
		$declinationGroup = $event->getDocument();
		$product = $declinationGroup->getDeclinedProduct();
		if ($product instanceof Product)
		{
			$product->setCategorizable(true);
			$product->setDeclinationGroup($declinationGroup);
			$product->update();
		}
	}

	protected function normalizeProductMatrix()
	{
		$pmi = $this->getProductMatrixInfo();
		$axesInfo = array_reduce($this->getAxesInfo(), function($r, $i) {
			$r[$i['id']] = $i;
			return $r;
		}, array());

		$added = array();
		$removed = array();

		foreach ($pmi as $entry)
		{
			if ($entry['id'] < 0)
			{
				/** @var $product Product */
				$axesValues = $this->buildAxeValues($entry, $pmi);
				$product = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
				$product->setLabel($this->getLabel() . ' - ' . $this->buildProductLabel($entry, $pmi, $axesInfo, 'label'));
				$product->getCurrentLocalization()->setTitle($product->getLabel());
				$product->setDeclinationGroup($this);
				$product->setAttributeValues($axesValues);
				$product->setCategorizable(($axesInfo[$entry['axeId']]['cat'] == true));
				$product->setNewSkuOnCreation($this->getNewSkuOnCreation() && !$entry['declination']);
				$product->create();

				$added[$entry['id']] = $product->getId();
			}
			elseif ($entry['id'] == 0 && isset($entry['removed']) && $entry['removed'] > 0)
			{
				$removed[$entry['removed']] = $entry['removed'];
				$product = $this->getDocumentManager()->getDocumentInstance($entry['removed']);
				if ($product instanceof Product)
				{
					$product->delete();
				}
			}
		}

		$productMatrixInfo = array();
		foreach ($pmi as $entry)
		{
			if ($entry['id'] == 0 || !isset($axesInfo[$entry['axeId']]))
			{
				continue;
			}

			if ($entry['id'] < 0)
			{
				if (!isset($added[$entry['id']]))
				{
					continue;
				}
				$entry['id'] = $added[$entry['id']];
			}

			if ($entry['parentId'] < 0)
			{
				if (!isset($added[$entry['parentId']]))
				{
					continue;
				}
				$entry['parentId'] = $added[$entry['parentId']];
			}
			$productMatrixInfo[] = $entry;
		}

		$this->setProductMatrixInfo($productMatrixInfo);
	}

	/**
	 * @param array $entry
	 * @param array $productMatrixInfo
	 * @param array $axesInfo
	 * @param string $type title|label|value
	 * @return string
	 */
	protected function buildProductLabel($entry, $productMatrixInfo, $axesInfo, $type = 'label')
	{
		$pe = $this->getProductMatrixEntryById($entry['parentId'], $productMatrixInfo);
		if ($pe)
		{
			$label = $this->buildProductLabel($pe, $productMatrixInfo, $axesInfo, $type) . ' - ';
		}
		else
		{
			$label = '';
		}

		$av = $entry['axeValue'];
		if ($type != 'value' && isset($axesInfo[$entry['axeId']]))
		{
			$ai = $axesInfo[$entry['axeId']];
			foreach ($ai['dv'] as $dv)
			{
				if ($dv['value'] == $av)
				{
					$av = $dv[$type];
					break;
				}
			}
		}
		return $label . $av;
	}

	/**
	 * @param integer $id
	 * @param array $productMatrixInfo
	 * @return array|null
	 */
	protected function getProductMatrixEntryById($id, $productMatrixInfo)
	{
		foreach ($productMatrixInfo as $entry)
		{
			if ($entry['id'] == $id)
			{
				return $entry;
			}
		}
		return null;
	}

	/**
	 * @param array $entry
	 * @param array $productMatrixInfo
	 * @return array
	 */
	protected function buildAxeValues($entry, $productMatrixInfo)
	{
		$axeValues = array();
		$axeValues[] = array('id' => $entry['axeId'], 'value' => $entry['axeValue']);
		$parentEntry = $this->getProductMatrixEntryById($entry['parentId'], $productMatrixInfo);
		if ($parentEntry)
		{
			$axeValues = array_merge($this->buildAxeValues($parentEntry, $productMatrixInfo), $axeValues);
		}
		return $axeValues;
	}

	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$axesDefinition = $this->buildAxesDefinition();
		$documentResult->setProperty('axesDefinition', array_values($axesDefinition));
	}

	/**
	 * @return array
	 */
	protected function buildAxesDefinition()
	{
		$axesDefinition = array();

		$attrEngine = new AttributeEngine($this->getDocumentServices());
		foreach ($this->getAxesInfo() as $axeInfo)
		{
			$axeAttribute = $this->getDocumentManager()->getDocumentInstance($axeInfo['id']);
			if (!($axeAttribute instanceof Attribute))
			{
				continue;
			}
			$def = $attrEngine->buildAttributeDefinition($axeAttribute);
			if ($def)
			{
				$axesDefinition[$axeInfo['id']] = $def;
			}
		}
		return $axesDefinition;
	}
}