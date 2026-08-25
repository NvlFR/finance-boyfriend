<script setup lang="ts">
import { Head, useForm, useHttp, usePoll } from '@inertiajs/vue3';
import {
    Navigation,
    Gauge,
    MapPin,
    Play,
    CheckCircle2,
    Bell,
    Share2,
    ShieldCheck,
    Clock,
    Car,
    Bike,
    Footprints,
    Bus,
    LocateFixed,
    Receipt,
    Sparkles,
    Compass,
} from '@lucide/vue';
import L from 'leaflet';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import CoupleHeader from '@/components/CoupleHeader.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { subscribe as pushSubscribe } from '@/routes/push';
import {
    complete as tripComplete,
    position as tripPosition,
    store as tripStore,
} from '@/routes/trips';
import type { User } from '@/types/auth';
import type { Trip, Wallet, Category } from '@/types/finance';
import 'leaflet/dist/leaflet.css';

const props = withDefaults(
    defineProps<{
        activeTrip?: Trip | null;
        trips?: {
            data: Trip[];
        };
        wallets?: Wallet[];
        categories?: Category[];
        partner?: User | null;
        auth: {
            user: User;
        };
        pushPublicKey?: string | null;
    }>(),
    {
        activeTrip: null,
        trips: () => ({ data: [] }),
        partner: null,
    },
);

const { openModalWithDefaults } = useTransactionModal();

const isStartModalOpen = ref(false);
const isSummaryModalOpen = ref(false);
const isAnyTripModalOpen = computed(
    () => isStartModalOpen.value || isSummaryModalOpen.value,
);

function closeActiveTripModal(): void {
    if (isStartModalOpen.value) {
        closeStartTripModal();

        return;
    }

    isSummaryModalOpen.value = false;
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isAnyTripModalOpen,
    closeActiveTripModal,
);
const summaryData = ref<{
    distance: number;
    maxSpeed: number;
    duration: string;
} | null>(null);
const copiedLink = ref(false);
const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let userMarker: L.Marker | null = null;
let watchId: number | null = null;
let markerAnimationFrame: number | null = null;
let freshnessTimer: number | null = null;
let wakeLock: WakeLockSentinel | null = null;

const currentLat = ref<number | null>(props.activeTrip?.current_lat || null);
const currentLng = ref<number | null>(props.activeTrip?.current_lng || null);
const currentSpeed = ref<number>(props.activeTrip?.speed || 0);
const maxSpeed = ref<number>(props.activeTrip?.max_speed || 0);
const totalDistance = ref<number>(props.activeTrip?.total_distance_km || 0);
const pushGranted = ref<boolean>(false);
const selectedMode = ref<'car' | 'bike' | 'walk' | 'bus'>('car');
const gpsStatus = ref<'idle' | 'locating' | 'tracking' | 'error'>('idle');
const gpsError = ref('');
const gpsAccuracy = ref<number | null>(null);
const clockNow = ref(Date.now());
const isTripOwner = computed(
    () => props.activeTrip?.user_id === props.auth.user.id,
);
const isViewingPartnerTrip = computed(() =>
    Boolean(props.activeTrip && !isTripOwner.value),
);
const lastPositionAgeSeconds = computed(() => {
    if (!props.activeTrip?.updated_at) {
        return null;
    }

    return Math.max(
        0,
        Math.round(
            (clockNow.value - new Date(props.activeTrip.updated_at).getTime()) /
                1000,
        ),
    );
});
const isPositionStale = computed(
    () =>
        isViewingPartnerTrip.value && (lastPositionAgeSeconds.value ?? 0) > 30,
);
const lastPositionLabel = computed(() => {
    const age = lastPositionAgeSeconds.value;

    if (age === null) {
        return 'menunggu posisi pertama';
    }

    if (age < 5) {
        return 'baru saja';
    }

    if (age < 60) {
        return `${age} detik lalu`;
    }

    return `${Math.floor(age / 60)} menit lalu`;
});

const startForm = useForm({
    title: 'Perjalanan Kencan / Bepergian',
    origin_name: 'Lokasi Saya Saat Ini',
    destination_name: '',
    origin_lat: null as number | null,
    origin_lng: null as number | null,
    destination_lat: null as number | null,
    destination_lng: null as number | null,
    notes: '',
});

const positionRequest = useHttp<
    { lat: number; lng: number; speed: number; accuracy: number | null },
    { status: string; trip: Trip }
