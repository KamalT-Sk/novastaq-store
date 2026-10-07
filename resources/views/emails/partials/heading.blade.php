{{-- Eyebrow + big heading + intro. Vars: $eyebrow, $title, $intro (HTML allowed) --}}
<p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#4f46e5;">{{ $eyebrow }}</p>
<h1 class="h1" style="margin:0 0 12px;font-size:28px;line-height:1.2;font-weight:700;letter-spacing:-0.02em;color:#0b0b0f;">{{ $title }}</h1>
@isset($intro)<p style="margin:0 0 4px;color:#52525b;">{!! $intro !!}</p>@endisset
