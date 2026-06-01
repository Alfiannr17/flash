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
    <Head title="Syarat & Ketentuan" />

    <div class="relative min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-slate-900 text-slate-200 flex flex-col items-center py-10 px-4 md:px-6 font-sans selection:bg-blue-500/30 selection:text-blue-200">
        
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-blue-600/10 rounded-full blur-[80px] md:blur-[120px] pointer-events-none z-0"></div>
        <div class="fixed bottom-0 right-[-10%] w-64 h-64 bg-emerald-600/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

        <div class="relative z-10 w-full max-w-3xl bg-slate-800/60 backdrop-blur-md p-6 sm:p-10 rounded-3xl md:rounded-[2rem] shadow-2xl border border-slate-700 animate-fade-in my-4">
            
            <div class="mb-10 text-center md:text-left">
                <h1 class="text-3xl md:text-4xl font-black text-white mb-2 tracking-tight">Syarat & Ketentuan Layanan</h1>
                <p class="text-slate-400 text-sm font-medium">Pembaruan Terakhir: Mei 2026</p>
            </div>

            <div class="space-y-8 text-slate-300 leading-relaxed text-sm md:text-base">
                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">1</span>
                        Pendahuluan
                    </h3>
                    <p class="pl-11">Dengan mendaftar dan menggunakan layanan Flash OTP (flashotp.shop), Anda secara otomatis menyetujui semua Syarat dan Ketentuan yang berlaku. Jika Anda tidak setuju, harap tidak menggunakan layanan kami.</p>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">2</span>
                        Akun dan Keamanan
                    </h3>
                    <ul class="list-disc pl-16 space-y-2">
                        <li>Pengguna wajib menjaga kerahasiaan password dan data akun. Segala kerugian akibat kelalaian menjaga keamanan akun adalah tanggung jawab pengguna sepenuhnya.</li>
                        <li>Flash OTP berhak memblokir atau menghapus akun yang terindikasi melakukan kecurangan, spamming, phishing, atau tindakan melanggar hukum tanpa pengembalian saldo.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">3</span>
                        Saldo dan Transaksi (Top Up)
                    </h3>
                    <ul class="list-disc pl-16 space-y-2">
                        <li>Semua transaksi pengisian saldo (Top Up) yang telah berstatus "Sukses" bersifat <strong class="text-white bg-slate-700/50 px-2 py-0.5 rounded border border-slate-600">FINAL</strong> dan tidak dapat dibatalkan atau di-refund (dikembalikan) menjadi uang tunai ke rekening bank/e-wallet pengguna dengan alasan apapun.</li>
                        <li>Saldo hanya dapat digunakan untuk layanan di dalam platform Flash OTP.</li>
                        <li>Kesalahan transfer akibat tidak membaca instruksi pembayaran bukan tanggung jawab Flash OTP.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">4</span>
                        Penggunaan Layanan (OTP & API)
                    </h3>
                    <ul class="list-disc pl-16 space-y-2">
                        <li>Pengguna dilarang keras menggunakan nomor virtual atau layanan Flash OTP untuk tujuan ilegal, penipuan, pinjaman online ilegal, atau kejahatan siber lainnya.</li>
                        <li>Tingkat keberhasilan OTP bergantung pada operator jaringan masing-masing negara dan platform tujuan.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg md:text-xl font-bold text-white mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-sm font-black shadow-inner">5</span>
                        Perubahan Ketentuan
                    </h3>
                    <p class="pl-11">Flash OTP berhak mengubah sebagian atau seluruh isi Syarat & Ketentuan ini sewaktu-waktu tanpa pemberitahuan terlebih dahulu.</p>
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