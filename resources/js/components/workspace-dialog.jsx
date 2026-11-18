import { useEffect, useRef, useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export function WorkspaceDialog() {
    const [page, setPage] = useState(null);
    const [loading, setLoading] = useState(true);
    const frame = useRef(null);
    const changed = useRef(false);
    const trigger = useRef(null);

    function close() {
        setPage(null);
        trigger.current?.focus();
        if (changed.current) window.location.reload();
    }

    useEffect(() => {
        const open = event => {
            const link = event.target.closest('a[data-workspace-dialog]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            url.searchParams.set('_dialog', '1');
            changed.current = false;
            trigger.current = link;
            setLoading(true);
            const width = url.searchParams.get('_dialog_panel') === 'bulk' ? 'max-w-lg' : /\/payroll\/(create|\d+\/edit)$/.test(url.pathname) ? 'max-w-xl' : 'max-w-3xl';
            setPage({ url:url.href, title:link.dataset.dialogTitle || link.textContent.trim() || 'TaxCore', width, refresh:link.hasAttribute('data-dialog-refresh') });
        };
        document.addEventListener('click', open);
        return () => document.removeEventListener('click', open);
    }, []);

    useEffect(() => {
        const receive = event => {
            if (event.origin !== window.location.origin || event.source !== frame.current?.contentWindow) return;
            if (event.data?.type === 'taxcore:dialog-updated') {
                changed.current = true;
                if (page?.refresh && event.data.complete) close();
            }
            if (event.data?.type === 'taxcore:dialog-close') close();
        };
        window.addEventListener('message', receive);
        return () => window.removeEventListener('message', receive);
    }, [page]);

    return <Dialog open={!!page} onOpenChange={open => { if (!open) close(); }}><DialogContent className={cn('workspace-dialog flex h-[90dvh] max-h-[900px] w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden rounded-2xl bg-card p-0 sm:rounded-2xl',page?.width)}><DialogHeader className="shrink-0 border-b bg-muted/80 px-5 py-3.5 pr-12 text-left"><DialogTitle className="text-sm font-bold">{page?.title}</DialogTitle><DialogDescription className="sr-only">Formulir TaxCore. Tekan Escape atau tombol tutup untuk kembali.</DialogDescription></DialogHeader>{loading && <p role="status" className="absolute inset-x-0 top-20 text-center text-xs text-muted-foreground">Memuat formulir…</p>}{page && <iframe ref={frame} src={page.url} title={page.title} className="min-h-0 w-full flex-1 border-0 bg-background" onLoad={() => {
        setLoading(false);
        const url = new URL(frame.current?.contentWindow.location.href || page.url);
        if (url.origin === window.location.origin && /\/(login|setup)$/.test(url.pathname)) window.location.assign(url.href);
    }} />}</DialogContent></Dialog>;
}
