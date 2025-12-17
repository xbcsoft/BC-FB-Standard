<?php

// C语言类型到PHP类型的映射
$TYPE_MAP = [
    'char' => 'T_char',
    'short' => 'T_short',
    'int' => 'T_int',
    'int64' => 'T_int64',
    'uchar' => 'T_uchar',
    'ushort' => 'T_ushort',
    'uint' => 'T_uint',
    'bool' => 'T_bool',
    'float' => 'T_float',
    'double' => 'T_double',
    'String' => 'T_String',
    'Bytes' => 'T_Bytes'
];

// 需要特殊处理的类型映射到对应的函数
$PACK_FUNC_MAP = [
    'char' => 'i8',
    'short' => 'i16',
    'int64' => 'i64',
    'uchar' => 'i8',
    'ushort' => 'i16',
    'float' => 'f32'
];

// 数组类型的特殊处理函数前缀
$ARR_PACK_PREFIX = 'arr_';

// 解析C语言结构体定义的函数
function parse_struct($struct_text) {
    $result = [];

    // 提取结构体名称和内容
    if (!preg_match('/struct\s+([\w\x{4e00}-\x{9fa5}]+)\s*{([^}]+)};/su', $struct_text, $matches)) {
        return null;
    }

    $struct_name = $matches[1];
    $struct_content = $matches[2];

    // 解析每一行
    $lines = explode(';', $struct_content);
    $fields = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // 检查是否是数组
        if (preg_match('/(\w+)\s+([\w\x{4e00}-\x{9fa5}]+)\[(\d+)\]/u', $line, $matches)) {
            $type = $matches[1];
            $name = $matches[2];
            $size = $matches[3];
            $fields[$name] = ['type' => $type, 'is_array' => true, 'size' => $size];
        }
        // 普通字段
        else if (preg_match('/(\w+)\s+([\w\x{4e00}-\x{9fa5}]+)/u', $line, $matches)) {
            $type = $matches[1];
            $name = $matches[2];
            $fields[$name] = ['type' => $type, 'is_array' => false];
        }
    }

    return ['name' => $struct_name, 'fields' => $fields];
}

// 生成new_类函数的代码
function generate_new_functions($structs) {
    global $TYPE_MAP;

    $code = "<?php\ninclude_once 'FB.php';\n\n";

    foreach ($structs as $struct) {
        // generate PHP class for the struct
        $code .= "class {$struct['name']}\n{\n";
        // emit constructor immediately after properties (no extra blank line)
        $code .= "\tfunction __construct() {\n";
        foreach ($struct['fields'] as $name => $field) {
            if ($field['is_array']) {
                if (isset($TYPE_MAP[$field['type']])) {
                    $init = "Arr({$TYPE_MAP[$field['type']]}, {$field['size']})";
                } else {
                    $init = "Arr(new {$field['type']}(), {$field['size']})";
                }
            } else {
                if (isset($TYPE_MAP[$field['type']])) {
                    $init = $TYPE_MAP[$field['type']];
                } else {
                    $init = "new {$field['type']}()";
                }
            }
            $code .= "\t\t\$this->" . $name . " = " . $init . ";\n";
        }
        $code .= "\t}\n}\n\n";

    // no factory function needed: class has constructor
    }

    return $code;
}

// 生成FB_类函数的代码
function generate_fb_functions($structs) {
    global $PACK_FUNC_MAP, $TYPE_MAP, $ARR_PACK_PREFIX;

    $code = "";

    foreach ($structs as $struct) {
        $code .= "function FB_{$struct['name']}(&\$o)\n{\n";

        // 处理数组字段（嵌套 struct 数组需要先转换为序列化 blob 列表）
        foreach ($struct['fields'] as $name => $field) {
            if ($field['is_array'] && !isset($TYPE_MAP[$field['type']])) {
                $code .= "\t\$n = count(\$o->" . $name . ");\n";
                $code .= "\t\$o_" . $name . " = Arr('', \$n);\n";
                $code .= "\tfor (\$i = 0; \$i<\$n; \$i++){\n";
                $code .= "\t\t\$o_" . $name . "[\$i] = FB_" . $field['type'] . "(\$o->" . $name . "[\$i]);\n";
                $code .= "\t}\n";
            }
        }

        $code .= "\treturn FB(";
        $params = [];

        foreach ($struct['fields'] as $name => $field) {
            if ($field['is_array']) {
                if (isset($TYPE_MAP[$field['type']])) {
                    if (isset($PACK_FUNC_MAP[$field['type']])) {
                        $params[] = $ARR_PACK_PREFIX . $PACK_FUNC_MAP[$field['type']] . "(\$o->" . $name . ")";
                    } else {
                        $params[] = "\$o->" . $name;
                    }
                } else {
                    $params[] = "\$o_" . $name;
                }
            } else {
                if (isset($TYPE_MAP[$field['type']])) {
                    if (isset($PACK_FUNC_MAP[$field['type']])) {
                        $params[] = $PACK_FUNC_MAP[$field['type']] . "(\$o->" . $name . ")";
                    } else {
                        $params[] = "\$o->" . $name;
                    }
                } else {
                    $params[] = "FB_" . $field['type'] . "(\$o->" . $name . ")";
                }
            }
        }

        $code .= implode(", ", $params);
        $code .= ");\n}\n\n";
    }

    return $code;
}

