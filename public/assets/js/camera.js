/**
 * SICA-E · Módulo de Captura Facial con Cámara Web (HTML5 Canvas + MediaDevices)
 * Institución Educativa Brighton Pamplona
 */

class CameraCapture {
  constructor(options = {}) {
    this.videoEl = document.getElementById(options.videoId || 'cameraVideo');
    this.previewImg = document.getElementById(options.previewId || 'cameraPreviewImg');
    this.placeholderEl = document.getElementById(options.placeholderId || 'cameraPlaceholder');
    this.btnStart = document.getElementById(options.btnStartId || 'btnStartCamera');
    this.btnSnap = document.getElementById(options.btnSnapId || 'btnSnapCamera');
    this.btnRetake = document.getElementById(options.btnRetakeId || 'btnRetakeCamera');
    this.inputHidden = document.getElementById(options.inputId || 'photoDataInput');
    this.inputFile = document.getElementById(options.fileInputId || 'photoFileInput');
    this.errorEl = document.getElementById(options.errorId || 'cameraError');

    this.stream = null;
    this.capturedDataUrl = null;

    this.init();
  }

  init() {
    if (this.btnStart) {
      this.btnStart.addEventListener('click', () => this.start());
    }

    if (this.btnSnap) {
      this.btnSnap.addEventListener('click', () => this.snap());
    }

    if (this.btnRetake) {
      this.btnRetake.addEventListener('click', () => this.retake());
    }

    if (this.inputFile) {
      this.inputFile.addEventListener('change', (e) => this.handleFile(e));
    }
  }

  async start() {
    this.clearError();
    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error("Tu navegador no soporta captura de cámara. Usa la opción de subir archivo.");
      }

      this.stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: { ideal: 640 },
          height: { ideal: 640 }
        },
        audio: false
      });

      this.videoEl.srcObject = this.stream;
      await this.videoEl.play();

      this.videoEl.style.display = 'block';
      if (this.previewImg) this.previewImg.style.display = 'none';
      if (this.placeholderEl) this.placeholderEl.style.display = 'none';

      if (this.btnStart) this.btnStart.style.display = 'none';
      if (this.btnSnap) this.btnSnap.style.display = 'inline-flex';
      if (this.btnRetake) this.btnRetake.style.display = 'none';

    } catch (err) {
      console.warn("Camera error:", err);
      this.showError("No se pudo iniciar la cámara (" + err.message + "). Puedes subir una foto desde tu equipo.");
    }
  }

  stop() {
    if (this.stream) {
      this.stream.getTracks().forEach(track => track.stop());
      this.stream = null;
    }
  }

  snap() {
    if (!this.videoEl || !this.stream) return;

    const canvas = document.createElement('canvas');
    const size = Math.min(this.videoEl.videoWidth, this.videoEl.videoHeight) || 480;
    canvas.width = size;
    canvas.height = size;

    const ctx = canvas.getContext('2d');
    const sx = (this.videoEl.videoWidth - size) / 2;
    const sy = (this.videoEl.videoHeight - size) / 2;

    ctx.drawImage(this.videoEl, sx, sy, size, size, 0, 0, size, size);

    this.capturedDataUrl = canvas.toDataURL('image/jpeg', 0.85);

    // Detener la cámara
    this.stop();

    // Actualizar vista
    this.videoEl.style.display = 'none';
    if (this.previewImg) {
      this.previewImg.src = this.capturedDataUrl;
      this.previewImg.style.display = 'block';
    }

    if (this.inputHidden) {
      this.inputHidden.value = this.capturedDataUrl;
    }

    if (this.btnSnap) this.btnSnap.style.display = 'none';
    if (this.btnRetake) this.btnRetake.style.display = 'inline-flex';

    // Disparar evento de captura para la mascota
    window.dispatchEvent(new CustomEvent('photoCaptured', { detail: { dataUrl: this.capturedDataUrl } }));
  }

  retake() {
    this.capturedDataUrl = null;
    if (this.inputHidden) this.inputHidden.value = '';
    if (this.previewImg) {
      this.previewImg.src = '';
      this.previewImg.style.display = 'none';
    }
    this.start();
  }

  handleFile(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      this.capturedDataUrl = event.target.result;
      this.stop();

      if (this.videoEl) this.videoEl.style.display = 'none';
      if (this.placeholderEl) this.placeholderEl.style.display = 'none';
      if (this.previewImg) {
        this.previewImg.src = this.capturedDataUrl;
        this.previewImg.style.display = 'block';
      }
      if (this.inputHidden) {
        this.inputHidden.value = this.capturedDataUrl;
      }
      if (this.btnStart) this.btnStart.style.display = 'none';
      if (this.btnSnap) this.btnSnap.style.display = 'none';
      if (this.btnRetake) this.btnRetake.style.display = 'inline-flex';

      window.dispatchEvent(new CustomEvent('photoCaptured', { detail: { dataUrl: this.capturedDataUrl } }));
    };
    reader.readAsDataURL(file);
  }

  showError(msg) {
    if (this.errorEl) {
      this.errorEl.textContent = msg;
      this.errorEl.style.display = 'block';
    }
  }

  clearError() {
    if (this.errorEl) {
      this.errorEl.textContent = '';
      this.errorEl.style.display = 'none';
    }
  }
}

window.CameraCapture = CameraCapture;
