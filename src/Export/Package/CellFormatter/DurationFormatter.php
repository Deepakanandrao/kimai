<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

use App\Utils\Duration;

final class DurationFormatter implements CellFormatterInterface
{
    private Duration $duration;

    public function __construct()
    {
        $this->duration = new Duration();
    }

    public function formatValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return $this->duration->format((int) $value);
        }

        return $this->duration->format(0);
    }
}
