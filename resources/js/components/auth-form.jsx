import { useState } from 'react';
import { ArrowRight, Cpu, Eye, EyeOff, LockKeyhole, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

export function AuthForm({ mode, action, csrf, errors = [], values = {} }) {
    const setup = mode === 'setup';
    const [visible, setVisible] = useState(false);
    return <Card className="auth-card p-0 sm:p-0"><CardHeader className="space-y-5 p-6 pt-8 sm:p-8"><div className="flex items-center gap-3.5"><span className="brand-mark size-12 rounded-2xl"><Cpu className="size-5" /></span><div><div className="flex items-center gap-2"><strong className="text-lg font-extrabold tracking-tight text-white">TAXCORE</strong><span className="quantum-badge">QUANTUM 21</span></div><p className="mt-1 text-[11px] text-slate-400">Sistem Payroll & Kalkulator Pajak PP 58/2023</p></div></div><div className="space-y-2"><CardTitle className="text-lg font-extrabold tracking-tight">{setup ? 'Buat Super Admin pertama' : 'Masuk ke workspace'}</CardTitle><CardDescription className="text-xs leading-5">{setup ? 'Siapkan akun administrator. Form ditutup setelah akun pertama dibuat.' : 'Gunakan akun yang diberikan administrator.'}</CardDescription></div></CardHeader><CardContent className="px-6 sm:px-8">
        {errors.length > 0 && <div className="notice error mb-5" role="alert">{errors.map((error,index) => <p key={index}>{error}</p>)}</div>}
        <form method="post" action={action} className="space-y-4"><input type="hidden" name="_token" value={csrf} />
            {setup && <div className="space-y-2"><Label htmlFor="auth-name">Nama lengkap</Label><Input id="auth-name" name="name" defaultValue={values.name ?? ''} required autoComplete="name" /></div>}
            <div className="space-y-2"><Label htmlFor="auth-username">Username</Label><Input id="auth-username" name="username" defaultValue={values.username ?? ''} required autoComplete="username" autoFocus /></div>
            {setup && <div className="space-y-2"><Label htmlFor="auth-email">Email administrator</Label><Input id="auth-email" name="email" type="email" defaultValue={values.email ?? ''} required autoComplete="email" /></div>}
            <div className="space-y-2"><Label htmlFor="auth-password">{setup ? 'Password (minimal 8 karakter)' : 'Password'}</Label><div className="relative"><Input id="auth-password" className="pr-10" name="password" type={visible ? 'text' : 'password'} required minLength={setup ? 8 : undefined} autoComplete={setup ? 'new-password' : 'current-password'} /><Button type="button" variant="ghost" size="icon" className="absolute right-0 top-0" aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'} aria-pressed={visible} onClick={() => setVisible(!visible)}>{visible ? <EyeOff /> : <Eye />}</Button></div></div>
            {setup && <div className="space-y-2"><Label htmlFor="auth-confirmation">Ulangi password</Label><Input id="auth-confirmation" name="password_confirmation" type="password" required minLength={8} autoComplete="new-password" /></div>}
            <Button type="submit" className="cyber-primary h-11 w-full rounded-xl text-xs font-bold"><LockKeyhole />{setup ? 'Buat akun & lanjutkan' : 'Masuk ke TaxCore'}<ArrowRight /></Button>
        </form></CardContent><CardFooter className="mx-6 border-t border-slate-700/80 px-0 pb-6 pt-5 sm:mx-8 sm:pb-8"><div><p className="mb-2 flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-wider text-cyan-400"><ShieldCheck className="size-3.5" />PPh 21 Enterprise</p><p className="text-[11px] leading-5 text-slate-400">Akses mengikuti peran dan perusahaan yang ditetapkan. Aktivitas dicatat dalam log audit.</p></div></CardFooter></Card>;
}
