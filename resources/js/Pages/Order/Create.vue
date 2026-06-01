<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ auth: Object, countries: Object, allServices: [Array, Object] });

const searchService = ref('');

const defaultServices = [
    {c:'wa', n:'WhatsApp'}, {c:'tg', n:'Telegram'}, 
    {c:'go', n:'Google/YouTube'}, {c:'ig', n:'Instagram'},
    {c:'fb', n:'Facebook'}, {c:'ka', n:'Shopee'}, {c:'tw', n:'Twitter'},
    {c:'oi', n:'Tinder'}, {c:'lf', n:'Tiktok'}, {c:'fr', n:'DANA'},
    {c:'ni', n:'Gojek'}, {c:'xh', n:'OVO'}, {c:'me', n:'LINE'},
    {c:'jg', n:'Grab'}, {c:'ds', n:'Discord'}, {c:'ju', n:'Indomaret'},
    {c:'xd', n:'Tokopedia'}, {c:'wb', n:'WeChat'}, {c:'fz', n:'KFC'},
    {c:'ry', n:'MCD'}, {c:'sr', n:'Starbucks'}, {c:'ip', n:'Burger King'},
    {c:'fh', n:'LALAMOVE'}, {c:'rl', n:'InDriver'}, {c:'am', n:'Amazon'},
    {c:'wx', n:'Apple ID'}, {c:'bcq', n:'Kopi Kenangan'}, {c:'vy', n:'META'},
    {c:'dr', n:'ChagtGPT'}, {c:'mm', n:'Microsoft'}, {c:'mb', n:'Yahoo!'},
];

const serviceDomains = {
    wa: "whatsapp.com",
    tg: "telegram.org",
    go: "google.com",
    ig: "instagram.com",
    fb: "facebook.com",
    tw: "x.com",
    oi: "tinder.com",
    lf: "tiktok.com",
    ka: "shopee.co.id",
    fr: "dana.id",
    xh: "ovo.com",
    ni: "gojek.com",
    jg: "grab.com",
    rl: "indriver.com",

    me: "line.me",
    ds: "discord.com",
    wb: "wechat.com",

    ju: "klikindomaret.com",
    xd: "tokopedia.com",
    bcq: "kopikenangan.com",
    sr: "starbucks.com",
    wx: "apple.com",
    vy: "meta.com",
    am: "amazon.com",
    fz: "kfc.com",
    ip: "burgerking.co.id",
    ry: "mcdonalds.com",
    fh: "lalamove.com",
    dr: "chatgpt.com",
    mm: "microsoft.com",
    mb: "yahoo.com"
};

// Ngerubah Object JSON Inertia balik jadi Array biar bisa di looping
const availableServices = computed(() => {
    const servicesArray = Array.isArray(props.allServices) 
        ? props.allServices 
        : Object.values(props.allServices || {});
        
    return servicesArray && servicesArray.length > 0 
        ? servicesArray 
        : defaultServices;
});

const filteredServices = computed(() => {
    const list = availableServices.value;
    
    if (!searchService.value) return list;
    
    return list.filter(s => 
        (s.n && s.n.toLowerCase().includes(searchService.value.toLowerCase())) ||
        (s.c && s.c.toLowerCase().includes(searchService.value.toLowerCase()))
    );
});

const getServiceIconUrl = (code) => {
    const domain =
        serviceDomains[code] || "google.com";

    return `https://www.google.com/s2/favicons?sz=64&domain=${domain}`;
};

// 🌟 PERUBAHAN: method pembayaran default dikunci ke 'balance'
const form = useForm({
    api_version: 'v1',
    country_id: '',
    service_code: '',
    selected_id: '',
    selected_price: '',
    payment_method: 'balance',
});

// -- DROPDOWN STATES --
const isCountryDropdownOpen = ref(false);
const isServiceDropdownOpen = ref(false);
const countryDropdownRef = ref(null);
const serviceDropdownRef = ref(null);

