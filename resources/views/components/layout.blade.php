@props(['company'=>null,'title'=>'Workspace'])
@php
    $navigationCompany = $company ?? $switchCompanies->firstWhere('id', (int) session('active_company_id'));
    $navigation = [];
    $modules = [];
    if (auth()->user()->can('payroll.view')) {
        $navigation[] = ['label'=>'Daftar Gaji & PPh 21','href'=>$navigationCompany ? route('payroll.index',$navigationCompany) : null,'icon'=>'payroll','disabled'=>!$navigationCompany,'active'=>request()->routeIs('payroll.*')];
        $navigation[] = ['label'=>'Database Penerima','href'=>$navigationCompany ? route('employees.index',$navigationCompany) : null,'icon'=>'people','disabled'=>!$navigationCompany,'active'=>request()->routeIs('employees.*')];
        $navigation[] = ['label'=>'Simulator Pajak','href'=>$navigationCompany ? route('simulator.index',$navigationCompany) : null,'icon'=>'calculator','disabled'=>!$navigationCompany,'active'=>request()->routeIs('simulator.*')];
        foreach (config('taxcore.modules') as $key=>$label) {
            $modules[] = ['label'=>$label,'disabled'=>!$navigationCompany,'href'=>$navigationCompany ? route('payroll.index',['company'=>$navigationCompany,'module'=>$key,'month'=>$key==='annual' ? 12 : now()->month,'year'=>2026]) : null,'active'=>request()->routeIs('payroll.*') && request('module','monthly')===$key];
        }
    }
    $system = [];
    if (auth()->user()->can('payroll.view')) {
        $system[] = ['label'=>'Tabel TER & PTKP','href'=>route('tax.reference'),'icon'=>'reference','active'=>request()->routeIs('tax.reference')];
    }
    $system[] = ['label'=>'Perusahaan & Cabang','href'=>route('companies.index'),'icon'=>'company','active'=>request()->routeIs('companies.*','branches.*')];
    if (auth()->user()->can('access.manage')) {
        $system[] = ['label'=>'Manajemen Akses','href'=>route('access.index'),'icon'=>'access','active'=>request()->routeIs('access.*')];
    }
    if (auth()->user()->can('payroll.view')) {
        $system[] = ['label'=>'Log Audit','href'=>$navigationCompany ? route('audit.index',$navigationCompany) : null,'icon'=>'audit','disabled'=>!$navigationCompany,'active'=>request()->routeIs('audit.*')];
    }
    $workspaceUi = ['title'=>$title,'dashboardUrl'=>route('dashboard'),'companyId'=>$navigationCompany?->id,'companies'=>$switchCompanies->map(fn($item)=>['id'=>$item->id,'name'=>$item->name,'href'=>route('payroll.index',$item)])->values(),'companiesUrl'=>route('companies.index'),'isManagingCompanies'=>request()->routeIs('companies.index'),'navigation'=>$navigation,'modules'=>$modules,'system'=>$system,'user'=>['name'=>auth()->user()->name,'role'=>config('taxcore.roles.'.auth()->user()->role)],'logoutUrl'=>route('logout'),'csrf'=>csrf_token()];
@endphp
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} - TaxCore</title>
@include('components.theme-script')
@viteReactRefresh
@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body>
<div class="app-shell">
<div data-shadcn="workspace" data-props="{{ json_encode($workspaceUi, JSON_THROW_ON_ERROR) }}"></div>
@include('components.navigation-fallback',['workspaceUi'=>$workspaceUi])
<div class="main-shell">
<main class="content" id="main-content">
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Periksa kembali data berikut:</strong><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main>
<footer class="app-footer"><span>TaxCore - PPh 21 Enterprise</span><span>PP 58/2023 - PMK 168/2023</span></footer>
</div></div>
<div data-shadcn="confirmation"></div>
</body></html>
