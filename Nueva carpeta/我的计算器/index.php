<?php
$当前值 = 0;
$输入 = [];

function 获取输入字符串($值们){
    $输出 = "";
    foreach ($值们 as $值){
        $输出 .= $值;
    }
    return $输出;
}

function 计算输入($用户输入){
    // 格式化用户输入
    $数组 = [];
    $字符 = "";
    foreach ($用户输入 as $输入项){
        if(is_numeric($输入项) || $输入项 == "."){
            $字符 .= $输入项;
        } else {
            if(!empty($字符)){
                $数组[] = $字符;
                $字符 = "";
            }
            $数组[] = $输入项;
        }
    }
    if(!empty($字符)){
        $数组[] = $字符;
    }
    // 计算用户输入

    $当前 = 0;
    $操作 = null;
    for($i=0; $i <= count($数组)-1; $i++){
        if(is_numeric($数组[$i]) || strpos($数组[$i], '.') !== false){
            if($操作){
                if($操作 == "＋"){
                    $当前 += $数组[$i];
                } elseif($操作 == "－"){
                    $当前 -= $数组[$i];
                } elseif($操作 == "×"){
                    $当前 *= $数组[$i];
                } elseif($操作 == "÷"){
                    $当前 /= $数组[$i];
                }
                $操作 = null;
            } else {
                if($当前 == 0){
                    $当前 = $数组[$i];
                }
            }
        } else {
            $操作 = $数组[$i];
        }
    }
    return $当前;
}

if($_SERVER['REQUEST_METHOD'] == "POST"){
    if(isset($_POST['输入'])){
        $输入 = json_decode($_POST['输入']);
    }

    if(isset($_POST)){
        foreach ($_POST as $键 => $值){
            if($键 == '等于'){
               $当前值 = 计算输入($输入);
               $输入 = [];
               $输入[] = $当前值;
            } elseif($键 == "清除输入"){
                array_pop($输入);
            } elseif($键 == "清除全部"){
                $输入 = [];
                $当前值 = 0;
            } elseif($键 == "返回"){
                $最后指针 = count($输入) -1;
                if(is_numeric($输入[$最后指针])){
                    array_pop($输入);
                }
            } elseif($键 != '输入'){
                $输入[] = $值;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>我的计算器</title>
    <style>
        body {
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }
        .计算器 {
            background-color: #ffffff;
            border: 2px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
            width: 320px;
        }
        .显示屏 {
            background-color: #222;
            color: #fff;
            font-size: 2em;
            padding: 10px;
            border-radius: 5px;
            text-align: right;
            margin-bottom: 10px;
            min-height: 50px;
            word-wrap: break-word;
        }
        .显示值 {
            background-color: #444;
            color: #fff;
            font-size: 1.5em;
            padding: 10px;
            border: none;
            width: 100%;
            text-align: right;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .按钮区 {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .按钮区 button, .按钮区 input[type="submit"] {
            width: 70px;
            height: 50px;
            font-size: 1.2em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .按钮区 button:hover, .按钮区 input[type="submit"]:hover {
            background-color: #ddd;
        }
        .按钮区 button:active, .按钮区 input[type="submit"]:active {
            transform: scale(0.98);
        }
        .操作 {
            background-color: #f5923e;
            color: #fff;
        }
        .数字 {
            background-color: #e0e0e0;
        }
        .功能 {
            background-color: #a6a6a6;
            color: #fff;
        }
        .等于 {
            background-color: #4caf50;
            color: #fff;
            width: 150px;
        }
    </style>
</head>
<body>
<div class="计算器">
    <form method="post">
        <input type="hidden" name="输入" value='<?php echo json_encode($输入);?>'/>
        <div class="显示屏"><?php echo 获取输入字符串($输入);?></div>
        <input type="text" class="显示值" value="<?php echo $当前值;?>" readonly/>
        <div class="按钮区">
            <input type="submit" name="清除输入" value="CE" class="功能"/>
            <input type="submit" name="清除全部" value="C" class="功能"/>
            <button type="submit" name="返回" value="返回" class="功能">&#8592;</button>
            <button type="submit" name="除" value="÷" class="操作">÷</button>

            <input type="submit" name="7" value="7" class="数字"/>
            <input type="submit" name="8" value="8" class="数字"/>
            <input type="submit" name="9" value="9" class="数字"/>
            <button type="submit" name="乘" value="×" class="操作">×</button>

            <input type="submit" name="4" value="4" class="数字"/>
            <input type="submit" name="5" value="5" class="数字"/>
            <input type="submit" name="6" value="6" class="数字"/>
            <button type="submit" name="减" value="－" class="操作">－</button>

            <input type="submit" name="1" value="1" class="数字"/>
            <input type="submit" name="2" value="2" class="数字"/>
            <input type="submit" name="3" value="3" class="数字"/>
            <button type="submit" name="加" value="＋" class="操作">＋</button>

            <button type="submit" name="正负" value="±" class="功能">±</button>
            <input type="submit" name="0" value="0" class="数字"/>
            <input type="submit" name="." value="." class="数字"/>
            <input type="submit" name="等于" value="=" class="等于"/>
        </div>
    </form>
</div>
</body>
</html>