>({
    lat: 0,
    lng: 0,
    speed: 0,
    accuracy: null,
});

const pushRequest = useHttp({
    endpoint: '',
    public_key: '',
    auth_token: '',
    content_encoding: 'aes128gcm',
});

// Speedometer gauge calculations
const maxGaugeSpeed = 120;
const speedPercentage = computed(() =>
    Math.min(100, Math.round((currentSpeed.value / maxGaugeSpeed) * 100)),
);

const speedStatusClass = computed(() => {
    if (currentSpeed.value === 0) {
        return {
            label: 'Diam / Parkir',
            text: 'text-zinc-500',
            bg: 'bg-zinc-100 dark:bg-zinc-800',
            stroke: '#9CA3AF',
        };
    }

    if (currentSpeed.value <= 30) {
        return {
            label: 'Santai',
            text: 'text-emerald-500',
            bg: 'bg-emerald-50 dark:bg-emerald-950/40',
            stroke: '#10B981',
        };
    }

    if (currentSpeed.value <= 70) {
        return {
            label: 'Kecepatan Normal',
            text: 'text-indigo-500',
            bg: 'bg-indigo-50 dark:bg-indigo-950/40',
            stroke: '#6366F1',
        };
    }

    if (currentSpeed.value <= 100) {
        return {
            label: 'Kencang',
            text: 'text-amber-500',
            bg: 'bg-amber-50 dark:bg-amber-950/40',
            stroke: '#F59E0B',
        };
    }

    return {
        label: 'Ngebut / Jalan Tol',
        text: 'text-rose-500',
        bg: 'bg-rose-50 dark:bg-rose-950/40',
        stroke: '#F43F5E',
    };
});

const modeOptions = [
    { key: 'car', label: 'Mobil', icon: Car, defaultTitle: 'OTW Naik Mobil' },
    { key: 'bike', label: 'Motor', icon: Bike, defaultTitle: 'OTW Naik Motor' },
    {
        key: 'walk',
        label: 'Jalan Kaki',
        icon: Footprints,
        defaultTitle: 'Jalan Kaki / Santai',
    },
    {
        key: 'bus',
        label: 'Umum / Bus',
        icon: Bus,
        defaultTitle: 'Naik Transportasi Umum',
    },
];

function selectTransportMode(
    key: 'car' | 'bike' | 'walk' | 'bus',
    defaultTitle: string,
) {
    selectedMode.value = key;
    startForm.title = defaultTitle;
}

function escapeHtml(value: string): string {
    return value.replace(
        /[&<>"']/g,
        (character) =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character] || character,
    );
}

