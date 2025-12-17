<?php
include_once 'FB.php';

class A
{
	function __construct() {
		$this->a = T_short;
		$this->b = T_String;
	}
}

class B
{
	function __construct() {
		$this->aa = new A();
		$this->bb = Arr(T_Bytes, 1);
	}
}

class C
{
	function __construct() {
		$this->aaa = Arr(new A(), 2);
		$this->bbb = Arr(new B(), 1);
	}
}

function FB_A(&$o)
{
	return FB(i16($o->a), $o->b);
}

function FB_B(&$o)
{
	return FB(FB_A($o->aa), $o->bb);
}

function FB_C(&$o)
{
	$n = count($o->aaa);
	$o_aaa = Arr('', $n);
	for ($i = 0; $i<$n; $i++){
		$o_aaa[$i] = FB_A($o->aaa[$i]);
	}
	$n = count($o->bbb);
	$o_bbb = Arr('', $n);
	for ($i = 0; $i<$n; $i++){
		$o_bbb[$i] = FB_B($o->bbb[$i]);
	}
	return FB($o_aaa, $o_bbb);
}

function deFB_A(&$p, &$o)
{
	deFB($p, $o->a, $o->b);
}

function deFB_B(&$p, &$o)
{
	$p_aa = '';
	deFB($p, $p_aa, $o->bb);
	deFB_A($p_aa, $o->aa);
}

function deFB_C(&$p, &$o)
{
	$p_aaa = $p_bbb = '';
	deFB($p, $p_aaa, $p_bbb);
	$n = gFB_n($p_aaa);
	$o->aaa = Arr(new A(), $n);
	for ($i = 0; $i<$n; $i++){
		deFB_A(gFB($p_aaa, $i), $o->aaa[$i]);
	}
	$n = gFB_n($p_bbb);
	$o->bbb = Arr(new B(), $n);
	for ($i = 0; $i<$n; $i++){
		deFB_B(gFB($p_bbb, $i), $o->bbb[$i]);
	}
}

