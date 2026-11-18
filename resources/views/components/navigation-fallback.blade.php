<div data-navigation-fallback>
<aside class="sidebar p-4"><a href="{{ $workspaceUi['dashboardUrl'] }}" class="brand mb-6"><span class="brand-mark">T</span><span>TaxCore<small>Payroll & Tax Workspace</small></span></a><p class="sidebar-label">Workspace</p>@foreach($workspaceUi['navigation'] as $item)@if($item['disabled'] ?? false)<button type="button" class="nav-link w-full opacity-50" aria-label="{{ $item['label'] }}" aria-disabled="true" disabled title="Pilih perusahaan terlebih dahulu">{{ $item['label'] }}</button>@else<a class="nav-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endif
@endforeach
@if($workspaceUi['modules'])<details open><summary class="sidebar-label">Modul Pajak</summary>@foreach($workspaceUi['modules'] as $item)@if($item['disabled'] ?? false)<button type="button" class="nav-link w-full opacity-50" aria-label="{{ $item['label'] }}" aria-disabled="true" disabled title="Pilih perusahaan terlebih dahulu">{{ $item['label'] }}</button>@else<a class="nav-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endif
@endforeach</details>@endif
<p class="sidebar-label">Referensi & Sistem</p>@foreach($workspaceUi['system'] as $item)@if($item['disabled'] ?? false)<button type="button" class="nav-link w-full opacity-50" aria-label="{{ $item['label'] }}" aria-disabled="true" disabled title="Pilih perusahaan terlebih dahulu">{{ $item['label'] }}</button>@else<a class="nav-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endif
@endforeach
<form class="mt-auto pt-5" method="post" action="{{ $workspaceUi['logoutUrl'] }}">@csrf<button class="button w-full">Keluar dari workspace</button></form></aside>
<header class="workspace-header"><a href="{{ route('dashboard') }}" class="button small lg:hidden">Workspace</a><h1 class="text-sm font-semibold">{{ $title }}</h1><a class="button small" href="{{ route('companies.index') }}">Perusahaan</a></header>
</div>
