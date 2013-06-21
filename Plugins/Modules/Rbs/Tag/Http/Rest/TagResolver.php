<?php
namespace Rbs\Tag\Http\Rest;

use \Change\Http\Event;
use \Change\Http\Rest\Request;
use Rbs\Tag\Http\Rest\Actions\GetDocumentTags;
use Rbs\Tag\Http\Rest\Actions\SetDocumentTags;
use Rbs\Tag\Http\Rest\Actions\GetTaggedDocuments;

class TagResolver {

	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$pathParts = $event->getParam('pathParts');

		if ($event->getAction() || ! $event->getParam('isDirectory') || $pathParts[0] !== 'resources')
		{
			return;
		}

		$nbParts = count($pathParts);
		$modelName = $pathParts[1] . '_' . $pathParts[2] . '_' .$pathParts[3];
		$method = $event->getRequest()->getMethod();

		if ($modelName === 'Rbs_Tag_Tag' && $nbParts === 7)
		{
			if ($pathParts[$nbParts-1] !== 'documents')
			{
				return;
			}

			// Check if the Tag document really exists.
			$tagId = intval($pathParts[4]);
			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			$tag = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($tagId, $model);
			if ($tag === null)
			{
				return;
			}

			$event->setParam('tagId', $tagId);
			$event->setParam('requestedModelName', $event->getRequest()->getQuery('model'));

			if ($method === Request::METHOD_GET)
			{
				$action = function ($event)
				{
					$action = new GetTaggedDocuments();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}


		}
		else if (($nbParts === 6 || $nbParts === 7) && $pathParts[$nbParts-1] === 'tags')
		{
			// Check if the model really exists.
			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if ($model === null)
			{
				return;
			}

			// Check if the document really exists.
			$docId = intval($pathParts[4]);
			$doc = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($docId, $model);
			if ($doc === null)
			{
				return;
			}

			$event->setParam('docId', $docId);
			$event->setParam('modelName', $modelName);

			if ($method === Request::METHOD_GET)
			{
				$action = function ($event)
				{
					$action = new GetDocumentTags();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}

			if ($method === Request::METHOD_POST)
			{
				$event->setParam('tags', $event->getRequest()->getPost()->get("ids"));
				$action = function ($event)
				{
					$action = new SetDocumentTags();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}


			/*
						if ($method === Request::METHOD_POST)
						{
							$privilege = $modelName . '.create';
							$this->resolver->setAuthorisation($event, $modelName, $privilege);

							$action = function ($event)
							{
								$action = new CreateLocalizedDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === Request::METHOD_PUT)
						{
							$privilege = $modelName . '.updateLocalized';
							$this->resolver->setAuthorisation($event, $documentId, $privilege);

							$action = function ($event)
							{
								$action = new UpdateLocalizedDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}

						if ($method === Request::METHOD_DELETE)
						{
							$privilege = $modelName . '.deleteLocalized';
							$this->resolver->setAuthorisation($event, $documentId, $privilege);

							$action = function ($event)
							{
								$action = new DeleteLocalizedDocument();
								$action->execute($event);
							};
							$event->setAction($action);
							return;
						}
			*/
		}




	}
}