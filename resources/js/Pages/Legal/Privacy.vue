<script setup>
import { Head } from '@inertiajs/vue3';

// Fungsi untuk kembali ke halaman sebelumnya secara dinamis
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/'; // Fallback ke beranda kalau dibuka di tab baru
    }
};
</script>

<template>
    <Head title="Kebijakan Privasi" />

    <div class="relative min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-slate-900 text-slate-200 flex flex-col items-center py-10 px-4 md:px-6 font-sans selection:bg-blue-500/30 selection:text-blue-200">
        
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-blue-600/10 rounded-full blur-[80px] md:blur-[120px] pointer-events-none z-0"></div>
        <div class="fixed bottom-0 right-[-10%] w-64 h-64 bg-emerald-600/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

        <div class="relative z-10 w-full max-w-3xl bg-slate-800/60 backdrop-blur-md p-6 sm:p-10 rounded-3xl md:rounded-[2rem] shadow-2xl border border-slate-700 animate-fade-in my-4">
            
            <div class="mb-10 text-center md:text-left">
                <h1 class="text-3xl md:text-4xl font-black text-white mb-2 tracking-tight">Kebijakan Privasi</h1>
                <p class="text-slate-400 text-sm font-medium">Pembaruan Terakhir: Mei 2026</p>
            </div>

            <div class="space-y-8 text-slate-300 leading-relaxed text-sm md:text-base">
                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">1</span>
                        Pengumpulan Data
                    </h3>
                    <p class="pl-11">Kami mengumpulkan informasi dasar saat Anda mendaftar, seperti Alamat Email, Nama, dan rekam jejak alamat IP (Internet Protocol) demi keamanan akun Anda dan pencegahan penipuan.</p>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">2</span>
                        Penggunaan Data
                    </h3>
                    <p class="pl-11">Data yang kami kumpulkan hanya digunakan untuk keperluan internal operasional Flash OTP, seperti pengiriman invoice transaksi, pemulihan akun (reset password), dan notifikasi sistem terkait keamanan.</p>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">3</span>
                        Keamanan & Kerahasiaan
                    </h3>
                    <p class="pl-11">Flash OTP berkomitmen menjaga kerahasiaan data Anda. Kami <strong class="text-white bg-slate-700/50 px-2 py-0.5 rounded border border-slate-600">TIDAK AKAN PERNAH</strong> menjual, menyewakan, atau membagikan data email dan transaksi Anda kepada pihak ketiga untuk tujuan marketing atau iklan.</p>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">4</span>
                        Cookie
                    </h3>
                    <p class="pl-11">Kami menggunakan cookies secara terbatas hanya untuk menjaga sesi login Anda agar tetap aman dan terhubung dengan sistem kami. Anda dapat menghapus cookie melalui pengaturan browser Anda kapan saja.</p>
                </section>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-700/50">
                <button @click="goBack" type="button" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 px-8 rounded-2xl transition-all border border-slate-600 text-sm md:text-md active:scale-95 mx-auto">
                    
                    Kembali ke Halaman Sebelumnya
                </button>
            </div>
        </div>

        <div class="mt-4 mb-8 text-slate-600 text-[12px] font-black relative z-10">
            &copy; {{ new Date().getFullYear() }} FlashOTP. All rights reserved.
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>