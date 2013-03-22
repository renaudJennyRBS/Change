<?php
namespace Change\Documents\Events;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Localizable;

/**
 * @name \Change\Documents\Events\DeleteListener
 */
class DeleteListener
{

	/**
	 * @param DocumentEvent $event
	 */
	public function onDelete($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$document = $event->getDocument();
			if (!$document->getDocumentModel()->isStateless())
			{
				$backupData = $this->generateBackupData($document);
				$event->setParam('backupData', count($backupData) ? $backupData : null);
			}
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @return array
	 */
	protected function generateBackupData($document)
	{
		$datas = array();
		$datas['metas'] = $document->getMetas();
		$model = $document->getDocumentModel();

		if ($model->useTree() && $document->getTreeName())
		{
			$node = $document->getDocumentServices()->getTreeManager()->getNodeByDocument($document);
			if ($node)
			{
				$datas['treeName'] = array($document->getTreeName(), $node->getParentId());
			}
		}

		$localized = array();
		foreach ($model->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized())
			{
				$localized[$propertyName] = $property;
				continue;
			}
			$val = $property->getValue($document);
			if ($val instanceof AbstractDocument)
			{
				$datas[$propertyName] = array($val->getId(), $val->getDocumentModelName());
			}
			elseif ($val instanceof \DateTime)
			{
				$datas[$propertyName] = $val->format('c');
			}
			elseif (is_array($val))
			{
				foreach ($val as $doc)
				{
					if ($doc instanceof AbstractDocument)
					{
						$datas[$propertyName][] = array($doc->getId(), $doc->getDocumentModelName());
					}
				}
			}
			else
			{
				$datas[$propertyName] = $val;
			}
		}

		$dm = $document->getDocumentServices()->getDocumentManager();

		if (count($localized) && $document instanceof Localizable)
		{
			$datas['LCID'] = array();
			foreach ($document->getLocalizableFunctions()->getLCIDArray() as $LCID)
			{
				$dm->pushLCID($LCID);
				$datas['LCID'][$LCID] = array();
				foreach ($localized as $propertyName => $property)
				{
					/* @var $property \Change\Documents\Property */
					$val = $property->getValue($document);
					if ($val instanceof \DateTime)
					{
						$datas[$propertyName] = $val->format('c');
					}
					else
					{
						$datas['LCID'][$LCID][$propertyName] = $val;
					}
				}
				$dm->popLCID();
			}
		}
		return $datas;
	}
}