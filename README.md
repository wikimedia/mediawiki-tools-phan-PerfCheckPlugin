*WIP*

Phan plugin to detect common potential performance holes. This uses static analysis so there's not much it can do.
Given the nature of micro-optimization (and how bad it can be), every sniff is intended to help people, instead of
bothering them, and help code readability. See acceptance criteria below.

The following is a raw copypasta from my original list.

DONE
-array_map *with lambdas* https://3v4l.org/fmBPD - https://3v4l.org/LdeNS
-$db->select inside loops
-function calls in loop cond e.g. for ( $i = 0; $i < count( $x ) )
-preg_match/preg_match_all/preg_replace used with plain strings (->strpos,str_replace)
-switch instead of elseif https://3v4l.org/DdSBu
-strtr is *sometimes* faster than str_replace:
	-prefer str_replace if you already have keys and values separate https://3v4l.org/4katS 
	-but prefer strtr if you have them together, instead of doing str_replace(array_keys($a),array_values($a),$s) https://3v4l.org/Us60j

-(from phpcs) in_array/array_key_exists/isset

(-literals (or anyway immutable values) unconditionally assigned to vars inside loops)


DUBIOUS (probably as low prio)
-ctype instead of preg_match - https://3v4l.org/PnAh3 ? watch out for Tim's comment
-$a[$v] = 1 and array_keys instead of array_unique https://3v4l.org/ocvBh (??)
-++$i when return value is unused (e.g. in loops) https://3v4l.org/qu6Qg (????)


PROVED UNWORTHY
-NO single quotes instead of double quotes if no vars/special chars
-NO params to echo instead of concat https://3v4l.org/RH4qt
-NO foreach k/v vs array_keys https://3v4l.org/IVAjP (?)
-NO isset/array_key_exists on array_flip/array_fill_keys instead of in_array https://3v4l.org/8iO3p

---

ACCEPTANCE CRITERIA
-Doesn't affect code readability (or improves it) - e.g. isset instead of strlen is bad
-Clear and obvious reduction of runtime
-Don't throw tons of warnings for micro-optimizable code bits repeated lots of times throughout the codebase - e.g. single quotes, or even ++$i
-Must be detectable with static analysis - e.g. can't tell if $x = func() inside function loops can be extracted or not