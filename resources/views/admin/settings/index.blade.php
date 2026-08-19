@php
    $admin = auth('admin')->user();
    $logoUrl = $settings['academy_logo'] ? \App\Helpers\StorageHelper::url($settings['academy_logo']) : null;
@endphp

<x-layout.admin title="Settings">

    <x-admin.page-header title="Settings" subtitle="Branding and profile — changes apply across the whole software"
        :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Settings' => null]" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- ------------------------------------------------ Academy branding --}}
        <div class="panel h-full">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">🏏</span>
                <h5 class="text-lg font-semibold dark:text-white-light">Academy Branding</h5>
            </div>
            <p class="mb-5 text-xs text-white-dark">
                The name and logo appear on the login page, sidebar, header, receipts and WhatsApp messages.
            </p>

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
                class="space-y-5" x-data="{ logoPreview: @js($logoUrl), removeLogo: false }">
                @csrf @method('PUT')

                <x-admin.field label="Academy Name" name="academy_name" :required="true">
                    <input type="text" name="academy_name" id="academy_name" class="text-base font-semibold form-input"
                        value="{{ old('academy_name', $settings['academy_name']) }}" required maxlength="100" />
                </x-admin.field>

                {{-- Logo --}}
                <div>
                    <p class="mb-1 text-sm font-semibold dark:text-white-light">Academy Logo</p>
                    <p class="mb-3 text-xs text-white-dark">PNG or JPG up to 2 MB. Square images look best. Leave empty to keep the cricket-ball icon.</p>
                    <div class="flex items-center gap-4">
                        <div class="grid w-20 h-20 overflow-hidden border-2 border-dashed rounded-xl place-content-center shrink-0 border-[#e0e6ed] dark:border-[#253b5e] bg-white-light/40 dark:bg-[#1b2e4b]">
                            <template x-if="logoPreview && !removeLogo">
                                <img :src="logoPreview" alt="Logo" class="object-contain w-20 h-20" />
                            </template>
                            <template x-if="!logoPreview || removeLogo">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="text-primary">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" />
                                    <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </template>
                        </div>
                        <div class="space-y-2">
                            <input type="file" name="logo" accept="image/*" class="form-input text-sm !p-1.5"
                                @change="const f = $event.target.files[0]; if (f) { logoPreview = URL.createObjectURL(f); removeLogo = false; }" />
                            @if ($logoUrl)
                                <label class="flex items-center gap-2 text-xs cursor-pointer text-danger">
                                    <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="form-checkbox" />
                                    Remove current logo (go back to the default icon)
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin.field label="Currency Symbol" name="currency_symbol" :required="true" hint="Shown on every money figure">
                        <input type="text" name="currency_symbol" id="currency_symbol" class="text-base font-bold form-input"
                            value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required maxlength="5" />
                    </x-admin.field>

                    <x-admin.field label="WhatsApp Country Code" name="whatsapp_country_code" :required="true" hint="Prepended to 10-digit numbers">
                        <input type="text" name="whatsapp_country_code" id="whatsapp_country_code" class="form-input"
                            value="{{ old('whatsapp_country_code', $settings['whatsapp_country_code']) }}" required maxlength="4" />
                    </x-admin.field>
                </div>

                <button class="w-full btn btn-primary">Save Academy Settings</button>
            </form>
        </div>

        {{-- ---------------------------------------------------- My profile --}}
        <div class="panel h-full">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">👤</span>
                <h5 class="text-lg font-semibold dark:text-white-light">My Profile</h5>
            </div>
            <p class="mb-5 text-xs text-white-dark">
                Your display name is used in the dashboard greeting — “Welcome back, {{ explode(' ', $admin->name)[0] }}”.
            </p>

            <form method="POST" action="{{ route('admin.settings.profile') }}" class="space-y-5">
                @csrf @method('PUT')

                <x-admin.field label="Your Name" name="name" :required="true">
                    <input type="text" name="name" id="name" class="text-base font-semibold form-input"
                        value="{{ old('name', $admin->name) }}" required maxlength="100" />
                </x-admin.field>

                <x-admin.field label="Email (login)" name="email" :required="true">
                    <input type="email" name="email" id="email" class="form-input"
                        value="{{ old('email', $admin->email) }}" required />
                </x-admin.field>

                <x-admin.field label="Phone" name="phone">
                    <input type="text" name="phone" id="phone" class="form-input"
                        value="{{ old('phone', $admin->phone) }}" maxlength="20" />
                </x-admin.field>

                <div class="p-3 text-xs rounded bg-primary/5 text-white-dark">
                    Signed in as <strong>{{ $admin->role_label }}</strong>
                    · joined {{ $admin->created_at?->format('M Y') }}
                </div>

                <button class="w-full btn btn-secondary">Update Profile</button>
            </form>
        </div>

    </div>

</x-layout.admin>
