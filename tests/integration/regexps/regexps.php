<?php

namespace NS_Regexps;

$s = 'foobarbaz';

preg_match( '/literal!/', $s );

preg_match( '|insensitive|i', $s ); // @todo This is bad

preg_match( '/regex \\\\(x\\\\)/', $s );

preg_match_all( '!with dot.!', $s );

$literal = '|lit|';
preg_match_all( $literal, $s );

preg_match( '!\\(escaped parensl!', $s );

preg_replace( '@lit@', 'lit2', $s );

preg_replace( '@lit@u', 'lit2', $s ); // @todo This is bad

$reg = '~regexp?s~';
preg_replace( $reg, 'regs', $s );
