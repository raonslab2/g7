<?php

namespace Modules\Raonslab\Product\Enums;

enum ConsultationStatus: string
{
    case New = 'NEW';
    case Contacted = 'CONTACTED';
    case Qualified = 'QUALIFIED';
    case Closed = 'CLOSED';

    public function next(): ?self
    {
        return match ($this) {
            self::New => self::Contacted,
            self::Contacted => self::Qualified,
            self::Qualified => self::Closed,
            self::Closed => null,
        };
    }
}
