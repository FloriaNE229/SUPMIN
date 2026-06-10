<?php

namespace App\Modules\Form\Enums;

enum QuestionTypeEnum: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case IMAGE = 'image';
    case DATE = 'date';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}