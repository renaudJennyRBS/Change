<?php
namespace Rbs\Workflow\Setup;

use  Rbs\Workflow\Documents;
use  Rbs\Workflow\Std;

/**
* @name \Rbs\Workflow\Setup\PublicationProcessWorkflow
*/
class PublicationProcessWorkflow
{
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function __construct($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return Documents\Workflow
	 * @throws \RuntimeException
	 */
	public function install()
	{
		/* @var $workflow Documents\Workflow */
		$workflow = $this->documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Workflow_Workflow');

		$workflow->setStartTask('publicationProcess')->setActive(true);
		$workflow->setLabel('Document publication process');

		$draft = $workflow->getNewPlace()->setName('Draft')->setType(Std\Place::TYPE_START);
		$validation = $workflow->getNewPlace()->setName('Validation');
		$validContent = $workflow->getNewPlace()->setName('ValidContent');
		$valid = $workflow->getNewPlace()->setName('Valid');
		$publishable = $workflow->getNewPlace()->setName('Publishable');
		$unpublishable = $workflow->getNewPlace()->setName('Unpublishable');
		$frozen = $workflow->getNewPlace()->setName('Frozen');
		$filed = $workflow->getNewPlace()->setName('Filed')->setType(Std\Place::TYPE_END);

		$requestValidation = $workflow->getNewTransition()->setName('Request Validation')->setTaskCode('requestValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Creator');


		$contentValidation = $workflow->getNewTransition()->setName('Content Validation')->setTaskCode('contentValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Editor');

		$publicationValidation = $workflow->getNewTransition()->setName('Publication Validation')->setTaskCode('publicationValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Publisher');


		$publication = $workflow->getNewTransition()->setName('Publication')->setTaskCode('checkPublication')
			->setTrigger(Std\Transition::TRIGGER_AUTO);

		$checkPublication = $workflow->getNewTransition()->setName('Check Publication')->setTaskCode('checkPublication')
			->setTrigger(Std\Transition::TRIGGER_MSG);

		$freeze = $workflow->getNewTransition()->setName('Freeze')->setTaskCode('freeze')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Validation');

		$unfreeze =$workflow->getNewTransition()->setName('Unfreeze')->setTaskCode('unfreeze')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Validation');

		$file = $workflow->getNewTransition()->setName('File')->setTaskCode('file')
			->setTrigger(Std\Transition::TRIGGER_TIME)->setTimeLimit('P10Y');

		$workflow->getNewArc()->connect($draft, $requestValidation);
		$workflow->getNewArc()->connect($requestValidation, $validation);
		$workflow->getNewArc()->connect($validation, $contentValidation);
		$workflow->getNewArc()->connect($contentValidation, $draft)->setPreCondition('NO');
		$workflow->getNewArc()->connect($contentValidation, $validContent)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);
		$workflow->getNewArc()->connect($validContent, $publicationValidation);
		$workflow->getNewArc()->connect($publicationValidation, $valid);
		$workflow->getNewArc()->connect($valid, $publication);
		$workflow->getNewArc()->connect($publication, $unpublishable)->setPreCondition('NO');
		$workflow->getNewArc()->connect($unpublishable, $checkPublication);
		$workflow->getNewArc()->connect($checkPublication, $unpublishable)->setPreCondition('NO');
		$workflow->getNewArc()->connect($checkPublication, $publishable)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);

		$workflow->getNewArc()->connect($publication, $publishable)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);
		$workflow->getNewArc()->connect($publishable, $checkPublication)->setType(Std\Arc::TYPE_IMPLICIT_OR_SPLIT);
		$workflow->getNewArc()->connect($publishable, $freeze)->setType(Std\Arc::TYPE_IMPLICIT_OR_SPLIT);
		$workflow->getNewArc()->connect($freeze, $frozen);
		$workflow->getNewArc()->connect($frozen, $unfreeze);
		$workflow->getNewArc()->connect($unfreeze, $valid);

		$workflow->getNewArc()->connect($publishable, $file)->setType(Std\Arc::TYPE_IMPLICIT_OR_SPLIT);
		$workflow->getNewArc()->connect($file, $filed)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);
		$workflow->getNewArc()->connect($file, $valid)->setPreCondition('NO');

		if ($workflow->isValid())
		{
			$workflow->save();
			return $workflow;
		}
		else
		{
			throw new \RuntimeException($workflow->getErrors());
		}
	}
}