function createRadarIcon(): L.DivIcon {
    const markerUser = props.activeTrip?.user || props.auth.user;
    const avatarLetter = escapeHtml(
        markerUser.nickname?.charAt(0) || markerUser.name.charAt(0),
    );

    return L.divIcon({
        className: 'custom-radar-marker',
        html: `<div class="relative flex items-center justify-center">
                <span class="animate-ping absolute inline-flex h-10 w-10 rounded-full bg-indigo-500 opacity-60"></span>
                <div class="relative flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-white font-black text-xs shadow-lg shadow-indigo-500/50 ring-4 ring-white dark:ring-zinc-900">
                    ${avatarLetter}
                </div>
               </div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });
}

function initMap() {
    if (!mapContainer.value || map) {
        return;
    }

    const initialLat = currentLat.value ?? -6.2088;
    const initialLng = currentLng.value ?? 106.8456;

    map = L.map(mapContainer.value, {
        zoomControl: false,
    }).setView([initialLat, initialLng], 15);

    // CartoDB Positron / Dark Matter Tiles
    const isDark = document.documentElement.classList.contains('dark');
    const tileUrl = isDark
        ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
        : 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

    L.tileLayer(tileUrl, {
        attribution: '&copy; CartoDB & OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    if (currentLat.value !== null && currentLng.value !== null) {
        userMarker = L.marker([currentLat.value, currentLng.value], {
            icon: createRadarIcon(),
        }).addTo(map);
    }

    // If destination exists, add flag marker
    if (
        props.activeTrip &&
        props.activeTrip.destination_lat !== null &&
        props.activeTrip.destination_lng !== null
    ) {
        const destIcon = L.divIcon({
            className: 'custom-dest-marker',
            html: `<div class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-500 text-white shadow-lg ring-4 ring-white dark:ring-zinc-900">
                    📍
                   </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
        L.marker(
            [
                props.activeTrip.destination_lat,
                props.activeTrip.destination_lng,
            ],
            { icon: destIcon },
        ).addTo(map);
    }
}

function recenterMap() {
    if (!map || currentLat.value === null || currentLng.value === null) {
        return;
    }

    map.flyTo([currentLat.value, currentLng.value], 16, {
        animate: true,
        duration: 1,
    });
}

function updateMarkerPosition(lat: number, lng: number) {
    if (!map) {
        return;
    }

    const destination = new L.LatLng(lat, lng);

    if (!userMarker) {
        userMarker = L.marker(destination, { icon: createRadarIcon() }).addTo(
            map,
        );
        map.flyTo(destination, 16, { animate: true, duration: 0.8 });

        return;
    }

    if (markerAnimationFrame !== null) {
        cancelAnimationFrame(markerAnimationFrame);
    }

    const origin = userMarker.getLatLng();
    const startedAt = performance.now();
    const duration = 1200;

    const animate = (timestamp: number) => {
        const progress = Math.min(1, (timestamp - startedAt) / duration);
        const easedProgress = 1 - (1 - progress) ** 3;
        const animatedPosition = new L.LatLng(
            origin.lat + (destination.lat - origin.lat) * easedProgress,
            origin.lng + (destination.lng - origin.lng) * easedProgress,
        );

        userMarker?.setLatLng(animatedPosition);

        if (progress < 1) {
            markerAnimationFrame = requestAnimationFrame(animate);
        } else {
            markerAnimationFrame = null;
        }
    };

    markerAnimationFrame = requestAnimationFrame(animate);
    map.panTo(destination, { animate: true, duration: 0.8 });
}

function startGeolocationTracking() {
    if (watchId !== null) {
        return;
    }

    if (!window.isSecureContext) {
        gpsStatus.value = 'error';
        gpsError.value =
            'Pelacakan GPS hanya bisa berjalan melalui koneksi HTTPS.';

        return;
    }

    if (!('geolocation' in navigator)) {
        gpsStatus.value = 'error';
        gpsError.value = 'Perangkat atau browser ini tidak mendukung GPS.';

        return;
    }

    gpsStatus.value = 'locating';
    gpsError.value = '';

    watchId = navigator.geolocation.watchPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const speedKmh =
                pos.coords.speed && pos.coords.speed > 0
                    ? Math.round(pos.coords.speed * 3.6)
                    : 0;

            gpsStatus.value = 'tracking';
            gpsError.value = '';
            gpsAccuracy.value = Math.round(pos.coords.accuracy);
            currentLat.value = lat;
            currentLng.value = lng;
            currentSpeed.value = speedKmh;

            startForm.origin_lat = lat;
            startForm.origin_lng = lng;

            if (speedKmh > maxSpeed.value) {
                maxSpeed.value = speedKmh;
            }

            updateMarkerPosition(lat, lng);

            if (
                props.activeTrip &&
                props.activeTrip.user_id === props.auth.user.id
            ) {
                sendPositionUpdate(lat, lng, speedKmh, pos.coords.accuracy);
            }
        },
        (err) => {
            gpsStatus.value = 'error';
            gpsError.value =
                err.code === err.PERMISSION_DENIED
                    ? 'Izin lokasi ditolak. Aktifkan izin lokasi browser agar pasangan bisa melihat perjalananmu.'
                    : 'GPS belum mendapatkan lokasi. Pastikan GPS aktif dan coba di area yang lebih terbuka.';
        },
        {
            enableHighAccuracy: true,
            maximumAge: 3000,
            timeout: 20000,
        },
    );
}

function stopGeolocationTracking() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }

    if (!props.activeTrip) {
        gpsStatus.value = 'idle';
    }
}

let lastSentTime = 0;
function sendPositionUpdate(
    lat: number,
    lng: number,
    speed: number,
    accuracy: number,
) {
    const now = Date.now();

    if (now - lastSentTime < 4000 || positionRequest.processing) {
        return;
    }

    if (!props.activeTrip || props.activeTrip.user_id !== props.auth.user.id) {
        return;
    }

    lastSentTime = now;
    positionRequest.lat = lat;
    positionRequest.lng = lng;
    positionRequest.speed = speed;
    positionRequest.accuracy = accuracy;
    positionRequest.post(tripPosition.url(props.activeTrip.id), {
        onSuccess: ({ trip }) => {
            totalDistance.value = trip.total_distance_km;
            maxSpeed.value = trip.max_speed;
        },
        onError: () => {
            gpsError.value =
                'Posisi belum berhasil dikirim. Sistem akan mencoba lagi otomatis.';
        },
    });
}

