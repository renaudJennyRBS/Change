<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\Request;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\Http\Rest\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (\Change\Events\Event $event)
		{
			$controller = $event->getTarget();
			if ($controller instanceof \Change\Http\Rest\Controller)
			{
				$resolver = $controller->getActionResolver();
				if ($resolver instanceof \Change\Http\Rest\Resolver)
				{
					$resolver->addResolverClasses('admin', '\Rbs\Admin\Http\Rest\AdminResolver');
					$resolver->addResolverClasses('plugins', '\Rbs\Plugins\Http\Rest\PluginsResolver');
					$resolver->addResolverClasses('user', '\Rbs\User\Http\Rest\UserResolver');
				}
			}
		};
		$events->attach(Event::EVENT_REQUEST, $callback, 1);
		$events->attach(Event::EVENT_ACTION, array($this, 'registerActions'), 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param Event $event
	 */
	public function registerActions(Event $event)
	{
		if (!$event->getAction())
		{
			$relativePath = $event->getParam('pathInfo');

			if (preg_match('#^resources/Rbs/Media/Image/([0-9]+)/resize$#', $relativePath, $matches))
			{
				$event->setParam('documentId', intval($matches[1]));
				$event->setAction(function ($event)
				{
					(new \Rbs\Media\Http\Rest\Actions\Resize())->resize($event);
				});
				return;
			}
			else if (preg_match('#^resources/Rbs/User/User/([0-9]+)/Profiles/$#', $relativePath, $matches))
			{
				$event->setParam('documentId', intval($matches[1]));
				$event->setAction(function ($event)
				{
					(new \Rbs\User\Http\Rest\Actions\Profiles())->execute($event);
				});
				return;
			}
			else if (preg_match('#^resources/Rbs/Workflow/Task/([0-9]+)/(execute|executeAll)$#', $relativePath, $matches))
			{
				$task = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($matches[1]);
				if ($task instanceof \Rbs\Workflow\Documents\Task)
				{
					$event->setParam('modelName', $task->getDocumentModelName());
					$event->setParam('documentId', $task->getId());
					$event->setParam('executeAll', ($matches[2] == 'executeAll'));
					$event->setParam('task', $task);
					$event->setAction(function ($event)
					{
						(new \Rbs\Workflow\Http\Rest\Actions\ExecuteTask())->executeTask($event);
					});

					$event->setAuthorization(function ($event)
					{
						return (new \Rbs\Workflow\Http\Rest\Actions\ExecuteTask())->canExecuteTask($event);
					});
				}
				return;
			}
			else if (preg_match('#^resources/Rbs/Tag/Tag/([0-9]+)/documents#', $relativePath, $matches))
			{
				if ($event->getParam('isDirectory'))
				{
					$method = $event->getRequest()->getMethod();
					$tagId = intval($matches[1]);
					$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Tag_Tag');
					$tag = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($tagId, $model);
					if ($tag !== null)
					{
						$event->setParam('tagId', $tagId);
						$event->setParam('requestedModelName', $event->getRequest()->getQuery('model'));
						if ($method === Request::METHOD_GET)
						{
							$event->setAction(function ($event)
							{
								(new \Rbs\Tag\Http\Rest\Actions\GetTaggedDocuments())->execute($event);
							});
						}
					}
				}
				return;
			}
			else if (preg_match('#^resources/([A-Z][a-z0-9]+)/([A-Z][a-z0-9]+)/([A-Z][A-Za-z0-9]+)/([0-9]+)(?:/([a-z]{2}_[A-Z]{2}))?/tags#',
				$relativePath, $matches)
			)
			{
				if ($event->getParam('isDirectory'))
				{
					$method = $event->getRequest()->getMethod();
					$modelName = $matches[1] . '_' . $matches[2] . '_' . $matches[3];
					$docId = intval($matches[4]);
					$model = $event->getApplicationServices()->getModelManager()->getModelByName($modelName);
					if ($model !== null)
					{
						$doc = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($docId, $model);
						if ($doc !== null)
						{
							$event->setParam('docId', $docId);
							$event->setParam('modelName', $modelName);
							if ($method === Request::METHOD_GET)
							{
								$event->setAction(function ($event)
								{
									(new \Rbs\Tag\Http\Rest\Actions\GetDocumentTags())->execute($event);
								});
							}
							elseif ($method === Request::METHOD_POST)
							{
								$event->setParam('tags', $event->getRequest()->getPost()->get("ids"));
								$event->setAction(function ($event)
								{
									(new \Rbs\Tag\Http\Rest\Actions\SetDocumentTags())->execute($event);
								});
							}
							elseif ($method === Request::METHOD_PUT)
							{
								$event->setParam('addIds', $event->getRequest()->getPost()->get("addIds"));
								$event->setAction(function ($event)
								{
									(new \Rbs\Tag\Http\Rest\Actions\AddDocumentTags())->execute($event);
								});
							}
						}
					}
				}
				return;
			}

			switch ($relativePath)
			{
				case 'Rbs/Website/FunctionsList' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Website\Http\Rest\Actions\FunctionsList())->execute($event);
					});
					break;
				case 'Rbs/Website/PagesForFunction' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Website\Http\Rest\Actions\PagesForFunction())->execute($event);
					});
					break;
				case 'Rbs/Website/InheritedFunctions' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Website\Http\Rest\Actions\InheritedFunctions())->execute($event);
					});
					break;
				case 'Rbs/ModelsInfo' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Admin\Http\Rest\Actions\ModelsInfo())->execute($event);
					});
					break;
				case 'Rbs/Seo/GetMetaVariables' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Seo\Http\Rest\Actions\GetMetaVariables())->execute($event);
					});
					break;
				case 'Rbs/Seo/CreateSeoForDocument' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Seo\Http\Rest\Actions\CreateSeoForDocument())->execute($event);
					});
					break;
				case 'Rbs/Avatar' :
					$event->setAction(function (Event $event)
					{
						$event->setParam('size', $event->getRequest()->getQuery('size'));
						$event->setParam('email', $event->getRequest()->getQuery('email'));
						$event->setParam('userId', $event->getRequest()->getQuery('userId'));
						$event->setParam('params', $event->getRequest()->getQuery('params'));

						(new \Rbs\Media\Http\Rest\Actions\Avatar())->execute($event);
					});
					break;
				case 'Rbs/Geo/AddressLines' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Geo\Http\Rest\AddressLines())->execute($event);
					});
					break;
				case 'Rbs/Mail/AddMailVariation' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Mail\Http\Rest\AddMailVariation())->execute($event);
					});
					break;
				case 'Rbs/Generic/DocumentCodeContextExist' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Generic\Http\Rest\DocumentCodeContextExist())->execute($event);
					});
					break;
				case 'Rbs/Generic/GetDocumentsByCodes' :
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Generic\Http\Rest\GetDocumentsByCodes())->execute($event);
					});
					break;
			}
		}
	}
}