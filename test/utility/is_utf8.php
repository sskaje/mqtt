<?php
require(__DIR__ . '/../test.inc.php');

use \sskaje\mqtt\Utility;

# alphabet + num
var_dump(Utility::ValidateUTF8("aaaa"));
var_dump(Utility::ValidateUTF8("01234817"));
var_dump(Utility::ValidateUTF8("0123.4817"));
var_dump(Utility::ValidateUTF8("01234E10+3=123"));
var_dump(Utility::ValidateUTF8("aaaaasdf123123412"));


# multiple byte
var_dump(Utility::ValidateUTF8("AA啊"));
var_dump(Utility::ValidateUTF8("放放风"));
var_dump(Utility::ValidateUTF8("a首发式地asdf方"));
var_dump(Utility::ValidateUTF8("阿三大法师地方aaa"));


# emoji: apple
var_dump(Utility::ValidateUTF8("🍎"));
# emoji: computer
var_dump(Utility::ValidateUTF8("💻"));
# emoji: car
var_dump(Utility::ValidateUTF8("🚗"));
# emoji: smile
var_dump(Utility::ValidateUTF8("😊"));



# bad cases
try {
    var_dump(Utility::ValidateUTF8(chr(0x7d) . chr(0xbb)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0x7d) . chr(0xff)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0xef) . chr(0xbb)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0xef) . chr(0xbb) . chr(0xbf)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0xef) . chr(0xff) . chr(0x0a)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}

try {
    var_dump(Utility::ValidateUTF8(chr(0xc0) . chr(0x0a)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0xc0) . chr(0xba)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}

# U+D800 ~ U+DFFF
try {
    var_dump(Utility::ValidateUTF8(chr(0xed) . chr(0xa0) . chr(0x80)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump(Utility::ValidateUTF8(chr(0xef) . chr(0xff)));
} catch (\Exception $e) {
    var_dump($e->getMessage());
}


