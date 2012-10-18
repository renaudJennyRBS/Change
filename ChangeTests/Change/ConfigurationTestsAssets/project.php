<?php
// \Change\Configuration\Configuration::setDefineArray PART // 
$configuration->setDefineArray(array (
  'CHANGE_RELEASE' => 4,
  'SUPPORTED_LANGUAGES' => 'fr en',
  'UI_SUPPORTED_LANGUAGES' => 'fr en',
  'CHANGE_USE_CORRECTION' => true,
  'CHANGE_USE_WORKFLOW' => true,
  'DEFAULT_UI_PROTOCOL' => 'http',
  'DEFAULT_TIMEZONE' => 'Europe/Paris',
  'RICHTEXT_PRESERVE_H1_TAGS' => false,
  'DISABLE_BLOCK_CACHE' => false,
  'DISABLE_DATACACHE' => false,
  'CHANGE_CACHE_MAX_TIME' => 3600,
  'SECURE_SESSION_BY_IP' => false,
  'CHANGECRON_EXECUTION' => 'http',
  'AG_WEBAPP_NAME' => 'RBS Change version 4 - alpha',
  'AG_SUPPORTED_LANGUAGES' => 'fr en',
  'AG_UI_SUPPORTED_LANGUAGES' => 'fr en',
  'AG_DISABLE_BLOCK_CACHE' => false,
  'AG_DISABLE_SIMPLECACHE' => false,
  'CHANGE_COMMAND' => 'change.php',
  'TMP_PATH' => '/tmp',
  'DEFAULT_HOST' => 'change.lowcoders.net',
  'PROJECT_ID' => 'change.lowcoders.net',
  'DOCUMENT_ROOT' => '/Users/fstauffer/Dev/Projects/Change/www',
  'PHP_CLI_PATH' => 'php',
  'DEVELOPMENT_MODE' => true,
));

// \Change\Configuration\Configuration::setConfigArray PART // 
$configuration->setConfigArray(array (
  'general' => 
  array (
    'projectName' => 'RBS CHANGE 4.0',
    'server-ip' => '127.0.0.1',
    'selfRequestProxy' => '127.0.0.1:80',
    'https-request-marker' => 'HTTPS',
    'https-request-marker-value' => 'on',
    'phase' => 'production',
  ),
  'logging' => 
  array (
    'level' => 'WARN',
    'writers' => 
    array (
      'default' => 'stream',
    ),
  ),
  'i18n' => 
  array (
    'en' => 'en_GB',
  ),
  'charts' => 
  array (
    'googleChartProvider' => 'http://chart.apis.google.com/chart',
  ),
  'documentcache' => 
  array (
    'ttl' => '86400',
  ),
  'http' => 
  array (
    'adapter' => '\\Zend\\Http\\Client\\Adapter\\Curl',
  ),
  'modules' => 
  array (
    'uixul' => 
    array (
      'disableRichtextTtoolbarButtons' => 
      array (
        'ruby' => 'true',
      ),
    ),
    'catalog' => 
    array (
      'modulesCatalogProductSuggestionFeeder' => 
      array (
        0 => 'catalog_SameShelvesProductFeeder',
        1 => 'catalog_SameBrandProductFeeder',
      ),
      'compilationChunkSize' => '100',
      'useAsyncWebsiteUpdate' => 'true',
      'crossitemsCompilationChunkSize' => '100',
      'crossitemsGenerationChunkSize' => '25',
      'crossitemsGenerationDelay' => '1d',
      'productListFavoriteClass' => 'catalog_SessionProductList',
      'productListFavoritePersist' => 'true',
      'productListFavoriteMaxCount' => '-1',
      'productListConsultedClass' => 'catalog_SessionProductList',
      'productListConsultedPersist' => 'false',
      'productListConsultedMaxCount' => '10',
      'productListComparisonClass' => 'catalog_SessionProductList',
      'productListComparisonPersist' => 'false',
      'productListComparisonMaxCount' => '25',
      'currentTaxZoneStrategyClass' => 'order_CurrentTaxZoneStrategy',
    ),
    'comment' => 
    array (
      'add-starrating-styles' => 'true',
    ),
    'media' => 
    array (
      'fileinfo_magic_file_path' => '/usr/share/misc/magic',
      'secureMediaStrategyClass' => 
      array (
        'default' => 'media_DisplaySecuremediaDefaultStrategy',
      ),
    ),
    'order' => 
    array (
      'modulesCatalogProductSuggestionFeeder' => 
      array (
        0 => 'order_OrderedTogetherProductFeeder',
      ),
      'genNumber' => 'true',
      'genBill' => 'false',
      'billPDFGenerator' => 'order_FPDFBillGenerator',
      'generateDefaultExpedition' => 'true',
      'useOrderPreparation' => 'false',
      'maxDraftBillAge' => '60',
      'orderProcess' => 
      array (
        'default' => 'order_OrderProcess',
      ),
    ),
    'task' => 
    array (
      'default-node' => '',
    ),
    'theme' => 
    array (
      'pagetemplateReplacementChunkSize' => '100',
    ),
    'users' => 
    array (
      'anonymousEmailAddress' => 'anonymous@domain.net',
    ),
    'website' => 
    array (
      'sample' => 
      array (
        'defaultPageTemplate' => 'default/sidebarpage',
        'defaultNosidebarTemplate' => 'default/nosidebarpage',
        'defaultHomeTemplate' => 'default/nosidebarpage',
        'defaultPopinTemplate' => 'default/popin',
      ),
      'jquery-ui-theme' => 'south-street',
      'highlighter' => 'website_MinimalHighlighter',
      'forms-use-qtip-help' => 'false',
      'forms-use-qtip-error' => 'false',
    ),
    'useractionlogger' => 
    array (
      'maxDaysHistory' => '30',
    ),
  ),
  'mail' => 
  array (
    'type' => 'Smtp',
    'host' => 'localhost',
    'port' => '25',
    'username' => '',
    'password' => '',
  ),
  'databases' => 
  array (
    'default' => 
    array (
      'dbprovider' => '\\Change\\Db\\Mysql\\DbProvider',
    ),
    'connections' => 
    array (
      'default' => 'webapp',
    ),
  ),
  'pdf' => 
  array (
    'user' => 'pdfUser',
    'password' => 'pdfPassword',
    'customer' => 'pdfCustomer',
  ),
  'nodes' => 
  array (
  ),
  'autoload' => 
  array (
  ),
  'tal' => 
  array (
    'prefix' => 
    array (
      'trans' => 'f_TalesI18n',
      'transdata' => 'f_TalesI18n',
      'transui' => 'f_TalesI18n',
      'date' => 'f_TalesDate',
      'datetime' => 'f_TalesDate',
      'alternateclass' => 'website_TalesAlternateClass',
      'url' => 'website_TalesUrl',
      'tagurl' => 'website_TalesUrl',
      'actionurl' => 'website_TalesUrl',
      'currenturl' => 'website_TalesUrl',
    ),
  ),
  'bench' => 
  array (
    'enabled' => 'false',
  ),
  'solr' => 
  array (
    'schemaVersion' => '3.0.3',
    'clientId' => 'change4',
    'url' => 'http://127.0.0.1:8983/solr',
    'batch_mode' => 'true',
    'request_method' => 'GET',
    'disable_commit' => 'false',
    'disable_document_cache' => 'false',
  ),
  'browsers' => 
  array (
    'backoffice' => 
    array (
      'firefox' => 
      array (
        0 => '12.0',
        1 => '13.0',
      ),
    ),
  ),
  'mypath' => 
  array (
    'myentry' => 'value',
  ),
));