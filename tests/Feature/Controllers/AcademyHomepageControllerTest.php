<?php

use App\Models\Academy;
use App\Models\User;

describe('AcademyHomepageController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('shows public homepage for academy', function () {
            $response = $this->get(route('academy.home', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('displays academy information', function () {
            $this->academy->update([
                'name' => 'Test Academy',
            ]);

            $response = $this->get(route('academy.home', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('about', function () {
        it('shows about page', function () {
            $response = $this->get(route('academy.about', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('contact', function () {
        it('shows contact page', function () {
            $response = $this->get(route('academy.contact', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });
});
