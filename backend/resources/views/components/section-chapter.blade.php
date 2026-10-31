@props([
    'id' => null,
    'number' => '01',
    'overline' => 'CAPÍTULO EDITORIAL',
    'title' => '',
    'italic' => null,
    'subtitle' => null,
])

<section @if($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => 'chapter-section']) }}>
    <div class="container">
        <header class="chapter-header">
            <div class="editorial-overline">
                <span class="chapter-number">{{ $number }} /</span>
                <span>{{ $overline }}</span>
            </div>
            
            <h2 class="chapter-title title-reveal">
                <span class="title-reveal-inner">
                    {{ $title }}
                    @if($italic)
                        <em>{{ $italic }}</em>
                    @endif
                </span>
            </h2>

            @if($subtitle)
                <p class="chapter-subtitle scroll-fade-up">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <div class="chapter-body">
            {{ $slot }}
        </div>
    </div>
</section>