// 生成deFB_类函数的代码
function generate_defb_functions($structs) {
    global $TYPE_MAP;

    $code = "";

    foreach ($structs as $struct) {
        $code .= "function deFB_{$struct['name']}(&\$p, &\$o)\n{\n";

        // 仅为嵌套（自定义 struct）字段声明临时变量
        $temp_vars = [];
        foreach ($struct['fields'] as $name => $field) {
            if (!isset($TYPE_MAP[$field['type']])) {
                $temp_vars[] = "\$p_$name";
            }
        }
        if (!empty($temp_vars)) {
            $code .= "\t" . implode(" = ", $temp_vars) . " = '';\n";
        }

        // 解包参数：基础类型或基础类型数组直接传入对象属性供 deFB 写回；嵌套类型使用临时变量
        $params = [];
        foreach ($struct['fields'] as $name => $field) {
            if (!isset($TYPE_MAP[$field['type']])) {
                // 嵌套 struct 使用临时变量
                $params[] = "\$p_$name";
            } else {
                // 基础类型或基础类型数组，直接传对象属性引用
                $params[] = "\$o->" . $name;
            }
        }
        $code .= "\tdeFB(\$p, " . implode(", ", $params) . ");\n";

        // 处理嵌套结构体和数组：对嵌套 struct（单个或数组）进行递归解包
        foreach ($struct['fields'] as $name => $field) {
            if (!isset($TYPE_MAP[$field['type']])) {
                // 嵌套类型
                if ($field['is_array']) {
                    $code .= "\t\$n = gFB_n(\$p_{$name});\n";
                    $code .= "\t\$o->{$name} = Arr(new {$field['type']}(), \$n);\n";
                    $code .= "\tfor (\$i = 0; \$i<\$n; \$i++){\n";
                    $code .= "\t\tdeFB_{$field['type']}(gFB(\$p_{$name}, \$i), \$o->{$name}[\$i]);\n";
                    $code .= "\t}\n";
                } else {
                    $code .= "\tdeFB_{$field['type']}(\$p_{$name}, \$o->{$name});\n";
                }
            }
        }

        $code .= "}\n\n";
    }

    return $code;
}

// 处理C语言结构体文本的主函数
function process_c_structs($c_text) {
    // 分离每个结构体定义
    preg_match_all('/struct\s+[\w\x{4e00}-\x{9fa5}]+\s*{[^}]+};/su', $c_text, $matches);

    $structs = [];
    foreach ($matches[0] as $struct_text) {
        $struct = parse_struct($struct_text);
        if ($struct) {
            $structs[] = $struct;
        }
    }

    // 生成所有需要的函数
    $code = generate_new_functions($structs);
    $code .= generate_fb_functions($structs);
    $code .= generate_defb_functions($structs);

    return $code;
}

// 命令行参数处理
if ($argc < 2) {
    echo "Usage: php CStructToPhp.php <input_file.h>\n";
    echo "Output will be generated as FB_<input_file_without_h>.php in the same directory\n";
    exit(1);
}

$input_file = $argv[1];

// 检查输入文件是否存在
if (!file_exists($input_file)) {
    echo "Error: Input file '$input_file' does not exist.\n";
    exit(1);
}

// 检查文件扩展名是否为.h
$path_info = pathinfo($input_file);
if ($path_info['extension'] !== 'h') {
    echo "Error: Input file must have .h extension\n";
    exit(1);
}

function preprocess_c_code($code) {
	// 去除单行注释
	$code = preg_replace('/\/\/[^\n]*/', '', $code);
	// 去除多行注释
	$code = preg_replace('/\/\*[\s\S]*?\*\//', '', $code);
	return $code;
}

// 生成输出文件名
$output_file = dirname($input_file) . DIRECTORY_SEPARATOR . 'FB_' . $path_info['filename'] . '.php';

// 读取输入文件
$c_text = preprocess_c_code(file_get_contents($input_file));

// 生成PHP代码
$php_code = process_c_structs($c_text);

// 写入输出文件
if (file_put_contents($output_file, $php_code) === false) {
    echo "Error: Failed to write to output file '$output_file'.\n";
    exit(1);
}

echo "Successfully generated PHP code in '$output_file'.\n";
