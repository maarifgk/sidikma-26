<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class DatabaseNotificationJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_can_filter_database_notifications_by_json_format(): void
    {
        $user = User::factory()->create();

        DatabaseNotification::query()->create([
            'id' => fake()->uuid(),
            'type' => 'test-notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->getKey(),
            'data' => [
                'format' => 'filament',
                'title' => 'Notifikasi Pengujian',
            ],
            'read_at' => null,
        ]);

        $this->assertSame(
            1,
            $user->unreadNotifications()
                ->where('data->format', 'filament')
                ->count(),
        );
    }
}
