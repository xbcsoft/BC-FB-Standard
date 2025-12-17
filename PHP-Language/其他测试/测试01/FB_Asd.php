<?php
include_once 'FB.php';

class Asd
{
	function __construct() {
		$this->a = T_int64;
		$this->b = T_short;
	}
}

function FB_Asd(&$o)
{
	return FB(i64($o->a), i16($o->b));
}

function deFB_Asd(&$p, &$o)
{
	deFB($p, $o->a, $o->b);
}
