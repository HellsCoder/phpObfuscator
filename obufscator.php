<?php


$source = $_POST['src'];

$tokens = token_get_all($source, TOKEN_PARSE);

$t_parsed = [];

//str stack
$t_func_stack = [];
$t_func_stack_pointer = 0;
$t_func_name = '__' . rand(100000, 999999);

//var stack
$t_var_stack = [];
$t_var_stack_pointer = 0;
$t_var_holder = 'miwix';

$t_skip_var = array('$this', '$_GET', '$_POST', '$_SERVER');


$read_pointer = 0;

//main loop
foreach ($tokens as $token) {
    if (is_array($token)) {
        $_token = $token[0];
        if ($_token === T_WHITESPACE) {
            continue;
        }
        if($_token === T_FUNCTION){
            $token = $token[1].' ';
        }
        if($_token === T_RETURN){
            $token = $token[1].' ';
        }
        if($_token === T_CLASS){
            $token = $token[1].' ';
        }
        if($_token === T_INCLUDE || $_token === T_INCLUDE_ONCE){
            $token = $token[1].' ';
        }
        if($_token === T_PUBLIC){
            $token = $token[1].' ';
        }
        if($_token === T_PRIVATE || $_token === T_NAMESPACE){
            $token = $token[1].' ';
        }
        if($_token === T_AS){
            $token = ' '.$token[1].' ';
        }
        if($_token === T_CLOSE_TAG){
            $token = '';
        }
        if ($_token === T_CONSTANT_ENCAPSED_STRING) {
            $t_func_stack[] = $token[1];
            $token[1] = $t_func_name.'('.$t_func_stack_pointer.')';
            $t_func_stack_pointer++;
        }
        
        if($_token === T_STRING){
            $t = $token[1];
            $t_var_replace = '';
            $t_find = find_token('$'.$t);
            if($t_find !== false){
                $token[1] = $t_find;
            }
        }
        
        if($_token === T_VARIABLE && !in_array($token[1], $t_skip_var)){
            $t = $token[1];
            $t_var_replace = '';
            $t_find = find_token($t);
            if($t_find !== false){
                $t_var_replace = $t_find;
            }else{
                $t_var_replace = $t_var_holder.'_________'.rand(100000, 999999);
                $t_var_stack[] = array(
                    "t" => $token[1],
                    "r" => $t_var_replace
                );
            }
            $token[1] = '$'.$t_var_replace;
        }
        
        //push reg
        $t_parsed[] = $token;
    } else {
        $t_parsed[] = $token;
    }
    $read_pointer++;
}


echo htmlspecialchars(compile());


function get_token_read($pos){
    global $tokens;
    if(is_array($tokens[$pos])){
        return $tokens[$pos][0];
    }else{
        return 0;
    }
}

function compile(){
    global $t_parsed;
    $code = twalk($t_parsed);
    return $code . '?>' . get_header();
}


//func reg

function find_token($token){
    global $t_var_stack;
    foreach($t_var_stack as $t_stack){
        if(is_array($t_stack)){
            if($t_stack['t'] === $token){
                return $t_stack['r'];
            }
        }
    }
    return false;
}

function get_header() {
    global $t_func_name;
    global $t_func_stack;
    if(count($t_func_stack) <= 0){
        return "";
    }
    $arr = '$a = array(';
    foreach ($t_func_stack as $t_str) {
        $t_str = substr($t_str, 1);
        $t_str = substr($t_str, 0, -1);
        $pack = base64_encode(base64_encode($t_str));
        $len = strlen($pack);
        for($i = 0; $i < $len / 5; $i++){
            $pack = stringInsert($pack, rand(0, $len-1), "'.'");
        }
        $each = "base64_decode('$pack'),";
        $arr .= $each;
    }
    $arr_s = substr($arr, 0, -1);
    $arr_s .= ');';
    return "<?php"
            . ' function ' . $t_func_name . '($v724){'
            . $arr_s
            . 'return base64_decode($a[$v724]);}?>';
}

//util reg

function twalk($tokens) {
    $code = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $code .= $token[1];
        } else {
            $code .= $token;
        }
    }
    return $code;
}


function stringInsert($str, $pos, $insertstr) {
    if($str[$pos] === '.' || $str[$pos] == "'"){
        return $str;
    }
    if (!is_array($pos)){
        $pos = array($pos);    
    }
    $offset = -1;
    foreach ($pos as $p) {
        $offset++;
        $str = substr($str, 0, $p + $offset) . $insertstr . substr($str, $p + $offset);
    } 
    return $str;
}