function urlBase64ToUint8Array(value: string): Uint8Array<ArrayBuffer> {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const output = new Uint8Array(rawData.length);

    for (let index = 0; index < rawData.length; index += 1) {
        output[index] = rawData.charCodeAt(index);
    }

    return output;
}

async function requestNotificationPermission() {
    if (
        !('Notification' in window) ||
        !('serviceWorker' in navigator) ||
        !props.pushPublicKey
    ) {
        return;
    }

    const permission = await Notification.requestPermission();

    if (permission === 'granted') {
        pushGranted.value = true;

        try {
            const reg = await navigator.serviceWorker.ready;
            let sub = await reg.pushManager.getSubscription();

            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(
                        props.pushPublicKey,
                    ),
                });
            }

            if (sub) {
                const serialized = sub.toJSON();
                pushRequest.endpoint = sub.endpoint;
                pushRequest.public_key = serialized.keys?.p256dh || '';
                pushRequest.auth_token = serialized.keys?.auth || '';
                pushRequest.post(pushSubscribe.url());
            }
        } catch (e) {
            console.error('Push subscription failed:', e);
        }
    }
}

function shareLiveTrip() {
    const url = window.location.href;
    const text = `Hai sayang! Gua lagi di jalan nih, pantau lokasi live gua di sini ya 🚗📍: ${url}`;

    if (navigator.share) {
        navigator
            .share({
                title: 'Live Radar Perjalanan',
                text: text,
                url: url,
            })
            .catch(() => {});
    } else {
        navigator.clipboard.writeText(text);
        copiedLink.value = true;
        setTimeout(() => {
            copiedLink.value = false;
        }, 2500);
    }
}

function openStartTripModal() {
    isStartModalOpen.value = true;
    startGeolocationTracking();
}

function closeStartTripModal() {
    isStartModalOpen.value = false;

    if (!props.activeTrip) {
        stopGeolocationTracking();
    }
}

async function requestScreenWakeLock() {
    if (
        wakeLock ||
        !('wakeLock' in navigator) ||
        document.visibilityState !== 'visible' ||
        !isTripOwner.value
    ) {
        return;
    }

    try {
        wakeLock = await navigator.wakeLock.request('screen');
    } catch {
        wakeLock = null;
    }
}

async function releaseScreenWakeLock() {
    if (wakeLock) {
        await wakeLock.release();
        wakeLock = null;
    }
}

function handleVisibilityChange() {
    if (
        document.visibilityState === 'visible' &&
        isTripOwner.value &&
        !wakeLock
    ) {
        requestScreenWakeLock();
    }
}

function handleStartTrip() {
    if (currentLat.value === null || currentLng.value === null) {
        gpsError.value =
            'Tunggu sampai titik GPS terkunci sebelum memulai perjalanan.';
        startGeolocationTracking();

        return;
    }

    startForm.origin_lat = currentLat.value;
    startForm.origin_lng = currentLng.value;
    startForm.post(tripStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isStartModalOpen.value = false;
            requestScreenWakeLock();
        },
    });
}

function handleCompleteTrip() {
    if (!props.activeTrip) {
        return;
    }

    const startTime = new Date(props.activeTrip.started_at).getTime();
    const durationMinutes = Math.max(
        1,
        Math.round((Date.now() - startTime) / 60000),
    );
    summaryData.value = {
        distance: props.activeTrip.total_distance_km || 0,
        maxSpeed: props.activeTrip.max_speed || currentSpeed.value,
        duration: `${durationMinutes} Menit`,
    };

    useForm({}).post(tripComplete.url(props.activeTrip.id), {
        preserveScroll: true,
        onSuccess: () => {
            isSummaryModalOpen.value = true;
            stopGeolocationTracking();
            releaseScreenWakeLock();
        },
    });
}

function openFuelExpenseModal() {
    isSummaryModalOpen.value = false;
    openModalWithDefaults({
        title: 'Bensin & Operasional Perjalanan',
        notes: `Pengeluaran bensin/tol untuk trip "${props.activeTrip?.title || 'Perjalanan'}"`,
        scope: 'shared',
    });
}

