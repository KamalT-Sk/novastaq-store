@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'field h-11']) }}>
