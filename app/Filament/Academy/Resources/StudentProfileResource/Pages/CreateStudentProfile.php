<?php

namespace App\Filament\Academy\Resources\StudentProfileResource\Pages;

use App\Filament\Academy\Resources\StudentProfileResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateStudentProfile extends CreateRecord
{
    protected static string $resource = StudentProfileResource::class;
}
