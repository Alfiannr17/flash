<script setup>
import { Link, Head } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const popularServices = [
    { name: 'WhatsApp', icon: 'https://img.icons8.com/color/48/whatsapp--v1.png', price: 'Mulai Rp 1.500' },
    { name: 'Telegram', icon: 'https://img.icons8.com/color/48/telegram-app--v1.png', price: 'Mulai Rp 2.000' },
    { name: 'Google', icon: 'https://img.icons8.com/color/48/google-logo.png', price: 'Mulai Rp 1.200' },
    { name: 'Instagram', icon: 'https://img.icons8.com/fluency/48/instagram-new.png', price: 'Mulai Rp 800' },
];

const features = [
    { title: 'Server Multi-Jalur (V1-V3)', desc: 'Koneksi langsung ke berbagai provider global untuk menjamin stok nomor selalu tersedia.', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01' },
    { title: 'Deposit Otomatis 24/7', desc: 'Dukung pembayaran QRIS real-time. Saldo masuk detik itu juga tanpa perlu konfirmasi admin.', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { title: 'Sistem Anti-Saldo Nyangkut', desc: 'Jika OTP tidak masuk dalam waktu yang ditentukan, saldo dikembalikan 100% secara otomatis.', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Komisi Referral 3%', desc: 'Ajak teman mendaftar menggunakan kodemu dan dapatkan passive income dari setiap top up mereka.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }
];

const steps = [
    { num: '01', title: 'Daftar Akun', desc: 'Buat akun dalam 10 detik tanpa ribet.' },
    { num: '02', title: 'Isi Saldo', desc: 'Top up via QRIS otomatis masuk.' },
    { num: '03', title: 'Pilih Layanan', desc: 'Pilih aplikasi dan negara yang diinginkan.' },
    { num: '04', title: 'Terima OTP', desc: 'Nomor muncul, tunggu SMS masuk, selesai!' }
];
</script>

<template>
    <Head title="Selamat Datang di FlashOTP" />
    
    <div class="relative min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-slate-900 text-slate-200 font-sans selection:bg-blue-500/30 selection:text-blue-200 scroll-smooth">
        
        <nav class="fixed top-0 left-0 w-full z-50 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 transition-all">
            <div class="max-w-7xl mx-auto px-4 md:px-6 h-20 flex items-center justify-between">
                <div class="flex items-center gap-2 md:gap-3">
                    <div class="bg-blue-600 p-1.5 md:p-2 rounded-xl shadow-lg shadow-blue-900/50">
                        <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-lg md:text-xl font-black text-white ">FLASH OTP</span>
                </div>
                
                <div class="hidden lg:flex gap-8 text-sm font-bold text-slate-400 uppercase tracking-widest">
                    <a href="#features" class="hover:text-white transition">Fitur</a>
                    <a href="#services" class="hover:text-white transition">Layanan</a>
                    <a href="#how-it-works" class="hover:text-white transition">Cara Kerja</a>
                </div>

                <div class="flex gap-2 md:gap-3">
                    <Link :href="route('login')" class="px-4 py-2.5 text-sm md:text-md font-black text-white hover:text-blue-400 transition ">Masuk</Link>
                    <Link :href="route('register')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm md:text-md font-black rounded-xl shadow-lg shadow-blue-900/50 transition">Daftar</Link>
                </div>
            </div>
        </nav>

        <section class="relative pt-32 md:pt-40 pb-16 md:pb-20 px-4 md:px-6 flex flex-col items-center text-center min-h-[90vh] justify-center border-b border-slate-800">
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 md:left-1/4 md:translate-x-0 w-64 md:w-96 h-64 md:h-96 bg-blue-600/10 rounded-full blur-[80px] md:blur-[120px] pointer-events-none"></div>
            
            <div class="relative z-10 w-full max-w-4xl mx-auto animate-fade-in-up">
                <div class="inline-block px-4 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-[10px] md:text-xs font-black uppercase tracking-widest mb-6">
                    #1 Provider OTP Indonesia
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-white mb-6 tracking-tighter leading-tight break-words">
                    VERIFIKASI DENGAN <br class="hidden sm:block" /> <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-indigo-500">NOMOR VIRTUAL</span>
                </h1>
                <p class="text-base sm:text-lg md:text-xl text-slate-400 mb-10 max-w-2xl mx-auto font-medium leading-relaxed px-2">
                    Terima SMS OTP untuk WhatsApp, Telegram, Instagram, dan 800+ layanan lainnya secara instan. Murah, cepat, dan 100% otomatis 24 jam.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center w-full px-4 sm:px-0">
                    <Link :href="route('register')" class="w-full sm:w-auto px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black shadow-[0_0_30px_rgba(37,99,235,0.4)] transition-all hover:scale-105 uppercase tracking-widest text-xs md:text-sm">
                        Mulai Sekarang
                    </Link>
                    <a href="#services" class="w-full sm:w-auto px-8 py-4 bg-slate-800/80 backdrop-blur-sm hover:bg-slate-700 text-white border border-slate-700 rounded-2xl font-black transition-all uppercase tracking-widest text-xs md:text-sm">
                        Lihat Layanan
                    </a>
                </div>
            </div>

            <div class="relative z-10 mt-16 md:mt-20 bg-slate-800/40 backdrop-blur-md py-6 md:py-8 px-4 md:px-6 rounded-3xl md:rounded-[2rem] border border-slate-700 shadow-2xl w-full max-w-4xl flex flex-wrap justify-center sm:justify-around items-center gap-6 sm:gap-8 animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="flex flex-col items-center w-[40%] sm:w-auto">
                    <span class="text-3xl md:text-4xl font-black text-white">800<span class="text-blue-500">+</span></span>
                    <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1 md:mt-2">Layanan App</span>
                </div>
                <div class="hidden sm:block w-px h-12 bg-slate-700/50"></div>
                <div class="flex flex-col items-center w-[40%] sm:w-auto">
                    <span class="text-3xl md:text-4xl font-black text-white">100<span class="text-emerald-500">+</span></span>
                    <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1 md:mt-2">Negara Server</span>
                </div>
                <div class="hidden sm:block w-px h-12 bg-slate-700/50"></div>
                <div class="flex flex-col items-center w-full sm:w-auto mt-2 sm:mt-0 pt-4 sm:pt-0 border-t border-slate-700/50 sm:border-0">
                    <span class="text-3xl md:text-4xl font-black text-white">99<span class="text-rose-500">%</span></span>
                    <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1 md:mt-2">Success Rate</span>
                </div>
            </div>
        </section>

        <section id="features" class="py-20 md:py-24 px-4 md:px-6 border-b border-slate-800 relative">
            <div class="absolute left-[-10%] top-1/2 w-64 h-64 bg-indigo-600/5 rounded-full blur-[100px] pointer-events-none"></div>
            
            <div class="max-w-7xl mx-auto relative z-10">
                <div class="text-center mb-12 md:mb-16">
                    <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter uppercase mb-4">Kenapa Flash OTP?</h2>
                    <p class="text-sm md:text-base text-slate-400 font-medium max-w-xl mx-auto">Kami membangun infrastruktur yang solid untuk memastikan setiap OTP masuk tepat waktu tanpa drama.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="feat in features" :key="feat.title" class="bg-slate-800/40 backdrop-blur-sm p-6 md:p-8 rounded-3xl border border-slate-700/80 hover:border-blue-500/50 transition-colors group shadow-lg">
                        <div class="w-12 h-12 md:w-14 md:h-14 bg-slate-900 border border-slate-700 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600/20 group-hover:border-blue-500/50 transition-colors">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feat.icon"></path></svg>
                        </div>
                        <h3 class="text-lg md:text-xl font-black text-white mb-3">{{ feat.title }}</h3>
                        <p class="text-xs md:text-sm text-slate-400 leading-relaxed font-medium">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="py-20 md:py-24 px-4 md:px-6 border-b border-slate-800 relative">
            <div class="absolute right-[-10%] top-1/2 w-64 h-64 bg-blue-600/5 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 md:mb-12 gap-4 md:gap-6">
                    <div>
                        <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter uppercase mb-2 md:mb-4">Layanan Terpopuler</h2>
                        <p class="text-sm md:text-base text-slate-400 font-medium">Aplikasi yang paling sering digunakan pengguna.</p>
                    </div>
                    <Link :href="route('register')" class="text-xs md:text-sm font-black text-blue-400 hover:text-blue-300 uppercase tracking-widest flex items-center gap-2">
                        Lihat Layanan Lainnya
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <div v-for="app in popularServices" :key="app.name" class="bg-slate-800/40 backdrop-blur-sm border border-slate-700/80 p-5 md:p-6 rounded-2xl md:rounded-3xl flex items-center gap-4 hover:border-blue-500/30 transition transform hover:-translate-y-1 shadow-lg">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-slate-900 border border-slate-700/50 rounded-xl md:rounded-2xl flex items-center justify-center shrink-0 shadow-inner">
                            <img :src="app.icon" :alt="app.name" class="w-7 h-7 md:w-8 md:h-8 object-contain">
                        </div>
                        <div>
                            <h4 class="text-base md:text-lg font-black text-white">{{ app.name }}</h4>
                            <p class="text-[10px] md:text-xs font-bold text-emerald-400 mt-1 bg-emerald-500/10 inline-block px-2 py-0.5 rounded border border-emerald-500/20">{{ app.price }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="py-20 md:py-24 px-4 md:px-6 border-b border-slate-800 relative">
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-[800px] h-64 bg-blue-600/5 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto text-center relative z-10">
                <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter uppercase mb-12 md:mb-16">Cara Kerja Simpel</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 relative">
                    <div class="hidden md:block absolute top-12 left-[10%] right-[10%] h-0.5 bg-gradient-to-r from-blue-600/0 via-blue-500/30 to-blue-600/0"></div>
                    
                    <div v-for="(step, index) in steps" :key="step.num" class="relative z-10 flex flex-col items-center">
                        <div class="w-20 h-20 md:w-24 md:h-24 bg-slate-800 border border-slate-600 rounded-3xl md:rounded-[2rem] flex items-center justify-center mb-4 md:mb-6 shadow-xl relative overflow-hidden group hover:border-blue-500 transition-colors">
                            <div class="absolute inset-0 bg-blue-600 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                            <span class="text-2xl md:text-3xl font-black text-white drop-shadow-md">{{ step.num }}</span>
                        </div>
                        <h3 class="text-lg md:text-xl font-black text-white mb-2">{{ step.title }}</h3>
                        <p class="text-xs md:text-sm text-slate-400 font-medium max-w-[200px]">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 md:py-24 px-4 md:px-6 relative overflow-hidden border-b border-slate-800">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl h-64 bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>
            
            <div class="relative z-10 w-full max-w-3xl mx-auto text-center bg-slate-800/30 backdrop-blur-sm p-8 md:p-16 rounded-3xl md:rounded-[3rem] border border-slate-700 shadow-2xl">
                
                <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter uppercase mb-4 md:mb-6 leading-tight">Siap Memulai?</h2>
                <p class="text-sm md:text-base text-slate-400 mb-8 md:mb-10 font-medium max-w-xl mx-auto leading-relaxed px-2">Bergabunglah dengan ribuan pengguna lainnya. Buat akun sekarang dan rasakan kemudahan verifikasi OTP tanpa batas.</p>
                <Link :href="route('register')" class="inline-block w-full sm:w-auto px-8 md:px-10 py-4 md:py-5 bg-blue-600 text-white rounded-2xl font-black shadow-[0_0_30px_rgba(37,99,235,0.4)] hover:bg-blue-500 hover:scale-105 transition transform uppercase tracking-widest text-xs md:text-sm">
                    Buat Akun Gratis Sekarang
                </Link>
            </div>
        </section>

        <footer class="pt-10 pb-8 relative z-10">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center text-center sm:text-left gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600/20 p-1.5 rounded-lg border border-blue-500/30">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-sm md:text-base font-black text-slate-300 tracking-widest uppercase">FLASH OTP</span>
                </div>
                <div class="text-slate-600 text-[9px] md:text-[10px] font-black uppercase tracking-widest">
                    &copy; {{ new Date().getFullYear() }} FlashOTP. All rights reserved.
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.custom-scrollbar-dark::-webkit-scrollbar { width: 4px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
</style>