<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
