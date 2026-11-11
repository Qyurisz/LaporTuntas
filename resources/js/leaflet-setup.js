import L from 'leaflet';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

// Fix default marker icon paths when bundled by Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: markerIconUrl,
    iconRetinaUrl: markerIcon2xUrl,
    shadowUrl: markerShadowUrl,
});

// Expose Leaflet globally BEFORE leaflet.markercluster is imported
window.L = L;
globalThis.L = L;

/**
 * Create real street, satellite hybrid, and clean base layers for Leaflet maps.
 */
window.createRealMapLayerSet = function () {
    const streetOsm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    });

    // Automatic fallback tile URL if primary OSM server encounters ISP/network rate limits
    streetOsm.on('tileerror', function (err) {
        if (err && err.tile && !err.tile.dataset.fallbackApplied) {
            err.tile.dataset.fallbackApplied = '1';
            const coords = err.coords;
            const sub = ['a', 'b', 'c'][Math.abs(coords.x + coords.y) % 3];
            err.tile.src = `https://${sub}.tile.openstreetmap.fr/hot/${coords.z}/${coords.x}/${coords.y}.png`;
        }
    });

    const satelliteImagery = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri',
        }
    );

    const satelliteLabels = L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
            pane: 'overlayPane',
        }
    );

    const satelliteHybrid = L.layerGroup([satelliteImagery, satelliteLabels]);

    return {
        jalan: streetOsm,
        satelit: satelliteHybrid,
    };
};

/**
 * Initialize a static/detail report map reliably regardless of module script load order.
 */
window.initStaticReportMap = function (elementId, lat, lng, popupTitle = '', statusColor = '#16A6D8') {
    const container = document.getElementById(elementId);
    if (!container || container.dataset.mapInitialized === '1') {
        return null;
    }
    container.dataset.mapInitialized = '1';

    const layers = window.createRealMapLayerSet();
    const map = L.map(container, {
        layers: [layers.jalan],
        scrollWheelZoom: false,
    }).setView([lat, lng], 17);

    L.control.layers(
        {
            'Peta Jalan (OSM)': layers.jalan,
            'Citra Satelit': layers.satelit,
        },
        {},
        { position: 'topright', collapsed: true }
    ).addTo(map);

    const pinIcon = L.divIcon({
        className: '',
        html: `<div class="lt-map-pin" style="background-color: ${statusColor};">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </div>`,
        iconSize: [46, 46],
        iconAnchor: [23, 23],
        popupAnchor: [0, -20],
    });

    const marker = L.marker([lat, lng], { icon: pinIcon }).addTo(map);
    if (popupTitle) {
        marker.bindPopup(popupTitle);
    }

    setTimeout(() => map.invalidateSize(), 150);
    if (typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => map.invalidateSize());
        ro.observe(container);
    }

    return map;
};

/**
 * Reverse-geocode coordinates into concise Indonesian area & street names via Nominatim.
 * Fails gracefully with null if offline or rate-limited.
 */
window.reverseGeocodeLocation = async function (lat, lng) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 4500);

    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1&accept-language=id`;
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        const addr = data.address || {};

        const subArea =
            addr.suburb ||
            addr.village ||
            addr.neighbourhood ||
            addr.quarter ||
            addr.hamlet ||
            addr.town ||
            '';
        const cityArea =
            addr.city_district ||
            addr.municipality ||
            addr.city ||
            addr.county ||
            addr.state ||
            '';

        const shortArea = [subArea, cityArea].filter(Boolean).slice(0, 2).join(', ');

        const roadPart = addr.road || addr.pedestrian || addr.Amenity || addr.building || '';
        const detailAddress = [roadPart, subArea, cityArea].filter(Boolean).join(', ') || data.display_name || '';

        return {
            shortArea: shortArea || null,
            detailAddress: detailAddress ? detailAddress.slice(0, 140) : null,
        };
    } catch {
        return null;
    } finally {
        clearTimeout(timer);
    }
};

export default L;

