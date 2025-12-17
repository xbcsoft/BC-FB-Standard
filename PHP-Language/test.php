<?php
include 'FB_C.php';

TEST_C();

function TEST_C()
{
	$o = new C;
	$o->aaa[0]->a = 123;
	$o->aaa[0]->b = 'hello';
	$o->bbb[0]->bb = ['world','_hello'];
	$fb_c = FB_C($o);

	echo jzjj($fb_c);

	$o_ = new C;
	deFB_C($fb_c, $o_);
	var_dump($o_);
}
