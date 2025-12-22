<?php

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;

describe('ChildSelectionMiddleware', function () {
    beforeEach(function () {
        $this->middleware = new ChildSelectionMiddleware();
        $this->academy = Academy::factory()->create();
    });

    describe('handle', function () {
        it('passes through for non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($student);

            $request = Request::create('/test');
            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('passes through for unauthenticated users', function () {
            $request = Request::create('/test');
            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('shares parent children with views', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach([
                $student1->studentProfileUnscoped->id,
                $student2->studentProfileUnscoped->id,
            ]);

            $this->actingAs($parent);

            $request = Request::create('/test');
            $this->middleware->handle($request, fn () => response('OK'));

            $shared = view()->getShared();
            expect($shared['parentChildren'])->toHaveCount(2);
        });

        it('stores child selection from request in session', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $this->actingAs($parent);

            $request = Request::create('/test', 'GET', ['child_id' => $student->studentProfileUnscoped->id]);
            $this->middleware->handle($request, fn () => response('OK'));

            expect(session('parent_selected_child_id'))->toBe($student->studentProfileUnscoped->id);
        });

        it('defaults to all when no selection', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $this->actingAs($parent);

            $request = Request::create('/test');
            $this->middleware->handle($request, fn () => response('OK'));

            $shared = view()->getShared();
            expect($shared['selectedChildId'])->toBe('all');
        });

        it('resets to all for invalid child selection', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $this->actingAs($parent);
            session(['parent_selected_child_id' => 99999]); // Non-existent child

            $request = Request::create('/test');
            $this->middleware->handle($request, fn () => response('OK'));

            expect(session('parent_selected_child_id'))->toBe('all');
        });

        it('merges child data into request', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $this->actingAs($parent);

            $request = Request::create('/test');
            $this->middleware->handle($request, fn () => response('OK'));

            expect($request->_parent_children)->not->toBeNull();
            expect($request->_selected_child_id)->toBe('all');
        });
    });

    describe('getChildIds', function () {
        it('returns empty array for non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($student);

            expect(ChildSelectionMiddleware::getChildIds())->toBe([]);
        });

        it('returns all child ids when selection is all', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach([
                $student1->studentProfileUnscoped->id,
                $student2->studentProfileUnscoped->id,
            ]);

            $this->actingAs($parent);
            session(['parent_selected_child_id' => 'all']);

            $ids = ChildSelectionMiddleware::getChildIds();
            expect($ids)->toContain($student1->studentProfileUnscoped->id);
            expect($ids)->toContain($student2->studentProfileUnscoped->id);
        });

        it('returns single child id when one is selected', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $this->actingAs($parent);
            session(['parent_selected_child_id' => $student->studentProfileUnscoped->id]);

            $ids = ChildSelectionMiddleware::getChildIds();
            expect($ids)->toBe([$student->studentProfileUnscoped->id]);
        });
    });

    describe('getChildUserIds', function () {
        it('returns user ids instead of profile ids', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $this->actingAs($parent);
            session(['parent_selected_child_id' => 'all']);

            $userIds = ChildSelectionMiddleware::getChildUserIds();
            expect($userIds)->toContain($student->id);
        });
    });
});
