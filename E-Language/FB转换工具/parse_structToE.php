<?php

function parseEFileStructFile($filename) {
    if (!file_exists($filename)) {
        die("Error: File '$filename' not found.\n");
    }
    
    $content = file_get_contents($filename);
    
    // 存储所有结构体定义
    $structures = [];
    $currentStruct = null;
    
    // 按行读取并解析
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // 匹配数据类型定义
        if (preg_match('/^\.数据类型\s+([_\p{L}\d]+)/u', $line, $matches)) {
            $currentStruct = $matches[1];
            $structures[$currentStruct] = [];
        }
        // 匹配成员定义，包括可能的数组大小
        elseif ($currentStruct && preg_match('/^\.成员\s+([^,]+),\s*([^,]+)(?:,\s*[^,]*,\s*"(\d+)")?/', $line, $matches)) {
            $memberName = trim($matches[1]);
            $memberType = trim($matches[2]);
            $arraySize = isset($matches[3]) ? $matches[3] : null;
            
            $structures[$currentStruct][$memberName] = [
                'type' => $memberType,
                'isArray' => ($arraySize !== null),
                'arraySize' => $arraySize
            ];
        }
    }
    
    return $structures;
}

function parseStructFile($filename) {
    // 根据文件扩展名选择解析方法
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'eh') {
        return parseEFileStructFile($filename);
    }
    
    // 原有的C头文件解析逻辑
    if (!file_exists($filename)) {
        die("Error: File '$filename' not found.\n");
    }
    
    $content = file_get_contents($filename);
    
    // 存储所有结构体定义
    $structures = [];
    
    // 匹配所有结构体定义，支持中文命名
    preg_match_all('/(?:struct|typedef struct)\s+([_\p{L}\d]+)?\s*{([^}]+)}\s*([_\p{L}\d]+)?;/su', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $structName = !empty($match[1]) ? $match[1] : $match[3];
        $members = [];
        
        // 解析成员，支持中文命名
        preg_match_all('/([_\p{L}\d]+(?:\s+[_\p{L}\d]+)*)\s+([_\p{L}\d]+)(?:\[(\d+)\])?;/mu', $match[2], $memberMatches, PREG_SET_ORDER);
        
        foreach ($memberMatches as $member) {
            $type = trim($member[1]);
            $name = $member[2];
            $isArray = isset($member[3]);
            $arraySize = $isArray ? (int)$member[3] : null;
            
            $members[$name] = [
                'type' => $type,
                'isArray' => $isArray,
                'arraySize' => $arraySize
            ];
        }
        
        $structures[$structName] = $members;
    }
    
    return $structures;
}

function generateFBString($structName, $prefix, $structures) {
    if (!isset($structures[$structName])) {
        die("Error: Structure '$structName' not found in the header file.\n");
    }
    
    $result = [];
    $localVars = [];
    $code = [];
    
    foreach ($structures[$structName] as $memberName => $memberInfo) {
        $currentPath = $prefix . '.' . $memberName;
        
        if (isset($structures[$memberInfo['type']])) {
            // 这是一个嵌套结构体
            if ($memberInfo['isArray']) {
                // 如果是结构体数组，生成循环代码
                $varName = "{$prefix}_{$memberName}";
                $localVars[] = ".局部变量 $varName, 字节集, , \"0\"";
                if (!in_array(".局部变量 i, 整数型", $localVars)) {
                    $localVars[] = ".局部变量 i, 整数型";
                }
                if (!in_array(".局部变量 n, 整数型", $localVars)) {
                    $localVars[] = ".局部变量 n, 整数型";
                }
                
                $code[] = "n ＝ 取数组成员数 ($currentPath)";
                $code[] = "重定义数组 ($varName, 假, n)";
                $code[] = ".计次循环首 (n, i)";
                $code[] = "    $varName [i] ＝ FB_{$memberInfo['type']}($currentPath [i])";
                $code[] = ".计次循环尾 ()";
                $result[] = $varName;  
            } else {
                // 非数组结构体，直接调用其FB函数
                $result[] = "FB_{$memberInfo['type']}($currentPath)";
            }
        } else {
            // 基本类型
            $result[] = $currentPath;
        }
    }
    
    return [
        'localVars' => $localVars,
        'code' => $code,
        'result' => implode(', ', $result)
    ];
}

