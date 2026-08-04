@props([
    'name' => 'photo',
    'current' => null,          // URL of an existing photo (edit mode)
    'label' => 'Student Photo',
])

{{--
    Live camera + file upload for the student's photo. The capture is written
    straight into a real <input type="file" name="{{ $name }}"> via DataTransfer,
    so the server side needs no changes — it still arrives as an uploaded file.
    Works on phones (front/back camera) and desktop webcams; falls back to plain
    file upload when the camera is unavailable or permission is denied.
--}}
<div x-data="photoCapture(@js($current))"
    x-on:beforeunload.window="stopStream()"
    @keydown.escape="cameraOn && closeCamera()">

    {{-- The real field the form submits. Driven by the buttons below. --}}
    <input type="file" name="{{ $name }}" x-ref="file" accept="image/*" class="hidden" @change="onFile($event)" />
    <canvas x-ref="canvas" class="hidden"></canvas>

    <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start">

        {{-- Preview / camera stage --}}
        <div class="relative shrink-0">
            {{-- Live camera --}}
            <div x-show="cameraOn" x-cloak
                class="relative overflow-hidden bg-black rounded-xl w-52 h-52">
                <video x-ref="video" autoplay playsinline muted class="object-cover w-full h-full"></video>
                <button type="button" @click="switchCamera()" title="Switch camera"
                    class="absolute p-2 text-white rounded-full top-2 ltr:right-2 rtl:left-2 bg-black/40 hover:bg-black/60">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6M1 20v-6h6" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            {{-- Still preview / placeholder --}}
            <div x-show="!cameraOn"
                class="flex items-center justify-center overflow-hidden border-2 border-dashed rounded-xl w-52 h-52 border-[#e0e6ed] dark:border-[#253b5e] bg-white-light/40 dark:bg-[#1b2e4b]">
                <template x-if="hasImage">
                    <img :src="previewUrl" alt="Student photo" class="object-cover w-full h-full" />
                </template>
                <template x-if="!hasImage">
                    <div class="p-4 text-center text-white-dark">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="12" cy="13" r="4" />
                        </svg>
                        <p class="text-xs">No photo yet</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Controls --}}
        <div class="flex-1 w-full">
            <p class="mb-1 text-sm font-semibold dark:text-white-light">{{ $label }}</p>
            <p class="mb-4 text-xs text-white-dark">Snap a photo with the camera or upload one. JPG or PNG, up to 2&nbsp;MB.</p>

            {{-- Idle actions --}}
            <div x-show="!cameraOn" class="flex flex-wrap gap-2">
                <button type="button" @click="startCamera()" class="gap-2 btn btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="12" cy="13" r="4" />
                    </svg>
                    <span x-text="hasImage ? 'Retake Photo' : 'Take Photo'">Take Photo</span>
                </button>
                <button type="button" @click="openFile()" class="gap-2 btn btn-outline-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Upload File
                </button>
                <button type="button" x-show="hasImage" @click="clearImage()" class="btn btn-outline-danger">
                    Remove
                </button>
            </div>

            {{-- Camera actions --}}
            <div x-show="cameraOn" x-cloak class="flex flex-wrap gap-2">
                <button type="button" @click="capture()" class="gap-2 btn btn-success">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <circle cx="12" cy="12" r="3" fill="currentColor" />
                    </svg>
                    Capture
                </button>
                <button type="button" @click="closeCamera()" class="btn btn-outline-danger">Cancel</button>
            </div>

            <p x-show="error" x-cloak x-text="error" class="mt-3 text-xs text-danger"></p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function photoCapture(current) {
                return {
                    stream: null,
                    cameraOn: false,
                    facing: 'environment',
                    previewUrl: current || '',
                    hasImage: !!current,
                    error: '',

                    openFile() {
                        this.$refs.file.click();
                    },

                    onFile(e) {
                        const f = e.target.files[0];
                        if (!f) return;
                        if (f.size > 2 * 1024 * 1024) {
                            this.error = 'That image is over 2 MB. Please pick a smaller one or use the camera.';
                            this.$refs.file.value = '';
                            return;
                        }
                        this.error = '';
                        this.setPreview(URL.createObjectURL(f));
                    },

                    async startCamera() {
                        this.error = '';
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            this.error = 'Camera is not available on this device. Please use Upload File.';
                            return;
                        }
                        try {
                            this.stream = await navigator.mediaDevices.getUserMedia({
                                video: { facingMode: this.facing }, audio: false,
                            });
                            this.cameraOn = true;
                            this.$nextTick(() => { this.$refs.video.srcObject = this.stream; });
                        } catch (err) {
                            this.error = 'Could not open the camera. Allow camera permission, or use Upload File.';
                        }
                    },

                    async switchCamera() {
                        this.facing = this.facing === 'environment' ? 'user' : 'environment';
                        this.stopStream();
                        await this.startCamera();
                    },

                    capture() {
                        const v = this.$refs.video;
                        if (!v || !v.videoWidth) return;
                        const maxW = 900;
                        const scale = Math.min(1, maxW / v.videoWidth);
                        const w = Math.round(v.videoWidth * scale);
                        const h = Math.round(v.videoHeight * scale);
                        const canvas = this.$refs.canvas;
                        canvas.width = w;
                        canvas.height = h;
                        canvas.getContext('2d').drawImage(v, 0, 0, w, h);
                        canvas.toBlob((blob) => {
                            if (!blob) return;
                            const file = new File([blob], 'admission-photo.jpg', { type: 'image/jpeg' });
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            this.$refs.file.files = dt.files;
                            this.setPreview(URL.createObjectURL(blob));
                            this.closeCamera();
                        }, 'image/jpeg', 0.85);
                    },

                    setPreview(url) {
                        this.previewUrl = url;
                        this.hasImage = true;
                    },

                    clearImage() {
                        this.previewUrl = '';
                        this.hasImage = false;
                        this.$refs.file.value = '';
                    },

                    stopStream() {
                        if (this.stream) {
                            this.stream.getTracks().forEach((t) => t.stop());
                            this.stream = null;
                        }
                    },

                    closeCamera() {
                        this.stopStream();
                        this.cameraOn = false;
                    },
                };
            }
        </script>
    @endpush
@endonce
