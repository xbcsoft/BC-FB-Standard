<?php
include 'FB_Asd.php';

$o = new Asd;
$o->a = 1234567898765;
$f = FB_Asd($o); //注意这里没有赋值b，实际应用中应当全都要赋值

$o_ = new Asd;
deFB_Asd($f,$o_);
var_dump($o_); //没赋值过的就只能用形参中的初值
