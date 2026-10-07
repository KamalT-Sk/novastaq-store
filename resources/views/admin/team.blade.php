<x-admin-layout title="Team">
    <h1 class="text-2xl font-semibold tracking-[-0.02em] mb-1">Team</h1>
    <p class="text-[14px] text-zinc-500 mb-6">Give people access to the admin. Each role only sees what it needs.</p>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        <section class="panel divide-y divide-black/[0.06]">
            @foreach ($staff as $member)
                @php($isMe = $member->is(auth()->user()))
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="w-9 h-9 shrink-0 rounded-full bg-zinc-100 text-zinc-600 text-[13px] font-semibold inline-flex items-center justify-center">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="font-medium text-[14px] truncate">{{ $member->name }} @if ($isMe)<span class="text-zinc-400 font-normal">(you)</span>@endif</p>
                            <p class="text-[13px] text-zinc-500 truncate">{{ $member->email }}</p>
                        </div>
                    </div>
                    @if ($isMe)
                        <span class="badge">{{ $member->roleLabel() }}</span>
                    @else
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.team.update', $member) }}">
                                @csrf @method('PATCH')
                                <select name="role" onchange="this.form.submit()" class="field h-9 text-[13px] w-44" aria-label="Role for {{ $member->name }}">
                                    @foreach ($roles as $key => $role)
                                        <option value="{{ $key }}" @selected($member->role === $key)>{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('admin.team.resend', $member) }}">
                                @csrf<button class="btn-secondary h-9 px-3 text-[13px]" title="Email a new link to set their password">Resend link</button>
                            </form>
                            <form method="POST" action="{{ route('admin.team.destroy', $member) }}" onsubmit="return confirm('Remove admin access for {{ addslashes($member->name) }}? Their customer account stays.')">
                                @csrf @method('DELETE')<button class="btn-danger h-9 px-3 text-[13px]">Remove</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.team.store') }}" class="panel p-6 space-y-4">
                @csrf
                <h2 class="font-semibold">Add someone</h2>
                <div>
                    <label class="label" for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="field h-11">
                    @error('name')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field h-11">
                    @error('email')<p class="text-[13px] text-red-600 mt-1.5">{{ $message }}</p>@enderror
                </div>
                <fieldset class="space-y-2">
                    <legend class="label">Role</legend>
                    @foreach ($roles as $key => $role)
                        <label class="flex gap-3 p-3 rounded-xl border border-black/[0.08] cursor-pointer has-[:checked]:border-ink has-[:checked]:bg-zinc-50">
                            <input type="radio" name="role" value="{{ $key }}" @checked(old('role', 'editor') === $key) class="mt-0.5 text-ink focus:ring-ink">
                            <span class="text-[14px]"><span class="font-medium">{{ $role['label'] }}</span><span class="block text-[13px] text-zinc-500 leading-snug mt-0.5">{{ $role['about'] }}</span></span>
                        </label>
                    @endforeach
                    @error('role')<p class="text-[13px] text-red-600">{{ $message }}</p>@enderror
                </fieldset>
                <button class="btn-primary w-full">Add and send invite</button>
                <p class="text-[12px] text-zinc-400">They get an email with a link to set their password. If they already have a store account, they just sign in as usual.</p>
            </form>
        </div>
    </div>
</x-admin-layout>
