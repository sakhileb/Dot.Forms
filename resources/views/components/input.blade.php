@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['style' => 'width: 100%; height: 44px; background: white; border: 1px solid #E5E7EB; border-radius: 10px; padding: 0 14px; font-size: 14px; color: #1A1A1A; outline: none; transition: border-color .15s; font-family: "Inter", sans-serif; margin-top: 6px;', 'onfocus' => "this.style.borderColor='#F5B800'", 'onblur' => "this.style.borderColor='#E5E7EB'"]) !!}}
