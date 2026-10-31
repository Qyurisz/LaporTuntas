import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet.markercluster';
import QRCode from 'qrcode';

window.Alpine = Alpine;
window.L = L;
window.QRCode = QRCode;

/**
 * Compress an image file client-side using HTMLCanvasElement before upload.
 * Keeps width/height <= 1280px and outputs JPEG at 0.82 quality.
 */
window.compressImageFile = async function (file, maxDimension = 1280, quality = 0.82) {
    if (!file || !file.type.startsWith('image/')) {
        return file;
    }

    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > maxDimension || height > maxDimension) {
                    if (width > height) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    } else {
                        width = Math.round((width * maxDimension) / height);
                        height = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            resolve(file);
                            return;
                        }
                        const safeName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                        const compressedFile = new File([blob], safeName, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        resolve(compressedFile);
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file);
            img.src = event.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
};

/**
 * Generate a downloadable PNG Ticket Card with QR Code for citizen proof.
 */
window.downloadTicketCardPng = async function (ticketData) {
    const canvas = document.createElement('canvas');
    canvas.width = 800;
    canvas.height = 480;
    const ctx = canvas.getContext('2d');

    // Background
    ctx.fillStyle = '#F3F7F8';
    ctx.fillRect(0, 0, 800, 480);

    // Card container
    ctx.fillStyle = '#FFFFFF';
    ctx.strokeStyle = '#D7E1E5';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.roundRect(32, 32, 736, 416, 24);
    ctx.fill();
    ctx.stroke();

    // Top accent bar
    ctx.fillStyle = '#16A6D8';
    ctx.beginPath();
    ctx.roundRect(32, 32, 736, 16, [24, 24, 0, 0]);
    ctx.fill();

    // Brand Title
    ctx.fillStyle = '#17324D';
    ctx.font = '800 28px "Nunito Sans", sans-serif';
    ctx.fillText('LAPOR TUNTAS — BUKTI TIKET LAPORAN', 64, 96);

    ctx.fillStyle = '#617482';
    ctx.font = '600 15px "Nunito Sans", sans-serif';
    ctx.fillText('Simpan tiket ini untuk melacak penanganan masalah publik sampai tuntas.', 64, 126);

    // Ticket ID Box
    ctx.fillStyle = '#DFF5FC';
    ctx.beginPath();
    ctx.roundRect(64, 152, 430, 88, 16);
    ctx.fill();

    ctx.fillStyle = '#0D8BB8';
    ctx.font = '700 13px "Nunito Sans", sans-serif';
    ctx.fillText('ID TIKET PELACAKAN', 84, 180);

    ctx.fillStyle = '#17324D';
    ctx.font = '800 34px ui-monospace, monospace';
    ctx.fillText(ticketData.ticketId, 84, 222);

    // Details
    ctx.fillStyle = '#17324D';
    ctx.font = '700 18px "Nunito Sans", sans-serif';
    ctx.fillText('Kategori: ' + ticketData.category, 64, 280);

    ctx.fillStyle = '#617482';
    ctx.font = '600 15px "Nunito Sans", sans-serif';
    const desc = (ticketData.description || '').slice(0, 62) + ((ticketData.description || '').length > 62 ? '...' : '');
    ctx.fillText('Ringkasan: ' + desc, 64, 312);
    ctx.fillText('Lokasi: ' + ticketData.coordinates, 64, 342);
    ctx.fillText('Waktu Lapor: ' + ticketData.createdAt, 64, 372);
    ctx.fillText('Status Awal: ' + ticketData.statusLabel, 64, 402);

    // QR Code on the right
    const qrDataUrl = await QRCode.toDataURL(ticketData.trackUrl, {
        width: 200,
        margin: 1,
        color: {
            dark: '#17324D',
            light: '#FFFFFF',
        },
    });

    const qrImg = new Image();
    await new Promise((resolve) => {
        qrImg.onload = resolve;
        qrImg.src = qrDataUrl;
    });
    ctx.drawImage(qrImg, 535, 152, 195, 195);

    ctx.fillStyle = '#617482';
    ctx.font = '700 13px "Nunito Sans", sans-serif';
    ctx.fillText('Scan QR untuk Lacak Status', 548, 372);

    const link = document.createElement('a');
    link.download = `Tiket-${ticketData.ticketId}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
};

Alpine.start();
