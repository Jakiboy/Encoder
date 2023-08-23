<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : Encoder
 * @version   : 1.0.2
 * @copyright : (c) 2023 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link      : https://jakiboy.github.io/Encoder/
 * @license   : MIT
 */

namespace Encoding;

/**
 * String encoder helper.
 * @see Upgrade of https://github.com/neitanod/forceutf8
 */
class Encoder
{
    /**
     * @access protected
     * @var bool $useSpecial
     * @var bool $useIconv
     * @var mixed $iconvOption
     */
    protected $useSpecial = true;
    protected $useIconv = false;
    protected $iconvOption = false;

    /**
     * Init encoder.
     * 
     * @param bool $useIconv
     * @param mixed $iconvOption
     */
    public function __construct(bool $useIconv = false, $iconvOption = false)
    {
        $this->useIconv = $useIconv;
        if ( is_string($iconvOption) ) {
            $iconvOption = str_replace('//', '', strtoupper($iconvOption));
            $this->iconvOption = str_replace('|', '//', $iconvOption);
        }
    }

    /**
     * Disable special UTF-8 converting.
     * 
     * @access public
     * @param string $string
     * @param string $to
     * @param string $from
     * @return string
     */
    public function noSpecial() : self
    {
        $this->useSpecial = false;
        return $this;
    }

    /**
     * Convert string encoding.
     * 
     * @access public
     * @param string $string
     * @param string $to
     * @param string $from
     * @return string
     */
    public function convert(string $string, string $to, string $from = 'ISO-8859-1') : string
    {
        $to   = $this->formatEncoding($to);
        $from = $this->formatEncoding($from);

        // Using iconv
        if ( $this->useIconv ) {
            if ( function_exists('iconv') ) {
                if ( $this->iconvOption ) {
                    $to = "{$to}//{$this->iconvOption}";
                }
                return (string)@iconv($from, $to, $string);
            }
        }

        // Using multibyte
        if ( function_exists('mb_convert_encoding') ) {
            return (string)@mb_convert_encoding($string, $to, $from);
        }

        return $string;
    }

    /**
     * Sanitize UTF-8 string.
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public function sanitize(string $string) : string
    {
        $last = '';
        while($last <> $string) {
            $last = $string;
            $string = $this->toUtf8($this->decodeUtf8($string));
        }
        $string = $this->toUtf8($this->decodeUtf8($string));
        return $string;
    }
    
    /**
     * Encode UTF-8 string.
     * 
     * @access public
     * @param string $string
     * @param string $from
     * @return string
     */
    public function encodeUtf8(string $string, string $from = 'ISO-8859-1') : string
    {
        $from = $this->formatEncoding($from);
        return $this->convert($string, 'UTF-8', $from);
    }

    /**
     * Decode UTF-8 string.
     * 
     * @access public
     * @param string $string
     * @param string $to
     * @return string
     */
    public function decodeUtf8(string $string, string $to = 'ISO-8859-1') : string
    {
        // Using converter
        $to = $this->formatEncoding($to);
        $decode = $this->convert($string, $to, 'UTF-8');

        // Using table
        if ( empty($decode) ) {
            $decode = $this->convertUtf8($string);
        }

        return $decode;
    }

    /**
     * Convert string to UTF-8.
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public function toUtf8(string $string) : string
    {
        $max = $this->getLength($string);
        $tmp = '';

        for ($i = 0; $i < $max; $i++) {

            $c1 = $string[$i];

            // Maybe require UTF-8 converting
            if ( $this->maybeRequireConverting($c1) ) {

                $c2 = ($i + 1 >= $max) ? "\x00" : $string[$i+1];
                $c3 = ($i + 2 >= $max) ? "\x00" : $string[$i+2];
                $c4 = ($i + 3 >= $max) ? "\x00" : $string[$i+3];

                // Maybe 2 bytes UTF-8
                if ( $this->maybe2Bytes($c1) ) {

                    // Valid UTF-8
                    if ( $this->isValidBytes($c2) ) {
                        $tmp .= "{$c1}{$c2}";
                        $i++;
                    
                    // Convert char to UTF-8
                    } else {
                        $tmp .= $this->convertChar($c1);
                    }

                // Maybe 3 bytes UTF8
                } elseif ( $this->maybe3Bytes($c1) ) {

                    // Valid UTF-8
                    if ( $this->isValidBytes($c2) && $this->isValidBytes($c3) ) {
                        $tmp .= "{$c1}{$c2}{$c3}";
                        $i += 2;
                    
                    // Convert char to UTF-8
                    } else {
                        $tmp .= $this->convertChar($c1);
                    }

                // Maybe 4 bytes UTF8
                } elseif ( $this->maybe4Bytes($c1) ) {

                    // Valid UTF-8
                    if ( $this->isValidBytes($c2) && $this->isValidBytes($c3) && $this->isValidBytes($c4) ) {
                        $tmp .= "{$c1}{$c2}{$c3}{$c4}";
                        $i += 3;
                    
                    // Convert char to UTF-8
                    } else {
                        $tmp .= $this->convertChar($c1);
                    }

                // Force convert char to UTF-8
                } else {
                    $tmp .= $this->convertChar($c1);
                }

            // Require UTF-8 converting
            } elseif ( $this->requireConverting($c1) ) {

                // Convert Windows-1252 to UTF-8
                if ( $this->isWindows1252($c1) ) {
                    $tmp .= $this->convertWindows1252($c1);

                // Force convert char to UTF-8
                } else {
                    $tmp .= $this->convertChar($c1);
                }

            // Valid UTF-8
            } else {
                $tmp .= $c1;
            }
        }

        // Convert special UTF-8
        if ( $this->useSpecial ) {
            $tmp = $this->convertSpecial($tmp);
        }

        return $tmp;
    }

    /**
     * Convert string to Windows-1252,
     * [Alias].
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public function toWindows1252(string $string) : string
    {
        return $this->decodeUtf8($string);
    }

    /**
     * Convert string to Latin-1,
     * [Alias].
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public function toLatin1(string $string) : string
    {
        return $this->decodeUtf8($string);
    }

    /**
     * Remove BOM from UTF-8.
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public static function unBom(string $string) : string
    {
        if ( substr($string, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf) ) {
            $string = substr($string, 3);
        }
        return $string;
    }

    /**
     * Fix broken UTF-8 string.
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public static function unbreak(string $string) : string
    {
        $search  = array_keys(Table::BROKEN);
        $replace = array_values(Table::BROKEN);
        return str_replace($search, $replace, $string);
    }

    /**
     * Check Windows-1252 chars table.
     * 
     * @access protected
     * @param string $string
     * @return bool
     */
    protected function isWindows1252(string $string) : bool
    {
        $n = $this->toInt($string);
        return isset(Table::WINDOWS1252[$n]);
    }

