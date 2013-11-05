<?php
namespace Change\Presentation\Pages;

/**
* @name \Change\Presentation\Pages\FileCacheAdapter
*/
class FileCacheAdapter
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onGetCacheAdapter(\Change\Events\Event $event)
	{
		$pageManager = $event->getTarget();

		if ($pageManager instanceof PageManager)
		{
			$workspace = $event->getApplication()->getWorkspace();
			$cache = new \Zend\Cache\Storage\Adapter\Filesystem();
			$cacheDir = $workspace->cachePath('page');
			\Change\Stdlib\File::mkdir($cacheDir);

			$cacheOptions = new \Zend\Cache\Storage\Adapter\FilesystemOptions(
				array('cache_dir'=> $cacheDir . DIRECTORY_SEPARATOR));
			$cache->setOptions($cacheOptions);

			$cachePlugin = new \Zend\Cache\Storage\Plugin\Serializer();
			$cachePluginOptions = new \Zend\Cache\Storage\Plugin\PluginOptions(
				array('serializer'=> new \Zend\Serializer\Adapter\PhpSerialize())
			);
			$cachePlugin->setOptions($cachePluginOptions);
			$cache->addPlugin($cachePlugin);
			$event->setParam('cacheAdapter', $cache);
		}
	}
}