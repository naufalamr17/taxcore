<?php

return [
    'modules' => [
        'monthly' => 'Pegawai Tetap Bulanan',
        'annual' => 'Masa Pajak Terakhir / Resign',
        'pension' => 'Pensiunan Berkala',
        'daily' => 'Pegawai Tidak Tetap / Harian',
        'contractor' => 'Bukan Pegawai / Tenaga Ahli',
        'severance' => 'Pesangon / Manfaat Pensiun Final',
    ],
    'roles' => ['SUPER_ADMIN' => 'Super Admin', 'TAX_MANAGER' => 'Tax Manager', 'PAYROLL_STAFF' => 'Payroll Staff', 'AUDITOR' => 'Auditor'],
    'permissions' => ['payroll.view', 'payroll.edit', 'payroll.review', 'payroll.finalize', 'payroll.correct', 'payroll.import', 'payroll.export', 'company.manage', 'access.manage'],
    'defaults' => [
        'SUPER_ADMIN' => ['payroll.view', 'payroll.edit', 'payroll.review', 'payroll.finalize', 'payroll.correct', 'payroll.import', 'payroll.export', 'company.manage', 'access.manage'],
        'TAX_MANAGER' => ['payroll.view', 'payroll.edit', 'payroll.review', 'payroll.finalize', 'payroll.correct', 'payroll.import', 'payroll.export'],
        'PAYROLL_STAFF' => ['payroll.view', 'payroll.edit', 'payroll.import'],
        'AUDITOR' => ['payroll.view', 'payroll.export'],
    ],
];
