<x-admin-layout title="Mail Settings & Server">
    <div class="mb-6">
        <a href="{{ route('admin.emails.broadcast') }}" class="text-[13px] text-zinc-500 hover:text-ink">← Emails & Broadcasts</a>
        <h1 class="text-2xl font-semibold tracking-[-0.02em] mt-1">Mail Settings & Server</h1>
        <p class="text-[13px] text-zinc-500 mt-0.5">Configure how Novastaq Store delivers receipts, digital activation keys, recovery reminders, and marketing broadcasts.</p>
    </div>

    @include('admin.emails.tabs', ['current' => 'settings'])

    {{-- Provider Status Banner --}}
    <div class="mb-6 p-4 rounded-xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 {{ $isConfigured ? 'bg-emerald-50/60 border-emerald-200/80 text-emerald-900' : 'bg-amber-50/70 border-amber-200 text-amber-900' }}">
        <div class="flex items-start gap-3">
            <span class="text-xl leading-none mt-0.5">{{ $isConfigured ? '🟢' : '⚠️' }}</span>
            <div>
                <p class="font-semibold text-[14px]">
                    {{ $isConfigured ? "Connected via {$driverLabel}" : 'Mail Provider Not Configured' }}
                </p>
                <p class="text-[12px] {{ $isConfigured ? 'text-emerald-700' : 'text-amber-800' }} mt-0.5">
                    @if ($isConfigured)
                        Sending from <strong>{{ $config['mail_from_name'] }} &lt;{{ $config['mail_from_address'] }}&gt;</strong>.
                    @else
                        Emails are currently not being delivered. Please enter your Mailhive API Key or custom SMTP credentials below.
                    @endif
                </p>
            </div>
        </div>
        <a href="#test-section" class="btn-secondary text-[12px] h-8 px-3 whitespace-nowrap">Jump to Diagnostic Test ↓</a>
    </div>

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
        {{-- Settings Form --}}
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.emails.settings.update') }}"
                  x-data="{
                      driver: '{{ old('mail_driver', $config['mail_driver']) }}',
                      showMailhiveKey: false,
                      showSmtpPass: false
                  }"
                  class="panel p-6 space-y-6">
                @csrf

                <div>
                    <label class="label font-semibold text-[14px] text-ink mb-2">Select Active Mail Provider</label>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="driver === 'mailhive' ? 'border-ink bg-zinc-50/80 ring-1 ring-ink' : 'border-black/[0.08] hover:bg-zinc-50/40'">
                            <input type="radio" name="mail_driver" value="mailhive" x-model="driver" class="mt-1 text-ink focus:ring-ink">
                            <div class="text-[13px]">
                                <p class="font-semibold text-ink">Mailhive Send API</p>
                                <p class="text-[12px] text-zinc-500 mt-0.5">Direct API integration (api.mailhive.africa) using a Bearer token.</p>
                            </div>
                        </label>

                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="driver === 'smtp' ? 'border-ink bg-zinc-50/80 ring-1 ring-ink' : 'border-black/[0.08] hover:bg-zinc-50/40'">
                            <input type="radio" name="mail_driver" value="smtp" x-model="driver" class="mt-1 text-ink focus:ring-ink">
                            <div class="text-[13px]">
                                <p class="font-semibold text-ink">Custom SMTP Server</p>
                                <p class="text-[12px] text-zinc-500 mt-0.5">Use Harmonweb cPanel Webmail, Gmail, Google Workspace, Brevo, etc.</p>
                            </div>
                        </label>

                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="driver === 'auto' ? 'border-ink bg-zinc-50/80 ring-1 ring-ink' : 'border-black/[0.08] hover:bg-zinc-50/40'">
                            <input type="radio" name="mail_driver" value="auto" x-model="driver" class="mt-1 text-ink focus:ring-ink">
                            <div class="text-[13px]">
                                <p class="font-semibold text-ink">Auto-Detect</p>
                                <p class="text-[12px] text-zinc-500 mt-0.5">Uses Mailhive if key exists; otherwise uses SMTP credentials.</p>
                            </div>
                        </label>

                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="driver === 'system' ? 'border-ink bg-zinc-50/80 ring-1 ring-ink' : 'border-black/[0.08] hover:bg-zinc-50/40'">
                            <input type="radio" name="mail_driver" value="system" x-model="driver" class="mt-1 text-ink focus:ring-ink">
                            <div class="text-[13px]">
                                <p class="font-semibold text-ink">System Default (.env)</p>
                                <p class="text-[12px] text-zinc-500 mt-0.5">Uses Laravel's environment mail settings configured on server.</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Mailhive Credentials Section --}}
                <div x-show="driver === 'mailhive' || driver === 'auto'" x-cloak class="p-4 rounded-xl bg-zinc-50 border border-black/[0.06] space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[12px] font-semibold text-zinc-700 uppercase tracking-wider">Mailhive Send API Credentials</p>
                        <span class="text-[11px] text-zinc-400">api.mailhive.africa</span>
                    </div>

                    <div>
                        <label class="label text-xs" for="mailhive_api_key">Mailhive API Key (Bearer Token)</label>
                        <div class="relative">
                            <input :type="showMailhiveKey ? 'text' : 'password'" id="mailhive_api_key" name="mailhive_api_key"
                                   value="{{ old('mailhive_api_key', $config['mailhive_api_key']) }}"
                                   placeholder="mhs_live_..."
                                   class="field h-10 bg-white pr-16 text-[13px] font-mono">
                            <button type="button" @click="showMailhiveKey = !showMailhiveKey"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-xs text-zinc-500 hover:text-ink">
                                <span x-text="showMailhiveKey ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">Found in your Mailhive Dashboard &gt; Send &gt; API keys.</p>
                    </div>

                    <div>
                        <label class="label text-xs" for="mailhive_api_url">API Base URL</label>
                        <input type="url" id="mailhive_api_url" name="mailhive_api_url"
                               value="{{ old('mailhive_api_url', $config['mailhive_api_url']) }}"
                               placeholder="https://api.mailhive.africa/v1"
                               class="field h-10 bg-white text-[13px] font-mono">
                    </div>
                </div>

                {{-- SMTP Credentials Section --}}
                <div x-show="driver === 'smtp' || driver === 'auto'" x-cloak class="p-4 rounded-xl bg-zinc-50 border border-black/[0.06] space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[12px] font-semibold text-zinc-700 uppercase tracking-wider">SMTP Server Settings (cPanel / Gmail / Brevo)</p>
                        <span class="text-[11px] text-zinc-400">Standard SMTP</span>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="label text-xs" for="smtp_host">SMTP Host</label>
                            <input type="text" id="smtp_host" name="smtp_host"
                                   value="{{ old('smtp_host', $config['smtp_host']) }}"
                                   placeholder="e.g. mail.novastaq.com or smtp.gmail.com"
                                   class="field h-10 bg-white text-[13px] font-mono">
                        </div>
                        <div>
                            <label class="label text-xs" for="smtp_port">Port</label>
                            <input type="number" id="smtp_port" name="smtp_port"
                                   value="{{ old('smtp_port', $config['smtp_port']) }}"
                                   placeholder="465"
                                   class="field h-10 bg-white text-[13px] font-mono">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="label text-xs" for="smtp_encryption">Encryption</label>
                            <select id="smtp_encryption" name="smtp_encryption" class="field h-10 bg-white text-[13px]">
                                <option value="ssl" @selected(old('smtp_encryption', $config['smtp_encryption']) === 'ssl')>SSL (Port 465)</option>
                                <option value="tls" @selected(old('smtp_encryption', $config['smtp_encryption']) === 'tls')>TLS (Port 587)</option>
                                <option value="none" @selected(old('smtp_encryption', $config['smtp_encryption']) === 'none')>None (Port 25)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label text-xs" for="smtp_username">Username / Email Address</label>
                            <input type="text" id="smtp_username" name="smtp_username"
                                   value="{{ old('smtp_username', $config['smtp_username']) }}"
                                   placeholder="hello@novastaq.com"
                                   class="field h-10 bg-white text-[13px] font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="label text-xs" for="smtp_password">SMTP Password / App Password</label>
                        <div class="relative">
                            <input :type="showSmtpPass ? 'text' : 'password'" id="smtp_password" name="smtp_password"
                                   value="{{ old('smtp_password', $config['smtp_password']) }}"
                                   placeholder="Your email password"
                                   class="field h-10 bg-white pr-16 text-[13px] font-mono">
                            <button type="button" @click="showSmtpPass = !showSmtpPass"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-xs text-zinc-500 hover:text-ink">
                                <span x-text="showSmtpPass ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Sender Identity Section --}}
                <div class="p-4 rounded-xl bg-zinc-50 border border-black/[0.06] space-y-4">
                    <p class="text-[12px] font-semibold text-zinc-700 uppercase tracking-wider">Sender Identity (What Customers See)</p>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs" for="mail_from_name">From Name</label>
                            <input type="text" id="mail_from_name" name="mail_from_name" required
                                   value="{{ old('mail_from_name', $config['mail_from_name']) }}"
                                   placeholder="Novastaq Store"
                                   class="field h-10 bg-white text-[13px]">
                        </div>
                        <div>
                            <label class="label text-xs" for="mail_from_address">From Email Address</label>
                            <input type="email" id="mail_from_address" name="mail_from_address" required
                                   value="{{ old('mail_from_address', $config['mail_from_address']) }}"
                                   placeholder="hello@novastaq.com"
                                   class="field h-10 bg-white text-[13px]">
                        </div>
                    </div>

                    <div>
                        <label class="label text-xs" for="mail_reply_to">Support & Reply-To Email</label>
                        <input type="email" id="mail_reply_to" name="mail_reply_to"
                               value="{{ old('mail_reply_to', $config['mail_reply_to']) }}"
                               placeholder="hello@novastaq.com"
                               class="field h-10 bg-white text-[13px]">
                        <p class="text-[11px] text-zinc-400 mt-1">When customers click "Reply", their response goes to this address.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit" class="btn-primary h-11 px-6 text-[14px]">
                        💾 Save Email Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: Live Diagnostic Test & Cheat Sheet --}}
        <div class="space-y-6" id="test-section">
            {{-- Diagnostics Card --}}
            <section class="panel p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink text-[14px] flex items-center gap-1.5">
                        <span>🧪</span>
                        <span>Send Diagnostic Test Email</span>
                    </h2>
                    <span class="badge {{ $isConfigured ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }} text-[11px]">
                        {{ $driverLabel }}
                    </span>
                </div>

                <p class="text-[12px] text-zinc-500 leading-relaxed">
                    Test your mail connection in real-time. If there is an authentication error, invalid API key, or timeout, the exact error will be displayed here immediately.
                </p>

                <form method="POST" action="{{ route('admin.emails.settings.test') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="label text-xs" for="test_to">Send Test To Email</label>
                        <input type="email" id="test_to" name="test_to" required
                               value="{{ old('test_to', $adminEmail) }}"
                               placeholder="novastaqtechnologies@gmail.com"
                               class="field h-10 bg-white text-[13px]">
                    </div>

                    <button type="submit" class="btn-secondary w-full h-10 text-[13px] inline-flex items-center justify-center gap-1.5">
                        <span>⚡</span>
                        <span>Send Diagnostic Ping</span>
                    </button>
                </form>

                @if ($lastError)
                    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs leading-relaxed">
                        <strong class="font-semibold block mb-0.5">Last Send Error:</strong>
                        {{ $lastError }}
                    </div>
                @endif
            </section>

            {{-- cPanel Help Card --}}
            <section class="panel p-5 space-y-3">
                <h3 class="font-semibold text-ink text-[13px] flex items-center gap-1.5">
                    <span>💡</span>
                    <span>Harmonweb cPanel SMTP Setup</span>
                </h3>
                <p class="text-[12px] text-zinc-600 leading-relaxed">
                    If you don't have an active Mailhive API token, you can use the free webmail built into your Harmonweb cPanel:
                </p>
                <div class="bg-zinc-50 p-3 rounded-lg border border-black/[0.06] text-xs font-mono space-y-1 text-zinc-700">
                    <p><strong>Host:</strong> mail.novastaq.com</p>
                    <p><strong>Port:</strong> 465 (SSL) or 587 (TLS)</p>
                    <p><strong>User:</strong> hello@novastaq.com</p>
                    <p><strong>Pass:</strong> (Your webmail password)</p>
                </div>
                <p class="text-[11px] text-zinc-400">
                    Create email accounts in <em>cPanel &gt; Email Accounts</em>.
                </p>
            </section>
        </div>
    </div>
</x-admin-layout>

