@extends('emails.layout')
@section('title', $subject)
@section('preheader', $eyebrow ? $eyebrow.' · '.$headline : $headline)
@section('body')
    @include('emails.partials.heading', [
        'eyebrow' => $eyebrow ?: 'Novastaq Announcement',
        'title' => $headline,
    ])

    <div style="font-size:15px;line-height:1.7;color:#3f3f46;margin:16px 0 24px;">
        {!! nl2br(e($content)) !!}
    </div>

    @if (! empty($buttonUrl) && ! empty($buttonLabel))
        @include('emails.partials.button', [
            'url' => $buttonUrl,
            'label' => $buttonLabel,
        ])
    @endif
@endsection
