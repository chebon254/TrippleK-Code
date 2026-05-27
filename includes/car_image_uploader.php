<!-- ===== Car Image Uploader with Cropper.js (1500×650) ===== -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<div class="rounded bg-dark-3 border border-border p-6"
  x-data="carImageUploader()"
  x-init="init()">

  <h2 class="mb-1 text-base font-medium text-white">Photos</h2>
  <p class="mb-4 text-xs text-light-1">
    Select &amp; crop each photo to the banner ratio (1500 × 650 px). First photo becomes the hero slider image.
  </p>

  <!-- Preview grid -->
  <div class="mb-4 grid grid-cols-3 gap-3" x-show="crops.length > 0">
    <template x-for="(crop, i) in crops" :key="i">
      <div class="relative group aspect-video overflow-hidden rounded border border-border">
        <img :src="crop.preview" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
          <button type="button" @click="removeCrop(i)"
            class="bg-red-500 text-white rounded-full h-7 w-7 flex items-center justify-center text-xs">
            <i class="icon-close"></i>
          </button>
        </div>
        <span x-show="i === 0"
          class="absolute top-1 left-1 rounded bg-blue-1 px-1.5 py-0.5 text-xs font-medium text-white">
          Primary
        </span>
        <!-- Hidden: base64 data sent to PHP -->
        <input type="hidden" name="cropped_images[]" :value="crop.data">
      </div>
    </template>
  </div>

  <!-- Add photo button -->
  <label class="flex cursor-pointer items-center gap-3 rounded border border-dashed border-border px-4 py-4 hover:border-blue-1 transition-colors">
    <i class="icon-plus text-blue-1 text-lg"></i>
    <span class="text-sm text-light-1">Click to select photo &mdash; you can crop it before adding</span>
    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
      @change="openCropper($event)">
  </label>

  <!-- Cropper Modal -->
  <div x-show="modalOpen" x-cloak
    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4">
    <div class="w-full max-w-3xl rounded bg-dark-2 border border-border shadow-2xl overflow-hidden">

      <div class="flex items-center justify-between px-5 py-3 border-b border-border">
        <h3 class="text-white font-semibold">Crop Photo <span class="text-xs font-normal text-light-1">(drag to reposition, scroll to zoom)</span></h3>
        <button type="button" @click="cancelCrop()" class="text-light-1 hover:text-white">
          <i class="icon-close text-lg"></i>
        </button>
      </div>

      <div class="p-4 bg-dark-1" style="max-height:60vh;overflow:hidden;">
        <img x-ref="cropImg" src="" alt="crop" style="max-width:100%;display:block;">
      </div>

      <div class="flex items-center justify-between px-5 py-3 border-t border-border">
        <div class="flex gap-2 text-xs text-light-1">
          <button type="button" @click="cropper.zoom(0.1)" class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">＋ Zoom</button>
          <button type="button" @click="cropper.zoom(-0.1)" class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">－ Zoom</button>
          <button type="button" @click="cropper.rotate(-90)" class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">↺ Rotate</button>
          <button type="button" @click="cropper.reset()" class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">Reset</button>
        </div>
        <button type="button" @click="useCrop()"
          class="bg-blue-1 hover:bg-yellow-3 text-dark-1 font-semibold rounded px-6 py-2 text-sm transition-colors">
          ✓ Use This Crop
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function carImageUploader() {
  return {
    crops: [],       // [{preview: objectURL, data: base64}]
    cropper: null,
    modalOpen: false,

    init() {},

    openCropper(e) {
      const file = e.target.files[0];
      e.target.value = ''; // reset so same file can be re-selected
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.$refs.cropImg.src = ev.target.result;
        this.modalOpen = true;
        this.$nextTick(() => {
          if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
          this.cropper = new Cropper(this.$refs.cropImg, {
            aspectRatio: 1500 / 650,  // banner ratio
            viewMode: 1,
            autoCropArea: 0.95,
            background: false,
            guides: true,
            highlight: false,
            movable: true,
            scalable: true,
            zoomable: true,
          });
        });
      };
      reader.readAsDataURL(file);
    },

    useCrop() {
      if (!this.cropper) return;
      const canvas = this.cropper.getCroppedCanvas({ width: 1500, height: 650, imageSmoothingQuality: 'high' });
      const preview = canvas.toDataURL('image/jpeg', 0.88);
      this.crops.push({ preview, data: preview });
      this.modalOpen = false;
      this.cropper.destroy();
      this.cropper = null;
    },

    cancelCrop() {
      this.modalOpen = false;
      if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
    },

    removeCrop(i) {
      this.crops.splice(i, 1);
    },
  };
}
</script>
