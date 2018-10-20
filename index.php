<?php
/**
 * Created by PhpStorm.
 * User: XiaoLin
 * Date: 2018-10-20
 * Time: 2:41 PM
 */

$data = json_decode(file_get_contents("php://input"),true);
if (empty($data) || !isset($data['inline_query'])) die;
error_log(json_encode($data));
$data = $data['inline_query'];

$bot_token = "34342432:45435ntytdh65gwg6srfrsfr";
$server = "https://api.xx.xx/voice";


$dir = __DIR__ . '/voice';
$allowList = [
    '八重樱',
    '卡莲·卡斯兰娜',
    '大伟哥',
    '姬子',
    '布洛妮娅·扎伊切克',
    '德莉莎',
    '月下初拥',
    '氯化娜',
    '琪亚娜',
    '符华',
    '第六夜幻想曲',
    '芽衣',
];

function getFiles($dir)
{
    $handler = opendir($dir);
    $files = [];
    while (($filename = readdir($handler)) !== false)
    {
        if ($filename !== "." && $filename !== "..")
        {
            $files[] = $filename;
        }
    }
    closedir($handler);
    return $files;
}

function retain_key_shuffle(array &$arr){
    if (!empty($arr)) {
        $key = array_keys($arr);
        shuffle($key);
        foreach ($key as $value) {
            $arr2[$value] = $arr[$value];
        }
        $arr = $arr2;
    }
}

$handler = opendir($dir);
$files = [];
while (($filename = readdir($handler)) !== false)
{
    if ($filename !== "." && $filename !== "..")
    {
        //echo substr($filename,5);
        if (in_array($filename,$allowList)) $files[] = $filename;
    }
}
closedir($handler);

$mp3 = [];
foreach ($files as $value) {
    //echo $value . "\n";
    foreach (getFiles($dir . '/' . $value) as $v)
    {
        //echo substr($value,5) . ': ' . $v . "\n";
        $mp3[$value][] = substr($v,0,-4);
    }
}


$name = $data['query'];
if ($name == "")
{
    $raw = [
        [
            'type' => 'article',
            'id' => rand(100000,999999),
            'title' => '请通过角色名或语音内容进行搜索',
            'description' => '目前支持的角色: 八重樱、卡莲、大伟哥、布洛妮娅、月下初拥、第六夜想曲、姬子、氯化娜、德莉莎、符华、芽衣、琪亚娜',
            'thumb_url' => 'https://i.loli.net/2018/10/20/5bcafe2ce01eb.png',
            'input_message_content' => [
                'message_text' => '请通过角色名或语音内容进行搜索',
            ],
        ],
    ];
    goto curl;
}

// Search
$result = [];
foreach ($mp3 as $key => $value)
{
    if (is_int(stripos($key,$name)))
    {
        $tmp = [];
        retain_key_shuffle($value);
        foreach ($value as $v)
        {
            $tmp[] = [
                'character' => $key,
                'filename' => $v,
                'filepath' => $dir . '/' . $key . '/' . $v . '.mp3',
            ];
        }

        $result = $tmp;
    }
}
if (count($result) == 0)
{
    foreach ($mp3 as $key => $value)
    {
        foreach ($value as $item)
        {
            if (is_int(stripos($item,$name)))
            {
                $result[] = [
                    'character' => $key,
                    'filename' => $item,
                    'filepath' => $dir . '/' . $key . '/' . $item . '.mp3',
                ];
            }
        }
    }
}

var_dump($result);


$raw = [];
foreach ($result as $value)
{
    $raw[] = [
        'type' => 'audio',
        'id' => md5($value['filepath']),
        'title' => $value['filename'],
        'audio_url' => $server . '/' . $value['character'] . '/' . $value['filename'] . '.mp3',
        'performer' => $value['character'],
    ];
}

$raw = array_slice($raw,0,15);

if (count($raw) == 0)
    $raw = [
        [
            'type' => 'article',
            'id' => rand(100000,999999),
            'title' => '无结果',
            'description' => '目前支持的角色: 八重樱、卡莲、大伟哥、布洛妮娅、月下初拥、第六夜想曲、姬子、氯化娜、德莉莎、符华、芽衣、琪亚娜',
            'thumb_url' => 'https://i.loli.net/2018/10/20/5bcafe2ce01eb.png',
            'input_message_content' => [
                'message_text' => '无结果',
            ],
        ],
    ];

curl:
error_log(json_encode($raw));


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$bot_token}/answerInlineQuery?inline_query_id={$data['id']}&results=" . urlencode(json_encode($raw)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');


$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);
error_log($result);