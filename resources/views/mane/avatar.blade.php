@props([
    'model' => null,
    'text' => null,
    'image' => null,
    'size' => 'md',
])

<x-ts-avatar {{ $attributes }} :model="$model" :text="$text" :image="$image" :size="$size" color="primary" />
