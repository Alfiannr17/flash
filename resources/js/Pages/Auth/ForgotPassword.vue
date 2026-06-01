<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="relative min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-slate-900 text-slate-200 flex flex-col justify-center items-center p-4 md:p-6 font-sans selection:bg-blue-500/30 selection:text-blue-200">
        
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-blue-600/10 rounded-full blur-[80px] md:blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-[-10%] w-64 h-64 bg-emerald-600/5 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-md bg-slate-800/60 backdrop-blur-md p-6 sm:p-10 rounded-3xl md:rounded-[2rem] shadow-2xl border border-slate-700 animate-fade-in my-8">
            
            <div class="text-center mb-6">
    <div class="inline-block bg-blue-600 p-3 rounded-2xl shadow-lg shadow-blue-900/50 mb-4">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"></path>
        </svg>
    </div>
    <h2 class="text-2xl md:text-3xl font-black text-white mb-2">Lupa Password?</h2>
    <p class="text-slate-400 text-[13px] md:text-sm font-medium leading-relaxed">
        Masukkan alamat email Anda dan kami akan mengirimkan link untuk reset password akun Anda.
    </p>
</div>

            <div v-if="status" class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-sm font-bold text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-[12px] md:text-xs font-black text-slate-400 mb-2">Alamat Email</label>
                    <input type="email" v-model="form.email" class="w-full px-5 py-4 bg-slate-900/80 border border-slate-700 rounded-2xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white outline-none transition placeholder-slate-600 shadow-inner text-sm" placeholder="nama@email.com" required autofocus>
                    <p v-if="form.errors.email" class="text-rose-400 text-xs mt-2 font-bold">{{ form.errors.email }}</p>
                </div>

                <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] uppercase tracking-widest text-sm mt-6 disabled:opacity-50 active:scale-95">
                    {{ form.processing ? 'MENGIRIM LINK...' : 'KIRIM LINK RESET' }}
                </button>
                
                <div class="text-center mt-6 pt-6 border-t border-slate-700/50">
                    <Link :href="route('login')" class="text-xs md:text-sm font-bold text-blue-400 hover:text-blue-300 transition">Kembali ke halaman Login</Link>
                </div>
            </form>
        </div>

        <div class="mt-2 text-slate-600 text-[12px] font-black relative z-10">
            &copy; {{ new Date().getFullYear() }} FlashOTP. All rights reserved.
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>