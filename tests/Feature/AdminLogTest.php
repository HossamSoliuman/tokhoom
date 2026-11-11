<?php

use App\Livewire\Admin\Log;
use App\Models\AdminAction;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

test('the admin log lists every action and filters by area', function () {
    $admin = User::factory()->platformAdmin()->create(['name' => 'هند العمري']);
    $workspace = Workspace::factory()->create(['name' => 'مكتب الراشد العقاري']);
    $this->actingAs($admin);
    AdminAction::record('workspace.suspended', $workspace);
    AdminAction::record('settings.updated', properties: ['section' => 'invoicing', 'fields' => ['seller_vat_number']]);

    $this->get(route('admin.log'))->assertOk()->assertSee(['مكتب الراشد العقاري', 'تعديل الفوترة والضريبة: الرقم الضريبي', 'هند العمري']);

    Livewire::test(Log::class)
        ->set('area', 'settings')
        ->assertSee('تعديل الفوترة والضريبة: الرقم الضريبي')
        ->assertDontSee('مكتب الراشد العقاري');
});

test('only platform admins open the admin log', function () {
    [$owner] = member();

    $this->actingAs($owner)->get(route('admin.log'))->assertForbidden();
});
