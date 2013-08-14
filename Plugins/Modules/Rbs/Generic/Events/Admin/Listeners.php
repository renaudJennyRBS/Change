<?php
namespace Rbs\Generic\Events\Admin;

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

		$body = array(
			'Rbs/Admin/lib/moment/i18n/' . $lcid . '.js',
			'Rbs/Admin/lib/angular/i18n/angular-locale_' . $lcid . '.js',
			'Rbs/Collection/js/admin.js',
			'Rbs/Collection/js/directives.js',
			'Rbs/Collection/Collection/controllers.js',
			'Rbs/Collection/Collection/editor.js',
			'Rbs/Collection/Item/controllers.js',
			'Rbs/Collection/Item/editor.js',
			'Rbs/Geo/js/admin.js',
			'Rbs/Geo/Zone/controllers.js',
			'Rbs/Geo/Zone/editor.js',
			'Rbs/Geo/Country/controllers.js',
			'Rbs/Geo/Country/editor.js',
			'Rbs/Geo/TerritorialUnit/controllers.js',
			'Rbs/Geo/TerritorialUnit/editor.js',
			'Rbs/Media/js/admin.js',
			'Rbs/Media/Image/controllers.js',
			'Rbs/Media/Image/editor.js',
			'Rbs/Plugins/js/admin.js',
			'Rbs/Plugins/js/services/plugins.js',
			'Rbs/Plugins/Installed/controllers.js',
			'Rbs/Plugins/Registered/controllers.js',
			'Rbs/Plugins/New/controllers.js',
			'Rbs/Tag/js/admin.js',
			'Rbs/Tag/js/directives.js',
			'Rbs/Tag/Tag/controllers.js',
			'Rbs/Tag/Tag/editor.js',
			'Rbs/Theme/js/admin.js',
			'Rbs/Theme/PageTemplate/controllers.js',
			'Rbs/Theme/PageTemplate/editor.js',
			'Rbs/Theme/Theme/controllers.js',
			'Rbs/Theme/Theme/editor.js',
			'Rbs/Timeline/js/directives/timeline.js',
			'Rbs/User/js/admin.js',
			'Rbs/User/User/controllers.js',
			'Rbs/User/User/editor.js',
			'Rbs/User/Group/controllers.js',
			'Rbs/User/Group/editor.js',
			'Rbs/User/Profile/controllers.js',
			'Rbs/Website/js/admin.js',
			'Rbs/Website/StaticPage/controllers.js',
			'Rbs/Website/StaticPage/editor.js',
			'Rbs/Website/FunctionalPage/controllers.js',
			'Rbs/Website/FunctionalPage/editor.js',
			'Rbs/Website/Topic/controllers.js',
			'Rbs/Website/Topic/editor.js',
			'Rbs/Website/Website/controllers.js',
			'Rbs/Website/Website/editor.js',
			'Rbs/Website/Menu/controllers.js',
			'Rbs/Website/Menu/editor.js',
			'Rbs/Website/SectionPageFunction/controllers.js'
		);

		$header = array('
	<link href="Rbs/Media/css/admin.css" rel="stylesheet"/>
	<link href="Rbs/Tag/css/admin.css" rel="stylesheet"/>
	<link href="Rbs/Timeline/css/admin.css" rel="stylesheet"/>
	<link href="Rbs/Website/css/admin.css" rel="stylesheet"/>');

		$menu = array(
			'sections' => array(
				array('code' => 'cms', 'label' => $i18nManager->trans('m.rbs.website.admin.section-name', array('ucf'))),
				array('code' => 'admin', 'label' => $i18nManager->trans('m.rbs.admin.admin-section-name', array('ucf')))
			),
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.collection.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Collection/Collection', 'section' => 'admin',
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

		$event->setParam('header', array_merge($event->getParam('header'), $header));
		$event->setParam('body', array_merge($event->getParam('body', array()), $body));
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
