@if($inline)
<div class="d-flex flex-wrap">
@endif

@foreach($options as $k => $label)
    <div class="vs-radio-con vs-radio-success{{ $style }}" style="margin-right: {{ $right }}">
        <input {!! in_array($k, $disabled) ? 'disabled' : '' !!} value="{{$k}}" {!! $attributes !!} {!! ($checked == $k && $checked !== null) ? 'checked' : '' !!}>
        <span class="vs-radio vs-radio-{{ $size }}">
          <span class="vs-radio--border"></span>
          <span class="vs-radio--circle"></span>
        </span>
        @if($label !== null && $label !== '')
            <span>{!! $label !!}</span>
        @endif
    </div>
@endforeach

@if($inline)
</div>
@endif