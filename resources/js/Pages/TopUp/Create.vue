<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    balance: [Number, String],
    errors: Object,
    hasReceivedBonus: Boolean, 
});

const form = useForm({
    amount: ''
});

// Fitur Pilihan Nominal Cepat
const presetNominals = [5000, 10000, 15000, 25000, 50000, 100000];

const setNominal = (value) => {
    form.amount = value;
};

const submit = () => {
    form.post(route('topup.store'));
};
</script>

<template>
    <Head title="Top Up Saldo" />

    <AuthenticatedLayout>
        <div class="relative space-y-6 animate-fade-in pb-10 min-h-screen text-slate-200">
            
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="w-full mx-auto space-y-6 relative z-10 mt-4 md:mt-8 md:px-0">
                
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-6 md:p-8 rounded-3xl border border-blue-400/30 shadow-[0_15px_40px_rgba(37,99,235,0.25)] flex justify-between items-center text-white relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-700 pointer-events-none">
                        <svg class="w-40 h-40 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                    </div>
                    <div class="relative z-10">
                        <span class="block font-bold text-[10px] md:text-xs uppercase tracking-[0.2em] text-blue-200 mb-1.5">Saldo utama</span>
                        <span class="block font-black text-3xl md:text-4xl tracking-tighter drop-shadow-lg">Rp {{ Number(balance || 0).toLocaleString('id-ID') }}</span>
                    </div>
                </div>

                <div v-if="errors.api" class="bg-rose-500/10 text-rose-400 p-4 rounded-2xl border border-rose-500/20 font-bold text-xs md:text-sm text-center flex items-center justify-center gap-2 animate-fade-in">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    {{ errors.api }}
                </div>

                <div class="bg-slate-800/60 backdrop-blur-md p-6 md:p-8 rounded-3xl border border-slate-700/80 shadow-2xl relative overflow-hidden">
                    
                    <div class="flex flex-col items-start gap-4 mb-8 relative z-10">
                        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-full">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[10px] md:text-xs font-black text-emerald-400 uppercase tracking-widest">QRIS Auto-Checking Aktif</span>
                        </div>
                        <p class="text-xs md:text-sm text-slate-400 font-medium leading-relaxed max-w-2xl">
                            Pembayaran diproses secara instan hitungan detik tanpa perlu konfirmasi manual ke admin.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5 relative z-10">
                        
                        <div>
                            <label class="block text-[10px] md:text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Pilih Nominal Instan</label>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 md:gap-4">
                                <button 
                                    v-for="nominal in presetNominals" 
                                    :key="nominal" 
                                    type="button"
                                    @click="setNominal(nominal)"
                                    :class="[
                                        'py-4 px-2 md:px-4 rounded-2xl font-black transition-all border-2 text-center flex flex-col items-center justify-center',
                                        Number(form.amount) === nominal 
                                            ? 'border-blue-500 bg-blue-500/10 text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.3)] scale-[1.02]' 
                                            : 'border-slate-700/80 bg-slate-900/50 text-slate-400 hover:border-blue-500/50 hover:bg-slate-800'
                                    ]"
                                >
                                    <span class="text-sm md:text-base tracking-wider">Rp {{ nominal.toLocaleString('id-ID') }}</span>
                                    
                                    <div v-if="!hasReceivedBonus && nominal >= 50000" class="mt-1">
                                        <span class="text-[9px] md:text-[10px] text-emerald-400 font-bold uppercase tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20 shadow-sm">
                                            + Bonus {{ nominal === 50000 ? '5K' : '10K' }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-700/50">
                            <label class="block text-[10px] md:text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Nominal Custom</label>
                            
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                    <span class="text-slate-500 font-black text-lg group-focus-within:text-blue-500 transition-colors">Rp</span>
                                </div>
                                <input 
                                    type="number" 
                                    v-model="form.amount" 
                                    :class="[
                                        'w-full pl-16 pr-6 py-4 bg-slate-900/50 border-2 rounded-2xl focus:ring-0 transition-all font-black text-white text-xl outline-none placeholder-slate-600 shadow-inner',
                                        form.amount && form.amount < 5000 ? 'border-rose-500 focus:border-rose-500' : 'border-slate-700 focus:border-blue-500'
                                    ]"
                                    placeholder="Masukkan nominal..."
                                    required
                                    :disabled="form.processing"
                                >
                            </div>

                            <div class=" flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                                <span :class="[
                                        'text-[10px] md:text-xs font-medium transition-colors flex items-center gap-1',
                                        form.amount && form.amount < 5000 ? 'text-rose-400' : 'text-slate-500'
                                    ]">
                                    <svg v-if="form.amount && form.amount < 5000" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                     {{ form.amount && form.amount < 5000 ? 'Minimal top up Rp 5.000' : '' }}
                                </span>
                                
                                <div v-if="!hasReceivedBonus && (form.amount == 50000 || form.amount == 100000)" class="animate-fade-in mt-3">
                                    <span class="text-emerald-400 text-[10px] md:text-xs font-black bg-emerald-500/10 px-2 py-1 rounded-md border border-emerald-500/20 uppercase tracking-widest">
                                        Bonus Klaim: Rp {{ form.amount == 50000 ? '5.000' : '10.000' }}
                                    </span>
                                </div>
                            </div>
                            
                        </div>

                        <div class="ml-1 flex items-start">
            <div class="text-sm">
                <label for="terms" class="font-medium text-slate-600 cursor-pointer">
                    Dengan melanjutkan Top Up, Anda menyetujui
                    <Link href="/terms" target="_blank" class="text-blue-600 hover:text-blue-500 hover:underline">Syarat & Ketentuan</Link> 
                    serta 
                    <Link href="/privacy" target="_blank" class="text-blue-600 hover:text-blue-500 hover:underline">Kebijakan Privasi</Link>.
                </label>
            </div>
        </div>


                        <button 
                            type="submit" 
                            :disabled="form.processing || !form.amount || form.amount < 5000"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 md:py-5 rounded-2xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] uppercase tracking-widest text-xs md:text-sm disabled:opacity-50 disabled:shadow-none disabled:bg-slate-700 disabled:text-slate-400 active:scale-95 flex items-center justify-center gap-2 mt-0"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'MEMPROSES...' : 'LANJUT KE PEMBAYARAN QRIS' }}</span>
                        </button>
                        
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }

input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>