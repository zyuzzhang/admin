<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace app\common;
use yii\base\InvalidConfigException;
use yii\base\Widget;
class AutoLayout extends Widget
{
	public $viewFile;
	public $params = [];
	public $blocks = [];
	public function init()
	{
		if($this->viewFile === null)
		{
			throw new InvalidConfigException('AutoLayout::viewFile must be set.');
		}
		ob_start();
		ob_implicit_flush(false);
	}
	public function run()
	{
		$params = $this->params;
		if(! isset($params['content']))
		{
			$params['content'] = ob_get_clean();
		}
		
		if(count($this->blocks) > 0)
		{
			foreach($this->blocks as $id)
			{
				if(in_array($id, $this->view->blocks))
				{
					$params[$id] = $this->view->blocks[$id];
					unset($this->view->blocks[$id]);
				}
			}
		}
		else
		{
			foreach($this->view->blocks as $id => $block)
			{
				$params[$id] = $block;
				unset($this->view->blocks[$id]);
			}
		}
		
		echo $this->view->renderFile($this->viewFile, $params);
	}
}