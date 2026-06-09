<!-- ===== Car Image Uploader ===== -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<div class="space-y-4" x-data="carImageUploader()" x-init="init()">

  <!-- Section 1: Gallery Photos (any dimensions) -->
  <div class="rounded bg-dark-3 border border-border p-6">
    <h2 class="mb-1 text-base font-medium text-white">Car Photos</h2>
    <p class="mb-4 text-xs text-light-1">Upload photos of the vehicle — any dimensions accepted. The first photo becomes the listing thumbnail.</p>

    <!-- Preview grid -->
    <div class="mb-3 grid grid-cols-3 gap-3" x-show="gallery.length > 0">
      <template x-for="(img, i) in gallery" :key="img.uid">
        <div class="relative group aspect-video overflow-hidden rounded border border-border">
          <img :src="img.preview" class="h-full w-full object-cover">
          <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
            <button type="button" @click="removeGallery(i)"
              class="bg-red-500 text-white rounded-full h-7 w-7 flex items-center justify-center">
              <i class="icon-close text-xs"></i>
            </button>
          </div>
          <span x-show="i === 0 && !heroImage"
            class="absolute top-1 left-1 rounded bg-blue-1 px-1.5 py-0.5 text-xs font-medium text-white">Primary</span>
        </div>
      </template>
    </div>

    <!-- Hidden real file input submitted with form -->
    <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple
      class="hidden" x-ref="galleryInput" @change="addGalleryImages($event)">

    <button type="button" @click="$refs.galleryInput.click()"
      class="flex w-full items-center gap-3 rounded border border-dashed border-border px-4 py-4 hover:border-blue-1 transition-colors">
      <i class="icon-plus text-blue-1 text-lg"></i>
      <span class="text-sm text-light-1">
        <span x-show="gallery.length === 0">Click to add photos</span>
        <span x-show="gallery.length > 0" x-cloak>Add more photos</span>
      </span>
    </button>
  </div>

  <!-- Section 2: Hero Carousel Image (shown only when "Feature on Homepage" is checked) -->
  <div class="rounded bg-dark-3 border border-border p-6" x-show="isFeatured" x-cloak>
    <h2 class="mb-1 text-base font-medium text-white">
      Hero Carousel Image
      <span class="ml-2 text-xs font-normal text-yellow-400">Required for homepage carousel</span>
    </h2>
    <p class="mb-4 text-xs text-light-1">
      Crop your image to the <strong class="text-white">1500 × 650 px</strong> banner ratio used in the homepage slider.
    </p>

    <!-- Hero preview -->
    <div x-show="heroImage" x-cloak class="mb-3 relative overflow-hidden rounded border border-yellow-500/40" style="aspect-ratio:1500/650">
      <img :src="heroImage" class="h-full w-full object-cover">
      <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 flex items-center justify-between px-3 transition-opacity">
        <span class="rounded bg-yellow-400 px-2 py-0.5 text-xs font-semibold text-dark-1">Carousel Hero</span>
        <button type="button" @click="$refs.heroInput.click()"
          class="rounded bg-dark-3 border border-border px-3 py-1.5 text-xs text-white hover:bg-dark-4">Change</button>
      </div>
    </div>

    <!-- Select/crop button (shown when no hero yet) -->
    <button type="button" @click="$refs.heroInput.click()" x-show="!heroImage"
      class="flex w-full items-center gap-3 rounded border border-dashed border-yellow-500/50 px-4 py-4 hover:border-yellow-400 transition-colors">
      <i class="icon-camera text-yellow-400 text-lg"></i>
      <span class="text-sm text-light-1">Click to select &amp; crop carousel image</span>
    </button>

    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
      x-ref="heroInput" @change="openHeroCropper($event)">
    <input type="hidden" name="cropped_hero" :value="heroImage || ''">
  </div>

  <!-- Cropper Modal -->
  <div x-show="modalOpen" x-cloak
    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 p-4">
    <div class="w-full max-w-3xl rounded bg-dark-2 border border-border shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 border-b border-border">
        <h3 class="text-white font-semibold">
          Crop Carousel Image
          <span class="text-xs font-normal text-light-1 ml-1">1500 × 650 — drag/scroll to adjust</span>
        </h3>
        <button type="button" @click="cancelCrop()" class="text-light-1 hover:text-white">
          <i class="icon-close text-lg"></i>
        </button>
      </div>
      <div class="p-4 bg-dark-1" style="max-height:60vh;overflow:hidden;">
        <img x-ref="cropImg" src="" alt="crop" style="max-width:100%;display:block;">
      </div>
      <div class="flex items-center justify-between px-5 py-3 border-t border-border">
        <div class="flex gap-2 text-xs text-light-1">
          <button type="button" @click="cropper.zoom(0.1)"  class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">＋ Zoom</button>
          <button type="button" @click="cropper.zoom(-0.1)" class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">－ Zoom</button>
          <button type="button" @click="cropper.rotate(-90)"class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">↺ Rotate</button>
          <button type="button" @click="cropper.reset()"    class="rounded border border-border px-3 py-1.5 hover:bg-dark-3">Reset</button>
        </div>
        <button type="button" @click="useHeroCrop()"
          class="bg-yellow-400 hover:bg-yellow-300 text-dark-1 font-semibold rounded px-6 py-2 text-sm transition-colors">
          ✓ Use This Crop
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function carImageUploader() {
  return {
    gallery: [],      // [{uid, preview: objectURL, file: File}]
    heroImage: null,  // base64 data URL of cropped hero
    cropper: null,
    modalOpen: false,
    isFeatured: false,
    _uid: 0,

    init() {
      this.isFeatured = window._carFeatured ?? false;
      window.addEventListener('featured-toggle', (e) => {
        this.isFeatured = e.detail.checked;
      });
    },

    addGalleryImages(e) {
      Array.from(e.target.files).forEach(file => {
        this.gallery.push({ uid: ++this._uid, preview: URL.createObjectURL(file), file });
      });
      e.target.value = '';  // clear before _syncInput — clearing after wipes the DataTransfer files
      this._syncInput();
    },

    removeGallery(i) {
      URL.revokeObjectURL(this.gallery[i].preview);
      this.gallery.splice(i, 1);
      this._syncInput();
    },

    _syncInput() {
      const dt = new DataTransfer();
      this.gallery.forEach(g => dt.items.add(g.file));
      this.$refs.galleryInput.files = dt.files;
    },

    openHeroCropper(e) {
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
            aspectRatio: 1500 / 650,
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

    useHeroCrop() {
      if (!this.cropper) return;
      const canvas = this.cropper.getCroppedCanvas({ width: 1500, height: 650, imageSmoothingQuality: 'high' });
      this.heroImage = canvas.toDataURL('image/jpeg', 0.88);
      this.modalOpen = false;
      this.cropper.destroy();
      this.cropper = null;
    },

    cancelCrop() {
      this.modalOpen = false;
      if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
    },
  };
}
</script>
