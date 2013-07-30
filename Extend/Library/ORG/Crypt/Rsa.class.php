<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
define("BCCOMP_LARGER", 1);
/**
 * Rsa 加密实现类
 * @category   ORG
 * @package  ORG
 * @subpackage  Crypt
 * @author    liu21st <liu21st@gmail.com>
 */
class Rsa {

    /**
     * 加密字符串
     * @access static
     * @param string $str 字符串
     * @param string $key 加密key
     * @return string
     */
    public static function encrypt($message, $public_key, $modulus, $keylength) {
        $padded = self::add_PKCS1_padding($message, true, $keylength / 8);
        $number = self::binary_to_number($padded);
        $encrypted = self::pow_mod($number, $public_key, $modulus);
        $result = self::number_to_binary($encrypted, $keylength / 8);
        return $result;
    }

    /**
     * 解密字符串
     * @access static
     * @param string $str 字符串
     * @param string $key 加密key
     * @return string
     */
    public static function decrypt($message, $private_key, $modulus, $keylength) {
        $number = self::binary_to_number($message);
        $decrypted = self::pow_mod($number, $private_key, $modulus);
        $result = self::number_to_binary($decrypted, $keylength / 8);

        return self::remove_PKCS1_padding($result, $keylength / 8);
    }

    function sign($message, $private_key, $modulus, $keylength) {
        $padded = self::add_PKCS1_padding($message, false, $keylength / 8);
        $number = self::binary_to_number($padded);
        $signed = self::pow_mod($number, $private_key, $modulus);
        $result = self::number_to_binary($signed, $keylength / 8);
        return $result;
    }

    function verify($message, $public_key, $modulus, $keylength) {
        return decrypt($message, $public_key, $modulus, $keylength);
    }

    function pow_mod($p, $q, $r) {
        // Extract powers of 2 from $q
        $factors = array();
        $div = $q;
        $power_of_two = 0;
        while(bccomp($div, "0") == BCCOMP_LARGER)
        {
            $rem = bcmod($div, 2);
            $div = bcdiv($div, 2);

            if($rem) array_push($factors, $power_of_two);
            $power_of_two++;
        }
        // Calculate partial results for each factor, using each partial result as a
        // starting point for the next. This depends of the factors of two being
        // generated in increasing order.
        $partial_results = array();
        $part_res = $p;
        $idx = 0;
        foreach($factors as $factor)
        {
            while($idx < $factor)
            {
                $part_res = bcpow($part_res, "2");
                $part_res = bcmod($part_res, $r);

                $idx++;
            }
            array_push($partial_results, $part_res);
        }
        // Calculate final result
        $result = "1";
        foreach($partial_results as $part_res)
        {
            $result = bcmul($result, $part_res);
            $result = bcmod($result, $r);
        }
        return $result;
    }

    //--
    // Function to add padding to a decrypted string
    // We need to know if this is a private or a public key operation [4]
    //--
    function add_PKCS1_padding($data, $isPublicKey, $blocksize) {
        $pad_length = $blocksize - 3 - strlen($data);

        if($isPublicKey)
        {
            $block_type = "\x02";

            $padding = "";
            for($i = 0; $i < $pad_length; $i++)
            {
                $rnd = mt_rand(1, 255);
                $padding .= chr($rnd);
            }
        }
        else
        {
            $block_type = "\x01";
            $padding = str_repeat("\xFF", $pad_length);
        }
        return "\x00" . $block_type . $padding . "\x00" . $data;
    }

    //--
    // Remove padding from a decrypted string
    // See [4] for more details.
    //--
    function remove_PKCS1_padding($data, $blocksize) {
        assert(strlen($data) == $blocksize);
        $data = substr($data, 1);

        // We cannot deal with block type 0
    if($data{0} == '\0')
            die("Block type 0 not implemented.");

        // Then the block type must be 1 or 2
    assert(($data{0} == "\x01") || ($data{0} == "\x02"));

        // Remove the padding
    $offset = strpos($data, "\0", 1);
        return substr($data, $offset + 1);
    }

    //--
    // Convert binary data to a decimal number
    //--
    function binary_to_number($data) {
        $base = "256";
        $radix = "1";
        $result = "0";

        for($i = strlen($data) - 1; $i >= 0; $i--)
        {
            $digit = ord($data{$i});
            $part_res = bcmul($digit, $radix);
            $result = bcadd($result, $part_res);
            $radix = bcmul($radix, $base);
        }
        return $result;
    }

    //--
    // Convert a number back into binary form
    //--
    function number_to_binary($number, $blocksize) {
        $base = "256";
        $result = "";
        $div = $number;
        while($div > 0)
        {
            $mod = bcmod($div, $base);
            $div = bcdiv($div, $base);

            $result = chr($mod) . $result;
        }
        return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);
    }

}