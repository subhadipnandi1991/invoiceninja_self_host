@component('email.template.admin', ['design' => 'light', 'settings' => $settings, 'logo' => $logo])
    <div class="center">
        @isset($greeting)
            <p>{{ $greeting }}</p>
        @endisset

        @isset($title)
            <h1>{{ $title }}</h1>
        @endisset

        @isset($h2)
            <h2>{{ $title }}</h2>
        @endisset

        <div style="margin-top: 10px; margin-bottom: 30px;">
            @isset($content)
                {{ $content }}
            @endisset

            @isset($slot)
                {{ $slot }}
            @endisset
        </div>

        @isset($additional_info)
            <p>{{ $additional_info }}</p>
        @endisset

        @isset($url)
            <a href="{{ $url }}" class="button" target="_blank">{{ ctrans($button) }}</a>
        @endisset
    </div>
@endcomponent