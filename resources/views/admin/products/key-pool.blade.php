<section id="key-inventory" class="panel p-5 space-y-4 min-w-0" x-data="productKeyPool({{ Js::from([
    'editing' => $editing,
    'previewUrl' => $editing ? route('admin.products.keys.preview', $product) : route('admin.products.keys.preview-new'),
    'storeUrl' => $editing ? route('admin.products.keys.store', $product) : null,
    'listUrl' => $editing ? route('admin.products.keys.index', $product) : null,
    'raw' => old('add_digital_keys', ''),
    'mode' => old('key_import_mode', 'lines'),
]) }})">
    <div>
        <h2 class="font-semibold text-lg">Digital key inventory</h2>
        <p class="text-sm text-zinc-500">{{ $editing ? 'Imports and removals save immediately. Product settings have their own Save button.' : 'Keys will be imported when you create the product.' }}</p>
    </div>
    <label class="label" for="key_import_mode">Paste format</label>
    <select id="key_import_mode" @if (! $editing) name="key_import_mode" @endif x-model="mode" @change="preview = null" class="field">
        <option value="lines">One exact link or code per line</option>
        <option value="list">Numbered or bulleted list (remove leading markers)</option>
        <option value="wrapped">Wrapped URLs (join continuation lines and remove leading markers)</option>
    </select>
    <label class="label" for="add_digital_keys">Links or codes</label>
    <textarea id="add_digital_keys" @if (! $editing) name="add_digital_keys" @endif rows="5" x-model="raw" @input="preview = null" maxlength="50000" class="field font-mono text-xs" placeholder="Paste one entry per line"></textarea>
    <p class="text-xs text-zinc-500">Punctuation is preserved. Choose wrapped URLs only when your source split links across lines. Review the exact preview before importing.</p>
    @error('add_digital_keys')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
    <div class="flex gap-2 flex-wrap">
        <button type="button" class="btn-secondary" @click="previewKeys()" :disabled="busy || !raw.trim()">Preview import</button>
        @if ($editing)
            <button type="button" class="btn-primary" @click="importKeys()" :disabled="busy || !preview || !preview.keys.length || preview.rejected.length">Import reviewed entries</button>
        @endif
    </div>
    <p role="status" aria-live="polite" class="text-sm" x-text="message"></p>
    <template x-if="preview">
        <div class="border rounded-lg p-3 space-y-2">
            <p class="text-sm" x-text="preview.keys.length + ' new, ' + preview.duplicates.length + ' duplicates skipped, ' + preview.rejected.length + ' rejected'"></p>
            <div class="max-h-48 overflow-auto space-y-1">
                <template x-for="(key, index) in preview.keys" :key="index"><p class="font-mono text-xs break-all whitespace-pre-wrap" x-text="key"></p></template>
            </div>
            <template x-if="preview.rejected.length"><p class="text-sm text-red-700">Rejected entries contain control characters or exceed 5,000 characters. Correct them before importing.</p></template>
        </div>
    </template>
    @if ($editing)
        <div class="border-t pt-4 space-y-3">
            <p class="text-sm" x-text="available + ' available · ' + delivered + ' delivered · ' + total + ' total'"></p>
            <label for="key-search" class="label">Search inventory</label>
            <div class="flex gap-2">
                <input id="key-search" type="search" x-model="search" @keydown.enter.prevent="loadKeys(1)" class="field" placeholder="Search links or codes" maxlength="190">
                <button type="button" class="btn-secondary" @click="loadKeys(1)" :disabled="busy">Search</button>
            </div>
            <div class="divide-y border rounded-lg max-h-80 overflow-auto">
                <template x-for="key in keys" :key="key.id">
                    <div class="p-3 flex items-center justify-between gap-3">
                        <span class="font-mono text-xs break-all select-all" x-text="key.key_content"></span>
                        <span x-show="key.assigned" class="text-xs text-zinc-500">Delivered</span>
                        <button type="button" x-show="!key.assigned" class="text-sm text-red-700 shrink-0" :disabled="busy" @click="removeKey(key)">Remove</button>
                    </div>
                </template>
                <p x-show="!keys.length" class="p-3 text-sm text-zinc-500">No matching keys.</p>
            </div>
            <div class="flex items-center justify-between gap-2">
                <button type="button" class="btn-secondary" @click="loadKeys(page - 1)" :disabled="busy || page <= 1">Previous</button>
                <span class="text-xs" x-text="'Page ' + page + ' of ' + lastPage"></span>
                <button type="button" class="btn-secondary" @click="loadKeys(page + 1)" :disabled="busy || page >= lastPage">Next</button>
            </div>
        </div>
    @endif
</section>
<script>
function productKeyPool(config) {
    return {
        raw: config.raw, mode: config.mode, preview: null, busy: false, message: '',
        keys: [], search: '', page: 1, lastPage: 1, available: 0, delivered: 0, total: 0,
        async init() { if (config.editing) await this.loadKeys(1); },
        async request(url, method = 'GET', body = null) {
            const response = await fetch(url, {
                method, headers: {'Accept': 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                ...(body ? {body: JSON.stringify(body)} : {})
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.error || data.message || 'Request failed. Please try again.');
            return data;
        },
        payload() { return {add_digital_keys: this.raw, key_import_mode: this.mode}; },
        async previewKeys() {
            this.busy = true; this.message = '';
            const raw = this.raw, mode = this.mode;
            try {
                const result = await this.request(config.previewUrl, 'POST', this.payload());
                if (raw === this.raw && mode === this.mode) this.preview = result;
            } catch (error) { this.message = error.message; }
            finally { this.busy = false; }
        },
        async importKeys() {
            if (!this.preview || this.busy) return;
            this.busy = true;
            const raw = this.raw, mode = this.mode;
            try {
                const data = await this.request(config.storeUrl, 'POST', this.payload());
                if (raw === this.raw && mode === this.mode) { this.raw = ''; this.preview = null; }
                this.message = data.message; this.search = '';
                await this.loadKeys(1);
            } catch (error) { this.message = error.message; }
            finally { this.busy = false; }
        },
        async loadKeys(page) {
            this.busy = true;
            try {
                const url = new URL(config.listUrl, location.origin);
                url.searchParams.set('page', page); url.searchParams.set('q', this.search);
                const data = await this.request(url);
                this.keys = data.keys.data; this.page = data.keys.current_page;
                this.lastPage = data.keys.last_page;
                this.available = data.available; this.delivered = data.delivered; this.total = data.total;
            } catch (error) { this.message = error.message; }
            finally { this.busy = false; }
        },
        async removeKey(key) {
            if (this.busy || !confirm('Remove this unused key from inventory?')) return;
            this.busy = true;
            try {
                const data = await this.request(key.destroy_url, 'DELETE');
                this.message = data.message;
                await this.loadKeys(this.keys.length === 1 && this.page > 1 ? this.page - 1 : this.page);
            } catch (error) { this.message = error.message; }
            finally { this.busy = false; }
        }
    };
}
</script>