usePoll(
    3000,
    {
        only: ['activeTrip', 'trips'],
    },
    {
        keepAlive: true,
        mode: 'rest',
    },
);

watch(
    () => props.activeTrip,
    (trip) => {
        if (!trip) {
            currentSpeed.value = 0;
            releaseScreenWakeLock();

            return;
        }

        const ownedByCurrentUser = trip.user_id === props.auth.user.id;

        if (!ownedByCurrentUser || gpsStatus.value !== 'tracking') {
            currentLat.value = trip.current_lat;
            currentLng.value = trip.current_lng;
            currentSpeed.value = trip.speed;

            if (trip.current_lat !== null && trip.current_lng !== null) {
                updateMarkerPosition(trip.current_lat, trip.current_lng);
            }
        }

        maxSpeed.value = trip.max_speed;
        totalDistance.value = trip.total_distance_km;

        if (ownedByCurrentUser) {
            startGeolocationTracking();
            requestScreenWakeLock();
        } else {
            stopGeolocationTracking();
        }
    },
    { deep: true },
);

onMounted(() => {
    initMap();

    if (isTripOwner.value) {
        startGeolocationTracking();
        requestScreenWakeLock();
    }

    if ('Notification' in window && Notification.permission === 'granted') {
        pushGranted.value = true;
    }

    freshnessTimer = window.setInterval(() => {
        clockNow.value = Date.now();
    }, 1000);
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    stopGeolocationTracking();
    releaseScreenWakeLock();
    document.removeEventListener('visibilitychange', handleVisibilityChange);

    if (freshnessTimer !== null) {
        window.clearInterval(freshnessTimer);
    }

    if (markerAnimationFrame !== null) {
        cancelAnimationFrame(markerAnimationFrame);
    }

    if (map) {
        map.remove();
    }
});
</script>

