<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\AbstractAction
 */
abstract class AbstractAction
{
	/**
	 * @var \Change\Mvc\Context
	 */
	private $context = null;
	
	/**
	 * Original Module name
	 * @var String
	 */
	private $m_moduleName;

	/**
	 * Action Name
	 * @var String
	 */
	private $m_actionName;

	/**
	 * @param string $moduleName
	 * @param string $actionName
	 */
	public function setFullName($moduleName, $actionName)
	{
		$this->m_moduleName = $moduleName;
		$this->m_actionName = $actionName;
	}
	
	/**
	 * @param \Change\Mvc\Context $context.
	 * @return boolean
	 */
	public function initialize($context)
	{
		$this->context = $context;
		if ($this->m_moduleName === null)
		{
			$this->m_moduleName =  $context->getModuleName();
			$this->m_actionName =  $context->getActionName();	
		}
		return true;
	}
	
	/**
	 * @return \Change\Mvc\Context
	 */
	public final function getContext()
	{
		return $this->context;
	}
	
	
	/**
	 * @return string
	 */
	public function getDefaultView()
	{
		return AbstractView::INPUT;
	}
		
	/**
	 * @return string
	 */
	public function handleError()
	{
		return AbstractView::ERROR;
	}
	
	/**
	 * @return boolean
	 */
	public function validate()
	{	
		return true;
	}
	
	/**
	 * @return string View name.
	 */
	public final function execute()
	{
		try
		{
			if ($this->checkPermissions())
			{
				$context = $this->getContext();
				$result = $this->_execute($context, $context->getRequest());
				return $result;
			}
			else
			{
				return AbstractView::NONE;
			}
		}
		catch (\Exception $e)
		{
			return $this->setExecuteException($e);
		}
	}
	
	
	/**
	 * Please use this method for the action body instead of execute() (without
	 * the underscore): it is called by execute and directly receives f_Context
	 * and Request objects.
	 *
	 * @param \Change\Mvc\Context $context
	 * @param \Change\Mvc\Request $request
	 * @return string|null|string[]
	 */
	protected function _execute($context, $request)
	{
		throw new \Exception('\Change\Mvc\AbstractAction::_execute($context, $request) is not implemented');
	}

	/**
	 * @param \Exception $e
	 * @return string|null
	 */
	protected function setExecuteException($e)
	{
		return AbstractView::NONE;
	}
	
	/**
	 * Sets the HTTP "Content-type" header value for the response of this action.
	 *
	 * @param string $contentType
	 */
	protected function setContentType($contentType)
	{
		header('Content-Type: '.$contentType);
	}
	
	protected function setContentLength($contentLength)
	{
		header('Content-Length: '.$contentLength);
	}
	
	protected function outputBinary($content, $contentType)
	{
		$this->setContentType($contentType);
		$this->setContentLength(strlen($content));
		echo $content;
		return AbstractView::NONE;
	}

