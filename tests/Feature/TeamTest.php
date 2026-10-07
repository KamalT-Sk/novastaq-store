<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mailhive.key' => 'mhs_test_dummy']);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    public function test_guests_and_customers_cannot_access_team(): void
    {
        $this->get(route('admin.team.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.team.index'))->assertForbidden();
    }

    public function test_only_owners_can_access_team_management(): void
    {
        $manager = $this->staff('manager');
        $editor = $this->staff('editor');
        $orders = $this->staff('orders');
        $owner = $this->staff('owner');

        $this->actingAs($manager)->get(route('admin.team.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.team.index'))->assertForbidden();
        $this->actingAs($orders)->get(route('admin.team.index'))->assertForbidden();

        $this->actingAs($owner)->get(route('admin.team.index'))
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee($manager->name)
            ->assertSee('Add someone');
    }

    public function test_owner_can_invite_new_staff_member(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);
        $owner = $this->staff('owner');

        $this->actingAs($owner)->post(route('admin.team.store'), [
            'name' => 'Kolawole Adeleke',
            'email' => 'kola@novastaq.com',
            'role' => 'editor',
        ])->assertRedirect()->assertSessionHas('status');

        $newStaff = User::where('email', 'kola@novastaq.com')->first();
        $this->assertNotNull($newStaff);
        $this->assertSame('editor', $newStaff->role);
        $this->assertSame('Catalogue editor', $newStaff->roleLabel());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), self::MAILHIVE)
                && $request['to'] === 'kola@novastaq.com'
                && str_contains($request['subject'], 'added you to Novastaq Store');
        });
    }

    public function test_owner_can_invite_existing_customer_to_team(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_2'])]);
        $owner = $this->staff('owner');
        $customer = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($owner)->post(route('admin.team.store'), [
            'name' => $customer->name,
            'email' => 'existing@example.com',
            'role' => 'orders',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame('orders', $customer->fresh()->role);
    }

    public function test_cannot_invite_user_who_is_already_staff(): void
    {
        $owner = $this->staff('owner');
        $existingStaff = $this->staff('manager');

        $this->actingAs($owner)->post(route('admin.team.store'), [
            'name' => $existingStaff->name,
            'email' => $existingStaff->email,
            'role' => 'editor',
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_owner_can_update_staff_role(): void
    {
        $owner = $this->staff('owner');
        $staff = $this->staff('editor');

        $this->actingAs($owner)->patch(route('admin.team.update', $staff), [
            'role' => 'manager',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame('manager', $staff->fresh()->role);
    }

    public function test_owner_cannot_modify_or_remove_themselves(): void
    {
        $owner = $this->staff('owner');

        $this->actingAs($owner)->patch(route('admin.team.update', $owner), [
            'role' => 'editor',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($owner)->delete(route('admin.team.destroy', $owner))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_owner_can_remove_staff_access(): void
    {
        $owner = $this->staff('owner');
        $staff = $this->staff('editor');

        $this->actingAs($owner)->delete(route('admin.team.destroy', $staff))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertNull($staff->fresh()->role);
        $this->assertFalse($staff->fresh()->isStaff());
    }

    public function test_owner_can_resend_invite_link(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_3'])]);
        $owner = $this->staff('owner');
        $staff = $this->staff('editor');

        $this->actingAs($owner)->post(route('admin.team.resend', $staff))
            ->assertRedirect()->assertSessionHas('status');

        Http::assertSent(fn ($req) => $req['to'] === $staff->email);
    }

    public function test_editor_can_edit_products_but_cannot_publish_or_delete(): void
    {
        $editor = $this->staff('editor');
        $cat = Category::create(['name' => 'Gadgets', 'slug' => 'gadgets']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Gizmo Pro',
            'slug' => 'gizmo-pro',
            'price' => 50_000_00,
            'is_active' => false,
        ]);

        // Editor can access dashboard, product index & form
        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()->assertSee('Live products');
        $this->actingAs($editor)->get(route('admin.products.index'))->assertOk();

        // Editor cannot toggle product live
        $this->actingAs($editor)->patch(route('admin.products.toggle', $product))->assertForbidden();

        // Editor cannot delete product
        $this->actingAs($editor)->delete(route('admin.products.destroy', $product))->assertForbidden();

        // Editor cannot view orders
        $this->actingAs($editor)->get(route('admin.orders.index'))->assertForbidden();
    }
}
