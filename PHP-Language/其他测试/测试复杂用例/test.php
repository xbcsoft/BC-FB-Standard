<?php
include_once 'FB_complex.php';

// 创建一个复杂订单测试
function test_complex_order() {
    // 创建一个新的ComplexOrder对象
    $order = new ComplexOrder();

    // 设置基本订单信息
    $order->orderId = 'ORD20250223001';
    $order->status = 1;
    $order->createTime = time();
    $order->updateTime = time();
    $order->isPaid = true;
    $order->totalWeight = 15.5;
    $order->itemCount = 2;

    // 设置客户基本信息
    $order->customerInfo->gender = 1;  // 假设1代表男性
    $order->customerInfo->age = 30;
    $order->customerInfo->id = 10086;
    $order->customerInfo->timestamp = time();
    $order->customerInfo->name = '张三';
    $order->customerInfo->isVIP = true;

    // 设置联系方式
    $order->contactInfo->phone = '13800138000';
    $order->contactInfo->email = 'zhangsan@example.com';
    $order->contactInfo->address[0] = '北京市海淀区';
    $order->contactInfo->address[1] = '上海市浦东新区';

    // 添加订单项
    // 第一个商品
    $order->items[0]->product->id = 'PROD001';
    $order->items[0]->product->name = '高级键盘';
    $order->items[0]->product->price = 299.99;
    $order->items[0]->product->stock = 100;
    $order->items[0]->product->isOnSale = true;
    $order->items[0]->quantity = 1;
    $order->items[0]->unitPrice = 299.99;
    $order->items[0]->discount = 95;  // 95折

    // 第二个商品
    $order->items[1]->product->id = 'PROD002';
    $order->items[1]->product->name = '游戏鼠标';
    $order->items[1]->product->price = 199.99;
    $order->items[1]->product->stock = 50;
    $order->items[1]->product->isOnSale = true;
    $order->items[1]->quantity = 2;
    $order->items[1]->unitPrice = 199.99;
    $order->items[1]->discount = 90;  // 90折

    // 添加支付信息
    $order->payments[0]->id = 'PAY001';
    $order->payments[0]->type = 1;  // 假设1代表支付宝
    $order->payments[0]->amount = 699.97;  // 总金额
    $order->payments[0]->currency = 'CNY';
    $order->payments[0]->timestamp = time();

    // 添加备注
    $order->remarks[0] = '普通快递配送';
    $order->remarks[1] = '需要发票';

    // 测试序列化和反序列化
    echo "开始测试序列化和反序列化...\n";

    // 序列化
    $serialized = FB_ComplexOrder($order);
    echo "序列化后的数据大小: " . strlen($serialized) . " 字节\n";

    // 反序列化
    $deserialized = new ComplexOrder();
    deFB_ComplexOrder($serialized, $deserialized);

    // 验证数据
    echo "\n验证反序列化后的数据:\n";
    echo "订单ID: " . $deserialized->orderId . "\n";
    echo "客户姓名: " . $deserialized->customerInfo->name . "\n";
    echo "联系电话: " . $deserialized->contactInfo->phone . "\n";
    echo "商品1名称: " . $deserialized->items[0]->product->name . "\n";
    echo "商品2数量: " . $deserialized->items[1]->quantity . "\n";
    echo "支付金额: " . $deserialized->payments[0]->amount . "\n";
    echo "订单备注1: " . $deserialized->remarks[0] . "\n";

    // 验证数组长度
    echo "\n验证数组长度:\n";
    echo "地址数量: " . count($deserialized->contactInfo->address) . "\n";
    echo "订单项数量: " . count($deserialized->items) . "\n";
    echo "支付记录数量: " . count($deserialized->payments) . "\n";
    echo "备注数量: " . count($deserialized->remarks) . "\n";

    //return $serialized === FB_ComplexOrder($deserialized);
	//file_put_contents('serialized.log',$serialized);
	//file_put_contents('FB_deserialized.log',FB_ComplexOrder($deserialized));

	//var_dump($order);
	return $serialized === FB_ComplexOrder($deserialized);
}

// 运行测试
if(test_complex_order()){
	echo '测试通过';
}else{
	echo '测试失败';
}