function generateAllFunctions($structures) {
    $functions = [];
    
    // 为每个结构体生成函数
    foreach ($structures as $structName => $members) {
        $generated = generateFBString($structName, 'o', $structures);
        $functionBody = [];
        $functionBody[] = ".子程序 FB_$structName, 字节集";
        $functionBody[] = ".参数 o, $structName";
        
        // 添加局部变量
        if (!empty($generated['localVars'])) {
            $functionBody = array_merge($functionBody, $generated['localVars']);
        }
        
        // 添加代码
        if (!empty($generated['code'])) {
            $functionBody = array_merge($functionBody, $generated['code']);
        }
        
        $functionBody[] = "返回 (FB (" . $generated['result'] . "))";
        
        $functions[] = implode("\r\n", $functionBody);
    }
    
    return implode("\r\n\r\n", $functions);
}

function generateDeFBFunction($structName, $structures) {
    if (!isset($structures[$structName])) {
        die("Error: Structure '$structName' not found in the header file.\n");
    }
    
    $localVars = [];
    $deFBCalls = [];
    $deFBParams = [];  // 用于按顺序存储 deFB 的参数
    $needLoopVars = false;
    
    foreach ($structures[$structName] as $memberName => $memberInfo) {
        if (isset($structures[$memberInfo['type']])) {
            // 这是一个嵌套结构体
            if ($memberInfo['isArray']) {
                // 如果是结构体数组，才需要循环变量
                $localVars[] = ".局部变量 p_$memberName, 子程序指针";
                $needLoopVars = true;
                $deFBParams[] = "p_$memberName";
                $deFBCalls[] = "n ＝ pFB_n (p_$memberName)";
                $deFBCalls[] = "重定义数组 (o.$memberName, 假, n)";
                $deFBCalls[] = ".计次循环首 (n, i)";
                $deFBCalls[] = "    deFB_{$memberInfo['type']} (pFB_p (p_$memberName, i), o.$memberName [i])";
                $deFBCalls[] = ".计次循环尾 ()";
            } else {
                // 非数组结构体
                $localVars[] = ".局部变量 p_$memberName, 子程序指针";
                $deFBParams[] = "p_$memberName";
                $deFBCalls[] = "deFB_{$memberInfo['type']} (p_$memberName, o.$memberName)";
            }
        } else {
            // 基本类型
            $deFBParams[] = "o.$memberName";
        }
    }
    
    $functionBody = [];
    $functionBody[] = ".子程序 deFB_$structName";
    $functionBody[] = ".参数 p, 子程序指针";
    $functionBody[] = ".参数 o, $structName";
    
    // 只在需要时添加循环变量（只有结构体数组才需要）
    if ($needLoopVars) {
        $functionBody[] = ".局部变量 n, 整数型";
        $functionBody[] = ".局部变量 i, 整数型";
    }
    
    // 添加其他局部变量（指针变量）
    foreach ($localVars as $var) {
        $functionBody[] = $var;
    }
    
    // 按照结构体成员的顺序生成 deFB 调用
    if (!empty($deFBParams)) {
        $functionBody[] = "deFB (p, " . implode(", ", $deFBParams) . ")";
    }
    
    $functionBody = array_merge($functionBody, $deFBCalls);
    
    return implode("\r\n", $functionBody);
}

function generateAllDeFBFunctions($structures) {
    $functions = [];
    
    foreach ($structures as $structName => $members) {
        $functions[] = generateDeFBFunction($structName, $structures);
    }
    
    return implode("\r\n\r\n", $functions);
}


// 解析头文件
$structures = parseStructFile("in.h");

// 生成所有函数
echo generateAllFunctions($structures) . "\r\n";

// 生成所有 deFB 函数
echo generateAllDeFBFunctions($structures) . "\r\n";
