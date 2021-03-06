<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017-2021 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/test-util
 */

namespace Ergebnis\Test\Util\Test\Util\DataProvider\Specification;

final class Pattern implements Specification
{
    /**
     * @var string
     */
    private $pattern;

    private function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public static function create(string $pattern): self
    {
        return new self($pattern);
    }

    public function isSatisfiedBy($value): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        return 1 === \preg_match($this->pattern, $value);
    }
}