    /**
     * Convert Windows-1252 chars to UTF-8 using table.
     *
     * @access protected
     * @param string $string
     * @return string
     */
    protected function convertWindows1252(string $string) : string
    {
        $n = $this->toInt($string);
        return Table::WINDOWS1252[$n] ?? '';
    }

    /**
     * Convert UTF-8 chars to Windows-1252 using table.
     *
     * @access protected
     * @param string $string
     * @return string
     */
    protected function convertUtf8(string $string) : string
    {
        $search  = array_keys(Table::UTF8);
        $replace = array_values(Table::UTF8);
        return str_replace($search, $replace, $this->toUtf8($string));
    }

    /**
     * Convert special UTF-8 string using table.
     * 
     * @access protected
     * @param string $string
     * @return string
     */
    protected function convertSpecial(string $string) : string
    {
        $search  = array_keys(Table::SPECIAL);
        $replace = array_values(Table::SPECIAL);
        return str_replace($search, $replace, $string);
    }

    /**
     * Convert char to UTF-8.
     *
     * @access protected
     * @param string $string
     * @return string
     */
    protected function convertChar(string $string) : string
    {
        $char1 = (chr(ord($string) / 64) | "\xc0");
        $char2 = (($string & "\x3f") | "\x80");
        return "{$char1}{$char2}";
    }

    /**
     * Convert the first byte of a string to a value between 0 and 255.
     *
     * @access protected
     * @param string $string
     * @return int
     */
    protected function toInt(string $string) : int
    {
        return ord($string);
    }

    /**
     * Get string length.
     *
     * @access protected
     * @param string $string
     * @return int
     */
    protected function getLength(string $string) : int
    {
        // Using multibyte
        if ( function_exists('mb_strlen') && ( (int)ini_get('mbstring.func_overload')) == 2 ) {
            return (int)mb_strlen($string, '8bit');
        }
        return strlen($string);
    }

    /**
     * Maybe require UTF-8 converting.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function maybeRequireConverting(string $char) : bool
    {
        return ($char >= "\xc0");
    }

    /**
     * Require UTF-8 converting.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function requireConverting(string $char) : bool
    {
        return (($char & "\xc0") == "\x80");
    }

    /**
     * Check valid UTF-8 bytes.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function isValidBytes(string $char) : bool
    {
        return ($char >= "\x80" && $char <= "\xbf");
    }

    /**
     * Maybe 2 bytes UTF-8.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function maybe2Bytes(string $char) : bool
    {
        return ($char >= "\xc0" && $char <= "\xdf");
    }

    /**
     * Maybe 3 bytes UTF-8.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function maybe3Bytes(string $char) : bool
    {
        return ($char >= "\xe0" && $char <= "\xef");
    }

    /**
     * Maybe 4 bytes UTF-8.
     *
     * @access protected
     * @param string $char
     * @return bool
     */
    protected function maybe4Bytes(string $char) : bool
    {
        return ($char >= "\xf0" && $char <= "\xf7");
    }

    /**
     * Format encoding.
     *
     * @access protected
     * @param string $encoding
     * @return string
     */
    protected function formatEncoding(string $encoding) : string
    {
        $encoding = strtoupper($encoding);
        $encoding = preg_replace('/[^a-zA-Z0-9\s]/', '', $encoding);
        $format = [
          'ISO88591'    => 'ISO-8859-1',
          'ISO8859'     => 'ISO-8859-1',
          'ISO'         => 'ISO-8859-1',
          'LATIN1'      => 'ISO-8859-1',
          'LATIN'       => 'ISO-8859-1',
          'WIN1252'     => 'ISO-8859-1',
          'WINDOWS1252' => 'ISO-8859-1'
        ];
        return $format[$encoding] ?? 'UTF-8';
    }
}