const selectedCountryName = ref('');
const selectedServiceName = ref('');
const priceOptions = ref([]);
const loadingPrices = ref(false);

// 🌟 TAMBAHAN: Computed property untuk menghitung sisa saldo
const sisaSaldo = computed(() => {
    const saldo = Number(props.auth?.user?.balance || 0);
    const harga = Number(form.selected_price || 0);
    return saldo - harga;
});

const toggleDropdown = (type) => {
    if (type === 'country') {
        isCountryDropdownOpen.value = !isCountryDropdownOpen.value;
        isServiceDropdownOpen.value = false;
    } else {
        isServiceDropdownOpen.value = !isServiceDropdownOpen.value;
        isCountryDropdownOpen.value = false;
        searchService.value = '';
    }
};

const getCountryFlagUrl = (name) => {
    const map = {
        "Indonesia": "id", "Russia": "ru", "Russian Federation": "ru",
        "Kazakhstan": "kz", "Ukraine": "ua", "United States": "us", 
        "China": "cn", "Philippines": "ph", "Myanmar": "mm", 
        "Malaysia": "my", "Kenya": "ke", "Tanzania": "tz", "Vietnam": "vn"
    };
    const code = map[name];
    return code ? `https://flagcdn.com/w40/${code.toLowerCase()}.png` : null;
};

const selectCountryCustom = (country) => {
    form.country_id = country.id;
    selectedCountryName.value = country.eng;
    isCountryDropdownOpen.value = false;
    fetchPrices();
};

const selectServiceCustom = (code, name) => {
    form.service_code = code;
    selectedServiceName.value = name;
    isServiceDropdownOpen.value = false;
    fetchPrices();
};

const fetchPrices = async () => {
    if (!form.country_id || !form.service_code) return;
    loadingPrices.value = true;
    priceOptions.value = [];
    form.selected_id = '';
    form.selected_price = '';

    try {
        const response = await axios.post(route('order.check-price'), {
            country_id: form.country_id, service_code: form.service_code, api_version: form.api_version
        });
        if (response.data.success) priceOptions.value = response.data.options;
    } catch (error) { console.error(error); } 
    finally { loadingPrices.value = false; }
};

const selectPrice = (opt) => {
    form.selected_id = opt.id;
    form.selected_price = opt.price;
};

const submit = () => form.post(route('order.store'), { preserveScroll: true });

