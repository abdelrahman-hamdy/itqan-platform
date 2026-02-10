<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="gateway-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="close"></div>

                {{-- Modal Panel --}}
                <div class="relative bg-white rounded-2xl text-right overflow-hidden shadow-2xl transform transition-all sm:my-8 w-full max-w-md">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 id="gateway-modal-title" class="text-lg font-bold text-white">
                                {{ __('payments.gateway_selection.title') }}
                            </h3>
                            <button wire:click="close" class="text-white/80 hover:text-white transition-colors">
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        </div>
                        <p class="text-blue-100 text-sm mt-1">
                            {{ __('payments.gateway_selection.subtitle') }}
                        </p>
                    </div>

                    {{-- Gateway Options --}}
                    <div class="px-6 py-5 space-y-3">
                        @foreach($availableGateways as $key => $gateway)
                            <button
                                wire:click="selectGateway('{{ $key }}')"
                                class="w-full p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 flex items-center gap-4 text-right
                                    {{ $selectedGateway === $key ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}"
                            >
                                {{-- Icon --}}
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center
                                    {{ $selectedGateway === $key ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                    <i class="{{ $gateway['icon'] }} text-2xl"></i>
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900">{{ $gateway['display_name'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ __('payments.gateway_selection.supported_methods') }}
                                        @foreach($gateway['methods'] as $method)
                                            <span class="inline-block bg-gray-100 rounded px-1.5 py-0.5 text-gray-600 ml-1">
                                                {{ __('payments.method_types.'.$method, [], app()->getLocale()) !== 'payments.method_types.'.$method
                                                    ? __('payments.method_types.'.$method)
                                                    : $method }}
                                            </span>
                                        @endforeach
                                    </p>
                                </div>

                                {{-- Checkmark --}}
                                @if($selectedGateway === $key)
                                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                        <i class="ri-check-line text-white text-sm"></i>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 pb-8">
                        <button
                            wire:click="confirm"
                            @if(!$selectedGateway) disabled @endif
                            class="w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200
                                {{ $selectedGateway
                                    ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-200'
                                    : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                        >
                            <i class="ri-lock-line ml-2"></i>
                            {{ __('payments.gateway_selection.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
