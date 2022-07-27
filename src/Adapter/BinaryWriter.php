<?php

/**
 * This file contains the BinaryReader class.
 * For more information see the class description below.
 *
 * @author Peter Bathory <peter.bathory@cartographia.hu>
 * @since 2016-02-18
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace geoPHP\Adapter;

/**
 * Helper class BinaryWriter.
 *
 * A simple binary writer supporting both byte orders.
 */
class BinaryWriter
{
    const BIG_ENDIAN = 0;
    const LITTLE_ENDIAN = 1;

    private $endianness = 0;

    /**
     * @param integer $endianness Constant value self::BIG_ENDIAN or self::LITTLE_ENDIAN.
     */
    public function __construct(int $endianness = 0)
    {
        $this->endianness = $endianness === self::BIG_ENDIAN
            ? self::BIG_ENDIAN
            : self::LITTLE_ENDIAN;
    }

    /**
     * @return bool Returns true if Writer is in BigEndian mode
     */
    public function isBigEndian(): bool
    {
        return $this->endianness === self::BIG_ENDIAN;
    }

    /**
     * @return bool Returns true if Writer is in LittleEndian mode
     */
    public function isLittleEndian(): bool
    {
        return $this->endianness === self::LITTLE_ENDIAN;
    }

    /**
     * Writes a signed 8-bit integer
     * @param int|float $value
     * @return string The integer as a binary string
     */
    public function writeSInt8($value): string
    {
        return pack('c', (int) $value);
    }

    /**
     * Writes an unsigned 8-bit integer
     * @param int|float $value
     * @return string The integer as a binary string
     */
    public function writeUInt8($value): string
    {
        return pack('C', (int) $value);
    }

    /**
     * Writes an unsigned 32-bit integer
     * @param int|float $value
     * @return string The integer as a binary string
     */
    public function writeUInt32($value): string
    {
        return pack($this->isLittleEndian() ? 'V' : 'N', (int) $value);
    }

    /**
     * Writes a double
     * @param float $value
     * @return string The floating point number as a binary string
     */
    public function writeDouble($value): string
    {
        return $this->isLittleEndian() ? pack('d', (float) $value) : strrev(pack('d', (float) $value));
    }

    /**
     * Writes a positive integer as an unsigned base-128 varint
     *
     * Ported from https://github.com/cschwarz/wkx/blob/master/lib/binaryreader.js
     *
     * @param int|float $value
     * @return string The integer as a binary string
     */
    public function writeUVarInt($value): string
    {
        $value = (int) $value;
        $out = '';

        while (($value & 0xFFFFFF80) !== 0) {
            $out .= $this->writeUInt8(($value & 0x7F) | 0x80);
            // Zero fill by 7 zero
            if ($value >= 0) {
                $value >>= 7;
            } else {
                $value = ((~$value) >> 7) ^ (0x7fffffff >> (7 - 1));
            }
        }

        $out .= $this->writeUInt8($value & 0x7F);

        return $out;
    }

    /**
     * Writes an integer as a signed base-128 varint
     * @param int|float $value
     * @return string The integer as a binary string
     */
    public function writeSVarInt($value): string
    {
        return $this->writeUVarInt(self::zigZagEncode((int) $value));
    }

    /**
     * ZigZag encoding maps signed integers to unsigned integers
     *
     * @param int $value Signed integer
     * @return int Encoded positive integer value
     */
    public static function zigZagEncode($value)
    {
        return ($value << 1) ^ ($value >> 31);
    }
}
