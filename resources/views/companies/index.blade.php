<x-layout title="Perusahaan & Cabang">
<div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
@can('company.manage')
<section class="panel self-start lg:col-span-5"><div class="panel-heading"><div><h2 class="text-sm font-bold">Form Profil Pemotong & ID TKU (22 Digit)</h2><p class="mt-1 text-[11px] text-muted-foreground">Sesuai standar e-Bupot 21 Coretax DJP</p></div><button type="reset" form="company-create-form" class="button small">Reset</button></div>
<form id="company-create-form" data-company-identity method="post" action="{{ route('companies.store') }}" class="panel-body space-y-3">@csrf
<x-input name="name" label="Nama Perusahaan (Pemotong Pajak)" required maxlength="150" placeholder="PT Nusantara Teknologi Mandiri" />
<label class="form-label">Nama Cabang / TKU<input class="field" value="Kantor Pusat" readonly></label>
<div class="grid grid-cols-2 gap-3"><x-input name="npwp" label="NPWP 16 Digit" required pattern="[0-9]{16}" maxlength="16" inputmode="numeric" /><label class="form-label">Kode Cabang NITKU (6 Digit)<input class="field font-mono" value="000000" readonly></label></div>
<label class="form-label text-indigo-700">ID TKU Lengkap (22 Digit — Otomatis)<input class="field border-indigo-200 bg-indigo-50/60 font-mono text-indigo-900" data-company-nitku value="{{ old('npwp') ? old('npwp').'000000' : '' }}" placeholder="NPWP 16 digit + 000000" readonly></label>
<div class="grid gap-3 sm:grid-cols-2"><x-input name="signer_name" label="Nama Penandatangan" required maxlength="150" /><x-input name="signer_npwp" label="NPWP/NIK Penandatangan" required pattern="[0-9]{16}" maxlength="16" inputmode="numeric" /></div>
<div class="grid gap-3 sm:grid-cols-2"><x-input name="city" label="Kota" required maxlength="100" /><x-input name="address" label="Alamat" maxlength="255" /></div>
<p class="text-[11px] leading-5 text-muted-foreground">Cabang pusat 000000 dibuat otomatis. Setelah tersimpan, pilih perusahaan dan cabang pada daftar untuk membuka workspace.</p><button class="button primary w-full">Simpan ke Database Perusahaan</button>
</form></section>
@endcan
<section class="panel min-w-0 self-start {{ auth()->user()->can('company.manage') ? 'lg:col-span-7' : 'lg:col-span-12' }}"><div class="panel-heading"><h3>Daftar Perusahaan & Cabang TKU Terdaftar</h3></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Nama Perusahaan & Cabang</th><th>NPWP (16 Digit)</th><th>ID TKU (22 Digit)</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($companies as $company)
@foreach($company->branches as $branch)
<tr><td><strong>{{ $company->name }}</strong><small class="mt-1 block text-muted-foreground">{{ $branch->name }} · {{ $company->city }}</small></td><td class="font-mono">{{ $company->npwp }}</td><td class="font-mono text-indigo-700">{{ $branch->nitku }}</td><td><span class="pill">Terdaftar</span></td><td><div class="flex flex-wrap gap-2">@can('payroll.view')<a class="button small primary" href="{{ route('payroll.index',['company'=>$company,'branch_id'=>$branch->id]) }}">Buka workspace ↗</a>@endcan
@can('company.manage')<details><summary class="button small">Edit cabang</summary><form class="mt-3 min-w-56 space-y-3" method="post" action="{{ route('branches.update',[$company,$branch]) }}">@csrf @method('PUT')<x-input name="name" label="Nama cabang" :value="$branch->name" required maxlength="150" /><x-input name="code" label="Kode TKU (6 digit)" :value="$branch->code" required pattern="[0-9]{6}" maxlength="6" /><button class="button small">Simpan cabang</button></form></details>@endcan</div></td></tr>
@endforeach
@can('company.manage')<tr><td colspan="5" class="whitespace-normal"><details id="company-settings-{{ $company->id }}"><summary class="text-xs font-semibold text-blue-600">Kelola profil & cabang — {{ $company->name }}</summary>
<form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('companies.update',$company) }}">@csrf @method('PUT')@include('companies.fields',['data'=>$company])<div class="sm:col-span-2"><button class="button">Simpan profil</button></div></form>
<details class="mt-4"><summary class="text-xs font-semibold text-blue-600">+ Tambah cabang / TKU</summary><form method="post" action="{{ route('branches.store',$company) }}" class="mt-4 grid items-end gap-3 sm:grid-cols-2">@csrf<x-input name="name" label="Nama cabang" required /><x-input name="code" label="Kode cabang (6 digit)" required pattern="[0-9]{6}" maxlength="6" placeholder="000001" /><div class="sm:col-span-2"><button class="button">Tambahkan cabang</button></div></form></details>
<form class="mt-5" method="post" action="{{ route('companies.destroy',$company) }}" data-confirm="Hapus perusahaan kosong ini? Perusahaan yang memiliki data akan ditolak.">@csrf @method('DELETE')<button class="button small danger">Hapus perusahaan kosong</button></form>
</details></td></tr>@endcan
@empty<tr><td colspan="5"><div class="empty-state"><span class="empty-icon">▥</span><h3>Belum ada perusahaan yang dapat diakses</h3><p>@can('company.manage')Tambahkan profil pemotong pertama untuk mulai mengelola payroll.@else Hubungi administrator untuk mendapatkan penugasan perusahaan.@endcan</p></div></td></tr>@endforelse
</tbody></table></div></section>
</div>
</x-layout>