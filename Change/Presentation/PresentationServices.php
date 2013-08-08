<?php
namespace Change\Presentation;

use Change\Application\ApplicationServices;
use Change\Presentation\RichText\RichTextManager;
use Change\Presentation\Templates\TemplateManager;
use Change\Presentation\Themes\ThemeManager;
use Change\Presentation\Blocks\BlockManager;
use Zend\Di\Definition\ClassDefinition;
use Zend\Di\DefinitionList;
use Zend\Di\Di;

/**
 * @api
 * @name \Change\Presentation\PresentationServices
 */
class PresentationServices extends Di
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function __construct(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;

		$dl = new DefinitionList(array());

		$this->registerTemplateManager($dl);
		$this->registerThemeManager($dl);
		$this->registerBlockManager($dl);
		$this->registerRichTextManager($dl);
		parent::__construct($dl);

		$im = $this->instanceManager();
		$im->setParameters('Change\Presentation\Templates\TemplateManager', array('presentationServices' => $this));
		$im->setParameters('Change\Presentation\Themes\ThemeManager', array('presentationServices' => $this));
		$im->setParameters('Change\Presentation\Blocks\BlockManager', array('presentationServices' => $this));

		$im->setParameters('Change\Presentation\RichText\RichTextManager', array('presentationServices' => $this));
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerTemplateManager($dl)
	{
		$cl = new ClassDefinition('Change\Presentation\Templates\TemplateManager');
		$cl->setInstantiator('__construct')
			->addMethod('setPresentationServices', true)
			->addMethodParameter('setPresentationServices', 'presentationServices',
				array('type' => 'Change\Presentation\PresentationServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerThemeManager($dl)
	{
		$cl = new ClassDefinition('Change\Presentation\Themes\ThemeManager');
		$cl->setInstantiator('__construct')
			->addMethod('setPresentationServices', true)
			->addMethodParameter('setPresentationServices', 'presentationServices',
				array('type' => 'Change\Presentation\PresentationServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerBlockManager($dl)
	{
		$cl = new ClassDefinition('Change\Presentation\Blocks\BlockManager');
		$cl->setInstantiator('__construct')
			->addMethod('setPresentationServices', true)
			->addMethodParameter('setPresentationServices', 'presentationServices',
				array('type' => 'Change\Presentation\PresentationServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @param DefinitionList $dl
	 */
	protected function registerRichTextManager($dl)
	{
		$cl = new ClassDefinition('Change\Presentation\RichText\RichTextManager');
		$cl->setInstantiator('__construct')
			->addMethod('setPresentationServices', true)
			->addMethodParameter('setPresentationServices', 'presentationServices',
				array('type' => 'Change\Presentation\PresentationServices', 'required' => true));
		$dl->addDefinition($cl);
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @return TemplateManager
	 */
	public function getTemplateManager()
	{
		return $this->get('Change\Presentation\Templates\TemplateManager');
	}

	/**
	 * @return ThemeManager
	 */
	public function getThemeManager()
	{
		return $this->get('Change\Presentation\Themes\ThemeManager');
	}

	/**
	 * @return BlockManager
	 */
	public function getBlockManager()
	{
		return $this->get('Change\Presentation\Blocks\BlockManager');
	}

	/**
	 * @return RichTextManager
	 */
	public function getRichTextManager()
	{
		return $this->get('Change\Presentation\RichText\RichTextManager');
	}

}