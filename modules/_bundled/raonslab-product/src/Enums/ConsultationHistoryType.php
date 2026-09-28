<?php

namespace Modules\Raonslab\Product\Enums;

enum ConsultationHistoryType: string
{
    case Created = 'CREATED';
    case NoteAdded = 'NOTE_ADDED';
    case StatusChanged = 'STATUS_CHANGED';
}
