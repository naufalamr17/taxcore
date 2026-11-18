import { useState } from 'react';
import { ArrowRight, Eye, EyeOff, Landmark } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

export function AuthForm({ mode, action, csrf, errors = [], values = {} }) {
    const setup = mode === 'setup';
    const [visible, setVisible] = useState(false);
    return <Card className="w-full max-w-md shadow-none"><CardHeader className="space-y-2"><div className="mb-3 flex size-10 items-center justify-center rounded-lg border bg-muted"><Landmark className="size-5" /></div><CardTitle className="text-2xl tracking-tight">{setup ? 'Buat Super Admin pertama' : 'Masuk ke workspace'}</CardTitle><CardDescription>{setup ? 'Siapkan akun administrator. Form ditutup setelah akun pertama dibuat.' : 'Gunakan akun yang diberikan administrator.'}</CardDescription></CardHeader><CardContent>
        {errors.length > 0 && <div className="notice error mb-5" role="alert">{errors.map((error,index) => <p key={index}>{error}</p>)}</div>}
        <form method="post" action={action} className="space-y-4"><input type="hidden" name="_token" value={csrf} />
            {setup && <div className="space-y-2"><Label htmlFor="auth-name">Nama lengkap</Label><Input id="auth-name" name="name" defaultValue={values.name ?? ''} required autoComplete="name" /></div>}
            <div className="space-y-2"><Label htmlFor="auth-username">Username</Label><Input id="auth-username" name="username" defaultValue={values.username ?? ''} required autoComplete="username" autoFocus /></div>
            {setup && <div className="space-y-2"><Label htmlFor="auth-email">Email administrator</Label><Input id="auth-email" name="email" type="email" defaultValue={values.email ?? ''} required autoComplete="email" /></div>}
            <div className="space-y-2"><Label htmlFor="auth-password">{setup ? 'Password (minimal 8 karakter)' : 'Password'}</Label><div className="relative"><Input id="auth-password" className="pr-10" name="password" type={visible ? 'text' : 'password'} required minLength={setup ? 8 : undefined} autoComplete={setup ? 'new-password' : 'current-password'} /><Button type="button" variant="ghost" size="icon" className="absolute right-0 top-0" aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'} aria-pressed={visible} onClick={() => setVisible(!visible)}>{visible ? <EyeOff /> : <Eye />}</Button></div></div>
            {setup && <div className="space-y-2"><Label htmlFor="auth-confirmation">Ulangi password</Label><Input id="auth-confirmation" name="password_confirmation" type="password" required minLength={8} autoComplete="new-password" /></div>}
            <Button type="submit" className="w-full">{setup ? 'Buat akun & lanjutkan' : 'Masuk ke TaxCore'}<ArrowRight /></Button>
        </form></CardContent><CardFooter><p className="text-xs leading-5 text-muted-foreground">Akses mengikuti peran dan perusahaan yang ditetapkan. Aktivitas dicatat dalam log audit.</p></CardFooter></Card>;
}
