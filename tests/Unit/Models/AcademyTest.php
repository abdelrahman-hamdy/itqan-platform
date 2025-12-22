<?php

use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\GradientPalette;
use App\Enums\TailwindColor;
use App\Enums\Timezone;
use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademySettings;
use App\Models\QuranCircle;
use App\Models\User;

describe('Academy Model', function () {
    describe('factory', function () {
        it('can create an academy using factory', function () {
            $academy = Academy::factory()->create();

            expect($academy)->toBeInstanceOf(Academy::class)
                ->and($academy->id)->toBeInt()
                ->and($academy->name)->toBeString()
                ->and($academy->subdomain)->toBeString();
        });

        it('can create an active academy', function () {
            $academy = Academy::factory()->create(['is_active' => true]);

            expect($academy->is_active)->toBeTrue();
        });

        it('can create an inactive academy', function () {
            $academy = Academy::factory()->create(['is_active' => false]);

            expect($academy->is_active)->toBeFalse();
        });

        it('can create an academy in maintenance mode', function () {
            $academy = Academy::factory()->create(['maintenance_mode' => true]);

            expect($academy->maintenance_mode)->toBeTrue();
        });
    });

    describe('relationships', function () {
        it('has many users', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(3)->forAcademy($academy)->create();

            expect($academy->users)->toHaveCount(3)
                ->and($academy->users->first())->toBeInstanceOf(User::class);
        });

        it('belongs to an admin', function () {
            $academy = Academy::factory()->create();
            $admin = User::factory()->admin()->forAcademy($academy)->create();
            $academy->update(['admin_id' => $admin->id]);

            expect($academy->admin)->toBeInstanceOf(User::class)
                ->and($academy->admin->id)->toBe($admin->id);
        });

        it('has one settings relationship', function () {
            $academy = Academy::factory()->create();
            AcademySettings::create(['academy_id' => $academy->id]);

            expect($academy->settings)->toBeInstanceOf(AcademySettings::class);
        });

        it('has many students', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(2)->student()->forAcademy($academy)->create();

            expect($academy->students)->toHaveCount(2);
        });

        it('has many teachers', function () {
            $academy = Academy::factory()->create();
            User::factory()->quranTeacher()->forAcademy($academy)->create();
            User::factory()->academicTeacher()->forAcademy($academy)->create();

            expect($academy->teachers)->toHaveCount(2);
        });

        it('has many parents', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(2)->parent()->forAcademy($academy)->create();

            expect($academy->parents)->toHaveCount(2);
        });

        it('has many supervisors', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(2)->supervisor()->forAcademy($academy)->create();

            expect($academy->supervisors)->toHaveCount(2);
        });

        it('has many subjects', function () {
            $academy = Academy::factory()->create();
            AcademicSubject::factory()->count(3)->create(['academy_id' => $academy->id]);

            expect($academy->subjects)->toHaveCount(3);
        });

        it('has many grade levels', function () {
            $academy = Academy::factory()->create();
            AcademicGradeLevel::create(['academy_id' => $academy->id, 'name' => 'Grade 1', 'is_active' => true]);
            AcademicGradeLevel::create(['academy_id' => $academy->id, 'name' => 'Grade 2', 'is_active' => true]);

            expect($academy->gradeLevels)->toHaveCount(2);
        });

        it('has many quran circles', function () {
            $academy = Academy::factory()->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            QuranCircle::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($academy->quranCircles)->toHaveCount(2);
        });
    });

    describe('scopes', function () {
        it('can filter active academies', function () {
            Academy::factory()->count(2)->create(['is_active' => true]);
            Academy::factory()->count(1)->create(['is_active' => false]);

            expect(Academy::active()->count())->toBe(2);
        });

        it('can filter inactive academies', function () {
            Academy::factory()->count(2)->create(['is_active' => true]);
            Academy::factory()->count(3)->create(['is_active' => false]);

            expect(Academy::inactive()->count())->toBe(3);
        });

        it('can filter academies in maintenance mode', function () {
            Academy::factory()->count(2)->create(['maintenance_mode' => true]);
            Academy::factory()->count(1)->create(['maintenance_mode' => false]);

            expect(Academy::maintenance()->count())->toBe(2);
        });

        it('can filter active and available academies', function () {
            Academy::factory()->create(['is_active' => true, 'maintenance_mode' => false]);
            Academy::factory()->create(['is_active' => true, 'maintenance_mode' => true]);
            Academy::factory()->create(['is_active' => false, 'maintenance_mode' => false]);

            expect(Academy::activeAndAvailable()->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts is_active to boolean', function () {
            $academy = Academy::factory()->create(['is_active' => 1]);

            expect($academy->is_active)->toBeBool()->toBeTrue();
        });

        it('casts maintenance_mode to boolean', function () {
            $academy = Academy::factory()->create(['maintenance_mode' => 0]);

            expect($academy->maintenance_mode)->toBeBool()->toBeFalse();
        });

        it('casts country to enum', function () {
            $academy = Academy::factory()->create(['country' => Country::SAUDI_ARABIA]);

            expect($academy->country)->toBeInstanceOf(Country::class);
        });

        it('casts currency to enum', function () {
            $academy = Academy::factory()->create(['currency' => Currency::SAR]);

            expect($academy->currency)->toBeInstanceOf(Currency::class);
        });

        it('casts timezone to enum', function () {
            $academy = Academy::factory()->create(['timezone' => Timezone::RIYADH]);

            expect($academy->timezone)->toBeInstanceOf(Timezone::class);
        });

        it('casts brand_color to enum', function () {
            $academy = Academy::factory()->create(['brand_color' => TailwindColor::SKY]);

            expect($academy->brand_color)->toBeInstanceOf(TailwindColor::class);
        });

        it('casts gradient_palette to enum', function () {
            $academy = Academy::factory()->create(['gradient_palette' => GradientPalette::OCEAN_BREEZE]);

            expect($academy->gradient_palette)->toBeInstanceOf(GradientPalette::class);
        });

        it('casts academic_settings to array', function () {
            $settings = ['key' => 'value'];
            $academy = Academy::factory()->create(['academic_settings' => $settings]);

            expect($academy->academic_settings)->toBeArray()
                ->and($academy->academic_settings['key'])->toBe('value');
        });
    });

    describe('accessors', function () {
        it('returns status display for inactive academy', function () {
            $academy = Academy::factory()->create(['is_active' => false]);

            expect($academy->status_display)->toBe('غير نشطة');
        });

        it('returns status display for maintenance mode', function () {
            $academy = Academy::factory()->create(['is_active' => true, 'maintenance_mode' => true]);

            expect($academy->status_display)->toBe('تحت الصيانة');
        });

        it('returns status display for active academy', function () {
            $academy = Academy::factory()->create(['is_active' => true, 'maintenance_mode' => false]);

            expect($academy->status_display)->toBe('نشطة');
        });

        it('returns admin status for inactive academy', function () {
            $academy = Academy::factory()->create(['is_active' => false]);

            expect($academy->admin_status)->toBe('inactive');
        });

        it('returns admin status for maintenance mode', function () {
            $academy = Academy::factory()->create(['is_active' => true, 'maintenance_mode' => true]);

            expect($academy->admin_status)->toBe('maintenance');
        });

        it('returns admin status for active academy', function () {
            $academy = Academy::factory()->create(['is_active' => true, 'maintenance_mode' => false]);

            expect($academy->admin_status)->toBe('active');
        });

        it('returns teachers count', function () {
            $academy = Academy::factory()->create();
            User::factory()->quranTeacher()->forAcademy($academy)->create();
            User::factory()->academicTeacher()->forAcademy($academy)->create();

            expect($academy->teachers_count)->toBe(2);
        });

        it('returns students count', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(3)->student()->forAcademy($academy)->create();

            expect($academy->students_count)->toBe(3);
        });

        it('returns users count', function () {
            $academy = Academy::factory()->create();
            User::factory()->count(5)->forAcademy($academy)->create();

            expect($academy->users_count)->toBe(5);
        });

        it('returns full domain for subdomain academy', function () {
            $academy = Academy::factory()->create(['subdomain' => 'test-academy']);

            expect($academy->full_domain)->toContain('test-academy');
        });

        it('returns logo url when logo exists', function () {
            $academy = Academy::factory()->create(['logo' => 'logos/test.png']);

            expect($academy->logo_url)->toContain('storage/logos/test.png');
        });

        it('returns null logo url when logo is empty', function () {
            $academy = Academy::factory()->create(['logo' => null]);

            expect($academy->logo_url)->toBeNull();
        });
    });

    describe('methods', function () {
        it('can get or create settings', function () {
            $academy = Academy::factory()->create();

            $settings = $academy->getOrCreateSettings();

            expect($settings)->toBeInstanceOf(AcademySettings::class)
                ->and($settings->academy_id)->toBe($academy->id);
        });

        it('returns route key name as subdomain', function () {
            $academy = Academy::factory()->create();

            expect($academy->getRouteKeyName())->toBe('subdomain');
        });

        it('returns tenant key name', function () {
            $academy = Academy::factory()->create();

            expect($academy->getTenantKeyName())->toBe('id');
        });

        it('returns tenant key', function () {
            $academy = Academy::factory()->create();

            expect($academy->getTenantKey())->toBe($academy->id);
        });
    });

    describe('sections order accessor', function () {
        it('returns default order when sections_order is null', function () {
            $academy = Academy::factory()->create();
            $academy->setRawAttributes(['sections_order' => null], true);

            $sectionsOrder = $academy->sections_order;

            expect($sectionsOrder)->toBeArray()
                ->and($sectionsOrder)->toContain('hero', 'stats', 'reviews');
        });

        it('returns default order when sections_order is empty array', function () {
            $academy = Academy::factory()->create();
            $academy->setRawAttributes(['sections_order' => '[]'], true);

            $sectionsOrder = $academy->sections_order;

            expect($sectionsOrder)->toBeArray()
                ->and(count($sectionsOrder))->toBeGreaterThan(0);
        });

        it('returns custom order when set', function () {
            $academy = Academy::factory()->create();
            $customOrder = ['stats', 'hero', 'courses'];
            $academy->sections_order = $customOrder;
            $academy->save();

            expect($academy->fresh()->sections_order)->toBe($customOrder);
        });
    });

    describe('default attributes', function () {
        it('has default brand color', function () {
            $academy = new Academy();

            // Default attribute is string value, not enum object
            expect($academy->getAttributes()['brand_color'])->toBe(TailwindColor::SKY->value);
        });

        it('has default gradient palette', function () {
            $academy = new Academy();

            expect($academy->getAttributes()['gradient_palette'])->toBe(GradientPalette::OCEAN_BREEZE->value);
        });

        it('has default country as Saudi Arabia', function () {
            $academy = new Academy();

            expect($academy->getAttributes()['country'])->toBe(Country::SAUDI_ARABIA->value);
        });

        it('has default timezone as Riyadh', function () {
            $academy = new Academy();

            expect($academy->getAttributes()['timezone'])->toBe(Timezone::RIYADH->value);
        });

        it('has default currency as SAR', function () {
            $academy = new Academy();

            expect($academy->getAttributes()['currency'])->toBe(Currency::SAR->value);
        });

        it('is active by default', function () {
            $academy = new Academy();

            expect($academy->is_active)->toBeTrue();
        });

        it('allows registration by default', function () {
            $academy = new Academy();

            expect($academy->allow_registration)->toBeTrue();
        });

        it('is not in maintenance mode by default', function () {
            $academy = new Academy();

            expect($academy->maintenance_mode)->toBeFalse();
        });
    });

    describe('soft deletes', function () {
        it('can be soft deleted', function () {
            $academy = Academy::factory()->create();

            $academy->delete();

            expect($academy->trashed())->toBeTrue()
                ->and(Academy::withTrashed()->find($academy->id))->not->toBeNull();
        });

        it('can be restored', function () {
            $academy = Academy::factory()->create();
            $academy->delete();

            $academy->restore();

            expect($academy->trashed())->toBeFalse()
                ->and(Academy::find($academy->id))->not->toBeNull();
        });
    });
});
