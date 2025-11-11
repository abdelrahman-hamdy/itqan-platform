<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Services\AcademyContextService;
use Filament\Notifications\Notification;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        $title = 'المديرون';
        
        if (AcademyContextService::isSuperAdmin()) {
            if (AcademyContextService::hasAcademySelected()) {
                $academy = AcademyContextService::getCurrentAcademy();
                $title .= ' - ' . $academy?->name;
            } else {
                $title .= ' - جميع الأكاديميات';
            }
        }
        
        return $title;
    }

    public function getSubheading(): ?string
    {
        if (AcademyContextService::isSuperAdmin() && !AcademyContextService::hasAcademySelected()) {
            return 'لعرض مديري أكاديمية محددة، يرجى اختيار الأكاديمية من القائمة العلوية';
        }
        
        return null;
    }
}
