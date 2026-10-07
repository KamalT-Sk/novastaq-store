<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Owners add staff and choose what each person can reach (User::ROLES). */
class TeamController extends Controller
{
    public function index(): View
    {
        $order = array_flip(array_keys(User::ROLES));

        return view('admin.team', [
            'staff' => User::whereNotNull('role')->orderBy('name')->get()->sortBy(fn ($u) => $order[$u->role] ?? 99)->values(),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $user = User::where('email', $data['email'])->first();
        if ($user?->isStaff()) {
            return back()->with('error', "{$user->email} is already on the team as {$user->roleLabel()}. Change their role in the list.");
        }

        // Someone new gets an account with an unusable password and sets their own from the email.
        $isNew = ! $user;
        $user ??= User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(40)]);
        $user->forceFill(['role' => $data['role']])->save();

        $sent = $notifier->staffInvite($user, $request->user(), $isNew);

        return back()->with('status', "{$user->name} added as {$user->roleLabel()}. ".($sent
            ? ($isNew ? "We've emailed them a link to set their password." : "We've emailed them to let them know.")
            : "The email didn't send. Ask them to use “Forgot password” on the sign-in page with {$user->email}."));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($guard = $this->guard($request, $user)) {
            return $guard;
        }
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::ROLES))]]);
        $user->forceFill(['role' => $data['role']])->save();

        return back()->with('status', "{$user->name} is now {$user->roleLabel()}.");
    }

    /** Takes away admin access. Their account stays, as a normal customer account. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($guard = $this->guard($request, $user)) {
            return $guard;
        }
        $user->forceFill(['role' => null])->save();

        return back()->with('status', "{$user->name} no longer has admin access.");
    }

    public function resend(Request $request, User $user, Notifier $notifier): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        return back()->with(...($notifier->staffInvite($user, $request->user(), true)
            ? ['status', "Sent {$user->name} a new link to set their password."]
            : ['error', "The email didn't send. Ask {$user->name} to use “Forgot password” on the sign-in page."]));
    }

    /** You can't change or remove yourself, so the store always keeps an owner. */
    private function guard(Request $request, User $user): ?RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        return $user->is($request->user())
            ? back()->with('error', "You can't change your own access. Ask another owner.")
            : null;
    }
}
