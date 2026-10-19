<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHIVE = 'api.mailhive.africa/v1/send/emails';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailhive.key' => 'mhs_test_dummy',
            'store.support_email' => 'hello@novastaq.com',
            'store.admin_emails' => 'hello@novastaq.com',
        ]);
    }

    private function admin(string $role = 'owner'): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => $role])->save();

        return $admin;
    }

    private function emails(): Collection
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => str_contains($r->url(), 'mailhive'));
    }

    public function test_contact_form_stores_enquiry_and_sends_branded_emails(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_1'])]);

        $payload = [
            'subject' => 'Software & apps: enquiry from Jane Doe (Acme Corp)',
            'reply_to' => 'jane@example.com',
            'fields' => [
                'Name' => 'Jane Doe',
                'Email' => 'jane@example.com',
                'Phone/WhatsApp' => '08012345678',
                'Company' => 'Acme Corp',
                'Service' => 'Software & apps',
                'Budget' => '₦1M – ₦5M',
                'Message' => 'We need a bespoke inventory management web app with automated billing.',
            ],
        ];

        $response = $this->withHeader('Origin', 'https://novastaq.com')->postJson('/api/contact', $payload);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        // Verify Enquiry is persisted in the database
        $this->assertDatabaseHas('enquiries', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '08012345678',
            'company' => 'Acme Corp',
            'service' => 'Software & apps',
            'budget' => '₦1M – ₦5M',
            'status' => 'new',
        ]);

        $enquiry = Enquiry::where('email', 'jane@example.com')->first();
        $this->assertNotNull($enquiry);
        $this->assertSame('https://wa.me/2348012345678', $enquiry->whatsappUrl());

        // Verify emails dispatched
        $sentEmails = $this->emails();
        $this->assertGreaterThanOrEqual(2, $sentEmails->count());

        $recipients = $sentEmails->map(fn (HttpRequest $r) => $r->data()['to'] ?? null)->flatten()->all();
        // Guaranteed to include both hello@novastaq.com AND novastaqtechnologies@gmail.com
        $this->assertContains('hello@novastaq.com', $recipients);
        $this->assertContains('novastaqtechnologies@gmail.com', $recipients);
        // Also customer receipt sent to jane@example.com
        $this->assertContains('jane@example.com', $recipients);

        // Verify sender name is "Novastaq" (not "Novastaq Store")
        foreach ($sentEmails as $emailRequest) {
            $from = $emailRequest->data()['from'] ?? '';
            $this->assertStringStartsWith('Novastaq <', $from);
            $this->assertStringNotContainsString('Novastaq Store <', $from);
        }

        // Verify Admin alert email content is branded
        $adminEmail = $sentEmails->first(fn (HttpRequest $r) => in_array('novastaqtechnologies@gmail.com', (array) $r->data()['to'], true));
        $this->assertNotNull($adminEmail);
        $this->assertStringContainsString('Jane Doe', $adminEmail->data()['html']);
        $this->assertStringContainsString('Acme Corp', $adminEmail->data()['html']);
        $this->assertStringContainsString('Software &amp; apps', $adminEmail->data()['html']);
        $this->assertStringContainsString('bespoke inventory management web app', $adminEmail->data()['html']);
        $this->assertStringContainsString(route('admin.enquiries.show', $enquiry), $adminEmail->data()['html']);

        // Verify customer confirmation receipt is branded
        $customerReceipt = $sentEmails->first(fn (HttpRequest $r) => in_array('jane@example.com', (array) $r->data()['to'], true));
        $this->assertNotNull($customerReceipt);
        $this->assertStringContainsString('Thanks for reaching out', $customerReceipt->data()['html']);
        $this->assertStringContainsString('Software &amp; apps', $customerReceipt->data()['html']);
    }

    public function test_admin_can_view_enquiries_index_and_details(): void
    {
        $admin = $this->admin('owner');

        $enquiry = Enquiry::create([
            'name' => 'Michael Scott',
            'email' => 'michael@dundermifflin.com',
            'phone' => '08099887766',
            'company' => 'Dunder Mifflin',
            'service' => 'Procurement',
            'budget' => '₦5M – ₦20M',
            'subject' => 'Procurement: enquiry from Michael',
            'message' => 'Need 15 MacBook Pros for sales staff.',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.enquiries.index'));
        $response->assertOk();
        $response->assertSee('Enquiries &amp; Leads', false);
        $response->assertSee('Michael Scott');
        $response->assertSee('michael@dundermifflin.com');
        $response->assertSee('New');

        // Test search
        $this->actingAs($admin)->get(route('admin.enquiries.index', ['q' => 'Dunder']))
            ->assertOk()
            ->assertSee('Michael Scott');

        $this->actingAs($admin)->get(route('admin.enquiries.index', ['q' => 'Nonexistent']))
            ->assertOk()
            ->assertDontSee('Michael Scott');

        // View single enquiry
        $showResponse = $this->actingAs($admin)->get(route('admin.enquiries.show', $enquiry));
        $showResponse->assertOk();
        $showResponse->assertSee('Need 15 MacBook Pros for sales staff.');
        $showResponse->assertSee('Reply Directly to Customer');
    }

    public function test_admin_can_reply_to_enquiry_sending_branded_email(): void
    {
        Http::fake([self::MAILHIVE => Http::response(['id' => 'em_reply_1'])]);
        $admin = $this->admin('owner');

        $enquiry = Enquiry::create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'service' => 'Software & apps',
            'subject' => 'Software & apps: enquiry from Ada',
            'message' => 'Can you build an analytics algorithm?',
            'status' => 'new',
        ]);

        $replyPayload = [
            'subject' => 'Re: Software & apps: enquiry from Ada',
            'message' => 'Hello Ada, we would love to build this algorithm for you. Here are the next steps...',
        ];

        $response = $this->actingAs($admin)->post(route('admin.enquiries.reply', $enquiry), $replyPayload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Check enquiry status is updated
        $enquiry->refresh();
        $this->assertSame('replied', $enquiry->status);
        $this->assertNotNull($enquiry->replied_at);
        $this->assertSame($admin->id, $enquiry->replied_by_id);
        $this->assertSame('Hello Ada, we would love to build this algorithm for you. Here are the next steps...', $enquiry->reply_message);

        // Verify sent email
        $sentEmail = $this->emails()->last();
        $this->assertNotNull($sentEmail);
        $this->assertSame('ada@example.com', $sentEmail->data()['to']);
        $this->assertStringStartsWith('Novastaq <', $sentEmail->data()['from']);
        $this->assertStringContainsString('Re: Software &amp; apps: enquiry from Ada', $sentEmail->data()['html']);
        $this->assertStringContainsString('Hello Ada, we would love to build this algorithm for you', $sentEmail->data()['html']);
        $this->assertStringContainsString('Can you build an analytics algorithm?', $sentEmail->data()['html']);
    }

    public function test_admin_can_update_status_and_delete_enquiry(): void
    {
        $admin = $this->admin('owner');

        $enquiry = Enquiry::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message',
            'status' => 'new',
        ]);

        // Update status to archived
        $this->actingAs($admin)->patch(route('admin.enquiries.status', $enquiry), ['status' => 'archived'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('archived', $enquiry->fresh()->status);

        // Delete enquiry
        $this->actingAs($admin)->delete(route('admin.enquiries.destroy', $enquiry))
            ->assertRedirect(route('admin.enquiries.index'));

        $this->assertModelMissing($enquiry);
    }

    public function test_non_staff_cannot_access_enquiries(): void
    {
        $user = User::factory()->create(); // regular customer

        $this->actingAs($user)->get(route('admin.enquiries.index'))->assertForbidden();
    }
}

