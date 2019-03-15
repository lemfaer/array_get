<?php

/**
 * Get an array value(s) using key(s) / value(s) / index(es)
 * Use array or slice string to get multiple values from array
 *
 * Function modes:
 *  'k' - by comparing array key with search value(s)
 *  'v' - compare array value with searched
 *  'i' - matching indexes
 *  's' - slice: "start:length:step"
 *
 * Special modes with flag:
 *  'is' - indexes separated by comma
 *  'v1' - seatch single array
 *  'k1','i1' - default value is only array
 *  'kf','if' - flip default matching
 *
 * @param array $array
 * @param string $fmode one of 'k', 'v', 'i' or 's'
 * @param mixed $search values to return
 * @param mixed $default default value for 'k' and 'i'
 *
 * @return mixed value(s), array, if $search is array else value/null
 */
function array_get(array $array, $fmode = 'i', $search = 0, $default = null)
{
    $found = array();
    $nargs = func_num_args();
    $mode = substr($fmode, 0, 1);
    $flags = substr($fmode, 1);

    // specific flags
    $flag1 = strpos($flags, '1') !== false;
    $flagf = strpos($flags, 'f') !== false;
    $flagis = strpos($flags, 's') !== false;

    if ($mode === 'k') {
        if (!is_array($search)) {
            if (isset($array[$search])) {
                return $array[$search];
            }

            if ($nargs > 3) {
                return $default;
            }

            return null;
        }

        foreach ($search as $i => $item) {
            if (isset($array[$item])) {
                $found[$item] = $array[$item];
                continue;
            }

            if ($nargs > 3 && $flag1) {
                $found[$item] = $default;
                continue;
            }

            if ($nargs > 3) {
                if ($flagf) {
                    $key = $item;
                } else {
                    $key = $i;
                }

                if (
                    is_array($default)
                    && array_key_exists($key, $default)
                ) {
                    $found[$item] = $default[$key];
                    continue;
                } else {
                    $found[$item] = null;
                    continue;
                }
            }
        }
    }

    if ($mode === 'v') {
        if (!is_array($search) || $flag1) {
            $search = array($search);
        }

        foreach ($search as $i => $item) {
            if ($mode === 'v') {
                foreach ($array as $key => $value) {
                    if ($mode === 'v' && $item === $value) {
                        $found[$key] = $value;
                    }
                }
            }
        }
    }

    if ($mode === 'i') {
        if ($flagis) {
            if (is_string($search) || is_int($search)) {
                $search = explode(',', (string) $search);
                $search = array_map("trim", $search);
            } else {
                return array();
            }
        }

        if (!is_array($search)) {
            if (is_numeric($search) && $search >= -count($array)) {
                $slice = array_slice($array, $search, 1);
            }

            if (!empty($slice)) {
                return array_shift($slice);
            }

            if ($nargs > 3) {
                return $default;
            }

            return null;
        }

        foreach ($search as $i => $item) {
            if (is_numeric($item) && $item >= -count($array)) {
                $slice = array_slice($array, $item, 1);
            }

            if (!empty($slice)) {
                $found[] = array_shift($slice);
                continue;
            }

            if ($nargs > 3 && $flag1) {
                $found[] = $default;
                continue;
            }

            if ($nargs > 3) {
                if ($flagf) {
                    $key = $item;
                } else {
                    $key = $i;
                }

                if (
                    is_array($default)
                    && array_key_exists($key, $default)
                ) {
                    $found[] = $default[$key];
                    continue;
                } else {
                    $found[] = null;
                    continue;
                }
            }
        }
    }

    if ($mode === 's') {
        if (
            !is_string($search)
            || strpos($search, ':') === false
        ) {
            return array();
        }

        $n = count($array);
        $args = explode(':', $search);
        $args += array('', '', '');
        list($sa, $so, $se) = $args;

        $start = strlen($sa) ? (int) $sa : null;
        $stop  = strlen($so) ? (int) $so : null;
        $step  = strlen($se) ? (int) $se : null;

        if (
            (
                isset($start)
                && $start <= -$n
                && $step < 0
            ) || (
                isset($stop)
                && (
                    (
                        $stop <= -$n
                        && $step >= 0
                    ) || (
                        $stop >= $n
                        && $step < 0
                    )
                )
            )
        ) {
            return array();
        }

        if (!isset($start)) {
            $start = 0;
        }

        if (!isset($stop)) {
            $stop = $n;
        }

        if (!isset($step)) {
            $step = 1;
        }

        if ($start < 0) {
            $start = $n - min(abs($start), $n);
        } else {
            $start = min($start, $n);
        }

        if ($stop < 0) {
            $stop = abs($stop) <= $n ? $n - abs($stop) : $n;
        } else {
            $stop = min($stop, $n);
        }

        if ($step < 0) {
            $array = array_reverse($array);
            list($ostart, $ostop) = array($start, $stop);
            $start = $ostart && $ostart < $n ? $n - ($ostart + 1) : 0;
            $stop = $ostop !== $n ? $n - ($ostop + 1) : $n;
            $step = abs($step);
        }

        $i = 0;
        foreach ($array as $key => $value) {
            if (
                $start <= $i
                && $i < $stop
                && ($i - $start) % $step === 0
            ) {
                $found[] = $value;
            }

            $i++;
        }
    }

    return $found;
}

