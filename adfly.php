<?php
/**
 * @package		Joomla.Plugin
 * @subpackage	Content.adfly
 * @copyright	Copyright (C) 2010 - 2015 Nordmograph.com, Inc. All rights reserved.
 * @copyright	Copyright (C) 2019 - 2020 AlexonbStudio. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

//jimport('joomla.plugin.plugin');
#class plgContentAdfly extends JPlugin
class PlgContentAdfly extends CMSPlugin
{
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// simple performance check to determine whether bot should process further
		if (strpos($article->text, 'href') === false)
		{
			return true;
		}
		$key				= $this->params->def('key','cfea8b20afcad652f6608d1e88ed08b6');
		$uid				= $this->params->def('uid','8726147');
		$domain				= $this->params->def('domain','adf.ly');
		$advert_type		= $this->params->def('advert_type','int');
		$excepts			= $this->params->def('excepts').','.$_SERVER['HTTP_HOST'];
		$excepts			= explode(',',$excepts);
		$exclude_components	= $this->params->def('exclude_components');
		$exclude_views		= $this->params->def('exclude_views');
		$jinput 			= Factory::getApplication()->input;
		$component 			= $jinput->get('option');
		$view 				= $jinput->get('view');
		if( strpos($exclude_components,$component) !== false || strpos($exclude_views,$view) !== false )
		{
			return false;
		}
		$matches	= array();
  		$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
  		if(preg_match_all("/$regexp/siU", $article->text, $matches, PREG_SET_ORDER))
  		{
    		//var_dump($matches);
    		foreach($matches as $match)
    		{
    			$abort =0;
    			$count =0;
    			$item = $match[2];
    			for($i=0;$i<count($excepts);$i++)
    			{
    				if($excepts[$i]!='' && strpos($item,$excepts[$i])) // exclude exceptions
    					$abort = 1;
    				if( strpos($item,'http')=== false && strpos($item,'ftp://')=== false ) // exclude relative URL
    					$abort = 1;
    			}
    			if(!$abort)
    			{
    				$output = $this->_load($item ,$key,$uid,$domain,$advert_type);
    				$pos = strpos($article->text,$match[2]);
    				if ($pos !== false) {
					   $article->text = substr_replace($article->text,$output,$pos,strlen($match[2]));
					}
				}
    		}
  		}	
	}
	protected function _load($url,$key,$uid,$domain,$advert_type)
	{
		$url =  parse_url($url);
		$nurl =  $url['host'];
		if($url['path'])
			$nurl .= $url['path'];
		if($url['query'])
			$nurl .= '?'.$url['query'];
		$api = 'http://api.adf.ly/api.php?';
		$api .= 'key='.$key;
		$api .= '&uid='.$uid;
		$api .= '&advert_type='.$advert_type;
		$api .= '&domain='.$domain;
		$api .= '&url='.urlencode($nurl);
		if ($data = file_get_contents($api) )
		    return $data;
	}
}