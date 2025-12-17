<?php
include_once 'FB.php';

class 用户信息
{
	function __construct() {
		$this->姓名 = T_String;
		$this->年龄 = T_int;
		$this->身高 = Arr(T_float, 3);
	}
}

class 订单
{
	function __construct() {
		$this->买家 = new 用户信息();
		$this->商品名称 = T_String;
		$this->数量 = T_int;
	}
}

function FB_用户信息(&$o)
{
	return FB($o->姓名, $o->年龄, arr_f32($o->身高));
}

function FB_订单(&$o)
{
	return FB(FB_用户信息($o->买家), $o->商品名称, $o->数量);
}

function deFB_用户信息(&$p, &$o)
{
	deFB($p, $o->姓名, $o->年龄, $o->身高);
}

function deFB_订单(&$p, &$o)
{
	$p_买家 = '';
	deFB($p, $p_买家, $o->商品名称, $o->数量);
	deFB_用户信息($p_买家, $o->买家);
}

