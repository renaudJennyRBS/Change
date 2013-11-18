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


	/**
	 * @param \Rbs\Admin\Event $event
	 */
	public function registerResources(Event $event)
	{
		$manager = $event->getManager();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$lcid = strtolower(str_replace('_', '-', $i18nManager->getLCID()));
		$devMode = $event->getApplication()->inDevelopmentMode();

		$pm = $event->getApplicationServices()->getPluginManager();

		$plugin = $pm->getPlugin(Plugin::TYPE_MODULE, 'Rbs', 'Admin');
		if ($plugin)
		{
			$pluginPath = $plugin->getAbsolutePath($event->getApplication()->getWorkspace());
			$jsAssets = new AssetCollection();
			$path = $pluginPath . '/Assets/lib/moment/i18n/' . $lcid . '.js';
			if (file_exists($path))
			{
				$jsAssets->add(new FileAsset($path));
			}
			$path = $pluginPath . '/Assets/lib/angular/i18n/angular-locale_' . $lcid . '.js';
			if (file_exists($path))
			{
				$jsAssets->add(new FileAsset($path));
			}

			if (count($jsAssets->all()))
			{
				$manager->getJsAssetManager()->set('i18n_' . $i18nManager->getLCID(), $jsAssets);
			}

			$jsAssets = new AssetCollection();
			$jsAssets->add(new FileAsset($pluginPath . '/Assets/js/rbschange.js'));

			$jsAssets->add(new GlobAsset($pluginPath . '/Assets/js/*/*.js'));
			$jsAssets->add(new FileAsset($pluginPath . '/Assets/menu/menu.js'));
			$jsAssets->add(new FileAsset($pluginPath . '/Assets/clipboard/controllers.js'));
			$jsAssets->add(new FileAsset($pluginPath . '/Assets/dashboard/controllers.js'));

			$jsAssets->add(new FileAsset($pluginPath . '/Assets/js/help.js'));
			$jsAssets->add(new FileAsset($pluginPath . '/Assets/js/routes.js'));
			if (!$devMode)
			{
				$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
			}

			$manager->getJsAssetManager()->set($plugin->getName(), $jsAssets);

			$cssAsset = new AssetCollection();
			$cssAsset->add(new GlobAsset($pluginPath . '/Assets/css/*.css'));
			$cssAsset->add(new FileAsset($pluginPath . '/Assets/menu/menu.css'));
			$cssAsset->add(new FileAsset($pluginPath . '/Assets/dashboard/dashboard.css'));
			$manager->getCssAssetManager()->set($plugin->getName(), $cssAsset);
		}

		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			if ($plugin->getPackage() == "Core" && $plugin->getShortName() != "Admin")
			{
				$manager->registerStandardPluginAssets($plugin);
			}
		}

		$menu = array(
			'sections' => array(
				array('code' => 'cms', 'label' => $i18nManager->trans('m.rbs.website.admin.section_name', array('ucf'))),
				array('code' => 'admin', 'label' => $i18nManager->trans('m.rbs.admin.admin.admin_section_name', array('ucf')))
			),
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.collection.admin.module_name', array('ucf')),
					'url' => 'Rbs/Collection', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.collection.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.review.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Review', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.review.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.geo.admin.module_name', array('ucf')),
					'url' => 'Rbs/Geo', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.geo.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.media.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Media', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.media.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.plugins.admin.module_name', array('ucf')),
					'url' => 'Rbs/Plugins', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.plugins.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.seo.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Seo', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.seo.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.simpleform.admin.module_name', array('ucf')),
					'url' => 'Rbs/Simpleform', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.simpleform.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.tag.admin.module_name', array('ucf')),
					'url' => 'Rbs/Tag', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.tag.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.theme.admin.module_name', array('ucf')),
					'url' => 'Rbs/Theme', 'section' => 'cms'),
				array('label' => $i18nManager->trans('m.rbs.user.admin.module_name', array('ucf')),
					'url' => 'Rbs/User', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.user.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.website.admin.module_name', array('ucf')),
					'url' => 'Rbs/Website', 'section' => 'cms',
					'keywords' => $i18nManager->trans('m.rbs.website.admin.module_keywords'))
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
