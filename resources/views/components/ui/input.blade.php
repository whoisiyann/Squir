<div class="input-group">

    <i class="{{ $icon }}"></i>

    <input
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        name="{{ $name }}"
        {{ $attributes }}>

    @if($eye ?? false)
        <i class="fa-regular fa-eye eye"></i>
    @endif

</div>