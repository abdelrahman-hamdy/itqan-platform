<?php

use App\Http\Middleware\InteractiveCourseMiddleware;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;

describe('InteractiveCourseMiddleware', function () {
    beforeEach(function () {
        $this->middleware = new InteractiveCourseMiddleware();
        $this->academy = Academy::factory()->create();
    });

    describe('handle', function () {
        it('redirects unauthenticated users to login', function () {
            $request = Request::create('/interactive-course/1');
            $request->setRouteResolver(fn () => null);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->isRedirect())->toBeTrue();
        });

        it('redirects inactive users to login', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'is_active' => false,
            ]);

            $this->actingAs($user);
            $request = Request::create('/interactive-course/1');
            $request->setRouteResolver(fn () => null);

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->isRedirect())->toBeTrue();
        });

        it('allows student access to interactive course', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $this->actingAs($student);

            $request = Request::create('/interactive-course/1');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
        });

        it('allows academic teacher access with teacher view flag', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $this->actingAs($teacher);

            $request = Request::create('/interactive-course/1');

            $response = $this->middleware->handle($request, fn () => response('OK'));

            expect($response->getContent())->toBe('OK');
            expect($request->attributes->get('use_teacher_view'))->toBeTrue();
        });

        it('denies quran teacher from accessing interactive courses', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $this->actingAs($teacher);

            $request = Request::create('/interactive-course/1');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('denies parent from accessing interactive courses directly', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $this->actingAs($parent);

            $request = Request::create('/interactive-course/1');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });

        it('denies supervisor from accessing interactive courses', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $this->actingAs($supervisor);

            $request = Request::create('/interactive-course/1');

            expect(fn () => $this->middleware->handle($request, fn () => response('OK')))
                ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        });
    });
});
