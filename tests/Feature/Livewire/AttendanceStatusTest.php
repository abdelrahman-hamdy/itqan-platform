<?php

use App\Livewire\Student\AttendanceStatus;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Livewire\Livewire;

describe('Student AttendanceStatus', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('render', function () {
        it('renders for authenticated student', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $this->actingAs($this->student);

            Livewire::test(AttendanceStatus::class, [
                'session' => $session,
            ])
                ->assertStatus(200);
        });
    });

    describe('status display', function () {
        it('displays session attendance status', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $this->actingAs($this->student);

            Livewire::test(AttendanceStatus::class, [
                'session' => $session,
            ])
                ->assertStatus(200);
        });
    });
});
