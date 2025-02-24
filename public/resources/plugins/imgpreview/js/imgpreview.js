class ImagePreview {
    constructor() {
        // Inisialisasi event listeners untuk gambar yang memiliki attribute data-img-preview
        this.initializeImages();
    }

    createPreviewContainer() {
        // Buat modal container
        this.previewContainer = document.createElement("div");
        this.previewContainer.className = "image-preview-modal";

        // Buat container untuk title
        this.titleContainer = document.createElement("div");
        this.titleContainer.className = "preview-title";

        // Buat element gambar preview
        this.previewImage = document.createElement("img");
        this.previewImage.className = "preview-image";

        // Tambahkan tombol close
        this.closeButton = document.createElement("span");
        this.closeButton.innerHTML = "×";
        this.closeButton.className = "preview-close";

        // Gabungkan elements
        this.previewContainer.appendChild(this.titleContainer);
        this.previewContainer.appendChild(this.previewImage);
        this.previewContainer.appendChild(this.closeButton);

        // Sembunyikan container saat pertama kali
        this.previewContainer.style.display = "none";

        document.body.appendChild(this.previewContainer);

        // Tambahkan event listeners untuk menutup preview
        this.closeButton.onclick = () => this.hidePreview();
        this.previewContainer.onclick = (e) => {
            if (e.target === this.previewContainer) {
                this.hidePreview();
            }
        };

        // Tambahkan event listener untuk keyboard
        document.addEventListener("keydown", (e) => {
            if (
                e.key === "Escape" &&
                this.previewContainer.style.display !== "none"
            ) {
                this.hidePreview();
            }
        });
    }

    initializeImages() {
        // Ambil semua gambar dengan attribute data-img-preview
        const images = document.querySelectorAll("img[data-img-preview]");

        // Tambahkan event listener untuk setiap gambar yang memenuhi syarat
        images.forEach((img) => {
            // Skip preview image sendiri
            if (img === this.previewImage) return;

            // Tambahkan style cursor pointer
            img.classList.add("preview-enabled");

            // Tambahkan event click
            img.addEventListener("click", () => {
                const title = img.getAttribute("data-img-preview-title") || "";
                this.showPreview(img.src, title);
            });
        });
    }

    showPreview(imageSrc, title) {
        if (!this.previewContainer) {
            this.createPreviewContainer();
        }

        this.titleContainer.innerHTML = title;
        this.previewImage.src = imageSrc;
        this.previewContainer.style.display = "flex";

        // Tambahkan class untuk animasi fade in
        this.previewContainer.classList.add("fade-in");

        // Aktifkan scroll lock
        document.body.style.overflow = "hidden";
    }

    hidePreview() {
        this.previewContainer.classList.remove("fade-in");
        this.previewContainer.style.display = "none";
        this.previewImage.src = "";
        this.titleContainer.textContent = "";

        // Kembalikan scroll
        document.body.style.overflow = "";

        // Hapus element modal
        this.previewContainer.remove();

        // Hapus event listeners
        this.previewContainer = null;
    }
}
