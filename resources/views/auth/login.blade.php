<!DOCTYPE html>
<html lang="id" class="dark" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · TaxCore</title><link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="stylesheet" href="{{ asset('vendor/reference/fontawesome/css/all.min.css') }}">
@vite('resources/css/html-workspace.css')
</head><body>
    <div id="loginModalOverlay" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-xl select-none no-print" style="background-image: radial-gradient(at 20% 20%, rgba(56, 189, 248, 0.18) 0px, transparent 50%), radial-gradient(at 80% 80%, rgba(99, 102, 241, 0.22) 0px, transparent 50%);">
        <div class="w-full max-w-md rounded-3xl bg-slate-900/95 border border-cyan-500/30 shadow-2xl shadow-cyan-950/60 overflow-hidden text-slate-100">
            <!-- Top Accent Bar -->
            <div class="h-1.5 w-full bg-gradient-to-r from-cyan-400 via-blue-600 to-indigo-600"></div>

            <div class="p-6 sm:p-8 space-y-6">
                <!-- Brand Identity -->
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-400 via-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-cyan-500/30 ring-1 ring-cyan-300/40 shrink-0">
                        <i class="fa-solid fa-microchip text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-extrabold tracking-tight text-white">TAXCORE</h2>
                            <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase rounded-md bg-cyan-500/15 text-cyan-300 border border-cyan-400/30">QUANTUM 21</span>
                        </div>
                        <p class="text-xs text-slate-400">Sistem Payroll & Kalkulator Pajak PP 58/2023</p>
                    </div>
                </div>

                <!-- Error Banner -->
                <div id="loginErrorAlert" role="alert" class="{{ $errors->any() ? '' : 'hidden' }} p-3 rounded-xl bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                    <span id="loginErrorText">{{ $errors->first() }}</span>
                </div>

                <!-- Input Fields (No <form> tag to prevent iframe reload issues) -->
                <form method="post" action="{{ route('login.store') }}" class="space-y-4">@csrf
                    <div>
                        <label for="loginUsernameInput" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Username</label>
                        <div class="relative">
                            <i class="fa-regular fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="loginUsernameInput" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus
                                placeholder="Masukkan username"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 text-sm font-semibold text-white outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label for="loginPasswordInput" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="password" id="loginPasswordInput" name="password" autocomplete="current-password" required
                                placeholder="Masukkan password"
                                class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 text-sm font-mono text-white outline-none transition">
                            <button type="button" onclick="const input=document.getElementById('loginPasswordInput');const visible=input.type==='password';input.type=visible?'text':'password';this.setAttribute('aria-pressed',String(visible));this.setAttribute('aria-label',visible?'Sembunyikan password':'Tampilkan password');this.firstElementChild.className=visible?'fa-regular fa-eye-slash text-xs':'fa-regular fa-eye text-xs'" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan / Sembunyikan Password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-cyan-300 flex items-center justify-center cursor-pointer">
                                <i id="loginPasswordEyeIcon" class="fa-regular fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white font-extrabold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-blue-600/35 cursor-pointer transition">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Masuk ke Aplikasi</span>
                    </button>
                </form>

                <p class="pt-4 border-t border-slate-800 font-mono text-[10px] text-cyan-400">PP 58/2023 & PMK 168</p>
            </div>
        </div>
    </div>


</body></html>
