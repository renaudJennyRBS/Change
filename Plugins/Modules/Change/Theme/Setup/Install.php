<?php
namespace Change\Theme\Setup;

/**
 * Class Install
 * @package Change\Theme\Setup
 * @name \Change\Theme\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{

	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function executeServices($applicationServices, $documentServices)
	{
		$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Change_Theme_PageTemplate');
		$query = new \Change\Documents\Query\Builder($documentServices, $pageTemplateModel);
		$pageTemplate = $query->getFirstDocument();
		if (!$pageTemplate)
		{
			/* @var $pageTemplate \Change\Theme\Documents\PageTemplate */
			$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
		}

		$pageTemplate->setLabel('Exemple');
		$pageTemplate->setHtml('<!DOCTYPE html>
<html>
	<head>
{% for headLine in pageResult.head %}
		{{ headLine|raw }}
{% endfor %}
		<link rel="icon" type="image/png" href="Theme/Change/Default/img/favicon.png" />
		<link rel="stylesheet" href="Theme/Change/Default/css/screen.css" type="text/css" />
		<script src="Theme/Change/Default/js/bootstrap.min.js" type="text/javascript"></script>
		<script src="Theme/Change/Default/js/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="Theme/Change/Default/js/default.js" type="text/javascript"></script>
	</head>
<body>
	<header>
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container">
					<a class="brand logo" href=".">
						<img src="Theme/Change/Default/img/logo.png" alt="" />
					</a>
					<div class="navigation">
						<nav>
							<!-- menuHeader -->
						</nav>
					</div>
				</div>
			</div>
		</div>
	</header>
	<section id="subintro">
		<div class="jumbotron subhead" id="overview">
			<div class="container">
				<div class="row">
					<div class="span12">
						<div class="centered">
							<!-- subintro -->
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<section id="breadcrumb">
		<div class="container">
			<div class="row">
				<div class="span12">
					<!-- thread -->
				</div>
			</div>
		</div>
	</section>
	<section id="maincontent">
		<!-- zoneEditable1 -->
	</section>
	<footer class="footer">
		<div class="container">
			<div class="row">
				<div class="span3">
					<div class="widget">
						<!-- menuFooter1 -->
					</div>
				</div>
				<div class="span3">
					<div class="widget">
						<!-- menuFooter2 -->
					</div>
				</div>
				<div class="span3">
				</div>
				<div class="span3">
				</div>
			</div>
		</div>
		<div class="verybottom">
			<div class="container">
				<div class="row">
					<div class="span6">
						<p>
							&copy; Serenity 2013 - All right reserved
						</p>
					</div>
					<div class="span6">
						<p class="pull-right">
							Designed by <a href="http://iweb-studio.com">iWebStudio</a>
						</p>
					</div>
				</div>
			</div>
		</div>
	</footer>
</body>
</html>');
		$pageTemplate->setEditableContent('{
	"zoneEditable1" : {
		"id" : "zoneEditable1",
		"grid" : 12,
		"gridMode" : "fixed",
		"type" : "container",
		"parameters" : {
		}
	},
	"menuHeader" : {
		"id" : "menuHeader",
		"type" : "block",
		"name" : "Change_Website_Menu",
		"parameters" : {
			"documentId" : 100003,
			"maxLevel" : 3,
			"templateName" : "menu-header.twig"
		}
	},
	"thread" : {
		"id" : "thread",
		"type" : "block",
		"name" : "Change_Website_Thread",
		"parameters" : {
		}
	},
	"menuFooter1" : {
		"id" : "menuFooter1",
		"type" : "block",
		"name" : "Change_Website_Menu",
		"parameters" : {
			"showTitle" : true,
			"documentId" : 100003,
			"maxLevel" : 1
		}
	},
	"menuFooter2" : {
		"id" : "menuFooter2",
		"type" : "block",
		"name" : "Change_Website_Menu",
		"parameters" : {
			"showTitle" : true,
			"documentId" : 100003,
			"maxLevel" : 2
		}
	},
	"subintro" : {
		"id" : "subintro",
		"type" : "block",
		"name" : "Change_Website_Richtext",
		"parameters" : {
			"content" : "<h3>About us</h3><p>Lorem ipsum dolor sit amet, modus salutatus honestatis ex mea. Sit cu probo putant. Nam ne impedit atomorum.</p>"
		}
	}
}');
		$pageTemplate->setHtmlForBackoffice('<div data-editable-zone-id="zoneEditable1"></div>');
		$pageTemplate->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$pageTemplate->save();
	}
}