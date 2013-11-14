<?php
namespace Rbs\Generic\Events\BlockManager;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\BlockManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		new RegisterByBlockName('Rbs_Review_ReviewList', true, $events);
		new RegisterByBlockName('Rbs_Review_PostReview', true, $events);
		new RegisterByBlockName('Rbs_Review_PromotedReviewList', true, $events);
		new RegisterByBlockName('Rbs_Review_ReviewAverageRating', true, $events);
		new RegisterByBlockName('Rbs_Review_ReviewDetail', true, $events);
		new RegisterByBlockName('Rbs_Review_EditReview', true, $events);

		new  RegisterByBlockName('Rbs_Seo_HeadMetas', false, $events);

		new  RegisterByBlockName('Rbs_Simpleform_Form', true, $events);

		new  RegisterByBlockName('Rbs_User_Login', true, $events);

		new  RegisterByBlockName('Rbs_Website_Menu', true, $events);
		new  RegisterByBlockName('Rbs_Website_Thread', false, $events);
		new  RegisterByBlockName('Rbs_Website_SiteMap', true, $events);
		new  RegisterByBlockName('Rbs_Website_Richtext', true, $events);
		new  RegisterByBlockName('Rbs_Website_Exception', true, $events);
		new  RegisterByBlockName('Rbs_Website_Error', true, $events);
		new  RegisterByBlockName('Rbs_Website_XhtmlTemplate', true, $events);

		$callback = function($event)
		{
			(new \Change\Presentation\Blocks\FileCacheAdapter())->onGetCacheAdapter($event);
		};
		$events->attach(\Change\Presentation\Blocks\BlockManager::EVENT_GET_CACHE_ADAPTER, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
