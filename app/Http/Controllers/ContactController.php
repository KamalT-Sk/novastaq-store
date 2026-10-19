<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Support\Mailhive;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Relays the novastaq.com contact / referral / careers forms to the inbox via Mailhive,
 * stores the enquiry for the admin panel, and notifies admins and the customer.
 */
class ContactController extends Controller
{
    public function store(Request $request, Mailhive $mail, Notifier $notifier): JsonResponse
    {
        // Bots fill every field, people never see this one
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'reply_to' => ['required', 'email', 'max:190'],
            'fields' => ['required', 'array', 'max:20'],
            'fields.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $fields = $data['fields'];
        $name = $fields['Name'] ?? $fields['name'] ?? null;
        $email = $data['reply_to'] ?? $fields['Email'] ?? $fields['email'] ?? null;
        $phone = $fields['Phone/WhatsApp'] ?? $fields['phone'] ?? $fields['Phone'] ?? null;
        $company = $fields['Company'] ?? $fields['company'] ?? null;
        $service = $fields['Service'] ?? $fields['service'] ?? null;
        $budget = $fields['Budget'] ?? $fields['budget'] ?? null;
        $message = $fields['Message'] ?? $fields['message'] ?? '';

        // Store enquiry in database so it can be managed and replied to from the admin panel
        $enquiry = Enquiry::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'service' => $service,
            'budget' => $budget,
            'subject' => $data['subject'],
            'message' => $message,
            'fields' => $fields,
            'ip_address' => $request->ip(),
            'status' => 'new',
        ]);

        // Render branded admin notification email
        $adminHtml = view('emails.admin-enquiry', [
            'enquiry' => $enquiry,
            'subject' => $data['subject'],
            'replyTo' => $data['reply_to'],
            'fields' => $fields,
        ])->render();

        $adminEmails = $notifier->adminEmails();
        if (! in_array('novastaqtechnologies@gmail.com', $adminEmails, true)) {
            $adminEmails[] = 'novastaqtechnologies@gmail.com';
        }

        $sent = false;
        foreach ($adminEmails as $to) {
            $ok = $mail->send($to, '✉️ '.$data['subject'], $adminHtml, $data['reply_to'], ['type' => 'website-enquiry']);
            if ($ok) {
                $sent = true;
            }
        }

        // Send a branded confirmation receipt to the customer
        try {
            $customerHtml = view('emails.enquiry-received', [
                'enquiry' => $enquiry,
                'name' => $name ?: 'there',
                'service' => $service,
            ])->render();

            $mail->send(
                $data['reply_to'],
                'We received your message — Novastaq',
                $customerHtml,
                (string) config('store.support_email', 'hello@novastaq.com'),
                ['type' => 'customer-enquiry-receipt']
            );
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => $sent, 'id' => $enquiry->id], $sent ? 200 : 502);
    }
}
