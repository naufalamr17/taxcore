import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { Printer, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export function ConfirmationDialog() {
    const [pending, setPending] = useState(null);
    const approved = useRef(new WeakSet());
    useEffect(() => {
        const handler = event => {
            const form = event.target;
            if (!form.matches('form[data-confirm]')) return;
            if (approved.current.delete(form)) return;
            event.preventDefault();
            setPending({ form, submitter:event.submitter, message:form.dataset.confirm });
        };
        document.addEventListener('submit', handler, true);
        return () => document.removeEventListener('submit', handler, true);
    }, []);
    function confirm() {
        const { form, submitter } = pending;
        setPending(null);
        if (!form.isConnected || !form.reportValidity()) return;
        approved.current.add(form);
        form.requestSubmit(submitter?.isConnected ? submitter : undefined);
        queueMicrotask(() => approved.current.delete(form));
    }
    return <AlertDialog open={!!pending} onOpenChange={open => { if (!open) setPending(null); }}><AlertDialogContent><AlertDialogHeader><div className="mb-2 flex size-10 items-center justify-center rounded-full bg-muted"><ShieldAlert className="size-5" /></div><AlertDialogTitle>Konfirmasi tindakan</AlertDialogTitle><AlertDialogDescription>{pending?.message}</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction onClick={confirm}>Lanjutkan</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>;
}

export function ResultControls() {
    const [tab, setTab] = useState('worksheet');
    const [printTarget, setPrintTarget] = useState('worksheet');
    useLayoutEffect(() => {
        const output = document.querySelector('.result-output');
        if (!output) return;
        output.dataset.outputMode = tab;
        output.querySelectorAll('[data-output-panel]').forEach(panel => { panel.hidden = tab !== 'both' && panel.dataset.outputPanel !== tab; });
    }, [tab]);
    useEffect(() => {
        const print = event => {
            if (!event.target.closest('[data-print]')) return;
            event.preventDefault();
            document.body.dataset.printTarget = printTarget;
            window.print();
        };
        const done = () => { delete document.body.dataset.printTarget; };
        document.addEventListener('click', print);
        window.addEventListener('afterprint', done);
        return () => { document.removeEventListener('click', print); window.removeEventListener('afterprint', done); };
    }, [printTarget]);
    function changeTab(value) { setTab(value); setPrintTarget(value); }
    return <div className="no-print flex flex-wrap items-center justify-between gap-3">
        <Tabs value={tab} onValueChange={changeTab}><TabsList aria-label="Tampilan hasil"><TabsTrigger value="worksheet" aria-controls="worksheet-output">Kertas Kerja</TabsTrigger><TabsTrigger value="journal" aria-controls="journal-output">Jurnal Akuntansi</TabsTrigger></TabsList></Tabs>
        <div className="flex items-center gap-2"><label className="sr-only" htmlFor="print-target">Target cetak</label><select id="print-target" className="field mt-0 max-w-52" data-print-target value={printTarget} onChange={event => changeTab(event.target.value)}><option value="worksheet">Cetak: Kertas Kerja</option><option value="journal">Cetak: Jurnal Akuntansi</option></select><Button type="button" data-print><Printer />Cetak</Button></div>
    </div>;
}

export function TerDialog({ category = 'A', gross = 0, tables }) {
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(category);
    useEffect(() => {
        const handler = event => { if (event.target.closest('[data-dialog-open="ter-dialog"]')) { setActive(category); setOpen(true); } };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [category]);
    const rupiah = value => new Intl.NumberFormat('id-ID').format(value);
    return <Dialog open={open} onOpenChange={setOpen}><DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl"><DialogHeader><DialogTitle>Tabel Tarif Efektif Rata-rata</DialogTitle><DialogDescription>Bruto Rp {rupiah(gross)} - kategori TER {category}. PP 58/2023.</DialogDescription></DialogHeader>
        <Tabs value={active} onValueChange={setActive}><TabsList className="grid w-full grid-cols-4">{Object.keys(tables).map(key => <TabsTrigger key={key} value={key}>TER {key}</TabsTrigger>)}<TabsTrigger value="progressive">Pasal 17</TabsTrigger></TabsList>{Object.entries(tables).map(([key, brackets]) => <TabsContent key={key} value={key}><Table><TableHeader><TableRow><TableHead>Rentang bruto</TableHead><TableHead className="text-right">Tarif</TableHead></TableRow></TableHeader><TableBody>{brackets.map(([upper, rate], index) => {
            const lower = index === 0 ? 0 : brackets[index - 1][0];
            const selected = key === category && (index === 0 || gross > lower) && (upper === null || gross <= upper);
            return <TableRow key={index} className={cn(selected && 'bg-accent font-medium')}><TableCell>{upper === null ? `Di atas Rp ${rupiah(lower)}` : `Rp ${rupiah(lower)} - ${rupiah(upper)}`}{selected && <span className="ml-2 text-xs text-muted-foreground">Aktif</span>}</TableCell><TableCell className="text-right font-mono">{rate / 100}%</TableCell></TableRow>;
        })}</TableBody></Table></TabsContent>)}<TabsContent value="progressive"><p className="mb-3 text-xs text-muted-foreground">Tarif progresif atas Penghasilan Kena Pajak (PKP) setahun.</p><Table><TableHeader><TableRow><TableHead>Lapisan PKP</TableHead><TableHead className="text-right">Tarif</TableHead></TableRow></TableHeader><TableBody>{[['Sampai Rp 60.000.000','5%'],['Di atas Rp 60.000.000 – Rp 250.000.000','15%'],['Di atas Rp 250.000.000 – Rp 500.000.000','25%'],['Di atas Rp 500.000.000 – Rp 5.000.000.000','30%'],['Di atas Rp 5.000.000.000','35%']].map(([range, rate]) => <TableRow key={range}><TableCell>{range}</TableCell><TableCell className="text-right font-mono">{rate}</TableCell></TableRow>)}</TableBody></Table></TabsContent></Tabs>
    </DialogContent></Dialog>;
}
