<?php namespace Propel\Common\Util;

class CartesianProduct
{
    public static function get($input) {
        $result = array();

        while (list($key, $values) = each($input)) {
            if (empty($values)) {
                continue;
            }

            if (empty($result)) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                foreach($values as $value) {
                    $result[] = array($key => $value);
                }
            }
            else {
                $append = array();

                foreach($result as &$product) {
                    $product[$key] = array_shift($values);

                    $copy = $product;

                    foreach($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, $product[$key]);
                }

                $result = array_merge($result, $append);
            }
        }

        return $result;
    }
}