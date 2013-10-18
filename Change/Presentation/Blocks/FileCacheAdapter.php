<?php
namespace Change\Presentation\Blocks;

/**
* @name \Change\Presentation\Blocks\FileCacheAdapter
*/
class FileCacheAdapter
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onGetCacheAdapter(\Zend\EventManager\Event $event)
	{
		$blockManager = $event->getTarget();

		if ($blockManager instanceof BlockManager)
		{
			$workspace = $blockManager->getPresentationServices()->getApplicationServices()->getApplication()->getWorkspace();
			$cache = new \Zend\Cache\Storage\Adapter\Filesystem();
			$cacheDir = $workspace->cachePath('block');
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