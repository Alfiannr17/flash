<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import { Head, usePage, useForm, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

// Form khusus untuk Update Nama
const formName = useForm({
    name: user.name,
    email: user.email,
});

// Form khusus untuk Update Email
const formEmail = useForm({
    name: user.name,
    email: user.email,
});

// Fungsi submit Nama
const submitName = () => {
    formName.email = formEmail.email; // Sinkronisasi data email terbaru
    formName.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            formEmail.name = formName.name; // Update form sebelah
        },
    });
};

// Fungsi submit Email
const submitEmail = () => {
    formEmail.name = formName.name; // Sinkronisasi data nama terbaru
    formEmail.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            formName.email = formEmail.email; // Update form sebelah
        },
    });
};
</script>

<template>
    <Head title="Pengaturan Profil" />

    <AuthenticatedLayout>
        <div class="relative space-y-6 animate-fade-in pb-10 min-h-screen text-slate-200">
            
            <!-- Efek Latar Belakang -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-3xl mx-auto space-y-6 relative z-10 mt-4 md:mt-8 lg:px-0">
                
                <!-- ==============================================
                     1. HEADER PROFILE (ICON, NAMA, EMAIL)
                =============================================== -->
                <div class=" flex flex-col items-center text-center gap-4 relative overflow-hidden">
                    <div class="w-24 h-24 bg-blue-600 rounded-full  flex items-center justify-center text-5xl font-black text-white uppercase border-4 border-slate-800 shrink-0 z-10">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="z-10">
                        <h1 class="text-2xl pb-2 md:text-3xl font-black text-white tracking-tighter capitalize mb-1">
                            {{ user.name }}
                        </h1>
                        <p class="text-sm font-bold text-blue-400 bg-blue-500/10 px-4 py-1.5 rounded-full inline-block border border-blue-500/20">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <!-- ==============================================
                     2. UBAH NAMA (FORM NAMA & BUTTON)
                =============================================== -->
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl relative overflow-hidden group">
                    
                    
                    <div class="mb-6 border-b border-slate-700/50 pb-4">
                        <h2 class="text-lg md:text-xl font-black text-white tracking-tight uppercase">Ubah Nama</h2>
                    </div>

                    <form @submit.prevent="submitName" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                            <input v-model="formName.name" type="text" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition" required />
                            <p v-if="formName.errors.name" class="text-xs text-rose-500 mt-1">{{ formName.errors.name }}</p>
                        </div>
                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" :disabled="formName.processing" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition uppercase text-xs tracking-widest disabled:opacity-50">
                                Simpan Nama
                            </button>
                            <Transition enter-active-class="transition ease-in-out duration-300" enter-from-class="opacity-0" leave-active-class="transition ease-in-out duration-300" leave-to-class="opacity-0">
                                <p v-if="formName.recentlySuccessful" class="text-xs font-bold text-emerald-400">Nama Tersimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- ==============================================
                     3. UBAH EMAIL (FORM EMAIL & BUTTON)
                =============================================== -->
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl relative overflow-hidden group">
                   
                    
                    <div class="mb-6 border-b border-slate-700/50 pb-4">
                        <h2 class="text-lg md:text-xl font-black text-white tracking-tight uppercase">Ubah Email</h2>
                    </div>

                    <form @submit.prevent="submitEmail" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Alamat Email</label>
                            <input v-model="formEmail.email" type="email" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-emerald-500 transition" required />
                            <p v-if="formEmail.errors.email" class="text-xs text-rose-500 mt-1">{{ formEmail.errors.email }}</p>
                        </div>

                        <!-- Info jika email harus divrifikasi -->
                        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="bg-amber-500/10 border border-amber-500/30 p-4 rounded-xl">
                            <p class="text-xs text-amber-400 font-medium leading-relaxed">
                                Alamat email Anda belum diverifikasi.
                                <Link :href="route('verification.send')" method="post" as="button" class="underline font-bold hover:text-amber-300 transition block mt-1">
                                    Klik di sini untuk mengirim ulang email verifikasi.
                                </Link>
                            </p>
                            <div v-show="status === 'verification-link-sent'" class="mt-2 font-black text-xs text-emerald-400 uppercase tracking-widest">
                                Tautan verifikasi telah dikirim!
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" :disabled="formEmail.processing" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition uppercase text-xs tracking-widest disabled:opacity-50">
                                Simpan Email
                            </button>
                            <Transition enter-active-class="transition ease-in-out duration-300" enter-from-class="opacity-0" leave-active-class="transition ease-in-out duration-300" leave-to-class="opacity-0">
                                <p v-if="formEmail.recentlySuccessful" class="text-xs font-bold text-emerald-400">Email Tersimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- ==============================================
                     4. UPDATE PASSWORD
                =============================================== -->
                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl relative overflow-hidden group">
                 

                    <!-- Component UpdatePasswordForm bawaan Laravel/Breeze -->
                    <UpdatePasswordForm class="max-w-full" />
                </div>

               <div class="pt-6 pb-8">
                    <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center justify-center gap-3 p-4 bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/30 rounded-2xl transition-all shadow-lg active:scale-95 group">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        <span class="font-black text-sm uppercase tracking-widest">Log Out</span>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>