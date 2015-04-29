<?php 

$config = array();
$config['la_frontend'] = array(
	'production'         => true,
	'html_doctype'       => 'html5',
	'html_lang'          => 'en',
	'html_charset'       => 'UTF-8',
	'html_title_sep'     => ' | ',
	'html_title_reverse' => true
);
$config['la_frontend']['path_form'] = ''; //ex. PATH_BASE . 'storage/forms/';
$config['la_frontend']['path_script'] = ''; //ex. PATH_BASE . 'storage/scripts/';
$config['la_frontend']['path_public'] = ''; //ex. PATH_PUBLIC;
$config['la_frontend']['path_assets'] = $config['la_frontend']['path_public'] . 'assets/';
$config['la_frontend']['path_asset_css'] = $config['la_frontend']['path_assets'] . 'css/';
$config['la_frontend']['path_asset_js'] = $config['la_frontend']['path_assets'] . 'javascript/';
$config['la_frontend']['path_asset_image'] = $config['la_frontend']['path_assets'] . 'images/';
$config['la_frontend']['path_asset_third_party'] = $config['la_frontend']['path_assets'] . 'third-party/';
$config['la_frontend']['path_jquery'] = 'jquery.js';

?>