<x-filament::page>

    @if(method_exists($this, 'renderIntegrationHealthBanner'))
        {{ $this->renderIntegrationHealthBanner() }}
    @endif

    @php
        $progress = $this->getFeedProgress();
    @endphp

    <div @if($progress['active'] ?? false) wire:poll.3s @endif>
        <x-filament::section>
            <x-slot name="heading">{{ __('JSON feed') }}</x-slot>

            @if($progress)
                @php
                    $color = match (true) {
                        $progress['status'] === 'failed' || $progress['stale'] => 'rgb(var(--danger-500, 239 68 68))',
                        $progress['status'] === 'done' => 'rgb(var(--success-500, 34 197 94))',
                        default => 'rgb(var(--primary-500, 59 130 246))',
                    };
                    $startedAt = \Illuminate\Support\Carbon::parse($progress['started_at']);
                @endphp

                <div style="display: flex; justify-content: space-between; gap: 1rem; font-size: 0.875rem; margin-bottom: 0.5rem;">
                    <strong>
                        @if($progress['stale'])
                            {{ __('Vastgelopen: al :minutes minuten geen voortgang', ['minutes' => \Dashed\DashedEcommerceChannable\Classes\FeedProgress::STALE_AFTER_MINUTES]) }}
                        @elseif($progress['status'] === 'products')
                            {{ __('Productinformatie bijwerken: :done van :total productgroepen', ['done' => $progress['groups_done'], 'total' => $progress['groups_total']]) }}
                        @elseif($progress['status'] === 'queued')
                            {{ __('Wacht in de wachtrij') }}
                        @elseif($progress['status'] === 'running')
                            {{ __('Feed schrijven: :done van :total regels', ['done' => number_format($progress['rows_done'], 0, ',', '.'), 'total' => number_format($progress['rows_total'], 0, ',', '.')]) }}
                        @elseif($progress['status'] === 'done')
                            {{ __('Klaar') }}
                        @else
                            {{ __('Mislukt') }}
                        @endif
                    </strong>
                    <span>{{ $progress['percentage'] }}%</span>
                </div>

                <div style="height: 0.625rem; border-radius: 9999px; background: rgba(127, 127, 127, 0.2); overflow: hidden;">
                    <div style="height: 100%; width: {{ $progress['percentage'] }}%; background: {{ $color }}; transition: width 0.5s ease;"></div>
                </div>

                <p style="font-size: 0.8125rem; opacity: 0.75; margin-top: 0.5rem;">
                    {{ __('Gestart :time', ['time' => $startedAt->diffForHumans()]) }}
                    @if(! empty($progress['finished_at']))
                        · {{ __('klaar na :duration', ['duration' => $startedAt->diffForHumans(\Illuminate\Support\Carbon::parse($progress['finished_at']), \Carbon\CarbonInterface::DIFF_ABSOLUTE)]) }}
                    @endif
                    @if($progress['status'] === 'failed' && ! empty($progress['error']))
                        · {{ $progress['error'] }}
                    @endif
                </p>
            @else
                <p style="font-size: 0.875rem; opacity: 0.75;">{{ __('Nog geen feed vernieuwd vanaf deze pagina.') }}</p>
            @endif

            <p style="font-size: 0.8125rem; opacity: 0.75; margin-top: 0.75rem;">
                {{ __('Laatst geschreven') }}:
                @foreach($this->getFeedFiles() as $file)
                    {{ $file['locale'] }} {{ $file['updated_at'] ? \Illuminate\Support\Carbon::createFromTimestamp($file['updated_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i') : '-' }}@if(! $loop->last), @endif
                @endforeach
            </p>
        </x-filament::section>
    </div>

    <form wire:submit.prevent="submit" method="POST">
        {{ $this->form }}

        <button class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition bg-primary-500 text-white mt-4">Aanpassen</button>
    </form>

</x-filament::page>
