<?php

namespace Tests\Feature;

use App\Models\EmailBroadcast;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminEmailHubTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    private function admin(string $role = 'owner'): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => $role])->save();

        return $admin;
    }

    public function test_admin_can_view_email_broadcast_hub(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.emails.broadcast'));

        $response->assertOk();
        $response->assertSee('Email Broadcasts');
        $response->assertSee('Mail Settings');
        $response->assertSee('Automated Store Emails');
    }

    public function test_admin_can_clone_and_edit_previous_broadcast(): void
    {
        $admin = $this->admin();

        $past = EmailBroadcast::create([
            'subject' => 'Flash Sale 50% Off Everything',
            'eyebrow' => 'Limited Time',
            'headline' => 'Half price on all subscriptions',
            'content' => 'Use code FLASH50 at checkout to get 50% off.',
            'button_label' => 'Claim Offer Now',
            'button_url' => 'https://store.novastaq.com/shop',
            'audience' => 'paying',
            'status' => 'sent',
            'recipients_count' => 12,
            'sent_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.emails.broadcast', ['clone' => $past->id]));

        $response->assertOk();
        $response->assertSee('Loaded from Previous Broadcast');
        $response->assertSee('Flash Sale 50% Off Everything');
        $response->assertSee('Half price on all subscriptions');
        $response->assertSee('Use code FLASH50 at checkout');
    }

    public function test_admin_can_save_broadcast_as_draft(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.emails.broadcast.send'), [
            'audience' => 'registered',
            'subject' => 'Draft Announcement',
            'eyebrow' => 'Internal Draft',
            'headline' => 'Upcoming features coming soon',
            'content' => 'This is a draft that is not yet ready to send to customers.',
            'button_label' => 'Read More',
            'button_url' => 'https://novastaq.com',
            'save_draft' => '1',
        ]);

        $response->assertRedirect(route('admin.emails.broadcast'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('email_broadcasts', [
            'subject' => 'Draft Announcement',
            'status' => 'draft',
            'recipients_count' => 0,
        ]);
    }

    public function test_admin_can_preview_past_broadcast(): void
    {
        $admin = $this->admin();

        $broadcast = EmailBroadcast::create([
            'subject' => 'Preview Test Subject',
            'eyebrow' => 'Tag',
            'headline' => 'Big Headline',
            'content' => 'Sample body content for testing preview rendering.',
            'audience' => 'all',
            'status' => 'sent',
            'recipients_count' => 5,
            'sent_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.emails.broadcast.preview', $broadcast));

        $response->assertOk();
        $response->assertSee('Preview Test Subject');
        $response->assertSee('Big Headline');
    }

    public function test_admin_can_delete_past_broadcast(): void
    {
        $admin = $this->admin();

        $broadcast = EmailBroadcast::create([
            'subject' => 'Delete Me Later',
            'headline' => 'Headline',
            'content' => 'Content',
            'audience' => 'all',
            'status' => 'sent',
            'sent_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.emails.broadcast.destroy', $broadcast));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_broadcasts', ['id' => $broadcast->id]);
    }

    public function test_admin_can_view_and_update_mail_settings(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.emails.settings'));
        $response->assertOk();
        $response->assertSee('Mail Settings & Server');
        $response->assertSee('Harmonweb cPanel SMTP Setup');

        // Save new settings
        $postResponse = $this->actingAs($admin)->post(route('admin.emails.settings.update'), [
            'mail_driver' => 'smtp',
            'smtp_host' => 'mail.novastaq.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'hello@novastaq.com',
            'smtp_password' => 'secret_cpanel_password',
            'mail_from_address' => 'hello@novastaq.com',
            'mail_from_name' => 'Novastaq Store Live',
            'mail_reply_to' => 'support@novastaq.com',
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('status');

        $this->assertSame('smtp', Setting::get('mail_driver'));
        $this->assertSame('mail.novastaq.com', Setting::get('smtp_host'));
        $this->assertSame(465, Setting::get('smtp_port'));
        $this->assertSame('Novastaq Store Live', Setting::get('mail_from_name'));
    }

    public function test_admin_sees_informative_error_when_mail_provider_fails(): void
    {
        // Force an HTTP 401 Unauthorized from Mailhive
        Setting::set('mail_driver', 'mailhive');
        Setting::set('mailhive_api_key', 'invalid_key');

        Http::fake([
            '*' => Http::response(['message' => 'Invalid Bearer token provided'], 401),
        ]);

        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.emails.broadcast.test'), [
            'subject' => 'Test Failure Detection',
            'headline' => 'Test Failure Headline',
            'content' => 'Testing error reporting message.',
            'test_recipient' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('Mailhive API rejected request (HTTP 401)', $error);
        $this->assertStringContainsString('Invalid Bearer token provided', $error);
    }

    public function test_admin_can_view_automated_emails_page(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.emails.automated'));

        $response->assertOk();
        $response->assertSee('Automated Store Emails');
        $response->assertSee('Order Confirmation & Receipt');
        $response->assertSee('2-Hour Payment Recovery Reminder');
        $response->assertSee('Digital Content & License Delivery');
    }

    public function test_admin_broadcast_view_lists_active_products_in_quick_picker(): void
    {
        $admin = $this->admin();

        $category = \App\Models\Category::create(['name' => 'AI Tools', 'slug' => 'ai-tools']);

        \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'Google Gemini 1.5 Pro',
            'slug' => 'google-gemini-1-5-pro',
            'type' => 'digital',
            'price' => 7500,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.emails.broadcast'));

        $response->assertOk();
        $response->assertSee('Google Gemini 1.5 Pro');
        $response->assertSee(url('/p/google-gemini-1-5-pro'));
        $response->assertSee('Store Home');
        $response->assertSee('All Products');
    }

    public function test_button_url_normalizes_relative_and_domain_links(): void
    {
        $admin = $this->admin();

        // 1. Relative path like /shop
        $this->actingAs($admin)->post(route('admin.emails.broadcast.send'), [
            'audience' => 'all',
            'subject' => 'Relative Link Test',
            'headline' => 'Test Headline',
            'content' => 'Content here',
            'button_label' => 'Shop Now',
            'button_url' => '/shop',
            'save_draft' => 1,
        ]);

        $this->assertDatabaseHas('email_broadcasts', [
            'subject' => 'Relative Link Test',
            'button_url' => url('/shop'),
        ]);

        // 2. Domain path like store.novastaq.com/p/gemini
        $this->actingAs($admin)->post(route('admin.emails.broadcast.send'), [
            'audience' => 'all',
            'subject' => 'Domain Link Test',
            'headline' => 'Test Headline',
            'content' => 'Content here',
            'button_label' => 'Shop Now',
            'button_url' => 'store.novastaq.com/p/gemini',
            'save_draft' => 1,
        ]);

        $this->assertDatabaseHas('email_broadcasts', [
            'subject' => 'Domain Link Test',
            'button_url' => 'https://store.novastaq.com/p/gemini',
        ]);
    }

    public function test_diagnostic_test_sends_tags_as_dictionary_object_to_mailhive(): void
    {
        Setting::set('mail_driver', 'mailhive');
        Setting::set('mailhive_api_key', 'test_key_valid');

        Http::fake([
            '*' => Http::response(['status' => 'success', 'id' => 'msg_123'], 200),
        ]);

        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.emails.settings.test'), [
            'test_to' => 'diagnostics@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $tags = $request->data()['tags'] ?? null;

            // Must be an associative dictionary/object, never a sequential list
            return is_array($tags)
                && ! array_is_list($tags)
                && ($tags['type'] ?? null) === 'diagnostic-test';
        });
    }

    public function test_mailhive_normalizes_sequential_tags_array_into_dictionary(): void
    {
        Setting::set('mail_driver', 'mailhive');
        Setting::set('mailhive_api_key', 'test_key_valid');

        Http::fake([
            '*' => Http::response(['status' => 'success', 'id' => 'msg_123'], 200),
        ]);

        $mailhive = app(\App\Support\Mailhive::class);

        // Intentionally call with sequential list
        $ok = $mailhive->send(
            to: 'customer@example.com',
            subject: 'Testing List Tags Normalization',
            html: '<p>Hello</p>',
            tags: ['legacy-tag']
        );

        $this->assertTrue($ok);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            $tags = $request->data()['tags'] ?? null;

            return is_array($tags)
                && ! array_is_list($tags)
                && ($tags['type'] ?? null) === 'legacy-tag';
        });
    }
}

