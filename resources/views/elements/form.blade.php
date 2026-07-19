@php
    $classes = trim($node->cssId().' b-form b-button-scope '.($node->setting('advanced', 'css_class') ?? ''));
    $sent = request('sent') === $node->cssId();
@endphp
<div class="{{ $classes }}">
@if ($sent)
<div class="frm-success">{{ $success ?? 'Thanks!' }}</div>
@else
<form method="POST" action="{{ route('buildr.form.submit', $node->id) }}">
@csrf
@foreach (($fields ?? []) as $i => $f)
@php $id = $node->cssId().'-f'.$i; @endphp
<div>
<label for="{{ $id }}">{{ $f['label'] ?? '' }}@if($f['required'] ?? false) *@endif</label>
@if (($f['type'] ?? 'text') === 'textarea')
<textarea id="{{ $id }}" name="f{{ $i }}" rows="4"@if($f['required'] ?? false) required @endif></textarea>
@else
<input id="{{ $id }}" type="{{ $f['type'] ?? 'text' }}" name="f{{ $i }}"@if($f['required'] ?? false) required @endif>
@endif
</div>
@endforeach
<button type="submit" class="b-button" style="border:0;cursor:pointer;align-self:start">{{ $submit_label ?? 'Send' }}</button>
</form>
@endif
</div>
