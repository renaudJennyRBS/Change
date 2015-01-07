<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\ResourcesCode;

use Change\Http\Rest\Request;
use Change\Http\Rest\V1\DiscoverNameSpace;
use Change\Http\Rest\V1;
use Change\Http\Rest\V1\Resolver;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\V1\ResourcesCode\ResourcesCodeResolver
 */
class ResourcesCodeResolver implements \Change\Http\Rest\V1\NameSpaceDiscoverInterface
{
	const RESOLVER_NAME = 'resourcescode';

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		$namespaces = [];
		$base = implode('.', $namespaceParts);
		if (count($namespaceParts) == 1)
		{
			$contextIds = $event->getApplicationServices()->getDocumentCodeManager()->getAllContextIds();

			foreach ($contextIds as $contextId)
			{
				$namespaces[] = $base . '.' . $contextId;
			}
		}
		else
		{
			foreach (['codes', 'ids'] as $name)
			{
				$namespaces[] = $base . '.' . $name;
			}
		}

		return $namespaces;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$isDirectory = $event->getParam('isDirectory');
		$nbParts = count($resourceParts);
		if (($nbParts == 0 || ($nbParts == 1 && $isDirectory)) && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, static::RESOLVER_NAME);
			$event->setParam('namespace', implode('.', $resourceParts));
			$event->setParam('resolver', $this);
			$action = function ($event)
			{
				$action = new DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($nbParts == 1 && $method === Request::METHOD_GET && !$isDirectory)
		{
			$event->setParam('contextId', $resourceParts[0]);
			$event->setAction(array($this, 'getContextInfo'));
			return;
		}
		elseif ($nbParts == 2)
		{
			$event->setParam('contextId', $resourceParts[0]);
			if ($method === Request::METHOD_GET &&  $resourceParts[1] == 'codes')
			{
				$documentId = $event->getRequest()->getQuery('id');
				$event->setParam('documentId', $documentId);
				$event->setAction(array($this, 'getCodes'));
				return;
			}
			elseif ($method === Request::METHOD_GET &&  $resourceParts[1] == 'ids')
			{
				$code = $event->getRequest()->getQuery('code');
				$event->setParam('code', $code);
				$event->setAction(array($this, 'getIds'));
			}
		}
	}

	public function getContextInfo(\Change\Http\Event $event)
	{
		$documentCodeManager = $event->getApplicationServices()->getDocumentCodeManager();
		$contextId = $event->getParam('contextId');
		$info = ['id' => $documentCodeManager->resolveContextId($contextId)];
		$info['name'] = $documentCodeManager->getContextById($info['id']);
		$result = new V1\ArrayResult();
		$result->setArray($info);
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}

	public function getIds(\Change\Http\Event $event)
	{
		$documentCodeManager = $event->getApplicationServices()->getDocumentCodeManager();
		$contextId = $event->getParam('contextId');
		$code = $event->getParam('code');
		$info = ['contextId' => $documentCodeManager->resolveContextId($contextId)];
		$info['context'] = $documentCodeManager->getContextById($info['contextId']);
		$info['code'] = $code;
		$info['ids'] = [];
		if ($code) {
			$documents = $documentCodeManager->getDocumentsByCode($code, $info['contextId']);
			foreach ($documents as $document)
			{
				$documentLink = new \Change\Http\Rest\V1\Resources\DocumentLink($event->getUrlManager(), $document);
				$info['ids'][] = ['id' => $document->getId(), 'model' => $document->getDocumentModelName(), 'link' => $documentLink->toArray()];
			}
		}
		$result = new V1\ArrayResult();
		$result->setArray($info);
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}

	public function getCodes(\Change\Http\Event $event)
	{
		$documentCodeManager = $event->getApplicationServices()->getDocumentCodeManager();
		$contextId = $event->getParam('contextId');
		$documentId = $event->getParam('documentId');
		$info = ['contextId' => $documentCodeManager->resolveContextId($contextId)];
		$info['context'] = $documentCodeManager->getContextById($info['contextId']);
		$info['id'] = $documentId;
		$info['codes'] = [];
		if (is_numeric($documentId))
		{
			$info['codes'] = $documentCodeManager->getCodesByDocument($documentId, $info['contextId']);
		}
		$result = new V1\ArrayResult();
		$result->setArray($info);
		$result->setHttpStatusCode(Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}