@props(['name', 'value' => 0])
<input {{ $attributes }} id="{{ $name }}" name="{{ $name }}" type="text" inputmode="numeric" data-money-input
       pattern="[0-9]+|[0-9]{1,3}(,[0-9]{3})+" value="{{ \App\Support\Money::format($value) }}" required>
