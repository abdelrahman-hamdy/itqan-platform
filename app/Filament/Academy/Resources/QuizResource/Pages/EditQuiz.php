<?php

namespace App\Filament\Academy\Resources\QuizResource\Pages;

use App\Filament\Academy\Resources\QuizResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;
}
