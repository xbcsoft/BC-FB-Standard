<?php

// 输入的C语言结构体定义
$input = file_get_contents('in.h');


// C语言基本数据类型到易语言的映射
$typeMapping = [
    'char' => '字节型',
    'short' => '短整数型',
    'int' => '整数型',
    'int64' => '长整数型',
    'bool' => '逻辑型',
    'float' => '小数型',
    'double' => '双精度小数型',
	 'String' => '文本型',
    'Bytes' => '字节集',
];

// 解析输入的结构体定义
$structs = [];
$lines = explode("\n", $input);
$currentStruct = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, 'struct') === 0) {
        // 开始一个新的结构体
        $currentStruct = trim(str_replace(['struct', '{'], '', $line));
        $structs[$currentStruct] = [];
    } elseif (strpos($line, '}') === 0 && $currentStruct !== null) {
        // 结束当前结构体
        $currentStruct = null;
    } elseif ($currentStruct !== null && !empty($line)) {
        // 解析成员
        $line = rtrim($line, ';');
        $parts = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts[0] === 'unsigned') {
            if (count($parts) >= 3) {
                $memberType = $parts[1];
                $memberName = $parts[2];
            } else {
                $memberType = 'int';
                $memberName = $parts[1];
            }
        } else {
            $memberType = $parts[0];
            $memberName = $parts[1];
        }

        // 处理数组
        $arraySize = null;
        if (strpos($memberName, '[') !== false) {
            preg_match('/\[(\d+)\]/', $memberName, $matches);
            $arraySize = $matches[1];
            $memberName = str_replace($matches[0], '', $memberName);
        }

        // 映射到易语言类型
        if (isset($typeMapping[$memberType])) {
            $memberType = $typeMapping[$memberType];
        }

        $structs[$currentStruct][] = [
            'name' => $memberName,
            'type' => $memberType,
            'arraySize' => $arraySize,
        ];
    }
}

// 生成易语言结构体定义
foreach ($structs as $structName => $members) {
    echo ".数据类型 $structName\r\n";
    foreach ($members as $member) {
        $memberName = $member['name'];
        $memberType = $member['type'];
        $arraySize = $member['arraySize'];

        // 处理数组
        if ($arraySize !== null) {
            echo "    .成员 $memberName, $memberType, , \"$arraySize\"\r\n";
        } else {
            echo "    .成员 $memberName, $memberType\r\n";
        }
    }
    echo "\r\n";
}
