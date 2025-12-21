<?php

use App\Livewire\NotificationCenter;
use App\Models\Academy;
use App\Models\User;
use Livewire\Livewire;

describe('Notification Center Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('rendering', function () {
        it('renders successfully for authenticated users', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->assertStatus(200);
        });

        it('shows zero unread count initially', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->assertSet('unreadCount', 0);
        });
    });

    describe('category filtering', function () {
        it('can filter by category', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->call('filterByCategory', 'session')
                ->assertSet('selectedCategory', 'session');
        });

        it('resets page count when filtering', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->set('perPage', 30)
                ->call('filterByCategory', 'session')
                ->assertSet('perPage', 15);
        });
    });

    describe('pagination', function () {
        it('starts with default page size of 15', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->assertSet('perPage', 15);
        });

        it('can call loadMore method', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // The loadMore method should work without errors
            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->call('loadMore')
                ->assertStatus(200);
        });
    });

    describe('mark as read functionality', function () {
        it('can mark all as read', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($user)
                ->test(NotificationCenter::class)
                ->call('markAllAsRead')
                ->assertDispatched('all-notifications-read');
        });
    });
});
