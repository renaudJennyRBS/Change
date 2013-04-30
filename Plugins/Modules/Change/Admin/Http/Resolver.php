<?php
namespace Change\Admin\Http;

use Change\Http\ActionResolver;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Admin\Http\Resolver
 */
class Resolver extends ActionResolver
{
	/**
	 * @param Event $event
	 * @return void
	 */
	public function resolve(Event $event)
	{
		$request = $event->getRequest();
		$path = $request->getPath();
		if (strpos($path, '//') !== false)
		{
			return;
		}
		elseif ($path === $request->getServer('SCRIPT_NAME'))
		{
			$path = '/';
		}

		if ($path === '/')
		{
			$action = function($event) {
				$action = new \Change\Admin\Http\Actions\GetHome();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}

		$relativePath = $this->getRelativePath($path);
		if (preg_match('/^([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/(.+)\.([a-z]+)$/', $relativePath, $matches))
		{
			$event->setParam('resourcePath', $relativePath);
			list(,$vendor, $shortModuleName, $subPath, $extension) = $matches;
			$event->setParam('vendor', $vendor);
			$event->setParam('shortModuleName', $shortModuleName);
			$event->setParam('modulePath', $subPath);
			$event->setParam('extension', $extension);

			if ($extension === 'js' && strpos($subPath, 'i18n/') === 0)
			{
				$action = function($event) {
					$action = new \Change\Admin\Http\Actions\GetI18nPackage();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
			elseif ($extension === 'twig')
			{
				$action = function($event) {
					$action = new \Change\Admin\Http\Actions\GetHtmlFragment();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}


			$action = function($event) {
				$action = new \Change\Admin\Http\Actions\GetResource();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		else
		{
			$action = function($event) {
				$action = new \Change\Admin\Http\Actions\GetHome();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}

	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getRelativePath($path)
	{
		if ($path && $path[0] == '/')
		{
			$path = substr($path, 1);
		}
		return $path;
	}
}