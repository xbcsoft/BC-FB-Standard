<?php

// 输入的结构体定义
$input = file_get_contents('in.eh');


// 易语言基本数据类型到C语言的映射
$typeMapping = [
    '字节型' => 'char',
    '短整数型' => 'short',
    '整数型' => 'int',
    '长整数型' => 'int64',
    '逻辑型' => 'bool',
    '小数型' => 'float',
    '双精度小数型' => 'double',
    '文本型' => 'String',
    '字节集' => 'Bytes',
];

// 解析输入的结构体定义
$structs = [];
$lines = explode("\r\n", $input);
$currentStruct = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '.数据类型') === 0) {
        // 开始一个新的结构体
        $currentStruct = trim(str_replace('.数据类型', '', $line));
        $structs[$currentStruct] = [];
    } elseif (strpos($line, '.成员') === 0 && $currentStruct !== null) {
        // 解析成员
        $parts = explode(',', $line);
        $memberName = trim(str_replace('.成员', '', $parts[0]));
        $memberType = trim($parts[1]);
        $arraySize = isset($parts[3]) ? trim($parts[3], ' "') : null; // 解析数组大小
        $structs[$currentStruct][] = [
            'name' => $memberName,
            'type' => $memberType,
            'arraySize' => $arraySize,
        ];
    }
}

// 生成C语言结构体
foreach ($structs as $structName => $members) {
    echo "struct $structName {\r\n";
    foreach ($members as $member) {
        $memberName = $member['name'];
        $memberType = $member['type'];
        $arraySize = $member['arraySize'];

        // 检查是否是嵌套结构体
        if (isset($structs[$memberType])) {
            // 如果是嵌套结构体，直接使用结构体名（不加 struct）
            $cType = $memberType;
        } elseif (isset($typeMapping[$memberType])) {
            // 如果是基本数据类型，映射到C语言类型
            $cType = $typeMapping[$memberType];
        } else {
            // 其他类型（如文本型、字节集），原封不动搬过去
            $cType = $memberType;
        }

        // 处理数组
        if ($arraySize !== null) {
            $cType .= " $memberName" . "[$arraySize]"; // 保留数组大小
        } else {
            $cType .= " $memberName"; // 普通成员
        }

        echo "    $cType;\r\n";
    }
    echo "};\r\n\r\n";
}