<template>
    <div class="mx-auto max-w-lg space-y-4">
        <Head title="Live Trip & Radar Perjalanan - Couple Finance" />
        <CoupleHeader :user="auth.user" :partner="partner" />

        <div class="space-y-4">
            <!-- Header Title Bar -->
            <div
                class="flex flex-col items-stretch gap-3 min-[390px]:flex-row min-[390px]:items-center min-[390px]:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-lg font-black tracking-tight"
                    >
                        <Compass
                            class="animate-spin-slow h-5 w-5 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Radar Perjalanan</span>
                    </h1>
                    <p class="text-xs text-zinc-500">
                        Pelacak posisi & kecepatan live untuk kencan &
                        bepergian.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 min-[390px]:flex">
                    <button
                        type="button"
                        @click="shareLiveTrip"
                        class="flex min-h-11 items-center justify-center gap-1 rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-xs font-bold text-zinc-700 shadow-xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <Share2 class="h-3.5 w-3.5 text-indigo-500" />
                        <span>{{ copiedLink ? 'Tersalin!' : 'Bagikan' }}</span>
                    </button>

                    <button
                        v-if="!activeTrip"
                        type="button"
                        @click="openStartTripModal"
                        class="flex min-h-11 items-center justify-center gap-1.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/25 transition-all hover:opacity-95"
                    >
                        <Play class="h-4 w-4 fill-white" />
                        <span>Mulai Jalan</span>
                    </button>

                    <button
                        v-else-if="activeTrip.user_id === auth.user.id"
                        type="button"
                        @click="handleCompleteTrip"
                        class="flex min-h-11 items-center justify-center gap-1.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-500/25 transition-all hover:opacity-95"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <span>Sampai</span>
                    </button>
                </div>
            </div>

            <div
                v-if="activeTrip"
                class="rounded-2xl border p-3.5"
                :class="
                    isPositionStale
                        ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300'
                "
            >
                <div class="flex items-start gap-2.5">
                    <span class="relative mt-1 flex h-2.5 w-2.5 shrink-0">
                        <span
                            v-if="!isPositionStale"
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex h-2.5 w-2.5 rounded-full"
                            :class="
                                isPositionStale
                                    ? 'bg-amber-500'
                                    : 'bg-emerald-500'
                            "
                        />
                    </span>
                    <div>
                        <p class="text-xs font-bold">
                            {{
                                isViewingPartnerTrip
                                    ? `Lokasi ${activeTrip.user?.nickname || activeTrip.user?.name || 'pasangan'} sedang dipantau live`
                                    : 'Lokasimu sedang dibagikan ke pasangan'
                            }}
                        </p>
                        <p
                            class="mt-0.5 text-[11px] leading-relaxed opacity-80"
                        >
                            Posisi terakhir {{ lastPositionLabel }}.
                            <template v-if="isTripOwner"
                                >Biarkan halaman ini terbuka selama perjalanan
                                agar GPS tetap aktif.</template
                            >
                            <template v-else-if="isPositionStale"
                                >Sinyal lokasi terlambat; pasangan mungkin
                                mengunci layar atau kehilangan
                                internet.</template
                            >
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="gpsError && (isTripOwner || isStartModalOpen)"
                role="alert"
                class="rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs leading-relaxed font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            >
                {{ gpsError }}
            </div>

            <!-- DYNAMIC SPEEDOMETER GAUGE & ACTIVE TRIP STATUS -->
            <div
                class="space-y-4 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- Status Bar -->
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span
                                :class="[
                                    'absolute inline-flex h-full w-full animate-ping rounded-full opacity-75',
                                    activeTrip
                                        ? 'bg-emerald-400'
                                        : 'bg-zinc-400',
                                ]"
                            ></span>
                            <span
                                :class="[
                                    'relative inline-flex h-3 w-3 rounded-full',
                                    activeTrip
                                        ? 'bg-emerald-500'
                                        : 'bg-zinc-400',
                                ]"
                            ></span>
                        </span>
                        <span
                            class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            <template v-if="activeTrip">
                                {{
                                    activeTrip.user_id === auth.user.id
                                        ? `Kamu: "${activeTrip.title}"`
                                        : `${activeTrip.user?.nickname || activeTrip.user?.name} sedang OTW!`
                                }}
                            </template>
                            <template v-else>
                                Standby / Siap Bepergian
                            </template>
                        </span>
                    </div>

                    <span
                        :class="[
                            'rounded-full px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider uppercase',
                            speedStatusClass.bg,
                            speedStatusClass.text,
                        ]"
                    >
                        {{ speedStatusClass.label }}
                    </span>
                </div>

                <!-- Speedometer Visual Ring Gauge -->
                <div
                    class="relative flex flex-col items-center justify-center py-2"
                >
                    <div
                        class="relative flex h-36 w-36 items-center justify-center"
                    >
                        <svg
                            class="h-full w-full -rotate-90 transform"
                            viewBox="0 0 100 100"
                        >
                            <!-- Background Gauge Track -->
                            <circle
                                cx="50"
                                cy="50"
                                r="40"
                                class="stroke-zinc-100 dark:stroke-zinc-800"
                                stroke-width="8"
                                fill="transparent"
                            />
                            <!-- Progress Arc -->
                            <circle
                                cx="50"
                                cy="50"
                                r="40"
                                :stroke="speedStatusClass.stroke"
                                stroke-width="8"
                                stroke-linecap="round"
                                fill="transparent"
                                :stroke-dasharray="251.2"
                                :stroke-dashoffset="
                                    251.2 - (251.2 * speedPercentage) / 100
                                "
                                class="transition-all duration-700 ease-out"
                            />
                        </svg>

                        <!-- Speed Number Center Display -->
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center text-center"
                        >
                            <span
                                class="text-4xl font-black tracking-tight text-zinc-900 dark:text-zinc-100"
                            >
                                {{ currentSpeed }}
                            </span>
                            <span
                                class="-mt-1 text-[10px] font-extrabold tracking-widest text-zinc-400 uppercase"
                            >
                                KM/JAM
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-3 flex items-center gap-4 text-xs font-semibold text-zinc-500"
                    >
                        <span class="flex items-center gap-1">
                            <Gauge class="h-3.5 w-3.5 text-indigo-500" /> Max:
                            <strong class="text-zinc-800 dark:text-zinc-200"
                                >{{ maxSpeed }} km/h</strong
                            >
                        </span>
                        <span class="flex items-center gap-1">
                            <MapPin class="h-3.5 w-3.5 text-rose-500" /> Jarak:
                            <strong class="text-zinc-800 dark:text-zinc-200"
                                >{{ totalDistance.toFixed(1) }} km</strong
                            >
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mobile Notification Request Banner -->
            <div
                v-if="!pushGranted && pushPublicKey"
                class="flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50/80 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/30"
            >
                <div class="flex items-center gap-2.5">
                    <Bell
                        class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <div>
                        <h4
                            class="text-xs font-bold text-amber-900 dark:text-amber-200"
                        >
                            Notifikasi Layar HP Pasangan
                        </h4>
                        <p
                            class="text-[11px] text-amber-700 dark:text-amber-300"
                        >
                            Aktifkan agar pasangan dapat kabar saat kamu
                            berangkat.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="requestNotificationPermission"
                    class="min-h-11 shrink-0 rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-amber-700"
                >
                    Aktifkan
                </button>
            </div>

            <!-- LEAFLET INTERACTIVE MAP VIEW -->
            <div
                class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div ref="mapContainer" class="z-0 h-72 w-full"></div>

                <!-- Floating Map Controls -->
                <div class="absolute top-3 right-3 z-10 flex flex-col gap-2">
                    <button
                        type="button"
                        @click="recenterMap"
                        class="flex h-11 w-11 items-center justify-center rounded-2xl border border-zinc-200/50 bg-white/90 text-zinc-700 shadow-md backdrop-blur-md hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/90 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        aria-label="Fokus lokasi saya"
                        title="Fokus Lokasi Saya"
                    >
                        <LocateFixed
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                    </button>
                </div>

                <!-- Bottom GPS Overlay Info -->
                <div
                    class="absolute right-3 bottom-3 left-3 z-10 flex items-center justify-between rounded-2xl border border-zinc-200/50 bg-white/90 px-3.5 py-2.5 shadow-md backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/90"
                >
                    <div class="flex items-center gap-2">
                        <ShieldCheck
                            class="h-4 w-4"
                            :class="
                                currentLat !== null
                                    ? 'text-emerald-500'
                                    : 'text-amber-500'
                            "
                        />
                        <span
                            class="text-[11px] font-semibold text-zinc-700 dark:text-zinc-300"
                        >
                            {{
                                currentLat !== null
                                    ? `${currentLat.toFixed(4)}, ${currentLng?.toFixed(4)}`
                                    : isViewingPartnerTrip
                                      ? 'Menunggu posisi pasangan...'
                                      : 'Mengunci titik GPS...'
                            }}
                        </span>
                    </div>
                    <span class="text-[10px] font-bold text-zinc-400">
                        {{
                            gpsAccuracy !== null && isTripOwner
                                ? `±${gpsAccuracy} m`
                                : 'LIVE'
                        }}
                    </span>
                </div>
            </div>

            <!-- TRIP HISTORY LIST -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <h2
                        class="flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        <Clock class="h-4 w-4 text-zinc-400" />
                        <span>Riwayat Perjalanan Kencan</span>
                    </h2>
                </div>

                <div
                    class="divide-y divide-zinc-100 rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div
                        v-for="t in trips.data"
                        :key="t.id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                            >
                                <Navigation class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                                >
                                    {{ t.title }}
                                </h3>
                                <p
                                    class="text-[11px] text-zinc-500 dark:text-zinc-400"
                                >
                                    {{ t.user?.nickname || t.user?.name }} •
                                    {{
                                        new Date(
                                            t.started_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'short',
                                        })
                                    }}
                                </p>
                            </div>
                        </div>

                        <span
                            :class="[
                                'rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                t.status === 'completed'
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                                    : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
                            ]"
                        >
                            {{ t.status === 'completed' ? 'Selesai' : 'Aktif' }}
                        </span>
                    </div>

                    <div
                        v-if="trips.data.length === 0"
                        class="p-6 text-center text-xs text-zinc-500"
                    >
                        Belum ada riwayat perjalanan.
                    </div>
                </div>
            </div>
        </div>

        <!-- START TRIP MODAL -->
        <div
            v-if="isStartModalOpen"
            @click.self="closeStartTripModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                aria-labelledby="start-trip-title"
                tabindex="-1"
                @keydown="handleDialogKeydown"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-sm space-y-4 overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h3
                        id="start-trip-title"
                        class="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        <Play class="h-4 w-4 fill-indigo-600 text-indigo-600" />
                        <span>Mulai Perjalanan</span>
                    </h3>
                    <button
                        type="button"
                        @click="closeStartTripModal"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800"
                        aria-label="Tutup form perjalanan"
                    >
                        ✕
                    </button>
                </div>

                <!-- Mode Kendaraan Selector -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Pilih Moda Transportasi</label
                    >
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="mode in modeOptions"
                            :key="mode.key"
                            type="button"
                            @click="
                                selectTransportMode(
                                    mode.key as any,
                                    mode.defaultTitle,
                                )
                            "
                            :class="[
                                'flex flex-col items-center justify-center gap-1 rounded-2xl border p-2.5 text-xs font-bold transition-all',
                                selectedMode === mode.key
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-400'
                                    : 'border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400',
                            ]"
                        >
                            <component :is="mode.icon" class="h-5 w-5" />
                            <span class="text-[10px]">{{ mode.label }}</span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="handleStartTrip" class="space-y-3.5">
                    <div
                        class="rounded-2xl border p-3 text-[11px] leading-relaxed font-semibold"
                        :class="
                            gpsStatus === 'tracking'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300'
                        "
                    >
                        <template v-if="gpsStatus === 'tracking'">
                            GPS terkunci dengan akurasi sekitar
                            {{ gpsAccuracy }} meter. Perjalanan siap dimulai.
                        </template>
                        <template v-else>
                            Sedang mencari titik GPS. Izinkan akses lokasi saat
                            browser meminta izin.
                        </template>
                    </div>

                    <p
                        v-if="gpsError"
                        role="alert"
                        class="text-xs font-semibold text-rose-600 dark:text-rose-400"
                    >
                        {{ gpsError }}
                    </p>

                    <div>
                        <label
                            class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                            >Judul Kegiatan / OTW</label
                        >
                        <input
                            v-model="startForm.title"
                            type="text"
                            placeholder="Misal: OTW Rumah Pasangan / Resto Kencan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                            >Lokasi Tujuan (Opsional)</label
                        >
                        <input
                            v-model="startForm.destination_name"
                            type="text"
                            placeholder="Misal: Mall Senayan City / Resto Kencan"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeStartTripModal"
                            class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="
                                startForm.processing || gpsStatus !== 'tracking'
                            "
                            class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                gpsStatus === 'tracking'
                                    ? 'Mulai Sekarang 🚗'
                                    : 'Menunggu GPS...'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TRIP SUMMARY & FUEL EXPENSE MODAL -->
        <div
            v-if="isSummaryModalOpen"
            @click.self="isSummaryModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                aria-labelledby="trip-summary-title"
                tabindex="-1"
                @keydown="handleDialogKeydown"
                class="w-full max-w-sm space-y-4 rounded-3xl border border-zinc-200 bg-white p-6 text-center shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-emerald-500/10 text-emerald-500"
                >
                    <Sparkles class="h-7 w-7" />
                </div>

                <div>
                    <h3
                        id="trip-summary-title"
                        class="text-base font-black text-zinc-900 dark:text-zinc-100"
                    >
                        Sampai di Tujuan! 🎉
                    </h3>
                    <p class="mt-1 text-xs text-zinc-500">
                        Perjalanan kamu selesai dengan selamat.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 text-left">
                    <div class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/50">
                        <span class="text-[10px] font-bold text-zinc-400"
                            >Total Jarak</span
                        >
                        <p
                            class="text-sm font-black text-zinc-900 dark:text-zinc-100"
                        >
                            {{ summaryData?.distance.toFixed(1) || 0 }} km
                        </p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/50">
                        <span class="text-[10px] font-bold text-zinc-400"
                            >Kecepatan Maks</span
                        >
                        <p
                            class="text-sm font-black text-zinc-900 dark:text-zinc-100"
                        >
                            {{ summaryData?.maxSpeed || 0 }} km/h
                        </p>
                    </div>
                </div>

                <div class="space-y-2 pt-2">
                    <button
                        type="button"
                        @click="openFuelExpenseModal"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 py-3 text-xs font-bold text-white shadow-md shadow-emerald-500/20 hover:opacity-95"
                    >
                        <Receipt class="h-4 w-4" />
                        <span>Catat Bensin / Tol Ke Pasangan</span>
                    </button>

                    <button
                        type="button"
                        @click="isSummaryModalOpen = false"
                        class="w-full rounded-2xl border border-zinc-200 py-2.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300"
                    >
                        Selesai Tanpa Catat Pengeluaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.custom-radar-marker,
.custom-dest-marker {
    background: transparent;
    border: none;
}
@keyframes spinSlow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
.animate-spin-slow {
    animation: spinSlow 12s linear infinite;
}
</style>
