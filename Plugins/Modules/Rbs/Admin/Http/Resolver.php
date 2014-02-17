<?php
namespace Rbs\Admin\Http;

use Change\Http\BaseResolver;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Resolver
 */
class Resolver extends BaseResolver
{
	/**
	 * @param Event $event
	 * @return void
	 */
	public function resolve($event)
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
				(new \Rbs\Admin\Http\Actions\GetHome())->execute($event);
			};
			$event->setAction($action);
			return;
		}

		$relativePath = $this->getRelativePath($path);
		if ($relativePath === 'Rbs/Admin/i18n.js')
		{
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetI18nPackage())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($relativePath === 'Rbs/Admin/routes.js')
		{
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetRoutes())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($relativePath === 'Rbs/Admin/editors.js')
		{
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetDocumentEditors())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^I18nPackage\/([a-z]{2}_[A-Z]{2})\/(((m|t)(\.[a-z0-9]+){3})|((c)(\.[a-z0-9]+)))\.json$/', $relativePath, $matches))
		{
			$lcid = $matches[1];
			$package = $matches[2];
			$event->setParam('LCID', $lcid);
			$event->setParam('package', $package);
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetI18nPackage())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^Rbs\/Admin\/Aggregate\/[a-z]{2}_[A-Z]{2}\/.*$/', $relativePath, $matches))
		{
			$event->setParam('resourcePath', $relativePath);
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetAggregate())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^Block\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/parameters\.twig$/', $relativePath, $matches))
		{
			list(,$vendor, $shortModuleName, $shortBlockName) = $matches;
			$event->setParam('vendor', $vendor);
			$event->setParam('shortModuleName', $shortModuleName);
			$event->setParam('shortBlockName', $shortBlockName);
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetHtmlBlockParameters())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^Collection\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/filter-panel\.twig$/', $relativePath, $matches))
		{
			list(,$vendor, $shortModuleName, $shortDocumentName) = $matches;
			$event->setParam('vendor', $vendor);
			$event->setParam('shortModuleName', $shortModuleName);
			$event->setParam('shortDocumentName', $shortDocumentName);
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetHtmlCollectionFilterPanel())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^Document\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/(.+)\.twig$/', $relativePath, $matches))
		{
			list( ,$vendor, $shortModuleName, $shortDocumentName, $baseFileName) = $matches;
			$event->setParam('resourcePath', implode('/', [$vendor, $shortModuleName, 'Documents' , $shortDocumentName , $baseFileName . '.twig']));
			$event->setParam('vendor', $vendor);
			$event->setParam('shortModuleName', $shortModuleName);
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetHtmlFragment())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (preg_match('/^([A-Z][A-Za-z0-9]+)\/([A-Z][A-Za-z0-9]+)\/(.+)\.([a-z]+)$/', $relativePath, $matches))
		{
			$event->setParam('resourcePath', $relativePath);
			list(,$vendor, $shortModuleName, $subPath, $extension) = $matches;
			$event->setParam('vendor', $vendor);
			$event->setParam('shortModuleName', $shortModuleName);
			$event->setParam('modulePath', $subPath);
			$event->setParam('extension', $extension);

			if ($extension === 'twig')
			{
				$action = function($event) {
					(new \Rbs\Admin\Http\Actions\GetHtmlFragment())->execute($event);
				};
				$event->setAction($action);
				return;
			}
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetResource())->execute($event);
			};
			$event->setAction($action);
			return;
		}
		else
		{
			$action = function($event) {
				(new \Rbs\Admin\Http\Actions\GetHome())->execute($event);
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