const handleClickOutside = (e) => {
    if (countryDropdownRef.value && !countryDropdownRef.value.contains(e.target)) isCountryDropdownOpen.value = false;
    if (serviceDropdownRef.value && !serviceDropdownRef.value.contains(e.target)) isServiceDropdownOpen.value = false;
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

// -- FAQ STATE --
const openFaq = ref(null);
const toggleFaq = (idx) => {
    openFaq.value = openFaq.value === idx ? null : idx;
};

const faqs = [
    {
        q: 'Bagaimana cara order dan menerima OTP?',
        a: [
            'Pilih Jalur Server (V1 / V2 / V3 / V4) sesuai kebutuhan.',
            'Pilih Negara dan Layanan (misal: Shopee, WhatsApp, Telegram).',
            'Pilih harga dari opsi yang muncul, lalu klik "Bayar Pesanan".',
            'Nomor virtual akan langsung muncul di halaman "Pesanan Saya".',
            'Gunakan nomor tersebut di aplikasi/website tujuan untuk request OTP.',
            'Kode OTP akan masuk otomatis ke halaman pesanan dalam 1-3 menit.',
        ],
    },
    {
        q: 'OTP tidak masuk / gagal diterima, bagaimana?',
        a: [
            'Tunggu maksimal 3-5 menit setelah request OTP dari aplikasi tujuan.',
            'Pastikan kode negara saat input nomor sudah benar (cth: +62 untuk Indonesia).',
            'Jika sampai timeout OTP belum masuk, klik tombol "Batalkan" pada pesanan.',
            'Saldo akan otomatis dikembalikan 100% ke akun kamu.',
            'Coba order ulang dengan server lain (V2/V3/V4) untuk hasil lebih optimal.',
        ],
    },
    {
        q: 'Ketentuan refund / pengembalian saldo',
        a: [
            'Refund 100% otomatis bila OTP tidak masuk dan pesanan dibatalkan sebelum timeout.',
            'Refund TIDAK berlaku jika OTP sudah berhasil masuk ke sistem kami.',
            'Refund TIDAK berlaku jika nomor sudah digunakan untuk verifikasi di aplikasi tujuan.',
            'Saldo top up tidak dapat diuangkan kembali (non-refundable ke rekening).',
            'Kendala diluar ketentuan? Hubungi admin via menu kontak/Telegram support.',
        ],
    },
    {
        q: 'Berapa lama nomor virtual aktif?',
        a: [
            'Umumnya nomor aktif selama 15-20 menit setelah pesanan dibuat.',
            'Setelah OTP diterima, status pesanan otomatis "Selesai".',
            'Nomor yang sudah digunakan tidak dapat dipakai ulang untuk order berikutnya.',
        ],
    },
];

</script>

<template>
    <Head title="Order Layanan" />

    <AuthenticatedLayout>
        <div class="relative space-y-6 animate-fade-in pb-10 min-h-screen text-slate-200">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-[100vw] mx-auto space-y-6 relative z-10 mt-4 md:mt-8 lg:px-0">
                
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl flex flex-col gap-6 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] pointer-events-none group-hover:bg-blue-500/20 transition-colors duration-700"></div>

                    <div class="text-left relative z-10">
                        <p class="text-xs md:text-sm text-slate-300 font-medium leading-relaxed max-w-2xl">
                            Pilih layanan dan dapatkan nomor virtual OTP secara instan. Kami menyediakan 4 jalur server berbeda untuk memastikan kamu selalu mendapatkan nomor yang aktif.
                        </p>
                    </div>

                    <div class="h-px w-full bg-gradient-to-r from-slate-700/0 via-slate-700 to-slate-700/0 relative z-10"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
                        
                        <div class="flex items-center gap-3 md:gap-4 bg-blue-900/10 p-4 rounded-2xl border border-blue-500/20 relative">
                            <div class="w-10 h-10 rounded-full bg-blue-500/20 border border-blue-400/50 flex items-center justify-center text-blue-400 font-black shrink-0 shadow-[0_0_10px_rgba(59,130,246,0.2)]">V1</div>
                            <div>
                                <h4 class="text-[11px] md:text-xs font-black text-blue-400 uppercase tracking-widest mb-0.5">Server Utama</h4>
                                <p class="text-[10px] text-slate-400 font-medium">Stok melimpah & harga standar.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 md:gap-4 bg-emerald-900/10 p-4 rounded-2xl border border-emerald-500/20 relative">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-400/50 flex items-center justify-center text-emerald-400 font-black shrink-0 shadow-[0_0_10px_rgba(16,185,129,0.2)]">V2</div>
                            <div>
                                <h4 class="text-[11px] md:text-xs font-black text-emerald-400 uppercase tracking-widest mb-0.5">Server Premium</h4>
                                <p class="text-[10px] text-slate-400 font-medium">Tingkat sukses OTP paling tinggi & Harga variatif.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 md:gap-4 bg-amber-900/10 p-4 rounded-2xl border border-amber-500/20 relative">
                            <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-400/50 flex items-center justify-center text-amber-400 font-black shrink-0 shadow-[0_0_10px_rgba(245,158,11,0.2)]">V3</div>
                            <div>
                                <h4 class="text-[11px] md:text-xs font-black text-amber-400 uppercase tracking-widest mb-0.5">Server Plus</h4>
                                <p class="text-[10px] text-slate-400 font-medium">Alternatif optimal dengan performa tinggi & terjaga.</p>
                            </div>
                        </div>

                        <!-- 🌟 TAMBAHAN: Kartu Server V4 -->
                        <div class="flex items-center gap-3 md:gap-4 bg-purple-900/10 p-4 rounded-2xl border border-purple-500/20 relative">
                            <div class="w-10 h-10 rounded-full bg-purple-500/20 border border-purple-400/50 flex items-center justify-center text-purple-400 font-black shrink-0 shadow-[0_0_10px_rgba(168,85,247,0.2)]">V4</div>
                            <div>
                                <h4 class="text-[11px] md:text-xs font-black text-purple-400 uppercase tracking-widest mb-0.5">Server Prime</h4>
                                <p class="text-[10px] text-slate-400 font-medium">Server terbaru dengan kecepatan tinggi, kualitas nomor terbaik dan performa unggulan.</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div v-if="$page.props.errors.error" class="bg-rose-500/10 border border-rose-500/50 text-rose-400 p-4 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-bold text-sm uppercase tracking-widest">Pesanan Gagal Diproses!</p>
                        <p class="text-[10px] md:text-xs mt-0.5 opacity-80">{{ $page.props.errors.error }} Silakan pilih produk/server lain.</p>
                    </div>
                </div>

                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <div class="space-y-6">
                                
                                <div>
                                    <label class="block text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Jalur Server
                                    </label>
                                    <!-- 🌟 PERUBAHAN: Menjadi grid-cols-4 dan tambah array 'v4' -->
                                    <div class="grid grid-cols-4 gap-2 md:gap-3">
                                        <button v-for="v in ['v1', 'v2', 'v3', 'v4']" :key="v" type="button"
                                            @click="form.api_version = v; fetchPrices()"
                                            :class="['py-3 md:py-4 px-2 rounded-2xl font-black transition-all border-2 text-xs md:text-sm', 
                                            form.api_version === v ? 'border-blue-500 bg-blue-500/10 text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.2)] scale-[1.02]' : 'border-slate-700/80 text-slate-500 hover:border-slate-600 hover:bg-slate-800 bg-slate-900/50']">
                                            {{ v.toUpperCase() }}
                                        </button>
                                    </div>
                                </div>

                                <div class="relative" ref="countryDropdownRef">
                                    <label class="block text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Pilih Negara
                                    </label>
                                    <button type="button" @click="toggleDropdown('country')"
                                        class="w-full border-2 border-slate-700/80 rounded-2xl p-4 focus:border-blue-500 transition flex items-center justify-between bg-slate-900/50 text-left hover:bg-slate-800">
                                        <div class="flex items-center gap-3">
                                            <template v-if="form.country_id">
                                                <img v-if="getCountryFlagUrl(selectedCountryName)" :src="getCountryFlagUrl(selectedCountryName)" class="w-6 h-auto rounded-sm shadow-sm" />
                                                <span v-else class="text-xl">🌐</span>
                                                <span class="font-bold text-white text-sm">{{ selectedCountryName }}</span>
                                            </template>
                                            <span v-else class="text-slate-500 font-bold text-sm">Pilih Negara...</span>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{'rotate-180': isCountryDropdownOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>

                                    <div v-if="isCountryDropdownOpen" class="absolute z-[60] mt-2 w-full bg-slate-800 border border-slate-700 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.5)] max-h-64 overflow-y-auto custom-scrollbar-dark">
                                        <div v-for="country in countries" :key="country.id" @click="selectCountryCustom(country)"
                                            class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-700 transition border-b border-slate-700/50 last:border-b-0">
                                            <img v-if="getCountryFlagUrl(country.eng)" :src="getCountryFlagUrl(country.eng)" class="w-5 h-auto rounded-xs" />
                                            <span v-else class="text-lg">🌐</span>
                                            <span class="font-bold text-slate-200 text-sm">{{ country.eng }}</span>
                                            <div v-if="form.country_id === country.id" class="ml-auto w-2 h-2 bg-blue-500 rounded-full shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="relative" ref="serviceDropdownRef">
                                    <label class="block text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Pilih Layanan
                                    </label>
                                    <button type="button" @click="toggleDropdown('service')"
                                        class="w-full border-2 border-slate-700/80 rounded-2xl p-4 focus:border-blue-500 transition flex items-center justify-between bg-slate-900/50 text-left hover:bg-slate-800">
                                        <div class="flex items-center gap-3">
                                            <template v-if="form.service_code">
                                                <img :src="getServiceIconUrl(form.service_code)" class="w-6 h-6 object-contain" @error="(e) => e.target.src='https://img.icons8.com/fluency/48/services.png'" />
                                                <span class="font-bold text-white text-sm">{{ selectedServiceName }}</span>
                                            </template>
                                            <span v-else class="text-slate-500 font-bold text-sm">Pilih Layanan...</span>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-500 transition-transform" :class="{'rotate-180': isServiceDropdownOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>

                                    <div v-if="isServiceDropdownOpen" class="absolute z-[50] mt-2 w-full bg-slate-800 border border-slate-700 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.5)] overflow-hidden animate-fade-in">
                                        <div class="p-3 bg-slate-900/80 border-b border-slate-700 backdrop-blur-sm">
                                            <input 
                                                v-model="searchService" 
                                                type="text" 
                                                placeholder="Cari layanan (ex: Telegram...)" 
                                                class="w-full bg-slate-800/80 border border-slate-600 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500 transition shadow-inner placeholder-slate-500"
                                            />
                                        </div>
                                        
                                        <div class="max-h-64 overflow-y-auto custom-scrollbar-dark">
                                            <div v-for="s in filteredServices" :key="s.c" @click="selectServiceCustom(s.c, s.n)"
                                                class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-700 transition border-b border-slate-700/50 last:border-b-0">
                                                <img :src="getServiceIconUrl(s.c)" class="w-6 h-6 object-contain" @error="(e) => e.target.src='https://img.icons8.com/fluency/48/services.png'" />
                                                <span class="font-bold text-slate-200 text-sm">{{ s.n }}</span>
                                                <div v-if="form.service_code === s.c" class="ml-auto w-2 h-2 bg-blue-500 rounded-full shadow-[0_0_8px_rgba(59,130,246,0.8)]"></div>
                                            </div>
                                            <div v-if="filteredServices.length === 0" class="p-8 text-center text-slate-500 text-xs font-bold uppercase tracking-widest">
                                                Layanan tidak ditemukan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6 flex flex-col h-full">
                                <div class="flex-1 bg-slate-900/30 p-5 rounded-3xl border border-slate-700/50">
                                    <label class="block text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-[0.1em] mb-3">Opsi Harga Tersedia</label>
                                    
                                    <div v-if="loadingPrices" class="text-center py-10 bg-slate-900/50 rounded-2xl border border-slate-700">
                                        <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                                        <p class="text-[10px] md:text-xs text-slate-500 mt-3 font-black uppercase tracking-widest">Mengecek Server...</p>
                                    </div>
                                    
                                    <div v-else-if="priceOptions.length > 0" class="space-y-3 max-h-[220px] overflow-y-auto pr-2 custom-scrollbar-dark">
                                        <div v-for="opt in priceOptions" :key="opt.id" @click="selectPrice(opt)"
                                            :class="['p-4 rounded-2xl border-2 cursor-pointer transition-all flex justify-between items-center group',
                                            form.selected_id === opt.id ? 'border-blue-500 bg-blue-500/10 shadow-[0_0_15px_rgba(59,130,246,0.2)] transform scale-[1.02]' : 'border-slate-700/80 hover:border-blue-500/50 bg-slate-900/50']">
                                            <div>
                                                <span class="block font-black text-white text-xs md:text-sm tracking-tight">{{ opt.name }}</span>
                                                <span class="inline-block text-[9px] font-black bg-blue-500/20 px-2 py-0.5 rounded-md border border-blue-500/30 text-blue-400 mt-1.5 uppercase">Stok: {{ opt.count }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="block font-black text-blue-400 text-base md:text-lg">Rp {{ opt.price.toLocaleString('id-ID') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="text-center py-16 bg-slate-900/50 rounded-2xl border border-dashed border-slate-700 text-slate-500 text-xs font-bold px-10 leading-relaxed uppercase tracking-widest">
                                        Pilih negara & layanan untuk melihat harga
                                    </div>
                                </div>

                                <div v-if="form.selected_price" class="pt-4 animate-fade-in mt-auto border-t border-slate-700/50">
                                    <div class="bg-slate-900/50 rounded-2xl border border-slate-700/80 p-5 space-y-3 mb-6">
                                        
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-medium text-slate-400">Saldo Saat Ini</span>
                                            <span class="text-sm font-bold text-slate-200">Rp {{ Number($page.props.auth?.user?.balance || 0).toLocaleString('id-ID') }}</span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-medium text-slate-400">Total Pembayaran</span>
                                            <span class="text-sm font-black text-rose-400">- Rp {{ Number(form.selected_price).toLocaleString('id-ID') }}</span>
                                        </div>

                                        <div class="h-px w-full bg-slate-700/50"></div>

                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-white uppercase tracking-widest">Sisa Saldo</span>
                                            <span :class="['text-base font-black', sisaSaldo < 0 ? 'text-rose-500' : 'text-emerald-400']">
                                                Rp {{ sisaSaldo.toLocaleString('id-ID') }}
                                            </span>
                                        </div>
                                        
                                        

                                        <div v-if="sisaSaldo < 0" class="text-[10px] text-rose-500 font-bold mt-2 text-center flex items-center justify-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Saldo tidak cukup. Silakan Top Up terlebih dahulu.
                                        </div>
                                    </div>

                                    <div class="ml-1 mb-4 flex items-start">
                                        <div class="text-sm">
                                            <label for="terms" class="font-medium text-slate-600 cursor-pointer">
                                                Dengan melakukan pesanan, Anda menyetujui
                                                <Link href="/terms" target="_blank" class="text-blue-600 hover:text-blue-500 hover:underline">Syarat & Ketentuan</Link> 
                                                serta 
                                                <Link href="/privacy" target="_blank" class="text-blue-600 hover:text-blue-500 hover:underline">Kebijakan Privasi</Link>.
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" :disabled="form.processing || !form.selected_id || sisaSaldo < 0"
                                        class="w-full py-4 md:py-5 text-xs md:text-sm rounded-2xl bg-blue-600 text-white font-black tracking-[0.1em] shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] hover:bg-blue-500 disabled:bg-slate-700 disabled:text-slate-500 disabled:shadow-none transition-all active:scale-95 uppercase">
                                        {{ form.processing ? 'MEMPROSES...' : 'Bayar Pesanan' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
            <!-- 🌟 HIGHLIGHT TIPS PEMBELI NOMOR SHOPEE -->
<div class="bg-gradient-to-br from-blue-900/20 via-slate-800/60 to-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-blue-500/30 shadow-2xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] pointer-events-none"></div>

    <div class="flex items-start gap-4 relative z-10">
        
        <div class="flex-1 min-w-0">
            <span class="inline-block text-[9px] md:text-[10px] font-black bg-blue-500/20 px-2 py-1 rounded-md border border-blue-500/30 text-blue-400 mb-2 uppercase tracking-widest">Trik Penting</span>
            <h3 class="text-base md:text-lg font-black text-white mb-3 tracking-tight">Tips untuk Pembeli Nomor Shopee</h3>
            <p class="text-xs md:text-sm text-slate-300 leading-relaxed mb-4">
                Sebelum nomor digunakan untuk daftar akun Shopee, <span class="text-red-500 font-bold">cek terlebih dahulu</span> melalui menu <span class="font-bold text-white">"Lupa Password"</span> di aplikasi Shopee. Caranya masukkan nomor yang sudah dibeli, lalu perhatikan hasilnya:
            </p>

            <div class="space-y-2.5">
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-[11px] md:text-xs text-slate-200 leading-relaxed">
                        Jika muncul keterangan <span class="font-bold text-emerald-300">"Coba lagi nanti"</span>, biasanya nomor masih aman dan belum terdaftar di Shopee. Aman digunakan.
                    </p>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <p class="text-[11px] md:text-xs text-slate-200 leading-relaxed">
                        Jika nomor terdeteksi <span class="font-bold text-rose-300">sudah terdaftar di Shopee</span>, jangan lanjut digunakan dan langsung <span class="font-bold text-white">batalkan pesanan</span> di website. Saldo akan otomatis kembali.
                    </p>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 italic mt-4">Terima kasih sudah mengikuti panduan ini agar pesanan kamu lancar.</p>
        </div>
    </div>
</div>

<!-- 🌟 FAQ ACCORDION -->
<div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl relative overflow-hidden">
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] pointer-events-none"></div>

    <div class="flex items-center gap-3 mb-6 relative z-10">
        <div class="w-10 h-10 rounded-2xl bg-blue-500/20 border border-blue-400/50 flex items-center justify-center text-blue-400 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-base md:text-lg font-black text-white tracking-tight">Pertanyaan yang Sering Ditanyakan</h3>
            <p class="text-[10px] md:text-xs text-slate-400 font-medium">Klik salah satu untuk melihat jawaban lengkapnya</p>
        </div>
    </div>

    <div class="space-y-3 relative z-10">
        <div v-for="(faq, idx) in faqs" :key="idx"
            :class="['rounded-2xl border-2 transition-all overflow-hidden bg-slate-900/50',
                openFaq === idx ? 'border-blue-500/50 shadow-[0_0_15px_rgba(59,130,246,0.15)]' : 'border-slate-700/80 hover:border-slate-600']">
            <button type="button" @click="toggleFaq(idx)"
                class="w-full p-4 md:p-5 flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <span :class="['w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-[10px] font-black border transition-colors',
                        openFaq === idx ? 'bg-blue-500/20 border-blue-400/50 text-blue-400' : 'bg-slate-800 border-slate-700 text-slate-500']">
                        {{ String(idx + 1).padStart(2, '0') }}
                    </span>
                    <span class="font-black text-xs md:text-sm text-white tracking-tight">{{ faq.q }}</span>
                </div>
                <svg class="w-5 h-5 text-slate-500 transition-transform shrink-0"
                    :class="{'rotate-180 text-blue-400': openFaq === idx}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div v-if="openFaq === idx" class="px-4 md:px-5 pb-4 md:pb-5 animate-fade-in">
                <div class="pl-10 border-l-2 border-blue-500/30 ml-3">
                    <ul class="space-y-2">
                        <li v-for="(line, i) in faq.a" :key="i" class="flex items-start gap-2.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 shrink-0 shadow-[0_0_6px_rgba(59,130,246,0.6)]"></span>
                            <span class="text-[11px] md:text-xs text-slate-300 leading-relaxed">{{ line }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 pt-5 border-t border-slate-700/50 flex items-center justify-between gap-3 flex-wrap relative z-10">
        <p class="text-[10px] md:text-xs text-slate-400 font-medium">
            Masih ada pertanyaan lain? Hubungi admin kami.
        </p>
        <Link href="/terms" target="_blank"
            class="text-[10px] md:text-xs font-black  text-blue-400 hover:text-blue-300 transition flex items-center gap-1">
            Syarat & Ketentuan
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </Link>
    </div>
</div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar-dark::-webkit-scrollbar { width: 4px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
</style>