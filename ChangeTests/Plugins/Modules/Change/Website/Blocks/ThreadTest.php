<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fstauffer
 * Date: 30/04/13
 * Time: 10:21
 * To change this template use File | Settings | File Templates.
 */

namespace ChangeTests\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Layout\Block;

class ThreadTest extends \ChangeTests\Change\TestAssets\TestCase
{
	const CURRENT_WEBSITE_ID = 456;

	protected function getTestParameterizeEvent()
	{
		$event = new Event(BlockManager::EVENT_PARAMETERIZE);
		$event->setPresentationServices($this->getPresentationServices());
		$event->setDocumentServices($this->getDocumentServices());
		$layout = new Block();
		$layout->initialize(array('id' => 1, 'name' => 'Change_Website_Thread'));
		$event->setBlockLayout($layout);
		return $event;
	}

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		$app = self::getNewApplication();
		$compiler = new \Change\Documents\Generators\Compiler($app, self::getNewApplicationServices($app));
		$compiler->generate();
	}

	public function testOnParameterize()
	{
		$event = $this->getTestParameterizeEvent();
		$layout = $event->getBlockLayout();
		$layout->setParameters(array('separator' => '>'));
		$page = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Website_StaticPage');
		$page->initialize(123);
		$event->setParam('page', $page);

		$website = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Website_Website');
		$website->initialize(self::CURRENT_WEBSITE_ID);


		$pathRule = new \Change\Http\Web\PathRule($website, 'test/toto/titi/tata.html');
		$section = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Change_Website_Topic');
		$section->initialize(789);

		$pathRule->setSectionId(789);
		$event->setParam('pathRule', $pathRule);
		$block = new \Change\Website\Blocks\Thread();
		$block->onParameterize($event);
		$parameters = $event->getBlockParameters();

		$this->assertInstanceOf('\\Change\\Presentation\\Blocks\\Parameters', $parameters);

		$this->assertTrue(isset($parameters->templateName));
		$this->assertFalse(isset($parameters->zearazefazefazfazf));

		$meta = $parameters->getParameterMeta('separator');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals(Property::TYPE_STRING, $meta->getType());
		$this->assertTrue($meta->getRequired());
		$this->assertEquals('/', $meta->getDefaultValue());
		$this->assertEquals('>', $parameters->separator);

		$meta = $parameters->getParameterMeta('templateName');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals(Property::TYPE_STRING, $meta->getType());
		$this->assertTrue($meta->getRequired());
		$this->assertEquals('thread.twig', $meta->getDefaultValue());
		$this->assertEquals('thread.twig', $parameters->templateName);

		$meta = $parameters->getParameterMeta('pageId');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals(Property::TYPE_INTEGER, $meta->getType());
		$this->assertFalse($meta->getRequired());
		$this->assertNull($meta->getDefaultValue());
		$this->assertEquals(123, $parameters->pageId);
		$this->assertEquals(123, $parameters->getPageId());
		$this->assertEquals(123, $parameters->getParameterValue('pageId'));

		$meta = $parameters->getParameterMeta('sectionId');
		$this->assertInstanceOf('\Change\Presentation\Blocks\ParameterMeta', $meta);
		$this->assertEquals(Property::TYPE_INTEGER, $meta->getType());
		$this->assertFalse($meta->getRequired());
		$this->assertNull($meta->getDefaultValue());
		$this->assertEquals(789, $parameters->sectionId);
		return $event;
	}
}
