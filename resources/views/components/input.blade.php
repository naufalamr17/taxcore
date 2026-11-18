@props(['name','label','value'=>null,'type'=>'text','required'=>false])
<label class="form-label">{{ $label }}<input name="{{ $name }}" type="{{ $type }}" value="{{ old($name,$value) }}" @required($required) {{ $attributes->class(['field']) }}></label>
