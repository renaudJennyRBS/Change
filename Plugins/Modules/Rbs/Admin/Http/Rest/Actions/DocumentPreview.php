<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\ArrayResult;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\DocumentPreview
 */
class DocumentPreview
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$result = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);


		$id = $event->getRequest()->getQuery('id');
		if (! $id)
		{
			return;
		}

		/* @var $documentManager \Change\Documents\DocumentManager */
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$document = $documentManager->getDocumentInstance($id);
		$modelName = $document->getDocumentModelName();
		$model = $document->getDocumentModel();

		if (! $document)
		{
			return;
		}

		if ($document instanceof Publishable)
		{
			$title = $document->getDocumentModel()->getPropertyValue($document, 'title');
		}
		elseif ($document instanceof Editable)
		{
			$title = $document->getLabel();
		}
		else
		{
			$title = $modelName . ', ' . $document->getId();
		}


		$docPreview = [
			'title' =>  $title
		];

		/* @var $as \Change\Services\ApplicationServices */
		$as = $event->getApplicationServices();
		$tplFile = $event->getApplication()->getWorkspace()->pluginsModulesPath()
			. DIRECTORY_SEPARATOR . $model->getVendorName()
			. DIRECTORY_SEPARATOR . $model->getShortModuleName()
			. DIRECTORY_SEPARATOR . 'Admin'
			. DIRECTORY_SEPARATOR . 'Assets'
			. DIRECTORY_SEPARATOR . $model->getShortName()
			. DIRECTORY_SEPARATOR . 'popover-preview.twig'
		;
		if (is_readable($tplFile))
		{
			$urlManager = $event->getUrlManager();

			$properties = array();
			foreach ($model->getProperties() as $name => $property)
			{
				/* @var $property \Change\Documents\Property */
				$c = new PropertyConverter($document, $property, $documentManager, $urlManager);
				$properties[$name] = $c->getRestValue();
			}

			$templateManager = $as->getTemplateManager();
			$docPreview['content'] = $templateManager->renderTemplateFile($tplFile, $properties);
		}
		else
		{
			$docPreview['content'] = '';
		}

		$result->setArray($docPreview);
		$event->setResult($result);
	}
}