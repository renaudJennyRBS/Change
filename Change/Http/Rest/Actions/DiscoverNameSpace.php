<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\DiscoverNameSpace
 */
class DiscoverNameSpace
{

	/**
	 * Use Event Params: namespace
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$namespace = $event->getParam('namespace');
		$result = new \Change\Http\Rest\Result\NamespaceResult();
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);

		$urlManager = $event->getUrlManager();
		$selfLink = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($namespace));
		$result->addLink($selfLink);
		if ($namespace === '')
		{
			$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace('resources'), 'resources');
			$result->addLink($link);
			$event->setResult($result);

			$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace('resourcesactions'), 'resourcesactions');
			$result->addLink($link);
			$event->setResult($result);
			return;
		}

		$names = explode('.', $namespace);
		if ($names[0] === 'resources')
		{
			if (!isset($names[1]))
			{
				$vendors = $event->getDocumentServices()->getModelManager()->getVendors();
				foreach ($vendors as $vendor)
				{
					$ns = $namespace .'.'. $vendor;
					$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
			elseif (!isset($names[2]))
			{
				$vendor = $names[1];
				$modules = $event->getDocumentServices()->getModelManager()->getShortModulesNames($vendor);
				foreach ($modules as $module)
				{
					$ns = $namespace .'.'. $module;
					$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
			elseif (!isset($names[3]))
			{
				$documents = $event->getDocumentServices()->getModelManager()->getShortDocumentsNames($names[1], $names[2]);
				if ($documents)
				{
					foreach ($documents as $document)
					{
						$ns = $namespace .'.'. $document;
						$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
						$result->addLink($link);
					}
					$event->setResult($result);
				}
			}
		}
		elseif ($names[0] === 'resourcesactions')
		{
			if (!isset($names[1]))
			{
				foreach ($this->resourceActionClasses as $actionName => $class)
				{
					$ns = $namespace .'.'. $actionName;
					$link = new \Change\Http\Rest\Result\Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
		}
	}

	/**
	 * @param string $namespace
	 * @return string
	 */
	protected function generatePathInfoByNamespace($namespace)
	{
		if (empty($namespace))
		{
			return '/';
		}
		return '/' . str_replace('.', '/', $namespace) . '/';
	}
}
