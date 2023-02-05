<?php

declare(strict_types = 1);

namespace Fayela\Helper;

class HumanReadableConverter
{
    /**
     * Convert size in bytes to a more human-readable unit
     */
    public function size(int $sizeInBytes, int $precision = 2): string
    {
        static $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $destinationUnitIndex = $sizeInBytes > 0 ? floor(log($sizeInBytes, 1024)) : 0;
        $divisor = max(1, (1024 ** $destinationUnitIndex));

        return round($sizeInBytes / $divisor, $precision) . ' ' . $units[$destinationUnitIndex];
    }
}
