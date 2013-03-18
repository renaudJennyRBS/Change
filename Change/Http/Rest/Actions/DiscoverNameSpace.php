<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\Link;
use Change\Http\Rest\Result\NamespaceResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\DiscoverNameSpace
 */
class DiscoverNameSpace
{

	/**
	 * Use Event Params: namespace, Resolver
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$namespace = $event->getParam('namespace');
		$result = new NamespaceResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$urlManager = $event->getUrlManager();
		$selfLink = new Link($urlManager, $this->generatePathInfoByNamespace($namespace));
		$result->addLink($selfLink);
		if ($namespace === '')
		{
			foreach (array('resources', 'resourcesactions', 'resourcestree') as $name)
			{
				$link = new Link($urlManager, $this->generatePathInfoByNamespace($name), $name);
				$result->addLink($link);
				$event->setResult($result);
			}
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
					$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
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
					$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
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
						$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
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
				/* @var $resolver \Change\Http\Rest\Resolver */
				$resolver = $event->getParam('Resolver');

				foreach ($resolver->getResourceActionClasses() as $actionName => $class)
				{
					$ns = $namespace .'.'. $actionName;
					$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
					$result->addLink($link);
				}
				$event->setResult($result);
			}
		}
		elseif ($names[0] === 'resourcestree')
		{
			if (!isset($names[1]))
			{
				$treeNames = $event->getDocumentServices()->getTreeManager()->getTreeNames();
				$vendors = array();
				foreach ($treeNames as $treeName)
				{
					list($vendor, ) = explode('_', $treeName);
					if (!array_key_exists($vendor, $vendors))
					{
						$ns = $namespace .'.'. $vendor;
						$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
						$result->addLink($link);
						$vendors[$vendor] = true;
					}
				}
				$event->setResult($result);
			}
			elseif (!isset($names[2]))
			{
				$vendor = $names[1];
				$treeNames = $event->getDocumentServices()->getTreeManager()->getTreeNames();
				$shortModulesNames = array();
				foreach ($treeNames as $treeName)
				{
					list($vendorTree, $shortModuleName) = explode('_', $treeName);
					if ($vendorTree === $vendor && !array_key_exists($shortModuleName, $shortModulesNames))
					{
						$ns = $namespace .'.'. $shortModuleName;
						$link = new Link($urlManager, $this->generatePathInfoByNamespace($ns), $ns);
						$result->addLink($link);
						$shortModulesNames[$shortModuleName] = true;
					}
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
