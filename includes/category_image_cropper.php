<!-- ===== Category Image Cropper (451×375) ===== -->
<?php
// Only load Cropper.js once per page
static $cropper_loaded = false;
if (!$cropper_loaded):
    $cropper_loaded = true;
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
function categoryImageCropper() {
  return {
    cropper: null,
    modalOpen: false,
    preview: null,
    data: null,

    openCropper(e) {
      const file = e.target.files[0];
      e.target.value = '';
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.$refs.cropImg.src = ev.target.result;
        this.modalOpen = true;
        this.$nextTick(() => {
          if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
          this.cropper = new Cropper(this.$refs.cropImg, {
            aspectRatio: 451 / 375,
            viewMode: 1,
            autoCropArea: 0.95,
            background: false,
            guides: true,
            highlight: false,
          });
        });
      };
      reader.readAsDataURL(file);
    },

    useCrop() {
      if (!this.cropper) return;
      const canvas = this.cropper.getCroppedCanvas({ width: 451, height: 375, imageSmoothingQuality: 'high' });
      this.data    = canvas.toDataURL('image/jpeg', 0.88);
      this.preview = this.data;
      this.modalOpen = false;
      this.cropper.destroy();
      this.cropper = null;
    },

    cancelCrop() {
      this.modalOpen = false;
      if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
    },

    removeCrop() { this.preview = null; this.data = null; },
  };
}
</script>
<?php endif; ?>

<div>
  <label class="mb-1.5 block text-sm font-medium text-white">Category Image <span class="text-light-1">(451 × 375 px)</span></label>

  <!-- Preview -->
  <div x-show="preview" class="mb-3 relative inline-block">
    <img :src="preview" class="h-24 rounded object-cover border border-border" style="aspect-ratio:451/375">
    <button type="button" @click="removeCrop()"
      class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full h-5 w-5 text-xs flex items-center justify-center">
      <i class="icon-close text-xs"></i>
    </button>
  </div>
  <input type="hidden" name="cropped_image" :value="data">

  <!-- Select button -->
  <label class="flex cursor-pointer items-center gap-2 rounded border border-dashed border-border px-4 py-3 hover:border-blue-1 transition-colors w-full">
    <i class="icon-plus text-blue-1"></i>
    <span class="text-sm text-light-1" x-text="preview ? 'Replace image' : 'Select & crop image'">Select &amp; crop image</span>
    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
      @change="openCropper($event)">
  </label>

  <!-- Cropper Modal -->
  <div x-show="modalOpen" x-cloak
    class="fixed inset-0 z-[300] flex items-center justify-center bg-black/80 p-4">
    <div class="w-full max-w-2xl rounded bg-dark-2 border border-border shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 border-b border-border">
        <h3 class="text-white font-semibold text-sm">Crop Image <span class="text-xs font-normal text-light-1">451 × 375 px</span></h3>
        <button type="button" @click="cancelCrop()" class="text-light-1 hover:text-white">
          <i class="icon-close"></i>
        </button>
      </div>
      <div class="p-4 bg-dark-1" style="max-height:55vh;overflow:hidden;">
        <img x-ref="cropImg" src="" alt="" style="max-width:100%;display:block;">
      </div>
      <div class="flex items-center justify-between px-5 py-3 border-t border-border">
        <div class="flex gap-2">
          <button type="button" @click="cropper.zoom(0.1)" class="rounded border border-border px-3 py-1.5 text-xs text-light-1 hover:bg-dark-3">＋ Zoom</button>
          <button type="button" @click="cropper.zoom(-0.1)" class="rounded border border-border px-3 py-1.5 text-xs text-light-1 hover:bg-dark-3">－ Zoom</button>
          <button type="button" @click="cropper.rotate(-90)" class="rounded border border-border px-3 py-1.5 text-xs text-light-1 hover:bg-dark-3">↺ Rotate</button>
          <button type="button" @click="cropper.reset()" class="rounded border border-border px-3 py-1.5 text-xs text-light-1 hover:bg-dark-3">Reset</button>
        </div>
        <button type="button" @click="useCrop()"
          class="bg-blue-1 hover:bg-yellow-3 text-dark-1 font-semibold rounded px-5 py-2 text-sm transition-colors">
          ✓ Use This Crop
        </button>
      </div>
    </div>
  </div>
</div>
