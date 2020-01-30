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
