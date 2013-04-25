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
					<a class="brand logo" href="/">
						<img src="Theme/Change/Default/img/logo.png" alt="" />
					</a>
					<div class="navigation">
						<nav>
						<!-- blocMenu -->
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
					<ul class="breadcrumb notop">
						<li><a href="#">Home</a><span class="divider">/</span></li>
						<li class="active">Blog right sidebar</li>
					</ul>
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
						<h5>Browse pages</h5>
						<ul class="regular">
							<li><a href="#">Work for us</a></li>
							<li><a href="#">Creative process</a></li>
							<li><a href="#">Case study</a></li>
							<li><a href="#">Scaffold awwards</a></li>
							<li><a href="#">Meet the team</a></li>
						</ul>
					</div>
				</div>
				<div class="span3">
					<div class="widget">
						<h5>Recent blog posts</h5>
						<ul class="regular">
							<li><a href="#">Lorem ipsum dolor sit amet</a></li>
							<li><a href="#">Mea malis nominavi insolens ut</a></li>
							<li><a href="#">Minim timeam has no aperiri sanctus ei mea per pertinax</a></li>
							<li><a href="#">Te malorum dignissim eos quod sensibus</a></li>
						</ul>
					</div>
				</div>
				<div class="span3">
				</div>
				<div class="span3">
					<div class="widget">
						<!-- logo -->
						<a class="brand logo" href="index.html">
						<img src="assets/img/logo-dark.png" alt="" />
						</a>
						<!-- end logo -->
						<address>
						<strong>Registered Companyname, Inc.</strong><br>
						 8895 Somename Ave, Suite 600<br>
						 San Francisco, CA 94107<br>
						<abbr title="Phone">P:</abbr> (123) 456-7890 </address>
					</div>
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
	"subintro" : {
		"id"   : "subintro",
		"type" : "block",
		"name" : "Change_Website_Richtext",
		"parameters" : {
			"content" : "<h3>About us</h3><p>Lorem ipsum dolor sit amet, modus salutatus honestatis ex mea. Sit cu probo putant. Nam ne impedit atomorum.</p>"
		}
	},
	"zoneEditable1" : {
		"id"   : "zoneEditable1",
		"grid" : 12,
		"gridMode" : "fixed",
		"type" : "container",
		"parameters" : {
		}
	},
	"blocMenu" : {
		"id"   : "blocMenu",
		"type" : "block",
		"name" : "Change_Website_Menu",
		"parameters" : {
			"documentId" : 100003,
			"maxLevel" : 3
		}
	}
}');
		$pageTemplate->setHtmlForBackoffice('<div data-editable-zone-id="zoneEditable1"></div>');
		$pageTemplate->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$pageTemplate->save();
	}
}