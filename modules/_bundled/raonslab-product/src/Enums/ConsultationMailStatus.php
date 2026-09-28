<?php

namespace Modules\Raonslab\Product\Enums;

enum ConsultationMailStatus: string
{
    case NotConfigured = 'NOT_CONFIGURED';
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Failed = 'FAILED';
}
