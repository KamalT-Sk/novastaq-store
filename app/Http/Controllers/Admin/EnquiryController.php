<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Support\Mailhive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = Enquiry::query();

        if ($tab === 'new') {
            $query->where('status', 'new');
        } elseif ($tab === 'replied') {
            $query->where('status', 'replied');
        }

        if ($q !== '') {
            $query->search($q);
        }

        $enquiries = $query->latest()->paginate(20)->withQueryString();

        $totalCount = Enquiry::count();
        $newCount = Enquiry::where('status', 'new')->count();
        $repliedCount = Enquiry::where('status', 'replied')->count();

        return view('admin.enquiries.index', compact('enquiries', 'tab', 'q', 'totalCount', 'newCount', 'repliedCount'));
    }

    public function show(Enquiry $enquiry): View
    {
        $enquiry->loadMissing('repliedBy');

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function reply(Request $request, Enquiry $enquiry, Mailhive $mail): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        $replySubject = trim((string) ($data['subject'] ?? ''));
        if ($replySubject === '') {
            $replySubject = str_starts_with($enquiry->subject, 'Re:')
                ? $enquiry->subject
                : 'Re: '.$enquiry->subject;
        }

        $replyMessage = $data['message'];

        $html = view('emails.enquiry-reply', [
            'enquiry' => $enquiry,
            'replySubject' => $replySubject,
            'replyMessage' => $replyMessage,
            'repliedBy' => auth()->user(),
        ])->render();

        $sent = $mail->send(
            $enquiry->email,
            $replySubject,
            $html,
            (string) config('store.support_email', 'hello@novastaq.com'),
            ['type' => 'enquiry-reply']
        );

        if (! $sent) {
            $err = $mail->getLastError() ?: 'Failed to dispatch email to customer. Please check your mail settings.';

            return back()->withInput()->with('error', $err);
        }

        $enquiry->update([
            'status' => 'replied',
            'replied_at' => now(),
            'replied_by_id' => auth()->id(),
            'reply_subject' => $replySubject,
            'reply_message' => $replyMessage,
        ]);

        return back()->with('status', 'Response sent successfully to '.$enquiry->email);
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:new,replied,archived'],
        ]);

        $enquiry->update(['status' => $data['status']]);

        return back()->with('status', 'Enquiry marked as '.ucfirst($data['status']).'.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry removed.');
    }
}

