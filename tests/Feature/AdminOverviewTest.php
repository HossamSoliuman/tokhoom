<?php

use App\Enums\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PlatformStats;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create();
});

test('the overview shows the platform numbers and what needs attention', function () {
    $paying = Workspace::factory()->create(['name' => 'مكتب يدفع', 'trial_ends_at' => now()->subMonth()]);
    $paying->invoices()->create(['number' => 'TKH-1', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);
    Workspace::factory()->pending()->create(['name' => 'مكتب ينتظر']);
    Workspace::factory()->create(['name' => 'مكتب ترخيصه ينتهي', 'license_expires_on' => today()->addDays(5)]);

    $this->actingAs($this->admin)->get(route('admin.overview'))
        ->assertOk()
        ->assertSee(['طلب اعتماد واحد بانتظار المراجعة', 'فاتورة مستحقة على مكتب يدفع', 'ينتهي ترخيص مكتب ترخيصه ينتهي', 'مكتب ينتظر']);
});

test('monthly recurring revenue counts paying workspaces at their plan and seats', function () {
    Workspace::factory()->create(['plan' => Plan::Office, 'seats' => 7, 'trial_ends_at' => now()->subDay()]);
    Workspace::factory()->independentAgent()->create(['trial_ends_at' => now()->subDay()]);
    Workspace::factory()->create(['trial_ends_at' => now()->addDays(5)]);
    Workspace::factory()->suspended()->create(['trial_ends_at' => now()->subDay()]);
    Workspace::factory()->pending()->create(['trial_ends_at' => now()->subDay()]);

    expect((new PlatformStats)->monthlyRecurringRevenue())->toBe(349 + 2 * Plan::EXTRA_SEAT_PRICE + 99);
});

test('revenue is grouped by the month it was collected', function () {
    $this->travelTo('2026-11-08 10:00');
    $workspace = Workspace::factory()->create();
    $workspace->invoices()->create(['number' => 'A', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => '2026-10-01'])->forceFill(['paid_at' => '2026-10-02 09:00'])->save();
    $workspace->invoices()->create(['number' => 'B', 'description' => 'اشتراك', 'amount' => 99, 'issued_on' => '2026-11-01'])->forceFill(['paid_at' => '2026-11-03 09:00'])->save();
    $workspace->invoices()->create(['number' => 'C', 'description' => 'اشتراك', 'amount' => 799, 'issued_on' => '2026-11-05']);

    expect((new PlatformStats)->monthlyRevenue(3))->toBe(['2026-09' => 0, '2026-10' => 349, '2026-11' => 99]);
});

test('admins who belong to a workspace can move between it and the admin panel', function () {
    [$user] = member();
    $user->forceFill(['is_platform_admin' => true])->save();

    $this->actingAs($user)->get(route('admin.overview'))->assertSee('العودة إلى مساحة العمل');
    $this->actingAs($user)->get(route('more'))->assertSee('إدارة المنصة');
    $this->actingAs($user)->get(route('admin.account'))->assertOk();
});

test('members do not see the admin panel link', function () {
    [$owner] = member();

    $this->actingAs($owner)->get(route('more'))->assertDontSee(route('admin.overview'));
    $this->actingAs($owner)->get(route('admin.overview'))->assertForbidden();
});
