<?php
include_once 'FB.php';

class BasicInfo
{
	function __construct() {
		$this->gender = T_char;
		$this->age = T_short;
		$this->id = T_int;
		$this->timestamp = T_int64;
		$this->height = T_float;
		$this->weight = T_double;
		$this->name = T_String;
		$this->avatar = T_Bytes;
		$this->isVIP = T_bool;
	}
}

class Contact
{
	function __construct() {
		$this->phone = T_String;
		$this->email = T_String;
		$this->address = Arr(T_String, 3);
	}
}

class Product
{
	function __construct() {
		$this->id = T_String;
		$this->name = T_String;
		$this->price = T_double;
		$this->stock = T_int;
		$this->image = Arr(T_Bytes, 5);
		$this->isOnSale = T_bool;
	}
}

class OrderItem
{
	function __construct() {
		$this->product = new Product();
		$this->quantity = T_int;
		$this->unitPrice = T_double;
		$this->discount = T_short;
	}
}

class Payment
{
	function __construct() {
		$this->id = T_String;
		$this->type = T_uchar;
		$this->amount = T_double;
		$this->currency = T_String;
		$this->timestamp = T_int64;
	}
}

class ComplexOrder
{
	function __construct() {
		$this->orderId = T_String;
		$this->customerInfo = new BasicInfo();
		$this->contactInfo = new Contact();
		$this->items = Arr(new OrderItem(), 10);
		$this->payments = Arr(new Payment(), 3);
		$this->status = T_uint;
		$this->createTime = T_int64;
		$this->updateTime = T_int64;
		$this->remarks = Arr(T_String, 5);
		$this->isPaid = T_bool;
		$this->totalWeight = T_float;
		$this->itemCount = T_ushort;
	}
}

function FB_BasicInfo(&$o)
{
	return FB(i8($o->gender), i16($o->age), $o->id, i64($o->timestamp), f32($o->height), $o->weight, $o->name, $o->avatar, $o->isVIP);
}

function FB_Contact(&$o)
{
	return FB($o->phone, $o->email, $o->address);
}

function FB_Product(&$o)
{
	return FB($o->id, $o->name, $o->price, $o->stock, $o->image, $o->isOnSale);
}

function FB_OrderItem(&$o)
{
	return FB(FB_Product($o->product), $o->quantity, $o->unitPrice, i16($o->discount));
}

function FB_Payment(&$o)
{
	return FB($o->id, i8($o->type), $o->amount, $o->currency, i64($o->timestamp));
}

function FB_ComplexOrder(&$o)
{
	$n = count($o->items);
	$o_items = Arr('', $n);
	for ($i = 0; $i<$n; $i++){
		$o_items[$i] = FB_OrderItem($o->items[$i]);
	}
	$n = count($o->payments);
	$o_payments = Arr('', $n);
	for ($i = 0; $i<$n; $i++){
		$o_payments[$i] = FB_Payment($o->payments[$i]);
	}
	return FB($o->orderId, FB_BasicInfo($o->customerInfo), FB_Contact($o->contactInfo), $o_items, $o_payments, $o->status, i64($o->createTime), i64($o->updateTime), $o->remarks, $o->isPaid, f32($o->totalWeight), i16($o->itemCount));
}

function deFB_BasicInfo(&$p, &$o)
{
	deFB($p, $o->gender, $o->age, $o->id, $o->timestamp, $o->height, $o->weight, $o->name, $o->avatar, $o->isVIP);
}

function deFB_Contact(&$p, &$o)
{
	deFB($p, $o->phone, $o->email, $o->address);
}

function deFB_Product(&$p, &$o)
{
	deFB($p, $o->id, $o->name, $o->price, $o->stock, $o->image, $o->isOnSale);
}

function deFB_OrderItem(&$p, &$o)
{
	$p_product = '';
	deFB($p, $p_product, $o->quantity, $o->unitPrice, $o->discount);
	deFB_Product($p_product, $o->product);
}

function deFB_Payment(&$p, &$o)
{
	deFB($p, $o->id, $o->type, $o->amount, $o->currency, $o->timestamp);
}

function deFB_ComplexOrder(&$p, &$o)
{
	$p_customerInfo = $p_contactInfo = $p_items = $p_payments = '';
	deFB($p, $o->orderId, $p_customerInfo, $p_contactInfo, $p_items, $p_payments, $o->status, $o->createTime, $o->updateTime, $o->remarks, $o->isPaid, $o->totalWeight, $o->itemCount);
	deFB_BasicInfo($p_customerInfo, $o->customerInfo);
	deFB_Contact($p_contactInfo, $o->contactInfo);
	$n = gFB_n($p_items);
	$o->items = Arr(new OrderItem(), $n);
	for ($i = 0; $i<$n; $i++){
		deFB_OrderItem(gFB($p_items, $i), $o->items[$i]);
	}
	$n = gFB_n($p_payments);
	$o->payments = Arr(new Payment(), $n);
	for ($i = 0; $i<$n; $i++){
		deFB_Payment(gFB($p_payments, $i), $o->payments[$i]);
	}
}