	/**
	 * Return the document service for the given doc Id.
	 *
	 * @param integer $docId
	 * @return f_persistentdocument_DocumentService|null
	 */
	protected function getDocumentServiceByDocId($docId)
	{
		//TODO Old class Usage
		$document = \DocumentHelper::getDocumentInstanceIfExists($docId);
		return $document ? $document->getDocumentService() : null;
	}

	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->m_moduleName;
	}

	/**
	 * Returns the name of the action.
	 * @return string
	 */
	public function getActionName()
	{
		return $this->m_actionName;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected function logAction($document, $info = array())
	{
		$moduleName = $this->getModuleName();
		$actionName = strtolower($this->getActionName());
		//TODO Old class Usage
		if ($document instanceof \f_persistentdocument_PersistentDocument)
		{
			$actionName .= '.' . strtolower($document->getPersistentModel()->getDocumentName());
		}
		//TODO Old class Usage
		\UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName);
	}
	
	/**
	 * @param \Change\Mvc\Request $request
	 * @param \Exception $e
	 * @param boolean $popupAlert
	 */
	protected function setException($request, $e, $popupAlert = false)
	{

	}


	/**
	 * Returns an array of the documents IDs received by this action.
	 *
	 * All the IDs contained in the resulting array are REAL integer values, not
	 * strings.
	 *
	 * @param \Change\Mvc\Request $request
	 * @return array<integer>
	 */
	protected function getDocumentIdArrayFromRequest($request)
	{
		$docIds = $request->getParameter(Request::DOCUMENT_ID, array());
		if (is_string($docIds) && intval($docIds) == $docIds)
		{
			$docIds = array(intval($docIds));
		}		
		elseif (is_int($docIds)) 
		{
			$docIds = array($docIds);
		}
		else if (is_array($docIds))
		{
			foreach ($docIds as $index => $docId)
			{
				if (strval(intval($docId)) === $docId)
				{
					$docIds[$index] = intval($docId);
				}
				else if (!is_int($docId))
				{
					unset($docIds[$index]);
				}
			}
		}
		return $docIds;
	}


	/**
	 * Returns the document ID received by this action.
	 *
	 * If the request holds more than one document ID (ie. an array), only
	 * the first one is returned. Returned value is a REAL integer value, not
	 * a string.
	 *
	 * @param \Change\Mvc\Request $request
	 * @return integer
	 */
	protected function getDocumentIdFromRequest($request)
	{
		$docIds = $this->getDocumentIdArrayFromRequest($request);
		if (count($docIds) != 0)
		{
			return $docIds[0];
		}
		return null;
	}


	/**
	 * Returns the document instance received by this action.
	 *
	 * If the request holds more than one document ID (ie. an array), only
	 * the first one is returned. Returned value is a PersistentDocument instance.
	 *
	 * @param \Change\Mvc\Request $request
	 * @return \f_persistentdocument_PersistentDocument
	 */
	protected function getDocumentInstanceFromRequest($request)
	{
		//TODO Old class Usage
		return \DocumentHelper::getDocumentInstance($this->getDocumentIdFromRequest($request));
	}


	/**
	 * Returns an array of document instances from the IDs received in the request.
	 *
	 * @param \Change\Mvc\Request $request
	 * @return \f_persistentdocument_PersistentDocument[]
	 */
	protected final function getDocumentInstanceArrayFromRequest($request)
	{
		$docs = array();
		$docIds = $this->getDocumentIdArrayFromRequest($request);
		foreach ($docIds as $docId)
		{
			//TODO Old class Usage
			$docs[] = \DocumentHelper::getDocumentInstance($docId);
		}
		return $docs;
	}

	/**
	 * Returns the current lang.
	 *
	 * @return string
	 */
	public final function getLang()
	{
		return \Change\I18n\I18nManager::getInstance()->getLang();
	}

	/**
	 * Returns the HTTP methods available for this action.
	 *
	 * @return string
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET | Request::PUT |  Request::DELETE;
	}


	/**
	 * All generic actions are secured: they can't be executed from a
	 * non-authenticated user.
	 * Please override this only if you know exactly what you are doing.
	 *
	 * @return boolean Always true.
	 */
	public function isSecure()
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	protected function checkPermissions()
	{
		$moduleName = $this->getModuleName();
		//TODO Old class Usage
		$roleService = \change_PermissionService::getRoleServiceByModuleName($moduleName);
		if ($roleService !== null)
		{
			$permissionService = \change_PermissionService::getInstance();	
			$nodeIds = $this->getSecureNodeIds();
			if (count($nodeIds) == 0)
			{
				$defaultNodeId = \ModuleService::getInstance()->getRootFolderId($moduleName);
				$nodeIds[] = $defaultNodeId;
			}
			
			$user = null;
			foreach ($nodeIds as $nodeId)
			{
				$action  = $this->getSecureActionName($nodeId);
				if ($roleService->hasAction($action))
				{
					$permissions = $roleService->getPermissionsByAction($action);
					if (count($permissions))
					{
						//TODO Old class Usage
						if ($user === null) {$user = \users_UserService::getInstance()->getAutenticatedUser();}
						
						foreach ($permissions as $permission)
						{
							if (!$permissionService->hasPermission($user, $permission, $nodeId))
							{
								$this->onMissingPermission($user->getLogin(), $permission, $nodeId);
								return false;
							}
						}
					}	
				}
			}
		}
		return true;
	}
	
	/**
	 * Retourne le nom de l'action permissionnée
	 * @param integer $documentId
	 * @param boolean $addDocumentName
	 * @return string For example: modules_website.RewriteUrl
	 */
	protected function getSecureActionName($documentId)
	{
		$secureAction = "modules_" . $this->getModuleName() . "." . $this->getActionName();
		if ($this->isDocumentAction())
		{
			//TODO Old class Usage
			$secureAction .= '.' . \DocumentHelper::getDocumentInstance($documentId)->getPersistentModel()->getDocumentName();
		}
		return $secureAction;
	}

	/**
	 * Tell the permission system this action is a document action ie. the permission
	 * depends on the document the action acts on.
	 * @return boolean by default false
	 */
	protected function isDocumentAction()
	{
		return false;
	}

	protected function getSecureNodeIds()
	{
		return $this->getDocumentIdArrayFromRequest($this->getContext()->getRequest());
	}

	/**
	 * Traitement absence de permission
	 * @param string $login
	 * @param string $permission
	 * @param integer $nodeId
	 * @throw BaseExeption(modules.[MODULENAME].errors.[ESCAPEDPERMISSION ex : modules-photoalbum-move-topic])
	 */
	protected function onMissingPermission($login, $permission, $nodeId)
	{
		$message = str_replace(array('_', '.'), '-', $permission);
		//TODO Old class Usage
		throw new \BaseException($message, 'modules.'. $this->getModuleName() . '.errors.' . ucfirst($message));
	}
			
	public function shutdown()
	{
		$this->context = null;
	}
}