if (
    !class_exists('PHPUnit_Framework_TestCase')
    && class_exists('\PHPUnit\Framework\TestCase')
) {
    class_alias(
        '\PHPUnit\Framework\TestCase',
        'PHPUnit_Framework_TestCase'
    );
}

if (class_exists('PHPUnit_Framework_TestCase')) {
    class TestArrayGet extends PHPUnit_Framework_TestCase
    {
        /**
         * File with additional tests
         * @var string
         */
        // protected $filename = "array_get_test.php.zlib";

        /**
         * @covers array_get
         * @dataProvider provider_mode_key
         */
        function test_mode_key($args, $expected)
        {
            $result = call_user_func_array("array_get", $args);
            $this->assertSame($expected, $result);
        }

        function provider_mode_key()
        {
            $list = array(
                "come with me now",
                "author" => "kongos",
                "genre" => "alternative rock",
                array(
                    "come with me now",
                    "'m gonna take you down",
                    "afraid to lose control"
                ),
                "members" => array(
                    "vocals" => "daniel kongos",
                    "daniel kongos",
                    "dylan kongos",
                    "jesse kongos",
                    "johnny kongos"
                )
            );

            return array(
                "numeric_key" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => 1
                    ),
                    "expected" => array(
                        "come with me now",
                        "'m gonna take you down",
                        "afraid to lose control"
                    )
                ),

                "unknown_numeric" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => 2
                    ),
                    "expected" => null
                ),

                "string_key" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => "author"
                    ),
                    "expected" => "kongos"
                ),

                "unknown_string" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => "instant crush"
                    ),
                    "expected" => null
                ),

                "unknown_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => "boom",
                        "default" => "moon"
                    ),
                    "expected" => "moon"
                ),

                "multiple_mixed" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => array(
                            "members",
                            2,
                            '0',
                            "lucky",
                            123,
                            "genre",
                            "genre",
                            null
                        ),
                        "default" => null
                    ),
                    "expected" => array(
                        "members" => array(
                            "vocals" => "daniel kongos",
                            "daniel kongos",
                            "dylan kongos",
                            "jesse kongos",
                            "johnny kongos"
                        ),
                        2 => null,
                        0 => "come with me now",
                        "lucky" => null,
                        123 => null,
                        "genre" => "alternative rock",
                        null => null
                    )
                ),

                "multiple_mixed_no_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => array(
                            "members",
                            2,
                            '0',
                            "lucky",
                            123,
                            "genre",
                            "genre",
                            null
                        )
                    ),
                    "expected" => array(
                        "members" => array(
                            "vocals" => "daniel kongos",
                            "daniel kongos",
                            "dylan kongos",
                            "jesse kongos",
                            "johnny kongos"
                        ),
                        0 => "come with me now",
                        "genre" => "alternative rock"
                    )
                ),

                "multiple_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k',
                        "search" => array(
                            "members",
                            2,
                            '0',
                            "lucky",
                            123,
                            "genre",
                            "genre",
                            null
                        ),
                        "default" => array(
                            0 => "move on",
                            1 => "get over here",
                            4 => array()
                        )
                    ),
                    "expected" => array(
                        "members" => array(
                            "vocals" => "daniel kongos",
                            "daniel kongos",
                            "dylan kongos",
                            "jesse kongos",
                            "johnny kongos"
                        ),
                        2 => "get over here",
                        0 => "come with me now",
                        "lucky" => null,
                        123 => array(),
                        "genre" => "alternative rock",
                        null => null
                    )
                ),

                "multiple_default_one" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'k1',
                        "search" => array(
                            "members",
                            2,
                            '0',
                            "lucky",
                            123,
                            "genre",
                            "genre",
                            null
                        ),
                        "default" => array(
                            "members" => "move on",
                            1 => "get over here"
                        )
                    ),
                    "expected" => array(
                        "members" => array(
                            "vocals" => "daniel kongos",
                            "daniel kongos",
                            "dylan kongos",
                            "jesse kongos",
                            "johnny kongos"
                        ),
                        2 => array(
                            "members" => "move on",
                            1 => "get over here"
                        ),
                        0 => "come with me now",
                        "lucky" => array(
                            "members" => "move on",
                            1 => "get over here"
                        ),
                        123 => array(
                            "members" => "move on",
                            1 => "get over here"
                        ),
                        "genre" => "alternative rock",
                        null => array(
                            "members" => "move on",
                            1 => "get over here"
                        )
                    )
                ),

                "multiple_default_flip" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'kf',
                        "search" => array(
                            "members",
                            2,
                            '0',
                            "lucky",
                            123,
                            "genre",
                            "genre",
                            null
                        ),
                        "default" => array(
                            "members" => "move on",
                            2 => "get over here",
                            123 => array()
                        )
                    ),
                    "expected" => array(
                        "members" => array(
                            "vocals" => "daniel kongos",
                            "daniel kongos",
                            "dylan kongos",
                            "jesse kongos",
                            "johnny kongos"
                        ),
                        2 => "get over here",
                        0 => "come with me now",
                        "lucky" => null,
                        123 => array(),
                        "genre" => "alternative rock",
                        null => null
                    )
                )
            );
        }

        /**
         * @covers array_get
         * @dataProvider provider_mode_value
         */
        function test_mode_value($args, $expected)
        {
            $result = call_user_func_array("array_get", $args);
            $this->assertSame($expected, $result);
        }

        function provider_mode_value()
        {
            $list = array(
                "labels" => "kitsune",
                "author" => "parcels",
                "members" => array(
                    "louie swain",
                    "patrick hetherington",
                    "noah hill",
                    'anatole "toto" serret',
                    "toto" => "anatole serret",
                    "jules crommelin"
                ),
                "overnight",
                array(
                    "the minute i was thinking to hold you back",
                    "the moment i was wishing, it’s overnight",
                    "the minute i was thinking to hold you back",
                    "the moment i was wishing, it’s overnight"
                )
            );

            return array(
                "string" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'v',
                        "search" => "parcels"
                    ),
                    "expected" => array(
                        "author" => "parcels"
                    )
                ),

                "unknown_string" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'v',
                        "search" => "sharp"
                    ),
                    "expected" => array()
                ),

                "array" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'v',
                        "search" => array(array(
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight",
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight"
                        ))
                    ),
                    "expected" => array(
                        1 => array(
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight",
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight"
                        )
                    )
                ),

                "array_2" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'v1',
                        "search" => array(
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight",
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight"
                        )
                    ),
                    "expected" => array(
                        1 => array(
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight",
                            "the minute i was thinking to hold you back",
                            "the moment i was wishing, it’s overnight"
                        )
                    )
                ),

                "multiple" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'v',
                        "search" => array(
                            "sharp",
                            "parcels",
                            "overnight",
                            "test",
                            "test",
                            "test3",
                            "parcels",
                            "kitsune",
                            "yo"
                        )
                    ),
                    "expected" => array(
                        "author" => "parcels",
                        0 => "overnight",
                        "labels" => "kitsune"
                    )
                )
            );
        }

        /**
         * @covers array_get
         * @dataProvider provider_mode_index
         */
        function test_mode_index($args, $expected)
        {
            $result = call_user_func_array("array_get", $args);
            $this->assertSame($expected, $result);
        }

        function provider_mode_index()
        {
            $list = array(
                "labels" => "indianola",
                "author" => "a day to remember",
                "members" => array(
                    "jeremy mckinnon",
                    "neil westfall",
                    "joshua woodard",
                    "alex shelnutt",
                    "toto" => "joshua woodard",
                    "kevin skaff"
                ),
                "negative space",
                array(
                    "you reflect me in my negative space",
                    "please forgive me for the time you'll waste",
                    "if you let me i'll give you a taste",
                    "everyone eventually lets you down",
                    "everyone eventually lets you down",
                    "lets you down"
                )
            );

            return array(
                "default" => array(
                    "args" => array(
                        "array" => $list
                    ),
                    "expected" => "indianola"
                ),

                "last" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => "-1"
                    ),
                    "expected" => array(
                        "you reflect me in my negative space",
                        "please forgive me for the time you'll waste",
                        "if you let me i'll give you a taste",
                        "everyone eventually lets you down",
                        "everyone eventually lets you down",
                        "lets you down"
                    )
                ),

                "positive" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => 3
                    ),
                    "expected" => "negative space"
                ),

                "negative" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => -4
                    ),
                    "expected" => "a day to remember"
                ),

                "unknown_positive" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => 10
                    ),
                    "expected" => null
                ),

                "unknown_negative" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => -9
                    ),
                    "expected" => null
                ),

                "return_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => -9,
                        "default" => "blood"
                    ),
                    "expected" => "blood"
                ),

                "positive_before_edge" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => 4
                    ),
                    "expected" => array(
                        "you reflect me in my negative space",
                        "please forgive me for the time you'll waste",
                        "if you let me i'll give you a taste",
                        "everyone eventually lets you down",
                        "everyone eventually lets you down",
                        "lets you down"
                    )
                ),

                "positive_after_edge" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => 5
                    ),
                    "expected" => null
                ),

                "negative_before_edge" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => -5
                    ),
                    "expected" => "indianola"
                ),

                "negative_after_edge" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => -6
                    ),
                    "expected" => null
                ),

                "empty_array" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => array()
                    ),
                    "expected" => array()
                ),

                "multiple" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => array(
                            1,
                            10,
                            10,
                            0,
                            0,
                            -1,
                            -10,
                            -5
                        ),
                        "default" => null
                    ),
                    "expected" => array(
                        "a day to remember",
                        null,
                        null,
                        "indianola",
                        "indianola",
                        array(
                            "you reflect me in my negative space",
                            "please forgive me for the time you'll waste",
                            "if you let me i'll give you a taste",
                            "everyone eventually lets you down",
                            "everyone eventually lets you down",
                            "lets you down"
                        ),
                        null,
                        "indianola"
                    )
                ),

                "multiple_no_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => array(
                            1,
                            10,
                            10,
                            0,
                            0,
                            -1,
                            -10,
                            -5
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "indianola",
                        "indianola",
                        array(
                            "you reflect me in my negative space",
                            "please forgive me for the time you'll waste",
                            "if you let me i'll give you a taste",
                            "everyone eventually lets you down",
                            "everyone eventually lets you down",
                            "lets you down"
                        ),
                        "indianola"
                    )
                ),

                "multiple_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i',
                        "search" => array(
                            1,
                            10,
                            10,
                            0,
                            0,
                            -1,
                            -10,
                            -5
                        ),
                        "default" => array(
                            "salt",
                            "demons",
                            6 => array()
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "demons",
                        null,
                        "indianola",
                        "indianola",
                        array(
                            "you reflect me in my negative space",
                            "please forgive me for the time you'll waste",
                            "if you let me i'll give you a taste",
                            "everyone eventually lets you down",
                            "everyone eventually lets you down",
                            "lets you down"
                        ),
                        array(),
                        "indianola"
                    )
                ),

                "multiple_default_one" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'i1',
                        "search" => array(
                            1,
                            10,
                            10,
                            0,
                            0,
                            -1,
                            -10,
                            -5
                        ),
                        "default" => array(
                            10 => "demons",
                            1 => "salt"
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        array(
                            10 => "demons",
                            1 => "salt"
                        ),
                        array(
                            10 => "demons",
                            1 => "salt"
                        ),
                        "indianola",
                        "indianola",
                        array(
                            "you reflect me in my negative space",
                            "please forgive me for the time you'll waste",
                            "if you let me i'll give you a taste",
                            "everyone eventually lets you down",
                            "everyone eventually lets you down",
                            "lets you down"
                        ),
                        array(
                            10 => "demons",
                            1 => "salt"
                        ),
                        "indianola"
                    )
                ),

                "multiple_default_flip" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'if',
                        "search" => array(
                            1,
                            10,
                            10,
                            0,
                            0,
                            -1,
                            -10,
                            -5
                        ),
                        "default" => array(
                            10 => "demons",
                            1 => "salt"
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "demons",
                        "demons",
                        "indianola",
                        "indianola",
                        array(
                            "you reflect me in my negative space",
                            "please forgive me for the time you'll waste",
                            "if you let me i'll give you a taste",
                            "everyone eventually lets you down",
                            "everyone eventually lets you down",
                            "lets you down"
                        ),
                        null,
                        "indianola"
                    )
                ),

                "string_null" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => null
                    ),
                    "expected" => array()
                ),

                "string_positive" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => 1
                    ),
                    "expected" => array(
                        "a day to remember"
                    )
                ),

                "string_negative" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => "-2"
                    ),
                    "expected" => array(
                        "negative space"
                    )
                ),

                "string_multiple" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => "1, -2, 10",
                        "default" => null
                    ),
                    "expected" => array(
                        "a day to remember",
                        "negative space",
                        null
                    )
                ),

                "string_multiple_no_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => "1, -2, 10"
                    ),
                    "expected" => array(
                        "a day to remember",
                        "negative space"
                    )
                ),

                "string_skip" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => ",-2,10",
                        "default" => null
                    ),
                    "expected" => array(
                        null,
                        "negative space",
                        null
                    )
                ),

                "string_skip_no_defatult" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => ",-2,10"
                    ),
                    "expected" => array(
                        "negative space"
                    )
                ),

                "string_default" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is',
                        "search" => "1,-2,10",
                        "default" => array(
                            "swarm",
                            2 => "last time"
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "negative space",
                        "last time"
                    )
                ),

                "string_default_flip" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'ifs',
                        "search" => "1,-2,10",
                        "default" => array(
                            1 => "swarm",
                            10 => "graceless"
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "negative space",
                        "graceless"
                    )
                ),

                "string_default_one" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 'is1',
                        "search" => "1,-2,10",
                        "default" => array(
                            1 => "swarm",
                            10 => "last time"
                        )
                    ),
                    "expected" => array(
                        "a day to remember",
                        "negative space",
                        array(
                            1 => "swarm",
                            10 => "last time"
                        )
                    )
                )
            );
        }

        /**
         * @covers array_get
         * @dataProvider provider_mode_slice
         */
        function test_mode_slice($args, $expected)
        {
            $result = call_user_func_array("array_get", $args);
            $this->assertSame($expected, $result);
        }

        function provider_mode_slice()
        {
            $list = array(
                -11, 31, -93, 28, -24,
                67, -72, -50, 69, 60,
                -20, -38, 53, 100, -98,
                90, -27, 75, -26, 98,
                -28, -63, 68, -22, -49
            );

            $basic = array(
                "copy" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ':'
                    ),
                    "expected" => $list
                ),

                "limit" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":7"
                    ),
                    "expected" => array(
                        -11, 31, -93, 28, -24,
                        67, -72
                    )
                ),

                "nlimit" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":-5"
                    ),
                    "expected" => array(
                        -11, 31, -93, 28, -24,
                        67, -72, -50, 69, 60,
                        -20, -38, 53, 100, -98,
                        90, -27, 75, -26, 98
                    )
                ),

                "offset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "13:"
                    ),
                    "expected" => array(
                        100, -98, 90, -27, 75,
                        -26, 98, -28, -63, 68,
                        -22, -49
                    )
                ),

                "noffset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "-10:"
                    ),
                    "expected" => array(
                        90, -27, 75, -26, 98,
                        -28, -63, 68, -22, -49
                    )
                ),

                "step" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "::3"
                    ),
                    "expected" => array(
                        -11, 28, -72, 60, 53,
                        90, -26, -63, -49
                    )
                ),

                "nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "::-3"
                    ),
                    "expected" => array(
                        -49, -63, -26, 90, 53,
                        60, -72, 28, -11
                    )
                ),

                "limit_offset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "4:20"
                    ),
                    "expected" => array(
                        -24, 67, -72, -50, 69,
                        60, -20, -38, 53, 100,
                        -98, 90, -27, 75, -26,
                        98
                    )
                ),

                "nlimit_offset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "4:-10"
                    ),
                    "expected" => array(
                        -24, 67, -72, -50, 69,
                        60, -20, -38, 53, 100,
                        -98
                    )
                ),

                "limit_noffset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "-5:2"
                    ),
                    "expected" => array()
                ),

                "nlimit_noffset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "-7:-2"
                    ),
                    "expected" => array(
                        -26, 98, -28, -63, 68
                    )
                ),

                "offset_step" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "4::2"
                    ),
                    "expected" => array(
                        -24, -72, 69, -20, 53,
                        -98, -27, -26, -28, 68,
                        -49
                    )
                ),

                "noffset_step" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "-4::2"
                    ),
                    "expected" => array(
                        -63, -22
                    )
                ),

                "offset_nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "5::-2"
                    ),
                    "expected" => array(
                        67, 28, 31
                    )
                ),

                "noffset_nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "-3::-2"
                    ),
                    "expected" => array(
                        68, -28, -26, -27, -98,
                        53, -20, 69, -72, -24,
                        -93, -11
                    )
                ),

                "limit_step" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":20:4"
                    ),
                    "expected" => array(
                        -11, -24, 69, 53, -27
                    )
                ),

                "nlimit_step" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":-5:4"
                    ),
                    "expected" => array(
                        -11, -24, 69, 53, -27
                    )
                ),

                "limit_nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":5:-4"
                    ),
                    "expected" => array(
                        -49, -28, -27, 53, 69
                    )
                ),

                "nlimit_nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => ":-4:-2"
                    ),
                    "expected" => array(
                        -49, 68
                    )
                ),

                "full" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "2:15:4"
                    ),
                    "expected" => array(
                        -93, -72, -20, -98
                    )
                ),

                "full_nstep" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => '20:2:-2'
                    ),
                    "expected" => array(
                        -28, -26, -27, -98, 53,
                        -20, 69, -72, -24
                    )
                ),

                "reverse" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => "::-1"
                    ),
                    "expected" => array(
                        -49, -22, 68, -63, -28,
                        98, -26, 75, -27, 90,
                        -98, 100, 53, -38, -20,
                        60, 69, -50, -72, 67,
                        -24, 28, -93, 31, -11
                    )
                ),

                "reverse_limit_offset" => array(
                    "args" => array(
                        "array" => $list,
                        "mode" => 's',
                        "search" => '20:2:-1'
                    ),
                    "expected" => array(
                        -28, 98, -26, 75, -27,
                        90, -98, 100, 53, -38,
                        -20, 60, 69, -50, -72,
                        67, -24, 28
                    )
                )
            );

            $tests = $basic;
            $this->file = __DIR__ . "/" . $this->filename;

            if (is_file($this->file)) {
                $data = json_decode(
                    gzuncompress(
                        file_get_contents(
                            $this->file
                        )
                    ),
                    true
                );

                foreach ($data as $i => $row) {
                    list($params, $result) = $row;
                    list($sa, $so, $se) = $params;
                    $str = "$sa:$so:$se";

                    $tests[$str] = array(
                        "args" => array(
                            "array" => $list,
                            "mode" => 's',
                            "search" => $str
                        ),
                        "expected" => $result
                    );
                }
            }

            return $tests;
        }
    }
}
