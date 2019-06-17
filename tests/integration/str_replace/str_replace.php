<?php

namespace NS_StrReplace;

$a1 = [ 'foo', 'bar', 'baz' ];
$a2 = [ 'oof', 'rab', 'zab' ];
$str = 'The quick brown fox jumps over the lazy dog';

str_replace( $a1, $a2, $str );

$arr = array_combine( $a1, $a2 );

str_replace( array_keys( $arr ), array_values( $arr ), $str );
