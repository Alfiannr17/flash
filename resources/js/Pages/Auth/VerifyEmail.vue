<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const page = usePage();
const currentUserEmail = computed(() => page.props.auth.user.email);

// Form untuk resend link
const form = useForm({});
const submit = () => {
    form.post(route('verification.send'));
};

// Form untuk ganti email
const isEditingEmail = ref(false);
const emailForm = useForm({
    email: currentUserEmail.value,
});

const updateEmail = () => {
    emailForm.post(route('email.update.unverified'), {
        onSuccess: () => {
            isEditingEmail.value = false;
        },
    });
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
const emailUpdated = computed(() => props.status === 'email-updated');
</script>

<template>
    <Head title="Email Verification" />

    <div class="relative min-h-screen w-full max-w-[100vw] overflow-x-hidden bg-slate-900 text-slate-200 flex flex-col justify-center items-center p-4 md:p-6 font-sans selection:bg-blue-500/30 selection:text-blue-200">
        
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] md:w-[500px] h-[300px] md:h-[500px] bg-blue-600/10 rounded-full blur-[80px] md:blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-[-10%] w-64 h-64 bg-emerald-600/5 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-md bg-slate-800/60 backdrop-blur-md p-6 sm:p-10 rounded-3xl md:rounded-[2rem] shadow-2xl border border-slate-700 animate-fade-in my-8 text-center">
            
            <div class="inline-block bg-blue-600 p-3 rounded-2xl shadow-lg shadow-blue-900/50 mb-6">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>

           <h2 class="text-2xl md:text-3xl font-black text-white mb-4">
    Verifikasi Email Anda
</h2>

<div class="mb-6 text-[13px] md:text-sm font-medium text-slate-400 leading-relaxed">
    Kami telah mengirim link verifikasi ke email Anda.<br> 
    Silakan cek inbox atau folder spam untuk melanjutkan aktivasi akun.
</div>

            <div v-if="verificationLinkSent" class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs md:text-sm font-bold animate-fade-in">
                Link verifikasi telah dikirim ke email Anda! Cek folder Inbox atau Spam.
            </div>
            
            <div v-if="emailUpdated" class="mb-6 p-4 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-xl text-xs md:text-sm font-bold animate-fade-in">
                Alamat email berhasil diperbarui! Link baru sudah dikirim.
            </div>

            <div class="mb-6 bg-slate-900/50 rounded-2xl border border-slate-700 p-5">
                <div v-if="!isEditingEmail">
                    <p class="text-[11px] font-black text-slate-500 tracking-widest mb-1">Email Anda:</p>
                    <p class="text-sm font-bold text-white mb-4">{{ currentUserEmail }}</p>
                    <button @click="isEditingEmail = true" class="text-[12px] font-bold text-blue-400 hover:text-blue-300 transition underline tracking-wide">
                        Ubah Alamat Email
                    </button>
                </div>

                <form v-else @submit.prevent="updateEmail" class="text-left space-y-4 animate-fade-in">
                    <div>
                        <label class="block text-[11px] font-black text-slate-400 mb-2 uppercase tracking-wider">Masukkan Email Baru</label>
                        <input type="email" v-model="emailForm.email" class="w-full px-4 py-3 bg-slate-800 border border-slate-600 rounded-xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-white outline-none transition text-sm shadow-inner" placeholder="nama@email-asli.com" required>
                        <p v-if="emailForm.errors.email" class="text-rose-400 text-xs mt-2 font-bold">{{ emailForm.errors.email }}</p>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" :disabled="emailForm.processing" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-3 rounded-xl transition text-[11px] uppercase tracking-widest shadow-lg shadow-blue-900/30">
                            {{ emailForm.processing ? 'Menyimpan...' : 'Simpan & Kirim' }}
                        </button>
                        <button type="button" @click="isEditingEmail = false" class="px-5 py-3 bg-slate-700 hover:bg-slate-600 text-white font-black rounded-xl transition text-[11px] uppercase tracking-widest">
                            Batal
                        </button>
                    </div>
                </form>
            </div>

            <form v-if="!isEditingEmail" @submit.prevent="submit" class="space-y-6">
                <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] uppercase tracking-widest text-sm disabled:opacity-50">
                    {{ form.processing ? 'MENGIRIM...' : 'KIRIM ULANG LINK' }}
                </button>
                <div class="pt-4 border-t border-slate-700/50">
                    <Link :href="route('logout')" method="post" as="button" class="text-xs md:text-sm font-bold text-slate-500 hover:text-rose-400 transition tracking-wider uppercase">
                        Keluar / Log Out
                    </Link>
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
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
</style>