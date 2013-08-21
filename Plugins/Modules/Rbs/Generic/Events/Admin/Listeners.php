<?php
namespace Rbs\Generic\Events\Admin;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Change\Plugins\Plugin;
use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\Admin\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(Event::EVENT_RESOURCES, array($this, 'registerResources'));
	}

	public function registerResources(Event $event)
	{
		$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();
		$lcid = strtolower(str_replace('_', '-', $i18nManager->getLCID()));

		$manager = $event->getManager();
		$i18nManager = $manager->getApplicationServices()->getI18nManager();
		$pm = $manager->getApplicationServices()->getPluginManager();
		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			if ($plugin->getPackage() == "Core" && $plugin->getShortName() != "Admin")
			{
				$jsAssets = new GlobAsset($plugin->getBasePath(). '/Admin/Assets/*/*.js');
				$manager->getJsAssetManager()->set($plugin->getName(), $jsAssets);

				$cssAsset = new GlobAsset($plugin->getBasePath() . '/Admin/Assets/css/*.css');
				$manager->getCssAssetManager()->set($plugin->getName(), $cssAsset);
			}
		}

		$plugin = $pm->getPlugin(Plugin::TYPE_MODULE, 'Rbs', 'Admin');
		if ($plugin)
		{
			$jsAssets = new AssetCollection([
				new FileAsset($plugin->getBasePath() . '/Assets/lib/moment/i18n/' . $lcid . '.js'),
				new FileAsset($plugin->getBasePath() . '/Assets/lib/angular/i18n/angular-locale_' . $lcid . '.js')
				]
			);
			$manager->getJsAssetManager()->set('i18n', $jsAssets);
		}

		$menu = array(
			'sections' => array(
				array('code' => 'cms', 'label' => $i18nManager->trans('m.rbs.website.admin.section-name', array('ucf'))),
				array('code' => 'admin', 'label' => $i18nManager->trans('m.rbs.admin.admin-section-name', array('ucf')))
			),
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.collection.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Collection', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.collection.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.geo.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Geo', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.geo.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.media.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Media', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.media.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.plugins.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Plugins', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.plugins.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.tag.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Tag', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.tag.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.theme.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Theme', 'section' => 'cms'),
				array('label' => $i18nManager->trans('m.rbs.user.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/User', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.user.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.website.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Website', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.website.admin.js.module-keywords'))

			)
		);

		$event->setParam('menu', \Zend\Stdlib\ArrayUtils::merge($event->getParam('menu', array()), $menu));
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
