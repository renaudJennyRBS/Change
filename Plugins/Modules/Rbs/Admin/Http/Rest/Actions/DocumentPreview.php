<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\ArrayResult;

use Change\Presentation\PresentationServices;
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
		$documentManager = $event->getDocumentServices()->getDocumentManager();
		$document = $documentManager->getDocumentInstance($id);
		$modelName = $document->getDocumentModelName();
		$model = $document->getDocumentModel();

		if (! $document)
		{
			return;
		}

		if ($document instanceof Publishable)
		{
			$title = $document->getTitle();
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

		/* @var $as \Change\Application\ApplicationServices */
		$as = $event->getApplicationServices();
		$tplFile = $as->getApplication()->getWorkspace()->pluginsModulesPath()
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
				$c = new PropertyConverter($document, $property, $urlManager);
				$properties[$name] = $c->getRestValue();
			}

			$ps = new PresentationServices($event->getApplicationServices());
			$docPreview['content'] = $ps->getTemplateManager()->renderTemplateFile($tplFile, $properties);
		}
		else
		{
			$docPreview['content'] = '';
		}

		$result->setArray($docPreview);
		$event->setResult($result);
	}
}