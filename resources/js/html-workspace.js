const STORAGE_KEY_USERS = 'taxcore_pph21_users_v6';
        const STORAGE_KEY_EMPLOYEES = 'taxcore_pph21_employees_v7';
        const STORAGE_KEY_COMPANIES = 'taxcore_pph21_companies_v6';

        function safeSetStorage(key, val) {
            if (key === 'taxcore_cyber_theme_v1') return;
        }

        const DEFAULT_USERS = [];

        const PTKP_MAP = {
            'TK/0': { amount: 54000000, terCat: 'A' }, 'TK/1': { amount: 58500000, terCat: 'A' },
            'TK/2': { amount: 63000000, terCat: 'B' }, 'TK/3': { amount: 67500000, terCat: 'B' },
            'K/0':  { amount: 58500000, terCat: 'A' }, 'K/1':  { amount: 63000000, terCat: 'B' },
            'K/2':  { amount: 67500000, terCat: 'B' }, 'K/3':  { amount: 72000000, terCat: 'C' }
        };

        const AVAILABLE_YEARS = [2020, 2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028, 2029, 2030, 2031, 2032, 2033, 2034, 2035];
        const MONTH_NAMES = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        const PASAL_17_BRACKETS = [
            { min: 0, max: 60000000, rate: 0.05, label: 's.d. Rp 60.000.000 (5%)' },
            { min: 60000000, max: 250000000, rate: 0.15, label: '> Rp 60 Juta s.d. Rp 250 Juta (15%)' },
            { min: 250000000, max: 500000000, rate: 0.25, label: '> Rp 250 Juta s.d. Rp 500 Juta (25%)' },
            { min: 500000000, max: 5000000000, rate: 0.30, label: '> Rp 500 Juta s.d. Rp 5 Miliar (30%)' },
            { min: 5000000000, max: Infinity, rate: 0.35, label: 'Di atas Rp 5 Miliar (35%)' }
        ];

        const TER_TABLES = {
            A: [
                { min: 0, max: 5400000, rate: 0 }, { min: 5400001, max: 5650000, rate: 0.25 }, { min: 5650001, max: 5950000, rate: 0.5 },
                { min: 5950001, max: 6300000, rate: 0.75 }, { min: 6300001, max: 6750000, rate: 1 }, { min: 6750001, max: 7500000, rate: 1.25 },
                { min: 7500001, max: 8550000, rate: 1.5 }, { min: 8550001, max: 9650000, rate: 1.75 }, { min: 9650001, max: 10050000, rate: 2 },
                { min: 10050001, max: 10350000, rate: 2.25 }, { min: 10350001, max: 10700000, rate: 2.5 }, { min: 10700001, max: 11050000, rate: 3 },
                { min: 11050001, max: 11600000, rate: 3.5 }, { min: 11600001, max: 12500000, rate: 4 }, { min: 12500001, max: 13750000, rate: 5 },
                { min: 13750001, max: 15100000, rate: 6 }, { min: 15100001, max: 16950000, rate: 7 }, { min: 16950001, max: 19750000, rate: 8 },
                { min: 19750001, max: 24150000, rate: 9 }, { min: 24150001, max: 26450000, rate: 10 }, { min: 26450001, max: 28000000, rate: 11 },
                { min: 28000001, max: 30050000, rate: 12 }, { min: 30050001, max: 32400000, rate: 13 }, { min: 32400001, max: 35400000, rate: 14 },
                { min: 35400001, max: 39100000, rate: 15 }, { min: 39100001, max: 43850000, rate: 16 }, { min: 43850001, max: 47800000, rate: 17 },
                { min: 47800001, max: 51400000, rate: 18 }, { min: 51400001, max: 56300000, rate: 19 }, { min: 56300001, max: 62200000, rate: 20 },
                { min: 62200001, max: 68600000, rate: 21 }, { min: 68600001, max: 77500000, rate: 22 }, { min: 77500001, max: 89000000, rate: 23 },
                { min: 89000001, max: 103000000, rate: 24 }, { min: 103000001, max: 125000000, rate: 25 }, { min: 125000001, max: 157000000, rate: 26 },
                { min: 157000001, max: 206000000, rate: 27 }, { min: 206000001, max: 337000000, rate: 28 }, { min: 337000001, max: 454000000, rate: 29 },
                { min: 454000001, max: 550000000, rate: 30 }, { min: 550000001, max: 695000000, rate: 31 }, { min: 695000001, max: 910000000, rate: 32 },
                { min: 910000001, max: 1400000000, rate: 33 }, { min: 1400000001, max: Infinity, rate: 34 }
            ],
            B: [
                { min: 0, max: 6200000, rate: 0 }, { min: 6200001, max: 6500000, rate: 0.25 }, { min: 6500001, max: 6850000, rate: 0.5 },
                { min: 6850001, max: 7300000, rate: 0.75 }, { min: 7300001, max: 9200000, rate: 1 }, { min: 9200001, max: 10750000, rate: 1.5 },
                { min: 10750001, max: 11250000, rate: 2 }, { min: 11250001, max: 11600000, rate: 2.5 }, { min: 11600001, max: 12600000, rate: 3 },
                { min: 12600001, max: 13600000, rate: 4 }, { min: 13600001, max: 14950000, rate: 5 }, { min: 14950001, max: 16400000, rate: 6 },
                { min: 16400001, max: 18450000, rate: 7 }, { min: 18450001, max: 21850000, rate: 8 }, { min: 21850001, max: 26000000, rate: 9 },
                { min: 26000001, max: 27700000, rate: 10 }, { min: 27700001, max: 29350000, rate: 11 }, { min: 29350001, max: 31450000, rate: 12 },
                { min: 31450001, max: 33950000, rate: 13 }, { min: 33950001, max: 37100000, rate: 14 }, { min: 37100001, max: 41100000, rate: 15 },
                { min: 41100001, max: 45800000, rate: 16 }, { min: 45800001, max: 49500000, rate: 17 }, { min: 49500001, max: 53800000, rate: 18 },
                { min: 53800001, max: 58500000, rate: 19 }, { min: 58500001, max: 64000000, rate: 20 }, { min: 64000001, max: 71000000, rate: 21 },
                { min: 71000001, max: 80000000, rate: 22 }, { min: 80000001, max: 93000000, rate: 23 }, { min: 93000001, max: 109000000, rate: 24 },
                { min: 109000001, max: 129000000, rate: 25 }, { min: 129000001, max: 163000000, rate: 26 }, { min: 163000001, max: 211000000, rate: 27 },
                { min: 211000001, max: 374000000, rate: 28 }, { min: 374000001, max: 459000000, rate: 29 }, { min: 459000001, max: 555000000, rate: 30 },
                { min: 555000001, max: 704000000, rate: 31 }, { min: 704000001, max: 957000000, rate: 32 }, { min: 957000001, max: 1405000000, rate: 33 },
                { min: 1405000001, max: Infinity, rate: 34 }
            ],
            C: [
                { min: 0, max: 6600000, rate: 0 }, { min: 6600001, max: 6950000, rate: 0.25 }, { min: 6950001, max: 7350000, rate: 0.5 },
                { min: 7350001, max: 7800000, rate: 0.75 }, { min: 7800001, max: 8850000, rate: 1 }, { min: 8850001, max: 9800000, rate: 1.25 },
                { min: 9800001, max: 10950000, rate: 1.5 }, { min: 10950001, max: 11200000, rate: 1.75 }, { min: 11200001, max: 12050000, rate: 2 },
                { min: 12050001, max: 12950000, rate: 3 }, { min: 12950001, max: 14150000, rate: 4 }, { min: 14150001, max: 15550000, rate: 5 },
                { min: 15550001, max: 17050000, rate: 6 }, { min: 17050001, max: 19500000, rate: 7 }, { min: 19500001, max: 22700000, rate: 8 },
                { min: 22700001, max: 26600000, rate: 9 }, { min: 26600001, max: 28100000, rate: 10 }, { min: 28100001, max: 30100000, rate: 11 },
                { min: 30100001, max: 32600000, rate: 12 }, { min: 32600001, max: 35400000, rate: 13 }, { min: 35400001, max: 38900000, rate: 14 },
                { min: 38900001, max: 43000000, rate: 15 }, { min: 43000001, max: 47400000, rate: 16 }, { min: 47400001, max: 51200000, rate: 17 },
                { min: 51200001, max: 55800000, rate: 18 }, { min: 55800001, max: 60400000, rate: 19 }, { min: 60400001, max: 66700000, rate: 20 },
                { min: 66700001, max: 74500000, rate: 21 }, { min: 74500001, max: 83200000, rate: 22 }, { min: 83200001, max: 95600000, rate: 23 },
                { min: 95600001, max: 110000000, rate: 24 }, { min: 110000001, max: 134000000, rate: 25 }, { min: 134000001, max: 169000000, rate: 26 },
                { min: 169000001, max: 221000000, rate: 27 }, { min: 221000001, max: 390000000, rate: 28 }, { min: 390000001, max: 463000000, rate: 29 },
                { min: 463000001, max: 561000000, rate: 30 }, { min: 561000001, max: 709000000, rate: 31 }, { min: 709000001, max: 965000000, rate: 32 },
                { min: 965000001, max: 1419000000, rate: 33 }, { min: 1419000001, max: Infinity, rate: 34 }
            ]
        };

        const DEFAULT_EMPLOYEES = [];

        const DEFAULT_COMPANIES = [];

        let usersData = JSON.parse(JSON.stringify(DEFAULT_USERS));
        let employeesData = JSON.parse(JSON.stringify(DEFAULT_EMPLOYEES));
        let companiesData = JSON.parse(JSON.stringify(DEFAULT_COMPANIES));
        let activeCompanyId = 'comp_1';
        let currentWorkspaceMode = 'payroll';
        let currentSubject = 'pegawai_bulanan';
        let currentTaxMethod = 'GROSS';
        let payrollPeriodMode = 'BULANAN';
        let inlineSpreadsheetMode = false;
        let currentPayrollPage = 1;
        let payrollPageSize = 50;
        let lastTerCategoryForModal = 'A';
        let lastCalculatedBrutoForModal = 0;
        let currentViewingWorksheetEmpId = null;

        // UTILITIES
        function formatRupiah(num, withSymbol = true) {
            if (isNaN(num)) return withSymbol ? 'Rp 0' : '0';
            const rounded = Math.round(num);
            const isNeg = rounded < 0;
            const formatted = Math.abs(rounded).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return !withSymbol ? (isNeg ? '-' : '') + formatted : (isNeg ? '-Rp ' : 'Rp ') + formatted;
        }

        function formatRupiahPrecision(num, withSymbol = true) {
            if (isNaN(num)) return withSymbol ? 'Rp 0' : '0';
            const isNeg = num < 0;
            const absVal = Math.abs(Number(num));
            const fixed = absVal.toFixed(2);
            const parts = fixed.split('.');
            const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const decPart = parts[1];
            const hasDecimal = decPart !== '00';
            const formatted = hasDecimal ? `${intPart},${decPart}` : intPart;
            return !withSymbol ? (isNeg ? '-' : '') + formatted : (isNeg ? '-Rp ' : 'Rp ') + formatted;
        }

        function hasPrecisionFraction(num) {
            if (typeof num !== 'number' || isNaN(num)) return false;
            return Math.abs(num - Math.round(num)) >= 0.005;
        }

        function parseCurrency(str) {
            if (typeof str === 'number') return Math.round(str);
            if (!str) return 0;
            const s = String(str).trim();
            if (!s) return 0;
            // Tangani angka desimal 1-2 digit dari Excel/CSV seperti "18500000.00" (bukan pemisah ribuan 3 digit seperti "6.555")
            if (/^-?\d+\.\d{1,2}$/.test(s)) {
                return Math.round(parseFloat(s)) || 0;
            }
            // Jika menggunakan pemisah ribuan koma & desimal titik (format US: 18,500,000.00)
            if (/,\d{3}\.\d{1,2}$/.test(s)) {
                return Math.round(parseFloat(s.replace(/,/g, ''))) || 0;
            }
            // Jika menggunakan pemisah ribuan titik & desimal koma (format ID: 18.500.000,00)
            if (/\.\d{3},\d{1,2}$/.test(s)) {
                return Math.round(parseFloat(s.replace(/\./g, '').replace(',', '.'))) || 0;
            }
            const digitsOnly = s.replace(/[^0-9]/g, '');
            return digitsOnly ? (parseInt(digitsOnly, 10) || 0) : 0;
        }

        function formatCurrencyInput(el) {
            if (!el) return;
            const rawDigits = String(el.value || '').replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
            if (!rawDigits) {
                el.value = '0';
                return;
            }
            const limitedDigits = rawDigits.slice(0, 15);
            el.value = limitedDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function setInputValue(id, amount) {
            const el = document.getElementById(id);
            if (el) el.value = Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function showToast(msg) {
            const t = document.getElementById('toastNotification');
            const m = document.getElementById('toastMessage');
            if (!t || !m) return;
            m.textContent = msg;
            t.classList.remove('hidden');
            setTimeout(() => t.classList.add('hidden'), 2800);
        }

        function lookupTerBracket(cat, bruto) {
            const list = TER_TABLES[cat] || TER_TABLES.A;
            for (let i = 0; i < list.length; i++) {
                if (bruto >= list[i].min && bruto <= list[i].max) return { ...list[i], index: i + 1 };
            }
            return { ...list[list.length - 1], index: list.length };
        }

        function solveTerGrossUp(baseBruto, terCat, npwpMultiplier = 1.0) {
            let allowance = 0;
            let exactBruto = baseBruto;
            let exactTax = 0;
            for (let iter = 0; iter < 50; iter++) {
                const testBruto = baseBruto + allowance;
                const b = lookupTerBracket(terCat, testBruto);
                const effRate = (b.rate / 100) * npwpMultiplier;
                if (effRate === 0) {
                    allowance = 0;
                    exactBruto = baseBruto;
                    exactTax = 0;
                    break;
                }
                exactBruto = baseBruto / (1 - effRate);
                exactTax = exactBruto - baseBruto;
                const nextTax = Math.round(exactTax);
                if (Math.abs(nextTax - allowance) <= 1) {
                    allowance = nextTax;
                    break;
                }
                allowance = nextTax;
            }
            const finalBruto = baseBruto + allowance;
            return {
                tunjanganPajak: allowance,
                finalBruto,
                exactBruto,
                exactTax,
                finalBracket: lookupTerBracket(terCat, finalBruto)
            };
        }

        function calculatePasal17(pkpInput, npwpMultiplier = 1.0, roundToThousand = true) {
            const pkpExact = roundToThousand
                ? Math.max(0, Math.floor(pkpInput / 1000) * 1000)
                : Math.max(0, Number(pkpInput) || 0);
            let remaining = pkpExact, totalTaxExact = 0;
            const breakdown = [];
            for (let idx = 0; idx < PASAL_17_BRACKETS.length; idx++) {
                const b = PASAL_17_BRACKETS[idx];
                const cap = b.max === Infinity ? Infinity : (b.max - b.min);
                const taxableExact = Math.max(0, Math.min(remaining, cap));
                const rate = b.rate * npwpMultiplier;
                const taxExact = taxableExact * rate;
                totalTaxExact += taxExact;
                remaining -= taxableExact;
                breakdown.push({
                    layerNum: idx + 1,
                    label: b.label,
                    ratePercent: b.rate * 100 * npwpMultiplier,
                    effectiveRate: rate,
                    taxableAmountExact: taxableExact,
                    taxableAmount: Math.round(taxableExact),
                    taxAmountExact: taxExact,
                    taxAmount: Math.round(taxExact)
                });
            }
            return {
                pkp: Math.round(pkpExact),
                pkpExact,
                totalTax: Math.round(totalTaxExact),
                totalTaxExact,
                breakdown
            };
        }

        // Rumus Gross Up Presisi untuk DPP = 50% x Bruto x Tarif Pasal 17 (Bukan Pegawai / Tenaga Ahli & Tidak Tetap > 2,5 Jt)
        // Menyelesaikan persamaan B - PPh21(50% * B) = Net secara aljabar presisi tinggi
        function solveGrossUpDpp50Pasal17(netTarget, npwpMultiplier = 1.0) {
            if (netTarget <= 0) {
                const zeroRes = calculatePasal17(0, npwpMultiplier, false);
                return {
                    exactBruto: 0, exactDpp: 0, exactTax: 0,
                    brutoPajak: 0, dpp: 0, pph21: 0, tunjanganPajak: 0,
                    breakdown: zeroRes.breakdown
                };
            }
            let B = Number(netTarget);
            for (let iter = 0; iter < 60; iter++) {
                const dpp = 0.5 * B;
                const res = calculatePasal17(dpp, npwpMultiplier, false);
                const tax = res.totalTaxExact;
                const diff = (B - tax) - netTarget;
                if (Math.abs(diff) < 1e-8) break;
                let bracketRate = 0.05;
                for (const b of PASAL_17_BRACKETS) {
                    if (dpp > b.min && dpp <= b.max) {
                        bracketRate = b.rate;
                        break;
                    }
                }
                const dTax_dB = 0.5 * bracketRate * npwpMultiplier;
                B = B - (diff / (1 - dTax_dB));
            }
            const exactBruto = B;
            const exactDpp = 0.5 * exactBruto;
            const finalRes = calculatePasal17(exactDpp, npwpMultiplier, false);
            const exactTax = finalRes.totalTaxExact;
            const pph21 = Math.round(exactTax);
            const brutoPajak = Math.round(netTarget + pph21);
            const dpp = Math.round(exactDpp);
            return {
                exactBruto,
                exactDpp,
                exactTax,
                brutoPajak,
                dpp,
                pph21,
                tunjanganPajak: pph21,
                breakdown: finalRes.breakdown
            };
        }

        // SIDEBAR & NAVIGATION
        function toggleMobileSidebar(open) {
            const aside = document.getElementById('leftModuleSidebar');
            const backdrop = document.getElementById('mobileSidebarBackdrop');
            if (!aside || !backdrop) return;
            if (open) {
                aside.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                aside.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        function toggleLeftSidebar(collapse) {
            const aside = document.getElementById('leftModuleSidebar');
            const expanded = document.getElementById('sidebarExpandedContent');
            const rail = document.getElementById('sidebarCollapsedRail');
            if (!aside || !expanded || !rail) return;
            if (collapse) {
                expanded.classList.add('hidden');
                rail.classList.remove('hidden');
                rail.classList.add('flex');
                  aside.classList.replace('w-[276px]', 'w-[64px]');
            } else {
                rail.classList.add('hidden');
                rail.classList.remove('flex');
                expanded.classList.remove('hidden');
                  aside.classList.replace('w-[64px]', 'w-[276px]');
            }
        }

        function toggleSimulatorDropdownMenu() {
            const sub = document.getElementById('simulatorSubMenuContainer');
            if (sub) sub.classList.toggle('hidden');
        }

        function switchWorkspaceMode(mode, preserveCalc = false) {
            currentWorkspaceMode = mode;
            ['payroll', 'simulator', 'company', 'access', 'audit'].forEach(m => {
                document.getElementById('workspace_' + m)?.classList.toggle('hidden', m !== mode);
                document.getElementById('topModeBtn_' + m)?.classList.toggle('active', m === mode);
            });
            const titleEl = document.getElementById('topHeaderTitle');
            if (titleEl) {
                if (mode === 'payroll') titleEl.textContent = 'Master Data Pegawai & Perhitungan PPh 21 Kolektif';
                else if (mode === 'simulator') titleEl.textContent = 'Simulator Detail & Kertas Kerja PPh 21';
                else if (mode === 'company') titleEl.textContent = 'Database Perusahaan & Tempat Kegiatan Usaha (NPWP & ID TKU)';
                else if (mode === 'access') titleEl.textContent = 'Manajemen Akses Pengguna & Hak Otorisasi (RBAC)';
            }
            if (mode === 'payroll') renderEmployeePayrollTable();
            else if (mode === 'simulator') { if (!preserveCalc) calculateAll(); renderSimulatorSavedList(); }
            else if (mode === 'company') renderCompanyTable();

        }

        // COMPANY DATABASE (NPWP & 22-DIGIT ID TKU)
        function getActiveCompany() {
            return companiesData.find(c => c.id === activeCompanyId) || companiesData[0] || { name: '', npwp: '', idTkuFull: '' };
        }

        function autoFormatIdTku() {
            const npwp = (document.getElementById('compNpwpInput')?.value || '').replace(/[^0-9]/g, '').padStart(16, '0').slice(0, 16);
            const nitku = (document.getElementById('compNitkuSuffixInput')?.value || '').replace(/[^0-9]/g, '').padStart(6, '0').slice(0, 6);
            const fullEl = document.getElementById('compIdTkuFullInput');
            if (fullEl) fullEl.value = npwp + nitku;
        }

        function resetCompanyForm() {
            document.getElementById('compEditId').value = '';
            document.getElementById('compNameInput').value = '';
            document.getElementById('compBranchNameInput').value = 'Kantor Pusat (000000)';
            document.getElementById('compNpwpInput').value = '0127543973027000';
            document.getElementById('compNitkuSuffixInput').value = '000000';
            document.getElementById('compIdTkuFullInput').value = '0127543973027000000000';
        }

        function selectActiveCompany(id) {
            activeCompanyId = id;
            populateCompanySelects();
            renderCompanyTable();
            calculateAll();
            showToast('Perusahaan Pemotong Aktif berhasil diubah.');
        }

        function editCompanyRecord(id) {
            const c = companiesData.find(x => x.id === id);
            if (!c) return;
            document.getElementById('compEditId').value = c.id;
            document.getElementById('compNameInput').value = c.name || '';
            document.getElementById('compBranchNameInput').value = c.branchName || '';
            document.getElementById('compNpwpInput').value = c.npwp || '';
            document.getElementById('compNitkuSuffixInput').value = c.nitkuSuffix || '000000';
            document.getElementById('compIdTkuFullInput').value = c.idTkuFull || '';
            document.getElementById('compSignerNameInput').value = c.signerName || '';
            document.getElementById('compSignerNpwpInput').value = c.signerNpwp || '';
            document.getElementById('compCityInput').value = c.city || 'Jakarta';
            document.getElementById('compAddressInput').value = c.address || '';
        }

        function populateCompanySelects() {
            const sel = document.getElementById('activeCompanySelect');
            if (!sel) return;
            sel.innerHTML = companiesData.map(c => `<option value="${c.id}" ${c.id === activeCompanyId ? 'selected' : ''}>${htmlText(c.name)} — ${htmlText(c.branchName)} (${htmlText(c.npwp)})</option>`).join('');
        }

        function renderCompanyTable() {
            const tbody = document.getElementById('companyTableBody');
            if (!tbody) return;
            tbody.innerHTML = companiesData.map(c => `
                <tr class="${c.id === activeCompanyId ? 'bg-blue-50/50' : 'hover:bg-slate-50'}">
                    <td class="py-3 px-4">
                        <div class="font-bold text-slate-900">${htmlText(c.name)}</div>
                        <div class="text-[11px] text-slate-500">${htmlText(c.branchName)} • ${htmlText(c.city || 'Jakarta')}</div>
                    </td>
                    <td class="py-3 px-3 font-mono font-bold text-slate-800">${htmlText(c.npwp)}</td>
                    <td class="py-3 px-3 font-mono text-indigo-700 font-semibold">${htmlText(c.idTkuFull)}</td>
                    <td class="py-3 px-3 text-center">
                        ${c.id === activeCompanyId
                            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Aktif</span>'
                            : `<button type="button" onclick="selectActiveCompany('${c.id}')" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 hover:bg-blue-100 text-slate-700 cursor-pointer">Pilih</button>`}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" onclick="editCompanyRecord('${c.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-600 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-pen text-[10px]"></i></button>
                            <button type="button" onclick="deleteCompanyRecord('${c.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-trash text-[10px]"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // PAYROLL PERIOD & CALCULATION ENGINE
        function normalizeEmployeeRecord(e) {
            if (!e || typeof e !== 'object') e = {};
            return {
                _version: e._version,
                companyId: e.companyId,
                id: String(e.id || ('emp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6))),
                month: parseInt(e.month, 10) || 11,
                year: parseInt(e.year, 10) || 2026,
                name: String(e.name || 'Pegawai'),
                nik: String(e.nik || '3171000000000001').replace(/^'/, ''),
                position: String(e.position || 'Staff'),
                subjectType: String(e.subjectType || 'pegawai_bulanan'),
                ptkp: PTKP_MAP[e.ptkp] ? e.ptkp : 'K/1',
                taxMethod: ['GROSS', 'GROSS_UP', 'NETT'].includes(e.taxMethod) ? e.taxMethod : 'GROSS',
                gajiPokok: Number(e.gajiPokok) || 0,
                tunjangan: Number(e.tunjangan) || 0,
                bonus: Number(e.bonus) || 0,
                thr: Number(e.thr) || 0,
                kompensasi: Number(e.kompensasi) || 0,
                bpjs: Number(e.bpjs) || 0,
                iuranPensiun: Number(e.iuranPensiun) || 0,
                iuranJht: Number(e.iuranJht) || 0,
                workMonths: Math.min(12, Math.max(1, parseInt(e.workMonths, 10) || 12)),
                dailyScheme: String(e.dailyScheme || 'HARIAN'),
                workDays: Math.max(1, parseInt(e.workDays, 10) || 1),
                finalSubType: String(e.finalSubType || 'PESANGON'),
                hasNpwp: e.hasNpwp !== undefined ? !!e.hasNpwp : true,
                syncedFromMonthly: !!e.syncedFromMonthly,
                a1ManualRegBruto: e.a1ManualRegBruto !== undefined && e.a1ManualRegBruto !== null && e.a1ManualRegBruto !== '' ? Number(e.a1ManualRegBruto) : null,
                a1ManualIrregBruto: e.a1ManualIrregBruto !== undefined && e.a1ManualIrregBruto !== null && e.a1ManualIrregBruto !== '' ? Number(e.a1ManualIrregBruto) : null,
                a1ManualBpjsComp: e.a1ManualBpjsComp !== undefined && e.a1ManualBpjsComp !== null && e.a1ManualBpjsComp !== '' ? Number(e.a1ManualBpjsComp) : null,
                a1ManualBiayaJabatan: e.a1ManualBiayaJabatan !== undefined && e.a1ManualBiayaJabatan !== null && e.a1ManualBiayaJabatan !== '' ? Number(e.a1ManualBiayaJabatan) : null,
                a1ManualIuranPensiun: e.a1ManualIuranPensiun !== undefined && e.a1ManualIuranPensiun !== null && e.a1ManualIuranPensiun !== '' ? Number(e.a1ManualIuranPensiun) : null,
                a1ManualIuranJht: e.a1ManualIuranJht !== undefined && e.a1ManualIuranJht !== null && e.a1ManualIuranJht !== '' ? Number(e.a1ManualIuranJht) : null,
                a1ManualPriorTer: e.a1ManualPriorTer !== undefined && e.a1ManualPriorTer !== null && e.a1ManualPriorTer !== '' ? Number(e.a1ManualPriorTer) : null
            };
        }

        function calculateFinalTaxPP68(brutoInput, subType = 'PESANGON') {
            const b = Math.max(0, Number(brutoInput) || 0);
            if (subType === 'MANFAAT_PENSIUN') {
                const l1 = Math.min(b, 50000000);
                const l2 = Math.max(0, b - 50000000);
                const taxExact = l2 * 0.05;
                return {
                    totalTax: Math.round(taxExact),
                    totalTaxExact: taxExact,
                    label: b <= 50000000 ? 'Final 0%' : 'Final 5%',
                    breakdown: [
                        { layerNum: 1, label: 's.d. Rp 50.000.000 (0%)', ratePercent: 0, taxableAmountExact: l1, taxableAmount: Math.round(l1), taxAmountExact: 0, taxAmount: 0 },
                        ...(l2 > 0 ? [{ layerNum: 2, label: 'Di atas Rp 50.000.000 (5%)', ratePercent: 5, taxableAmountExact: l2, taxableAmount: Math.round(l2), taxAmountExact: taxExact, taxAmount: Math.round(taxExact) }] : [])
                    ]
                };
            }
            let rem = b, taxExact = 0;
            const brackets = [
                { cap: 50000000, rate: 0, label: 's.d. Rp 50.000.000 (0%)' },
                { cap: 50000000, rate: 0.05, label: '> Rp 50 Juta s.d. Rp 100 Juta (5%)' },
                { cap: 400000000, rate: 0.15, label: '> Rp 100 Juta s.d. Rp 500 Juta (15%)' },
                { cap: Infinity, rate: 0.25, label: 'Di atas Rp 500 Juta (25%)' }
            ];
            const breakdown = [];
            for (let i = 0; i < brackets.length; i++) {
                const br = brackets[i];
                const slice = Math.max(0, Math.min(rem, br.cap));
                const tSlice = slice * br.rate;
                taxExact += tSlice;
                rem -= slice;
                if (slice > 0 || i === 0) {
                    breakdown.push({
                        layerNum: i + 1,
                        label: br.label,
                        ratePercent: br.rate * 100,
                        taxableAmountExact: slice,
                        taxableAmount: Math.round(slice),
                        taxAmountExact: tSlice,
                        taxAmount: Math.round(tSlice)
                    });
                }
            }
            return { totalTax: Math.round(taxExact), totalTaxExact: taxExact, label: 'Final Psl 21', breakdown };
        }

        function solveGrossUpFinalPP68(netTarget, subType = 'PESANGON') {
            if (netTarget <= 0) {
                const z = calculateFinalTaxPP68(0, subType);
                return { exactBruto: 0, exactTax: 0, brutoPajak: 0, pph21: 0, tunjanganPajak: 0, label: z.label, breakdown: z.breakdown };
            }
            let B = Number(netTarget);
            for (let iter = 0; iter < 60; iter++) {
                const res = calculateFinalTaxPP68(B, subType);
                const diff = (B - res.totalTaxExact) - netTarget;
                if (Math.abs(diff) < 1e-8) break;
                let margRate = 0;
                if (subType === 'MANFAAT_PENSIUN') {
                    margRate = B > 50000000 ? 0.05 : 0;
                } else {
                    if (B <= 50000000) margRate = 0;
                    else if (B <= 100000000) margRate = 0.05;
                    else if (B <= 500000000) margRate = 0.15;
                    else margRate = 0.25;
                }
                B = B - (diff / (1 - margRate));
            }
            const finalRes = calculateFinalTaxPP68(B, subType);
            const pph21 = Math.round(finalRes.totalTaxExact);
            const brutoPajak = Math.round(netTarget + pph21);
            return {
                exactBruto: B,
                exactTax: finalRes.totalTaxExact,
                brutoPajak,
                pph21,
                tunjanganPajak: pph21,
                label: finalRes.label,
                breakdown: finalRes.breakdown
            };
        }

        function getLinkedMonthlyRecords(emp) {
            const targetYear = Number(emp.year) || 2026;
            const cleanNik = String(emp.nik || '').trim();
            const cleanName = String(emp.name || '').trim().toLowerCase();
            return employeesData.filter(m =>
                m.id !== emp.id &&
                m.subjectType === 'pegawai_bulanan' &&
                Number(m.year) === targetYear &&
                Number(m.month) >= 1 && Number(m.month) <= 11 &&
                ((cleanNik && cleanNik !== '3171000000000001' && String(m.nik || '').trim() === cleanNik) ||
                 String(m.name || '').trim().toLowerCase() === cleanName)
            );
        }

        function computeEmployeeTaxRow(emp) {
            const ptkpObj = PTKP_MAP[emp.ptkp] || PTKP_MAP['K/1'];
            const npwpMultiplier = emp.hasNpwp ? 1.0 : 1.2;
            const gaji = Number(emp.gajiPokok) || 0;
            const tunj = Number(emp.tunjangan) || 0;
            const bonus = Number(emp.bonus) || 0;
            const thr = Number(emp.thr) || 0;
            const kompensasi = Number(emp.kompensasi) || 0;
            const bpjsComp = Number(emp.bpjs) || 0;
            const iuranPensiun = Number(emp.iuranPensiun) || 0;
            const iuranJht = Number(emp.iuranJht) || 0;
            const workMonths = Math.min(12, Math.max(1, parseInt(emp.workMonths, 10) || 12));
            const isAnnualTarget = payrollPeriodMode === 'TAHUNAN' || Number(emp.month) === 12 || emp.subjectType === 'pegawai_tahunan' || (currentWorkspaceMode === 'simulator' && currentSubject === 'pegawai_tahunan');
            // Pegawai Bulanan (TER) murni dari Penghasilan Bruto tanpa potongan JHT/Pensiun; pengurang hanya di Masa Terakhir (A1)
            const bpjsEmpAll = isAnnualTarget ? (iuranPensiun + iuranJht) : 0;
            const cashIncome = gaji + tunj + bonus + thr + kompensasi;

            let brutoPajak = cashIncome + bpjsComp, tunjanganPajak = 0, pph21 = 0, rateNumeric = 0, rateLabel = '';
            let isAnnualA1 = false, priorMonths = 0, annualRegularBruto = 0, annualIrregularBruto = 0, annualBpjsComp = 0, annualBruto = 0;
            let biayaJabatan = 0, annualIuranPensiun = 0, annualIuranJht = 0, totalPengurang = 0, annualNeto = 0, ptkpAmount = ptkpObj.amount, pkpSetahun = 0;
            let pasal17Breakdown = [], pph21Setahun = 0, priorTer = 0, linkedMonthlyCount = 0;
            let dppAmount = 0, exactBruto = brutoPajak, exactDpp = 0, exactTax = 0, netBeforeTax = cashIncome;

            if (emp.subjectType === 'bukan_pegawai') {
                // Bukan Pegawai / Tenaga Ahli: DPP = 50% x Penghasilan Bruto x Tarif Pasal 17 (tanpa pembulatan ribuan pada DPP)
                const netHonorarium = Math.max(0, gaji - tunj);
                netBeforeTax = netHonorarium;
                if (emp.taxMethod === 'GROSS_UP') {
                    const gu = solveGrossUpDpp50Pasal17(netHonorarium, npwpMultiplier);
                    exactBruto = gu.exactBruto;
                    exactDpp = gu.exactDpp;
                    exactTax = gu.exactTax;
                    brutoPajak = gu.brutoPajak;
                    dppAmount = gu.dpp;
                    pph21 = gu.pph21;
                    tunjanganPajak = gu.tunjanganPajak;
                    pasal17Breakdown = gu.breakdown;
                } else {
                    exactBruto = netHonorarium;
                    exactDpp = 0.5 * exactBruto;
                    const p17 = calculatePasal17(exactDpp, npwpMultiplier, false);
                    exactTax = p17.totalTaxExact;
                    brutoPajak = Math.round(exactBruto);
                    dppAmount = p17.pkp;
                    pph21 = p17.totalTax;
                    pasal17Breakdown = p17.breakdown;
                }
                rateNumeric = brutoPajak > 0 ? Number(((pph21 / brutoPajak) * 100).toFixed(2)) : 0;
                rateLabel = '50% × Psl 17';
            } else if (emp.subjectType === 'pesangon_final') {
                // Pesangon / JHT / Pensiun Sekaligus (PP 68/2009)
                const baseFinal = gaji + tunj;
                netBeforeTax = baseFinal;
                if (emp.taxMethod === 'GROSS_UP') {
                    const guFinal = solveGrossUpFinalPP68(baseFinal, emp.finalSubType || 'PESANGON');
                    exactBruto = guFinal.exactBruto;
                    exactTax = guFinal.exactTax;
                    brutoPajak = guFinal.brutoPajak;
                    pph21 = guFinal.pph21;
                    tunjanganPajak = guFinal.tunjanganPajak;
                    pasal17Breakdown = guFinal.breakdown;
                    rateLabel = guFinal.label;
                } else {
                    const resFinal = calculateFinalTaxPP68(baseFinal, emp.finalSubType || 'PESANGON');
                    exactBruto = baseFinal;
                    exactTax = resFinal.totalTaxExact;
                    brutoPajak = baseFinal;
                    pph21 = resFinal.totalTax;
                    pasal17Breakdown = resFinal.breakdown;
                    rateLabel = resFinal.label;
                }
                rateNumeric = brutoPajak > 0 ? Number(((pph21 / brutoPajak) * 100).toFixed(2)) : 0;
            } else if (emp.subjectType === 'tidak_tetap') {
                // Pegawai Tidak Tetap / Harian (PP 58/2023)
                const scheme = emp.dailyScheme || 'HARIAN';
                const days = Math.max(1, parseInt(emp.workDays, 10) || 1);
                if (scheme === 'HARIAN') {
                    const totalDailyIncome = (gaji * days) + tunj;
                    netBeforeTax = totalDailyIncome;
                    const avgDaily = totalDailyIncome / days;
                    if (avgDaily <= 450000) {
                        brutoPajak = totalDailyIncome;
                        exactBruto = totalDailyIncome;
                        rateNumeric = 0;
                        pph21 = 0;
                        rateLabel = 'TER Harian 0%';
                    } else if (avgDaily <= 2500000) {
                        rateNumeric = 0.5 * npwpMultiplier;
                        const effRate = 0.005 * npwpMultiplier;
                        if (emp.taxMethod === 'GROSS_UP') {
                            exactBruto = totalDailyIncome / (1 - effRate);
                            exactTax = exactBruto - totalDailyIncome;
                            pph21 = Math.round(exactTax);
                            tunjanganPajak = pph21;
                            brutoPajak = totalDailyIncome + tunjanganPajak;
                        } else {
                            exactBruto = totalDailyIncome;
                            exactTax = totalDailyIncome * effRate;
                            brutoPajak = totalDailyIncome;
                            pph21 = Math.floor(exactTax);
                        }
                        rateLabel = 'TER Harian 0,5%';
                    } else {
                        if (emp.taxMethod === 'GROSS_UP') {
                            const gu = solveGrossUpDpp50Pasal17(totalDailyIncome, npwpMultiplier);
                            exactBruto = gu.exactBruto;
                            exactDpp = gu.exactDpp;
                            exactTax = gu.exactTax;
                            brutoPajak = gu.brutoPajak;
                            dppAmount = gu.dpp;
                            pph21 = gu.pph21;
                            tunjanganPajak = gu.tunjanganPajak;
                            pasal17Breakdown = gu.breakdown;
                        } else {
                            exactBruto = totalDailyIncome;
                            exactDpp = 0.5 * totalDailyIncome;
                            const p17 = calculatePasal17(exactDpp, npwpMultiplier, false);
                            exactTax = p17.totalTaxExact;
                            brutoPajak = totalDailyIncome;
                            dppAmount = p17.pkp;
                            pph21 = p17.totalTax;
                            pasal17Breakdown = p17.breakdown;
                        }
                        rateNumeric = brutoPajak > 0 ? Number(((pph21 / brutoPajak) * 100).toFixed(2)) : 0;
                        rateLabel = '50% × Psl 17';
                    }
                } else {
                    const baseBruto = gaji + tunj;
                    netBeforeTax = baseBruto;
                    if (emp.taxMethod === 'GROSS_UP') {
                        const gu = solveTerGrossUp(baseBruto, ptkpObj.terCat, npwpMultiplier);
                        tunjanganPajak = gu.tunjanganPajak;
                        brutoPajak = gu.finalBruto;
                        exactBruto = gu.exactBruto;
                        exactTax = gu.exactTax;
                        pph21 = tunjanganPajak;
                        rateNumeric = gu.finalBracket.rate;
                        rateLabel = `TER ${ptkpObj.terCat} (${gu.finalBracket.rate}%)`;
                    } else {
                        const b = lookupTerBracket(ptkpObj.terCat, baseBruto);
                        brutoPajak = baseBruto;
                        exactBruto = baseBruto;
                        rateNumeric = b.rate;
                        exactTax = baseBruto * (b.rate / 100) * npwpMultiplier;
                        pph21 = Math.floor(exactTax);
                        rateLabel = `TER ${ptkpObj.terCat} (${b.rate}%)`;
                    }
                }
            } else if (payrollPeriodMode === 'TAHUNAN' || Number(emp.month) === 12 || emp.subjectType === 'pegawai_tahunan' || (currentWorkspaceMode === 'simulator' && currentSubject === 'pegawai_tahunan')) {
                isAnnualA1 = true;
                priorMonths = Math.max(0, workMonths - 1);
                const regBruto = gaji + tunj + kompensasi + bpjsComp;
                const regBracket = lookupTerBracket(ptkpObj.terCat, regBruto);

                const linkedMonthly = getLinkedMonthlyRecords(emp);
                linkedMonthlyCount = linkedMonthly.length;

                if (linkedMonthlyCount > 0) {
                    let recordedRegBruto = 0, recordedIrregBruto = 0, recordedBpjs = 0, recordedPriorTer = 0;
                    let recordedPensiun = 0, recordedJht = 0;

                    linkedMonthly.forEach(mRec => {
                        const mGaji = Number(mRec.gajiPokok) || 0;
                        const mTunj = Number(mRec.tunjangan) || 0;
                        const mKomp = Number(mRec.kompensasi) || 0;
                        const mBonus = Number(mRec.bonus) || 0;
                        const mThr = Number(mRec.thr) || 0;
                        const mBpjs = Number(mRec.bpjs) || 0;
                        const mBaseBruto = mGaji + mTunj + mKomp + mBonus + mThr + mBpjs;
                        const mPtkp = PTKP_MAP[mRec.ptkp] || ptkpObj;
                        const mMult = mRec.hasNpwp !== false ? 1.0 : 1.2;

                        let mTerTax = 0;
                        if (mRec.taxMethod === 'GROSS_UP') {
                            mTerTax = solveTerGrossUp(mBaseBruto, mPtkp.terCat, mMult).tunjanganPajak;
                        } else {
                            const mb = lookupTerBracket(mPtkp.terCat, mBaseBruto);
                            mTerTax = Math.floor(mBaseBruto * (mb.rate / 100) * mMult);
                        }

                        recordedRegBruto += (mGaji + mTunj + mKomp);
                        recordedIrregBruto += (mBonus + mThr);
                        recordedBpjs += mBpjs;
                        recordedPriorTer += mTerTax;
                        recordedPensiun += (Number(mRec.iuranPensiun) || iuranPensiun);
                        recordedJht += (Number(mRec.iuranJht) || iuranJht);
                    });

                    const usedRecordedMonths = Math.min(linkedMonthlyCount, priorMonths);
                    const remainingPriorMonths = Math.max(0, priorMonths - usedRecordedMonths);
                    const singleMonthTer = Math.floor(regBruto * (regBracket.rate / 100) * npwpMultiplier);

                    priorTer = recordedPriorTer + (singleMonthTer * remainingPriorMonths);
                    annualRegularBruto = recordedRegBruto + ((gaji + tunj + kompensasi) * (remainingPriorMonths + 1));
                    annualIrregularBruto = recordedIrregBruto + bonus + thr;
                    annualBpjsComp = recordedBpjs + (bpjsComp * (remainingPriorMonths + 1));
                    annualIuranPensiun = recordedPensiun + (iuranPensiun * (remainingPriorMonths + 1));
                    annualIuranJht = recordedJht + (iuranJht * (remainingPriorMonths + 1));
                } else {
                    priorTer = Math.floor(regBruto * (regBracket.rate / 100) * npwpMultiplier) * priorMonths;
                    annualRegularBruto = (gaji + tunj + kompensasi) * workMonths;
                    annualIrregularBruto = bonus + thr;
                    annualBpjsComp = bpjsComp * workMonths;
                    annualIuranPensiun = iuranPensiun * workMonths;
                    annualIuranJht = iuranJht * workMonths;
                }

                // Terapkan hasil edit manual pada Kertas Kerja A1 jika diisi oleh pengguna
                if (emp.a1ManualRegBruto !== null && emp.a1ManualRegBruto !== undefined) annualRegularBruto = Number(emp.a1ManualRegBruto) || 0;
                if (emp.a1ManualIrregBruto !== null && emp.a1ManualIrregBruto !== undefined) annualIrregularBruto = Number(emp.a1ManualIrregBruto) || 0;
                if (emp.a1ManualBpjsComp !== null && emp.a1ManualBpjsComp !== undefined) annualBpjsComp = Number(emp.a1ManualBpjsComp) || 0;

                annualBruto = annualRegularBruto + annualIrregularBruto + annualBpjsComp;

                const maxBiayaJab = workMonths * 500000;
                biayaJabatan = (emp.a1ManualBiayaJabatan !== null && emp.a1ManualBiayaJabatan !== undefined)
                    ? Number(emp.a1ManualBiayaJabatan) || 0
                    : Math.min(Math.floor(annualBruto * 0.05), maxBiayaJab);

                if (emp.a1ManualIuranPensiun !== null && emp.a1ManualIuranPensiun !== undefined) annualIuranPensiun = Number(emp.a1ManualIuranPensiun) || 0;
                if (emp.a1ManualIuranJht !== null && emp.a1ManualIuranJht !== undefined) annualIuranJht = Number(emp.a1ManualIuranJht) || 0;
                if (emp.a1ManualPriorTer !== null && emp.a1ManualPriorTer !== undefined) priorTer = Number(emp.a1ManualPriorTer) || 0;

                const baseAnnualBruto = annualRegularBruto + annualIrregularBruto + annualBpjsComp;

                if (emp.taxMethod === 'GROSS_UP') {
                    // Iterasi Gross Up Pasal 17 Tahunan (1721-A1)
                    let allowanceA1 = 0;
                    for (let iter = 0; iter < 50; iter++) {
                        const testAnnualBruto = baseAnnualBruto + allowanceA1;
                        const testBiayaJab = (emp.a1ManualBiayaJabatan !== null && emp.a1ManualBiayaJabatan !== undefined)
                            ? Number(emp.a1ManualBiayaJabatan) || 0
                            : Math.min(Math.floor(testAnnualBruto * 0.05), maxBiayaJab);
                        const testPengurang = testBiayaJab + annualIuranPensiun + annualIuranJht;
                        const testNeto = Math.max(0, testAnnualBruto - testPengurang);
                        const testPkp = Math.max(0, Math.floor((testNeto - ptkpObj.amount) / 1000) * 1000);
                        const testTaxSetahun = calculatePasal17(testPkp, npwpMultiplier, true).totalTax;
                        const nextAllowance = Math.max(0, testTaxSetahun - priorTer);
                        if (Math.abs(nextAllowance - allowanceA1) <= 1) {
                            allowanceA1 = nextAllowance;
                            break;
                        }
                        allowanceA1 = nextAllowance;
                    }
                    tunjanganPajak = allowanceA1;
                    annualBruto = baseAnnualBruto + tunjanganPajak;
                    biayaJabatan = (emp.a1ManualBiayaJabatan !== null && emp.a1ManualBiayaJabatan !== undefined)
                        ? Number(emp.a1ManualBiayaJabatan) || 0
                        : Math.min(Math.floor(annualBruto * 0.05), maxBiayaJab);
                }

                totalPengurang = biayaJabatan + annualIuranPensiun + annualIuranJht;

                annualNeto = Math.max(0, annualBruto - totalPengurang);
                ptkpAmount = ptkpObj.amount;
                pkpSetahun = Math.max(0, Math.floor((annualNeto - ptkpAmount) / 1000) * 1000);

                const p17Res = calculatePasal17(pkpSetahun, npwpMultiplier, true);
                pph21Setahun = p17Res.totalTax;
                pasal17Breakdown = p17Res.breakdown;

                pph21 = pph21Setahun - priorTer;
                if (emp.taxMethod === 'GROSS_UP' && pph21 > 0) {
                    tunjanganPajak = pph21;
                }
                brutoPajak = cashIncome + bpjsComp + tunjanganPajak;
                exactBruto = brutoPajak;
                exactTax = pph21;
                rateNumeric = regBracket.rate;
                rateLabel = 'Psl 17 (A1)';
            } else {
                const baseBruto = cashIncome + bpjsComp;
                netBeforeTax = cashIncome;
                if (emp.taxMethod === 'GROSS_UP') {
                    const gu = solveTerGrossUp(baseBruto, ptkpObj.terCat, npwpMultiplier);
                    tunjanganPajak = gu.tunjanganPajak;
                    brutoPajak = gu.finalBruto;
                    exactBruto = gu.exactBruto;
                    exactTax = gu.exactTax;
                    pph21 = tunjanganPajak;
                    rateNumeric = gu.finalBracket.rate;
                    rateLabel = `TER ${ptkpObj.terCat} (${gu.finalBracket.rate}%)`;
                } else {
                    const b = lookupTerBracket(ptkpObj.terCat, baseBruto);
                    brutoPajak = baseBruto;
                    exactBruto = baseBruto;
                    rateNumeric = b.rate;
                    exactTax = baseBruto * (b.rate / 100) * npwpMultiplier;
                    pph21 = Math.floor(exactTax);
                    rateLabel = `TER ${ptkpObj.terCat} (${b.rate}%)`;
                }
            }

            const effectiveCashReceived = emp.subjectType === 'bukan_pegawai'
                ? Math.max(0, gaji - tunj)
                : (emp.subjectType === 'tidak_tetap' && (emp.dailyScheme || 'HARIAN') === 'HARIAN'
                    ? (gaji * Math.max(1, parseInt(emp.workDays, 10) || 1)) + tunj
                    : cashIncome);

            const thp = effectiveCashReceived - bpjsEmpAll - (emp.taxMethod === 'GROSS' ? pph21 : 0);
            return {
                terCat: ptkpObj.terCat, gaji, tunj, bonus, thr, kompensasi, bpjsComp,
                iuranPensiun, iuranJht, workMonths, bpjsEmpAll, tunjanganPajak, brutoPajak,
                rateNumeric, rateLabel, pph21, thp,
                dppAmount, exactBruto, exactDpp, exactTax, netBeforeTax: effectiveCashReceived,
                isAnnualA1, priorMonths, annualRegularBruto, annualIrregularBruto, annualBpjsComp,
                annualBruto, biayaJabatan, annualIuranPensiun, annualIuranJht, totalPengurang,
                annualNeto, ptkpAmount, pkpSetahun, pasal17Breakdown, pph21Setahun, priorTer, linkedMonthlyCount
            };
        }

        function onDailySchemeChange() {
            const scheme = document.getElementById('modalEmpDailyScheme')?.value || 'HARIAN';
            const wrapDays = document.getElementById('wrapModalWorkDays');
            const lblGaji = document.getElementById('lblModalGajiPokok');
            const lblTunj = document.getElementById('lblModalTunjangan');
            if (scheme === 'HARIAN') {
                wrapDays?.classList.remove('hidden');
                if (lblGaji) lblGaji.textContent = 'Upah Harian / Satuan (Per Hari)';
                if (lblTunj) lblTunj.textContent = 'Tambahan Lembur / Insentif Harian';
            } else {
                wrapDays?.classList.add('hidden');
                if (lblGaji) lblGaji.textContent = 'Total Upah Bulanan';
                if (lblTunj) lblTunj.textContent = 'Tunjangan / Insentif Tidak Tetap';
            }
        }

        function configureModalFieldsByModule(subjType) {
            const subj = subjType || 'pegawai_bulanan';
            const selSubj = document.getElementById('modalEmpSubjectType');
            if (selSubj && selSubj.value !== subj) selSubj.value = subj;
            document.getElementById('manualEmployeeModal').dataset.subjectType = subj;

            const titleEl = document.getElementById('modalFormMainTitle');
            const subTitleEl = document.getElementById('modalFormSubTitle');
            const lblName = document.getElementById('lblModalEmpName');
            const lblPos = document.getElementById('lblModalEmpPosition');
            const wrapPtkp = document.getElementById('wrapModalPtkp');
            const wrapTidakTetap = document.getElementById('wrapModalTidakTetapOptions');
            const wrapFinal = document.getElementById('wrapModalFinalOptions');

            const wrapRow1 = document.getElementById('wrapModalIncomeRow1');
            const wrapRow2 = document.getElementById('wrapModalIncomeRow2');
            const wrapGaji = document.getElementById('wrapModalGajiPokok');
            const wrapTunj = document.getElementById('wrapModalTunjangan');
            const wrapBonus = document.getElementById('wrapModalBonus');
            const wrapThr = document.getElementById('wrapModalThr');
            const wrapKomp = document.getElementById('wrapModalKompensasi');
            const wrapBpjs = document.getElementById('wrapModalBpjs');

            const lblGaji = document.getElementById('lblModalGajiPokok');
            const lblTunj = document.getElementById('lblModalTunjangan');
            const lblBonus = document.getElementById('lblModalBonus');
            const lblThr = document.getElementById('lblModalThr');
            const lblKomp = document.getElementById('lblModalKompensasi');
            const lblBpjs = document.getElementById('lblModalBpjs');

            const wrapDedRow = document.getElementById('wrapModalDeductionsRow');
            const wrapPensiun = document.getElementById('wrapModalIuranPensiun');
            const wrapJht = document.getElementById('wrapModalIuranJht');
            const wrapMonths = document.getElementById('wrapModalWorkMonths');

            wrapPtkp?.classList.remove('hidden');
            wrapTidakTetap?.classList.add('hidden');
            wrapFinal?.classList.add('hidden');
            wrapRow1?.classList.remove('hidden');
            wrapRow2?.classList.remove('hidden');
            wrapGaji?.classList.remove('hidden');
            wrapTunj?.classList.remove('hidden');
            wrapBonus?.classList.remove('hidden');
            wrapThr?.classList.remove('hidden');
            wrapKomp?.classList.remove('hidden');
            wrapBpjs?.classList.remove('hidden');
            wrapDedRow?.classList.remove('hidden');
            wrapPensiun?.classList.remove('hidden');
            wrapJht?.classList.remove('hidden');
            wrapMonths?.classList.remove('hidden');
            if (wrapRow1) wrapRow1.className = 'grid grid-cols-3 gap-3';
            if (wrapDedRow) wrapDedRow.className = 'grid grid-cols-3 gap-3 p-3 rounded-xl bg-indigo-50/60 border border-indigo-200/80';

            if (subj === 'pegawai_bulanan') {
                titleEl.textContent = 'Form Pegawai Tetap Bulanan (Skema TER)';
                subTitleEl.textContent = 'Perhitungan TER langsung dari Penghasilan Bruto (Tanpa pengurang Biaya Jabatan / Pensiun / JHT)';
                lblName.textContent = 'Nama Pegawai Tetap';
                lblPos.textContent = 'Jabatan / Divisi';
                lblGaji.textContent = 'Gaji Pokok';
                lblTunj.textContent = 'Tunjangan Tetap';
                lblBonus.textContent = 'Bonus / Jasa Produksi';
                lblThr.textContent = 'THR / Gaji Ke-13';
                lblKomp.textContent = 'Kompensasi / Lembur';
                lblBpjs.textContent = 'Premi BPJS (Perusahaan)';
                wrapDedRow?.classList.add('hidden');
            } else if (subj === 'pegawai_tahunan') {
                titleEl.textContent = 'Form Masa Pajak Terakhir — Desember / Resign (1721-A1)';
                subTitleEl.textContent = 'Rekonsiliasi tahunan Pasal 17 dengan pengurang Biaya Jabatan, Iuran Pensiun, JHT & PTKP';
                lblName.textContent = 'Nama Pegawai Tetap';
                lblPos.textContent = 'Jabatan';
                lblGaji.textContent = 'Gaji Pokok (Desember)';
                lblTunj.textContent = 'Tunjangan (Desember)';
                lblBonus.textContent = 'Bonus Setahun';
                lblThr.textContent = 'THR Setahun';
                lblKomp.textContent = 'Kompensasi / Lembur';
                lblBpjs.textContent = 'Premi BPJS /bln';
                document.getElementById('modalEmpMonth').value = '12';
            } else if (subj === 'pensiunan') {
                titleEl.textContent = 'Form Pensiunan Berkala (Uang Pensiun Bulanan)';
                subTitleEl.textContent = 'Perhitungan PPh 21 atas uang pensiun berkala sesuai TER Kategori A/B/C';
                lblName.textContent = 'Nama Penerima Pensiun';
                lblPos.textContent = 'Status Pensiunan';
                lblGaji.textContent = 'Uang Pensiun Pokok';
                lblTunj.textContent = 'Tunjangan Pensiun / Beras';
                lblBonus.textContent = 'Uang Pensiun Ke-13 / Lainnya';
                wrapRow2?.classList.add('hidden');
                wrapDedRow?.classList.add('hidden');
            } else if (subj === 'tidak_tetap') {
                titleEl.textContent = 'Form Pegawai Tidak Tetap / Tenaga Kerja Lepas';
                subTitleEl.textContent = 'Upah Harian / Satuan / Borongan (TER Harian 0% / 0,5% atau 50% × Pasal 17)';
                lblName.textContent = 'Nama Pekerja Harian / Lepas';
                lblPos.textContent = 'Jenis Pekerjaan / Proyek';
                wrapTidakTetap?.classList.remove('hidden');
                if (wrapRow1) wrapRow1.className = 'grid grid-cols-2 gap-3';
                wrapBonus?.classList.add('hidden');
                wrapRow2?.classList.add('hidden');
                wrapDedRow?.classList.add('hidden');
                onDailySchemeChange();
            } else if (subj === 'bukan_pegawai') {
                titleEl.textContent = 'Form Bukan Pegawai / Tenaga Ahli / Freelancer';
                subTitleEl.textContent = 'Formula: DPP = 50% × Penghasilan Bruto × Tarif Progresif Pasal 17 (Tanpa PTKP)';
                lblName.textContent = 'Nama Tenaga Ahli / Pemberi Jasa';
                lblPos.textContent = 'Profesi / Jenis Jasa (Dokter, Konsultan, dll)';
                wrapPtkp?.classList.add('hidden');
                if (wrapRow1) wrapRow1.className = 'grid grid-cols-2 gap-3';
                lblGaji.textContent = 'Jumlah Honorarium / Fee / Komisi Bruto';
                lblTunj.textContent = 'Biaya Materi / Penggantian (Non-Jasa)';
                wrapBonus?.classList.add('hidden');
                wrapRow2?.classList.add('hidden');
                wrapDedRow?.classList.add('hidden');
            } else if (subj === 'pesangon_final') {
                titleEl.textContent = 'Form Pesangon / Manfaat Pensiun / JHT Sekaligus (Final)';
                subTitleEl.textContent = 'Pemotongan PPh Pasal 21 bersifat Final sesuai PP 68 Tahun 2009';
                lblName.textContent = 'Nama Penerima Pesangon / JHT';
                lblPos.textContent = 'Jabatan Terakhir';
                wrapPtkp?.classList.add('hidden');
                wrapFinal?.classList.remove('hidden');
                if (wrapRow1) wrapRow1.className = 'grid grid-cols-2 gap-3';
                lblGaji.textContent = 'Uang Pesangon / JHT / Pensiun Sekaligus';
                lblTunj.textContent = 'Uang Penghargaan Masa Kerja / Penggantian Hak';
                wrapBonus?.classList.add('hidden');
                wrapRow2?.classList.add('hidden');
                wrapDedRow?.classList.add('hidden');
            }
        }

        function openAddEmployeeModal(subjectTypeOverride) {
            document.getElementById('modalEmpEditId').value = '';
            document.getElementById('modalEmpName').value = '';
            const isSim = currentWorkspaceMode === 'simulator';
            const targetSubj = subjectTypeOverride || (isSim ? currentSubject : (payrollPeriodMode === 'TAHUNAN' ? 'pegawai_tahunan' : 'pegawai_bulanan'));
            const m = isSim ? document.getElementById('simPeriodMonth')?.value : document.getElementById('payrollPeriodMonth')?.value;
            if (m && m !== 'ALL') document.getElementById('modalEmpMonth').value = m;
            else if (targetSubj === 'pegawai_tahunan') document.getElementById('modalEmpMonth').value = '12';
            const y = isSim ? document.getElementById('simPeriodYear')?.value : document.getElementById('payrollPeriodYear')?.value;
            if (y) document.getElementById('modalEmpYear').value = y;

            if (targetSubj === 'tidak_tetap') {
                document.getElementById('modalEmpPosition').value = 'Pekerja Harian';
                setInputValue('modalEmpGajiPokok', 400000);
                setInputValue('modalEmpTunjangan', 0);
                document.getElementById('modalEmpWorkDays').value = '1';
                document.getElementById('modalEmpDailyScheme').value = 'HARIAN';
            } else if (targetSubj === 'bukan_pegawai') {
                document.getElementById('modalEmpPosition').value = 'Konsultan / Tenaga Ahli';
                setInputValue('modalEmpGajiPokok', 25000000);
                setInputValue('modalEmpTunjangan', 0);
            } else if (targetSubj === 'pesangon_final') {
                document.getElementById('modalEmpPosition').value = 'Karyawan Purna Tugas';
                setInputValue('modalEmpGajiPokok', 120000000);
                setInputValue('modalEmpTunjangan', 0);
                document.getElementById('modalEmpFinalSubType').value = 'PESANGON';
            } else if (targetSubj === 'pensiunan') {
                document.getElementById('modalEmpPosition').value = 'Pensiunan Berkala';
                setInputValue('modalEmpGajiPokok', 8500000);
                setInputValue('modalEmpTunjangan', 500000);
            } else {
                document.getElementById('modalEmpPosition').value = 'Staff';
                setInputValue('modalEmpGajiPokok', 12000000);
                setInputValue('modalEmpTunjangan', 1500000);
            }
            setInputValue('modalEmpBonus', 0);
            setInputValue('modalEmpThr', 0);
            setInputValue('modalEmpKompensasi', 0);
            setInputValue('modalEmpBpjs', 0);
            setInputValue('modalEmpIuranPensiun', 0);
            setInputValue('modalEmpIuranJht', 0);
            document.getElementById('modalEmpWorkMonths').value = '12';

            configureModalFieldsByModule(targetSubj);
            document.getElementById('manualEmployeeModal').classList.remove('hidden');
        }

        function closeManualEmployeeModal() {
            document.getElementById('manualEmployeeModal').classList.add('hidden');
        }

        function editEmployee(id) {
            const emp = employeesData.find(x => x.id === id);
            if (!emp) return;
            const subj = emp.subjectType || 'pegawai_bulanan';
            document.getElementById('modalEmpEditId').value = emp.id;
            document.getElementById('modalEmpMonth').value = String(emp.month || 11);
            document.getElementById('modalEmpYear').value = String(emp.year || 2026);
            document.getElementById('modalEmpName').value = emp.name;
            document.getElementById('modalEmpPosition').value = emp.position;
            document.getElementById('modalEmpNik').value = emp.nik;
            document.getElementById('modalEmpPtkp').value = emp.ptkp;
            document.getElementById('modalEmpMethod').value = emp.taxMethod;
            document.getElementById('modalEmpDailyScheme').value = emp.dailyScheme || 'HARIAN';
            document.getElementById('modalEmpWorkDays').value = String(emp.workDays || 1);
            document.getElementById('modalEmpFinalSubType').value = emp.finalSubType || 'PESANGON';
            setInputValue('modalEmpGajiPokok', emp.gajiPokok);
            setInputValue('modalEmpTunjangan', emp.tunjangan);
            setInputValue('modalEmpBonus', emp.bonus);
            setInputValue('modalEmpThr', emp.thr);
            setInputValue('modalEmpKompensasi', emp.kompensasi);
            setInputValue('modalEmpBpjs', emp.bpjs || 0);
            setInputValue('modalEmpIuranPensiun', emp.iuranPensiun || 0);
            setInputValue('modalEmpIuranJht', emp.iuranJht || 0);
            document.getElementById('modalEmpWorkMonths').value = String(emp.workMonths || 12);

            configureModalFieldsByModule(subj);
            document.getElementById('manualEmployeeModal').classList.remove('hidden');
        }

        function saveManualEmployeeSubmit() {
            const editId = document.getElementById('modalEmpEditId').value;
            const subjType = document.getElementById('modalEmpSubjectType')?.value || document.getElementById('manualEmployeeModal').dataset.subjectType || 'pegawai_bulanan';
            const monthVal = parseInt(document.getElementById('modalEmpMonth').value, 10) || (subjType === 'pegawai_tahunan' ? 12 : 11);

            const isFullMonthlyOrAnnual = subjType === 'pegawai_bulanan' || subjType === 'pegawai_tahunan';
            const isAnnualA1Only = subjType === 'pegawai_tahunan';
            const rec = normalizeEmployeeRecord({
                id: editId || ('emp_' + Date.now()),
                month: monthVal,
                year: parseInt(document.getElementById('modalEmpYear').value, 10) || 2026,
                name: document.getElementById('modalEmpName').value.trim() || 'Subjek Pajak Baru',
                position: document.getElementById('modalEmpPosition').value.trim() || 'Staff',
                nik: document.getElementById('modalEmpNik').value.trim() || '3171000000000001',
                subjectType: subjType,
                ptkp: document.getElementById('modalEmpPtkp').value,
                taxMethod: document.getElementById('modalEmpMethod').value,
                dailyScheme: document.getElementById('modalEmpDailyScheme')?.value || 'HARIAN',
                workDays: parseInt(document.getElementById('modalEmpWorkDays')?.value || '1', 10) || 1,
                finalSubType: document.getElementById('modalEmpFinalSubType')?.value || 'PESANGON',
                gajiPokok: parseCurrency(document.getElementById('modalEmpGajiPokok').value),
                tunjangan: parseCurrency(document.getElementById('modalEmpTunjangan').value),
                bonus: (isFullMonthlyOrAnnual || subjType === 'pensiunan') ? parseCurrency(document.getElementById('modalEmpBonus').value) : 0,
                thr: isFullMonthlyOrAnnual ? parseCurrency(document.getElementById('modalEmpThr').value) : 0,
                kompensasi: isFullMonthlyOrAnnual ? parseCurrency(document.getElementById('modalEmpKompensasi').value) : 0,
                bpjs: isFullMonthlyOrAnnual ? parseCurrency(document.getElementById('modalEmpBpjs').value) : 0,
                iuranPensiun: isAnnualA1Only ? parseCurrency(document.getElementById('modalEmpIuranPensiun').value) : 0,
                iuranJht: isAnnualA1Only ? parseCurrency(document.getElementById('modalEmpIuranJht').value) : 0,
                workMonths: isAnnualA1Only ? (parseInt(document.getElementById('modalEmpWorkMonths').value, 10) || 12) : 12,
                hasNpwp: true
            });
            if (editId) {
                const idx = employeesData.findIndex(x => x.id === editId);
                if (idx !== -1) employeesData[idx] = rec;
            } else {
                employeesData.unshift(rec);
            }
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            closeManualEmployeeModal();
            showToast(`Data "${rec.name}" (${SUBJECT_TITLES[subjType] || subjType}) berhasil disimpan.`);
        }

        function pullFromMonthlyEmployees(silent = false) {
            const isSim = currentWorkspaceMode === 'simulator';
            const targetYear = parseInt((isSim ? document.getElementById('simPeriodYear')?.value : document.getElementById('payrollPeriodYear')?.value) || '2026', 10);

            // Ambil semua pegawai dari modul Pegawai Tetap Bulanan pada tahun aktif
            const monthlyList = employeesData.filter(e =>
                e.subjectType === 'pegawai_bulanan' &&
                Number(e.year) === targetYear &&
                Number(e.month) >= 1 && Number(e.month) <= 11
            );

            if (monthlyList.length === 0) {
                if (!silent) showToast(`Tidak ditemukan data di modul Pegawai Tetap Bulanan untuk tahun ${targetYear}.`);
                return 0;
            }

            // Kelompokkan berdasarkan NIK atau Nama agar tiap pegawai unik
            const groupedMap = new Map();
            monthlyList.forEach(emp => {
                const key = (emp.nik && emp.nik !== '3171000000000001') ? String(emp.nik).trim() : String(emp.name).trim().toLowerCase();
                if (!groupedMap.has(key)) {
                    groupedMap.set(key, []);
                }
                groupedMap.get(key).push(emp);
            });

            let addedCount = 0;
            let updatedCount = 0;

            groupedMap.forEach((records) => {
                // Urutkan dari bulan terbaru (misal Nov -> Jan) sebagai acuan gaji Desember
                records.sort((a, b) => Number(b.month) - Number(a.month));
                const latest = records[0];

                // Cek apakah sudah ada baris Desember / pegawai_tahunan untuk pegawai ini di tahun yang sama
                const existingIdx = employeesData.findIndex(e =>
                    (e.subjectType === 'pegawai_tahunan' || Number(e.month) === 12) &&
                    Number(e.year) === targetYear &&
                    (((latest.nik && latest.nik !== '3171000000000001') && String(e.nik).trim() === String(latest.nik).trim()) ||
                     String(e.name).trim().toLowerCase() === String(latest.name).trim().toLowerCase())
                );

                const defaultIuranPensiun = Number(latest.iuranPensiun) > 0 ? Number(latest.iuranPensiun) : 100000;
                const defaultIuranJht = Number(latest.iuranJht) > 0 ? Number(latest.iuranJht) : Math.round((Number(latest.gajiPokok) || 0) * 0.02);

                if (existingIdx !== -1) {
                    const curr = employeesData[existingIdx];
                    employeesData[existingIdx] = normalizeEmployeeRecord({
                        ...curr,
                        month: 12,
                        year: targetYear,
                        name: latest.name,
                        nik: latest.nik,
                        position: latest.position,
                        subjectType: 'pegawai_tahunan',
                        ptkp: latest.ptkp,
                        taxMethod: latest.taxMethod,
                        gajiPokok: latest.gajiPokok,
                        tunjangan: latest.tunjangan,
                        kompensasi: latest.kompensasi,
                        bpjs: latest.bpjs,
                        iuranPensiun: curr.iuranPensiun || defaultIuranPensiun,
                        iuranJht: curr.iuranJht || defaultIuranJht,
                        workMonths: curr.workMonths || 12,
                        syncedFromMonthly: true
                    });
                    updatedCount++;
                } else {
                    employeesData.push(normalizeEmployeeRecord({
                        id: 'emp_a1_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                        month: 12,
                        year: targetYear,
                        name: latest.name,
                        nik: latest.nik,
                        position: latest.position,
                        subjectType: 'pegawai_tahunan',
                        ptkp: latest.ptkp,
                        taxMethod: latest.taxMethod,
                        gajiPokok: latest.gajiPokok,
                        tunjangan: latest.tunjangan,
                        bonus: 0,
                        thr: 0,
                        kompensasi: latest.kompensasi,
                        bpjs: latest.bpjs,
                        iuranPensiun: defaultIuranPensiun,
                        iuranJht: defaultIuranJht,
                        workMonths: 12,
                        hasNpwp: latest.hasNpwp,
                        syncedFromMonthly: true
                    }));
                    addedCount++;
                }
            });

            saveEmployeesToStorage();

            if (!isSim) {
                const mSel = document.getElementById('payrollPeriodMonth');
                if (mSel) mSel.value = '12';
                setPayrollPeriodMode('TAHUNAN');
            } else {
                const simMSel = document.getElementById('simPeriodMonth');
                if (simMSel) simMSel.value = '12';
            }

            renderEmployeePayrollTable();
            renderSimulatorSavedList();

            if (!silent) {
                showToast(`Integrasi selesai: ${addedCount} pegawai baru ditarik & ${updatedCount} pegawai disinkronkan ke Desember (1721-A1)!`);
            }
            return addedCount + updatedCount;
        }

        function setPayrollPeriodMode(mode) {
            payrollPeriodMode = mode;
            const bBul = document.getElementById('periodModeBtn_BULANAN');
            const bTah = document.getElementById('periodModeBtn_TAHUNAN');
            if (bBul && bTah) {
                if (mode === 'BULANAN') {
                    bBul.className = 'py-2 px-2.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-2xs flex items-center justify-center gap-1.5';
                    bTah.className = 'py-2 px-2.5 rounded-lg text-xs font-semibold transition cursor-pointer text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5';
                } else {
                    bTah.className = 'py-2 px-2.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-2xs flex items-center justify-center gap-1.5';
                    bBul.className = 'py-2 px-2.5 rounded-lg text-xs font-semibold transition cursor-pointer text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5';
                }
            }
            renderEmployeePayrollTable();
        }

        function onPayrollPeriodChange() {
            const m = document.getElementById('payrollPeriodMonth')?.value || '11';
            if (m === '12') setPayrollPeriodMode('TAHUNAN');
            else if (payrollPeriodMode === 'TAHUNAN') setPayrollPeriodMode('BULANAN');
            currentPayrollPage = 1;
            renderEmployeePayrollTable();
        }

        function getFilteredPayrollEmployees() {
            const mVal = document.getElementById('payrollPeriodMonth')?.value || 'ALL';
            const yVal = parseInt(document.getElementById('payrollPeriodYear')?.value || '2026', 10);
            const q = (document.getElementById('empSearchInput')?.value || '').toLowerCase().trim();
            return employeesData.filter(e => {
                const matchPeriod = (mVal === 'ALL' || Number(e.month) === parseInt(mVal, 10)) && Number(e.year) === yVal;
                if (!matchPeriod) return false;
                if (!q) return true;
                return String(e.name || '').toLowerCase().includes(q) ||
                       String(e.position || '').toLowerCase().includes(q) ||
                       String(e.nik || '').includes(q) ||
                       String(e.ptkp || '').toLowerCase().includes(q);
            });
        }

        function toggleInlineSpreadsheetMode() {
            inlineSpreadsheetMode = !inlineSpreadsheetMode;
            ['inlineSpreadsheetToggleBtn', 'simSpreadsheetToggleBtn'].forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.className = inlineSpreadsheetMode
                        ? 'px-3 py-2 rounded-xl border border-blue-600 bg-blue-600 text-white text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer shrink-0'
                        : 'px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer shrink-0';
                }
            });
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
        }

        function updateInlineEmpField(id, field, val) {
            const emp = employeesData.find(x => x.id === id);
            if (!emp) return;
            if (['gajiPokok', 'tunjangan', 'bonus', 'thr', 'kompensasi', 'bpjs'].includes(field)) emp[field] = parseCurrency(val);
            else if (['month', 'year'].includes(field)) emp[field] = parseInt(val, 10);
            else emp[field] = val;
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
        }

        function saveEmployeesToStorage() {
            safeSetStorage(STORAGE_KEY_EMPLOYEES, employeesData);
        }

        const SUBJECT_TITLES = {
            pegawai_bulanan: 'Pegawai Tetap Bulanan (TER)',
            pegawai_tahunan: 'Masa Pajak Terakhir — Desember / Resign (1721-A1)',
            pensiunan: 'Pensiunan Berkala (TER)',
            tidak_tetap: 'Pegawai Tidak Tetap / Harian',
            bukan_pegawai: 'Bukan Pegawai / Tenaga Ahli (50% × Pasal 17)',
            pesangon_final: 'Pesangon / Manfaat Pensiun Sekaligus (Final)'
        };

        function changePayrollPageSize(size) {
            payrollPageSize = parseInt(size, 10) || 50;
            currentPayrollPage = 1;
            renderEmployeePayrollTable();
        }

        function goToPayrollPage(page) {
            currentPayrollPage = page;
            renderEmployeePayrollTable();
        }

        function renderEmployeePayrollTable() {
            const tbody = document.getElementById('employeeTableBody');
            const tfoot = document.getElementById('employeeTableFoot');
            if (!tbody || !tfoot) return;

            const filtered = getFilteredPayrollEmployees();
            let sumGaji = 0, sumTunj = 0, sumBonus = 0, sumThr = 0, sumKomp = 0, sumBpjs = 0, sumBruto = 0, sumTax = 0, sumThp = 0;

            const computedRows = filtered.map(emp => {
                const c = computeEmployeeTaxRow(emp);
                sumGaji += c.gaji; sumTunj += c.tunj; sumBonus += c.bonus; sumThr += c.thr; sumKomp += c.kompensasi;
                sumBpjs += c.bpjsComp; sumBruto += c.brutoPajak; sumTax += c.pph21; sumThp += c.thp;
                return { emp, c };
            });

            const totalItems = computedRows.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / payrollPageSize));
            if (currentPayrollPage > totalPages) currentPayrollPage = totalPages;
            const startIdx = (currentPayrollPage - 1) * payrollPageSize;
            const pageItems = computedRows.slice(startIdx, startIdx + payrollPageSize);

            tbody.innerHTML = pageItems.map(({ emp, c }, idx) => {
                const rowNum = startIdx + idx + 1;
                const monthStr = `${String(emp.month).padStart(2, '0')} - ${MONTH_NAMES[emp.month] || ''}`;

                if (inlineSpreadsheetMode) {
                    return `
                        <tr class="bg-blue-50/10 hover:bg-blue-50/40">
                            <td class="py-2 px-3 sticky-col-left bg-white">
                                <input type="text" value="${htmlText(emp.name)}" onchange="updateInlineEmpField('${emp.id}','name',this.value)" class="w-40 px-2 py-1 rounded border border-slate-300 font-bold text-xs">
                            </td>
                            <td class="py-2 px-2 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <select onchange="updateInlineEmpField('${emp.id}','month',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-semibold text-[11px]">
                                        ${Array.from({length: 12}, (_, i) => i + 1).map(m => `<option value="${m}" ${Number(emp.month)===m?'selected':''}>${String(m).padStart(2,'0')}</option>`).join('')}
                                    </select>
                                    <select onchange="updateInlineEmpField('${emp.id}','year',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-bold text-blue-700 text-[11px]">
                                        ${AVAILABLE_YEARS.map(yr => `<option value="${yr}" ${Number(emp.year)===yr?'selected':''}>${yr}</option>`).join('')}
                                    </select>
                                </div>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <select onchange="updateInlineEmpField('${emp.id}','ptkp',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-bold text-xs">
                                    ${Object.keys(PTKP_MAP).map(k => `<option value="${k}" ${emp.ptkp===k?'selected':''}>${k}</option>`).join('')}
                                </select>
                            </td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.gaji,false)}" onchange="updateInlineEmpField('${emp.id}','gajiPokok',this.value)" class="w-28 text-right px-2 py-1 rounded border border-slate-300 font-mono font-bold"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.tunj,false)}" onchange="updateInlineEmpField('${emp.id}','tunjangan',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.bonus,false)}" onchange="updateInlineEmpField('${emp.id}','bonus',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.thr,false)}" onchange="updateInlineEmpField('${emp.id}','thr',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.kompensasi,false)}" onchange="updateInlineEmpField('${emp.id}','kompensasi',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.bpjsComp,false)}" onchange="updateInlineEmpField('${emp.id}','bpjs',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-3 text-right font-mono font-bold">${formatRupiah(c.brutoPajak)}</td>
                            <td class="py-2 px-2 text-center font-mono text-[11px]">${c.rateLabel}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-amber-700">${formatRupiah(c.pph21)}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">${formatRupiah(c.thp)}</td>
                            <td class="py-2 px-3 text-center sticky-col-right bg-white">
                                <button type="button" onclick="deleteEmployee('${emp.id}')" class="w-7 h-7 rounded bg-rose-50 text-rose-600 cursor-pointer"><i class="fa-solid fa-trash text-[10px]"></i></button>
                            </td>
                        </tr>
                    `;
                }

                return `
                    <tr class="hover:bg-slate-50/90 transition group">
                        <td class="py-3 px-4 sticky-col-left bg-white group-hover:bg-slate-50/90">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono text-slate-400">#${rowNum}</span>
                                <span class="font-bold text-slate-900">${htmlText(emp.name)}</span>
                                ${(c.isAnnualA1 && (emp.syncedFromMonthly || c.linkedMonthlyCount > 0)) ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Terintegrasi otomatis dari modul Pegawai Tetap Bulanan"><i class="fa-solid fa-link mr-0.5"></i>Terintegrasi Bulanan</span>` : ''}
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="text-blue-600 font-medium">${htmlText(emp.position)}</span>
                                <span>•</span>
                                <span class="font-mono text-[10px] text-slate-400">${htmlText(emp.nik)}</span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">${monthStr}</span>
                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">Tahun ${emp.year}</div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">${emp.ptkp} (TER ${c.terCat})</span>
                            <div class="text-[10px] font-bold text-slate-500 mt-0.5">${emp.taxMethod}</div>
                        </td>
                        <td class="py-3 px-3.5 text-right font-mono font-semibold text-slate-900">${formatRupiah(c.gaji)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.tunj)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.bonus)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.thr)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.kompensasi)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.bpjsComp)}</td>
                        <td class="py-3 px-3.5 text-right font-mono bg-slate-50/70 font-bold text-slate-900">${formatRupiah(c.brutoPajak)}</td>
                        <td class="py-3 px-3 text-center"><span class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-mono text-[11px] font-semibold">${c.rateLabel}</span></td>
                        <td class="py-3 px-3.5 text-right font-mono font-bold text-amber-700 bg-amber-50/20">${formatRupiah(c.pph21)}</td>
                        <td class="py-3 px-3.5 text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">${formatRupiah(c.thp)}</td>
                        <td class="py-3 px-4 text-center sticky-col-right bg-white group-hover:bg-slate-50/90">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" onclick="viewEmployeeWorksheet('${emp.id}', 'worksheet')" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold flex items-center gap-1 cursor-pointer">
                                    <i class="fa-regular fa-eye"></i><span>Kertas Kerja</span>
                                </button>
                                <button type="button" onclick="editEmployee('${emp.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-600 flex items-center justify-center cursor-pointer" title="Edit"><i class="fa-solid fa-pen text-[10px]"></i></button>
                                <button type="button" onclick="deleteEmployee('${emp.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 flex items-center justify-center cursor-pointer" title="Hapus"><i class="fa-solid fa-trash text-[10px]"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tfoot.innerHTML = `
                <tr>
                    <td colspan="3" class="py-3.5 px-4 sticky-col-left bg-slate-900">TOTAL KOLEKTIF (${totalItems} PEGAWAI)</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumGaji)}</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumTunj)}</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumBonus)}</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumThr)}</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumKomp)}</td>
                    <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumBpjs)}</td>
                    <td class="py-3.5 px-3.5 text-right bg-slate-950">${formatRupiah(sumBruto)}</td>
                    <td class="py-3.5 px-3 text-center">-</td>
                    <td class="py-3.5 px-3.5 text-right text-amber-400">${formatRupiah(sumTax)}</td>
                    <td class="py-3.5 px-3.5 text-right text-emerald-400">${formatRupiah(sumThp)}</td>
                    <td class="py-3.5 px-4 sticky-col-right bg-slate-900"></td>
                </tr>
            `;

            const kpiCountEl = document.getElementById('payrollKpiCount');
            const kpiBrutoEl = document.getElementById('payrollKpiBruto');
            const kpiTaxEl = document.getElementById('payrollKpiTax');
            const kpiThpEl = document.getElementById('payrollKpiThp');
            const badgeTop = document.getElementById('employeeCountBadgeTop');
            if (kpiCountEl) kpiCountEl.textContent = `${totalItems} Pegawai`;
            if (kpiBrutoEl) kpiBrutoEl.textContent = formatRupiah(sumBruto);
            if (kpiTaxEl) kpiTaxEl.textContent = formatRupiah(sumTax);
            if (kpiThpEl) kpiThpEl.textContent = formatRupiah(sumThp);
            if (badgeTop) badgeTop.textContent = String(employeesData.length);

            const mVal = document.getElementById('payrollPeriodMonth')?.value || 'ALL';
            const yVal = document.getElementById('payrollPeriodYear')?.value || '2026';
            const subPeriod = document.getElementById('payrollKpiPeriodSub');
            if (subPeriod) subPeriod.textContent = mVal === 'ALL' ? `Semua Bulan / ${yVal}` : `Masa ${String(mVal).padStart(2, '0')}/${yVal}`;

            const pagInfo = document.getElementById('payrollPaginationInfo');
            if (pagInfo) pagInfo.textContent = `Menampilkan ${totalItems === 0 ? 0 : startIdx + 1}–${Math.min(startIdx + payrollPageSize, totalItems)} dari ${totalItems} pegawai`;

            const pagBtns = document.getElementById('payrollPaginationButtons');
            if (pagBtns) {
                let html = '';
                for (let p = 1; p <= Math.min(totalPages, 8); p++) {
                    html += `<button type="button" onclick="goToPayrollPage(${p})" class="px-2.5 py-1 rounded ${p === currentPayrollPage ? 'bg-blue-600 text-white font-bold' : 'bg-white border border-slate-300 text-slate-700'} cursor-pointer">${p}</button>`;
                }
                pagBtns.innerHTML = html;
            }
        }

        function deleteEmployee(id) {
            employeesData = employeesData.filter(x => x.id !== id);
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            showToast('Data pegawai dihapus.');
        }

        function copyPreviousMonthEmployees() {
            const curM = parseInt(document.getElementById('payrollPeriodMonth')?.value || '11', 10);
            const curY = parseInt(document.getElementById('payrollPeriodYear')?.value || '2026', 10);
            const prevM = curM > 1 ? curM - 1 : 12;
            const prevY = curM > 1 ? curY : curY - 1;
            const prevList = employeesData.filter(e => Number(e.month) === prevM && Number(e.year) === prevY);
            if (prevList.length === 0) {
                showToast(`Tidak ada data pada bulan ${MONTH_NAMES[prevM]} ${prevY}.`);
                return;
            }
            const cloned = prevList.map((e, i) => ({
                ...e,
                id: 'emp_copy_' + Date.now() + '_' + i,
                month: curM,
                year: curY
            }));
            employeesData = [...cloned, ...employeesData];
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            showToast(`${cloned.length} pegawai berhasil disalin ke ${MONTH_NAMES[curM]} ${curY}.`);
        }

        function resetSampleEmployees() {
            employeesData = JSON.parse(JSON.stringify(DEFAULT_EMPLOYEES)).map(normalizeEmployeeRecord);
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            showToast('Data contoh berhasil direset.');
        }

        function openBulkGeneratorModal() {
            document.getElementById('bulkGeneratorModal')?.classList.remove('hidden');
        }

        function applyBulkThrOrBonus(action) {
            const filtered = getFilteredPayrollEmployees();
            filtered.forEach(emp => {
                if (action === 'THR_1X') emp.thr = emp.gajiPokok;
                else if (action === 'RESET_THR') emp.thr = 0;
            });
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            document.getElementById('bulkGeneratorModal')?.classList.add('hidden');
            showToast('Aksi massal THR berhasil diterapkan!');
        }

        function openTerModal() {
            renderModalTerTable(lastTerCategoryForModal || 'A');
            document.getElementById('terModal')?.classList.remove('hidden');
        }

        function closeTerModal() {
            document.getElementById('terModal')?.classList.add('hidden');
        }

        function renderModalTerTable(cat) {
            lastTerCategoryForModal = cat;
            ['A', 'B', 'C'].forEach(c => {
                const btn = document.getElementById('modalBtnCat' + c);
                if (btn) {
                    btn.className = c === cat
                        ? 'px-3 py-1 rounded-lg text-xs font-semibold bg-blue-600 text-white cursor-pointer'
                        : 'px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-300 cursor-pointer';
                }
            });
            const tbody = document.getElementById('modalTerTableBody');
            if (!tbody) return;
            const list = TER_TABLES[cat] || TER_TABLES.A;
            tbody.innerHTML = list.map((b, i) => `
                <tr class="hover:bg-blue-50/50">
                    <td class="py-1.5 px-3 text-center font-bold text-slate-600">#${i + 1}</td>
                    <td class="py-1.5 px-3 text-right">${formatRupiah(b.min)}</td>
                    <td class="py-1.5 px-3 text-right">${b.max === Infinity ? 'Di atas Rp 1.400.000.000' : formatRupiah(b.max)}</td>
                    <td class="py-1.5 px-3 text-center font-bold text-blue-700">${b.rate}%</td>
                </tr>
            `).join('');
        }

        function setTaxMethod(method) {
            currentTaxMethod = method;
            calculateAll();
        }

        function onSimPeriodChange() {
            renderSimulatorSavedList();
        }

        const MODULE_TEMPLATE_CONFIG = {
            pegawai_bulanan: {
                shortLabel: 'Template Pegawai Bulanan',
                filePrefix: 'Template_Pegawai_Tetap_Bulanan_TER',
                sheetName: 'Pegawai_Bulanan_TER',
                columnsHint: 'Kolom Template Bulanan (TER): Masa_Pajak, Tahun_Pajak, Nama_Pegawai, NIK_NPWP, Jabatan, Status_PTKP, Metode_Pajak, Gaji_Pokok, Tunjangan_Tetap, Bonus_Jasa_Produksi, THR, Kompensasi_Lembur, Premi_BPJS_Perusahaan',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: m === 12 ? 11 : m,
                        Tahun_Pajak: y,
                        Nama_Pegawai: 'Ahmad Fauzi, S.T.',
                        NIK_NPWP: "'3174011205910002",
                        Jabatan: 'Project Manager',
                        Status_PTKP: 'K/1',
                        Metode_Pajak: 'GROSS',
                        Gaji_Pokok: 18500000,
                        Tunjangan_Tetap: 2500000,
                        Bonus_Jasa_Produksi: 0,
                        THR: 0,
                        Kompensasi_Lembur: 750000,
                        Premi_BPJS_Perusahaan: 550000
                    },
                    {
                        Masa_Pajak: m === 12 ? 11 : m,
                        Tahun_Pajak: y,
                        Nama_Pegawai: 'Clara Kartika, S.E.',
                        NIK_NPWP: "'3171042208940005",
                        Jabatan: 'Senior Finance Analyst',
                        Status_PTKP: 'TK/0',
                        Metode_Pajak: 'GROSS_UP',
                        Gaji_Pokok: 15000000,
                        Tunjangan_Tetap: 2000000,
                        Bonus_Jasa_Produksi: 3000000,
                        THR: 0,
                        Kompensasi_Lembur: 0,
                        Premi_BPJS_Perusahaan: 450000
                    }
                ]
            },
            pegawai_tahunan: {
                shortLabel: 'Template Desember / A1',
                filePrefix: 'Template_Desember_Resign_1721A1',
                sheetName: 'Desember_Resign_1721A1',
                columnsHint: 'Kolom Template Desember (1721-A1): Masa_Pajak (12), Tahun_Pajak, Nama_Pegawai, NIK_NPWP, Jabatan, Status_PTKP, Metode_Pajak, Jumlah_Bulan_Kerja (1-12), Gaji_Pokok_Desember, Tunjangan_Desember, Bonus_Setahun, THR_Setahun, Kompensasi_Lembur, Premi_BPJS_Per_Bulan, Iuran_Pensiun_Per_Bulan, Iuran_JHT_Per_Bulan, PPh21_TER_Jan_Nov_Dipotong',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: 12,
                        Tahun_Pajak: y,
                        Nama_Pegawai: 'Budi Santoso, S.E.',
                        NIK_NPWP: "'3174051807890002",
                        Jabatan: 'Finance & Tax Manager',
                        Status_PTKP: 'K/1',
                        Metode_Pajak: 'GROSS',
                        Jumlah_Bulan_Kerja: 12,
                        Gaji_Pokok_Desember: 22000000,
                        Tunjangan_Desember: 3500000,
                        Bonus_Setahun: 10000000,
                        THR_Setahun: 22000000,
                        Kompensasi_Lembur: 0,
                        Premi_BPJS_Per_Bulan: 598800,
                        Iuran_Pensiun_Per_Bulan: 100000,
                        Iuran_JHT_Per_Bulan: 440000,
                        PPh21_TER_Jan_Nov_Dipotong: ''
                    },
                    {
                        Masa_Pajak: 12,
                        Tahun_Pajak: y,
                        Nama_Pegawai: 'Rina Wijaya, S.Ak.',
                        NIK_NPWP: "'3171041209920004",
                        Jabatan: 'Senior Tax Specialist',
                        Status_PTKP: 'TK/0',
                        Metode_Pajak: 'GROSS_UP',
                        Jumlah_Bulan_Kerja: 12,
                        Gaji_Pokok_Desember: 14500000,
                        Tunjangan_Desember: 2000000,
                        Bonus_Setahun: 5000000,
                        THR_Setahun: 14500000,
                        Kompensasi_Lembur: 500000,
                        Premi_BPJS_Per_Bulan: 558300,
                        Iuran_Pensiun_Per_Bulan: 100000,
                        Iuran_JHT_Per_Bulan: 290000,
                        PPh21_TER_Jan_Nov_Dipotong: ''
                    }
                ]
            },
            pensiunan: {
                shortLabel: 'Template Pensiunan Berkala',
                filePrefix: 'Template_Pensiunan_Berkala_TER',
                sheetName: 'Pensiunan_Berkala',
                columnsHint: 'Kolom Template Pensiunan: Masa_Pajak, Tahun_Pajak, Nama_Penerima_Pensiun, NIK_NPWP, Status_Pensiunan, Status_PTKP, Metode_Pajak, Uang_Pensiun_Pokok, Tunjangan_Pensiun_Beras, Pensiun_Ke13_Lainnya',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Penerima_Pensiun: 'Drs. Haryanto Wibowo',
                        NIK_NPWP: "'3172010504620001",
                        Status_Pensiunan: 'Pensiunan Eks Direktur',
                        Status_PTKP: 'K/1',
                        Metode_Pajak: 'GROSS',
                        Uang_Pensiun_Pokok: 9500000,
                        Tunjangan_Pensiun_Beras: 750000,
                        Pensiun_Ke13_Lainnya: 0
                    },
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Penerima_Pensiun: 'Hj. Ratna Sari',
                        NIK_NPWP: "'3173041908650003",
                        Status_Pensiunan: 'Pensiunan Manajer',
                        Status_PTKP: 'TK/0',
                        Metode_Pajak: 'GROSS_UP',
                        Uang_Pensiun_Pokok: 7200000,
                        Tunjangan_Pensiun_Beras: 500000,
                        Pensiun_Ke13_Lainnya: 0
                    }
                ]
            },
            tidak_tetap: {
                shortLabel: 'Template Pegawai Tidak Tetap',
                filePrefix: 'Template_Pegawai_Tidak_Tetap_Harian',
                sheetName: 'Pegawai_Tidak_Tetap',
                columnsHint: 'Kolom Template Pegawai Tidak Tetap: Masa_Pajak, Tahun_Pajak, Nama_Pekerja, NIK_NPWP, Jenis_Pekerjaan, Status_PTKP, Metode_Pajak, Skema_Upah (HARIAN/BULANAN), Jumlah_Hari_Kerja, Upah_Per_Hari_Atau_Bulanan, Insentif_Lembur_Harian',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Pekerja: 'Dedi Kurniawan',
                        NIK_NPWP: "'3201051403950004",
                        Jenis_Pekerjaan: 'Teknisi Instalasi Harian',
                        Status_PTKP: 'TK/0',
                        Metode_Pajak: 'GROSS',
                        Skema_Upah: 'HARIAN',
                        Jumlah_Hari_Kerja: 5,
                        Upah_Per_Hari_Atau_Bulanan: 550000,
                        Insentif_Lembur_Harian: 150000
                    },
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Pekerja: 'Wahyu Setiawan',
                        NIK_NPWP: "'3201082107970002",
                        Jenis_Pekerjaan: 'Operator Gudang Lepas',
                        Status_PTKP: 'K/0',
                        Metode_Pajak: 'GROSS_UP',
                        Skema_Upah: 'HARIAN',
                        Jumlah_Hari_Kerja: 10,
                        Upah_Per_Hari_Atau_Bulanan: 400000,
                        Insentif_Lembur_Harian: 0
                    }
                ]
            },
            bukan_pegawai: {
                shortLabel: 'Template Bukan Pegawai / Ahli',
                filePrefix: 'Template_Bukan_Pegawai_Tenaga_Ahli',
                sheetName: 'Bukan_Pegawai_Ahli',
                columnsHint: 'Kolom Template Bukan Pegawai / Tenaga Ahli: Masa_Pajak, Tahun_Pajak, Nama_Tenaga_Ahli, NIK_NPWP, Profesi_Jenis_Jasa, Metode_Pajak (GROSS/GROSS_UP/NETT), Honorarium_Fee_Komisi_Bruto, Biaya_Materi_Non_Jasa',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Tenaga_Ahli: 'dr. Nadia Kusuma, Sp.PD',
                        NIK_NPWP: "'3172021903840005",
                        Profesi_Jenis_Jasa: 'Dokter Spesialis / Tenaga Ahli',
                        Metode_Pajak: 'GROSS',
                        Honorarium_Fee_Komisi_Bruto: 45000000,
                        Biaya_Materi_Non_Jasa: 0
                    },
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Tenaga_Ahli: 'KAP Hendrawan & Rekan (Partner)',
                        NIK_NPWP: "'3174091102810006",
                        Profesi_Jenis_Jasa: 'Konsultan Hukum & Pajak',
                        Metode_Pajak: 'GROSS_UP',
                        Honorarium_Fee_Komisi_Bruto: 200000000,
                        Biaya_Materi_Non_Jasa: 0
                    }
                ]
            },
            pesangon_final: {
                shortLabel: 'Template Pesangon / JHT Final',
                filePrefix: 'Template_Pesangon_JHT_Final_PP68',
                sheetName: 'Pesangon_JHT_Final',
                columnsHint: 'Kolom Template Pesangon / JHT Final: Masa_Pajak, Tahun_Pajak, Nama_Penerima, NIK_NPWP, Jabatan_Terakhir, Metode_Pajak, Jenis_Objek_Final (PESANGON / MANFAAT_PENSIUN), Uang_Pesangon_JHT_Bruto, Penghargaan_Masa_Kerja',
                buildSample: (m, y) => [
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Penerima: 'Ir. Bambang Sudibyo',
                        NIK_NPWP: "'3171021809680001",
                        Jabatan_Terakhir: 'General Manager',
                        Metode_Pajak: 'GROSS',
                        Jenis_Objek_Final: 'PESANGON',
                        Uang_Pesangon_JHT_Bruto: 150000000,
                        Penghargaan_Masa_Kerja: 35000000
                    },
                    {
                        Masa_Pajak: m,
                        Tahun_Pajak: y,
                        Nama_Penerima: 'Agus Salim, S.E.',
                        NIK_NPWP: "'3175062511700004",
                        Jabatan_Terakhir: 'Senior Supervisor',
                        Metode_Pajak: 'GROSS_UP',
                        Jenis_Objek_Final: 'MANFAAT_PENSIUN',
                        Uang_Pesangon_JHT_Bruto: 85000000,
                        Penghargaan_Masa_Kerja: 0
                    }
                ]
            }
        };

        function resolveActiveModuleForTemplate(subjectOverride) {
            if (subjectOverride && MODULE_TEMPLATE_CONFIG[subjectOverride]) return subjectOverride;
            const isSim = currentWorkspaceMode === 'simulator';
            if (isSim && MODULE_TEMPLATE_CONFIG[currentSubject]) return currentSubject;
            const rawM = document.getElementById('payrollPeriodMonth')?.value;
            return (payrollPeriodMode === 'TAHUNAN' || rawM === '12') ? 'pegawai_tahunan' : 'pegawai_bulanan';
        }

        function onImportModuleTypeChange(subjType) {
            const cfg = MODULE_TEMPLATE_CONFIG[subjType] || MODULE_TEMPLATE_CONFIG.pegawai_bulanan;
            const btnText = document.getElementById('modalDownloadTemplateBtnText');
            const hintEl = document.getElementById('importTemplateColumnsHint');
            if (btnText) btnText.textContent = `Download ${cfg.shortLabel} (.xlsx)`;
            if (hintEl) hintEl.textContent = cfg.columnsHint;
            refreshImportPreviewFromInputs();
        }

        function downloadImportTemplateExcel(subjectOverride) {
            const activeSubj = resolveActiveModuleForTemplate(subjectOverride);
            const cfg = MODULE_TEMPLATE_CONFIG[activeSubj] || MODULE_TEMPLATE_CONFIG.pegawai_bulanan;
            const isSim = currentWorkspaceMode === 'simulator';
            const rawM = isSim ? document.getElementById('simPeriodMonth')?.value : document.getElementById('payrollPeriodMonth')?.value;
            const activeMonth = (rawM && rawM !== 'ALL') ? parseInt(rawM, 10) : (activeSubj === 'pegawai_tahunan' ? 12 : 11);
            const activeYear = parseInt((isSim ? document.getElementById('simPeriodYear')?.value : document.getElementById('payrollPeriodYear')?.value) || '2026', 10);

            const sample = cfg.buildSample(activeMonth, activeYear);
            if (typeof XLSX !== 'undefined') {
                const ws = XLSX.utils.json_to_sheet(sample);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, cfg.sheetName);
                XLSX.writeFile(wb, `${cfg.filePrefix}_${activeMonth}_${activeYear}.xlsx`);
                showToast(`${cfg.shortLabel} (.xlsx) berhasil diunduh!`);
            }
        }

        let stagedImportRecords = [];
        let lastUploadedSheetRows = null;

        function openImportModal(subjectOverride) {
            clearStagedImport();
            const activeSubj = resolveActiveModuleForTemplate(subjectOverride);
            const selMod = document.getElementById('importTargetModuleSelect');
            if (selMod) selMod.value = activeSubj;
            onImportModuleTypeChange(activeSubj);
            document.getElementById('importExcelModal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('importExcelModal').classList.add('hidden');
        }

        function clearStagedImport() {
            stagedImportRecords = [];
            lastUploadedSheetRows = null;
            const fileEl = document.getElementById('excelFileInput');
            if (fileEl) fileEl.value = '';
            const pasteEl = document.getElementById('pasteImportTextarea');
            if (pasteEl) pasteEl.value = '';
            document.getElementById('importPreviewContainer')?.classList.add('hidden');
            document.getElementById('importStatusAlert')?.classList.add('hidden');
        }

        function showImportAlert(msg, isError = false) {
            const el = document.getElementById('importStatusAlert');
            if (!el) return;
            el.textContent = msg;
            el.className = isError
                ? 'p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 font-semibold text-xs'
                : 'p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-medium text-xs';
            el.classList.remove('hidden');
        }

        function getActiveImportDefaults() {
            const isSim = currentWorkspaceMode === 'simulator';
            const rawM = isSim ? document.getElementById('simPeriodMonth')?.value : document.getElementById('payrollPeriodMonth')?.value;
            const modalSelectedSubj = document.getElementById('importTargetModuleSelect')?.value;
            const defaultSubject = modalSelectedSubj || (isSim ? currentSubject : (payrollPeriodMode === 'TAHUNAN' || rawM === '12' ? 'pegawai_tahunan' : 'pegawai_bulanan'));
            const defaultMonth = defaultSubject === 'pegawai_tahunan' ? 12 : ((rawM && rawM !== 'ALL') ? parseInt(rawM, 10) : 11);
            const defaultYear = parseInt((isSim ? document.getElementById('simPeriodYear')?.value : document.getElementById('payrollPeriodYear')?.value) || '2026', 10);
            return { defaultMonth, defaultYear, defaultSubject };
        }

        function mapRawRowToEmployee(r, idx) {
            const { defaultMonth, defaultYear, defaultSubject } = getActiveImportDefaults();
            const overrideMode = document.getElementById('importPeriodOverrideSelect')?.value || 'ACTIVE_FILTER';

            // Normalisasi kunci objek agar tahan terhadap spasi, huruf besar/kecil, maupun simbol
            const norm = {};
            Object.keys(r || {}).forEach(k => {
                const cleanKey = String(k).toLowerCase().replace(/[^a-z0-9]/g, '');
                norm[cleanKey] = r[k];
            });

            const pick = (...keys) => {
                for (const k of keys) {
                    const ck = k.toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (norm[ck] !== undefined && norm[ck] !== null && String(norm[ck]).trim() !== '') {
                        return norm[ck];
                    }
                }
                return '';
            };

            const rawName = String(pick(
                'Nama_Pegawai', 'Nama Pegawai', 'Nama_Penerima_Pensiun', 'Nama_Pekerja',
                'Nama_Tenaga_Ahli', 'Nama_Penerima', 'Nama Karyawan', 'Nama', 'Name',
                'Employee Name', 'Penerima Penghasilan'
            )).trim();
            const rawNik = String(pick('NIK_NPWP', 'NPWP/NIK/TIN', 'NIK', 'NPWP', 'TIN', 'Identitas')).replace(/^'/, '').trim();
            const rawPos = String(pick(
                'Jabatan', 'Status_Pensiunan', 'Jenis_Pekerjaan', 'Profesi_Jenis_Jasa',
                'Jabatan_Terakhir', 'Posisi', 'Position', 'Divisi', 'Profesi'
            )).trim();
            const rawPtkp = String(pick('Status_PTKP', 'Status PTKP', 'PTKP', 'Status')).toUpperCase().trim();
            const rawMethod = String(pick('Metode_Pajak', 'Metode Pajak', 'Metode', 'Skema', 'Tax Method')).toUpperCase().replace(/\s+/g, '_').trim();
            const rawSubj = String(pick('Kategori_Subjek', 'Kategori Subjek', 'Modul', 'Subjek', 'Kode Objek Pajak')).toLowerCase().trim();

            const rawGaji = parseCurrency(pick(
                'Gaji_Pokok', 'Gaji_Pokok_Desember', 'Uang_Pensiun_Pokok',
                'Upah_Per_Hari_Atau_Bulanan', 'Honorarium_Fee_Komisi_Bruto',
                'Uang_Pesangon_JHT_Bruto', 'Gaji Pokok', 'Gaji', 'Basic Salary',
                'Penghasilan Kotor', 'Penghasilan Bruto', 'Bruto', 'Honorarium', 'Upah'
            ));
            const rawTunj = parseCurrency(pick(
                'Tunjangan_Tetap', 'Tunjangan_Desember', 'Tunjangan_Pensiun_Beras',
                'Insentif_Lembur_Harian', 'Biaya_Materi_Non_Jasa',
                'Penghargaan_Masa_Kerja', 'Tunjangan Tetap', 'Tunjangan', 'Allowance'
            ));
            const rawBonus = parseCurrency(pick(
                'Bonus_Jasa_Produksi', 'Bonus_Setahun', 'Pensiun_Ke13_Lainnya',
                'Bonus', 'Jasa Produksi', 'Insentif'
            ));
            const rawThr = parseCurrency(pick('THR', 'THR_Setahun', 'Tunjangan Hari Raya'));
            const rawKomp = parseCurrency(pick('Kompensasi_Lembur', 'Kompensasi', 'Lembur', 'Overtime'));
            const rawBpjs = parseCurrency(pick('Premi_BPJS_Perusahaan', 'Premi_BPJS_Per_Bulan', 'Premi_BPJS', 'Premi BPJS', 'BPJS', 'BPJS Perusahaan'));
            const rawPensiun = parseCurrency(pick('Iuran_Pensiun_Pegawai', 'Iuran_Pensiun_Per_Bulan', 'Iuran_Pensiun', 'Iuran Pensiun', 'JP'));
            const rawJht = parseCurrency(pick('Iuran_JHT_Pegawai', 'Iuran_JHT_Per_Bulan', 'Iuran_JHT', 'Iuran JHT', 'JHT'));
            const rawWorkMonths = parseInt(pick('Jumlah_Bulan_Kerja', 'Bulan Kerja', 'Masa Kerja'), 10) || 12;
            const rawDailyScheme = String(pick('Skema_Upah', 'Skema Upah')).toUpperCase().trim() || 'HARIAN';
            const rawWorkDays = parseInt(pick('Jumlah_Hari_Kerja', 'Hari Kerja', 'Jml Hari'), 10) || 1;
            const rawFinalSubType = String(pick('Jenis_Objek_Final', 'Objek Final')).toUpperCase().trim() || 'PESANGON';
            const rawPriorTerStr = pick('PPh21_TER_Jan_Nov_Dipotong', 'PPh21_Masa_Sebelumnya', 'TER_Jan_Nov');
            const rawPriorTer = rawPriorTerStr !== '' ? parseCurrency(rawPriorTerStr) : null;

            // Abaikan baris kosong atau baris judul yang ikut terbaca
            if (!rawName && rawGaji === 0 && rawTunj === 0 && !rawNik) {
                return null;
            }
            if (['namapegawai', 'nama', 'masapajak', 'npwppemotong', 'namapenerimapensiun', 'namapekerja', 'namatenagaahli', 'namapenerima'].includes(rawName.toLowerCase().replace(/[^a-z]/g, ''))) {
                return null;
            }

            let resolvedSubj = defaultSubject;
            if (rawSubj) {
                if (rawSubj.includes('tahunan') || rawSubj.includes('desember') || rawSubj.includes('a1') || rawSubj.includes('21-100-01-a1')) resolvedSubj = 'pegawai_tahunan';
                else if (rawSubj.includes('pensiun') && !rawSubj.includes('final')) resolvedSubj = 'pensiunan';
                else if (rawSubj.includes('tidak_tetap') || rawSubj.includes('harian') || rawSubj.includes('21-100-03')) resolvedSubj = 'tidak_tetap';
                else if (rawSubj.includes('bukan_pegawai') || rawSubj.includes('ahli') || rawSubj.includes('21-100-07')) resolvedSubj = 'bukan_pegawai';
                else if (rawSubj.includes('pesangon') || rawSubj.includes('final') || rawSubj.includes('21-401')) resolvedSubj = 'pesangon_final';
                else if (rawSubj.includes('pegawai_bulanan') || rawSubj.includes('bulanan') || rawSubj.includes('21-100-01')) resolvedSubj = 'pegawai_bulanan';
            }

            const fileMonth = parseInt(pick('Masa_Pajak', 'Masa Pajak', 'Masa', 'Bulan', 'Month'), 10);
            const fileYear = parseInt(pick('Tahun_Pajak', 'Tahun Pajak', 'Tahun', 'Year'), 10);

            const finalMonth = overrideMode === 'ACTIVE_FILTER'
                ? defaultMonth
                : ((fileMonth >= 1 && fileMonth <= 12) ? fileMonth : defaultMonth);
            const finalYear = overrideMode === 'ACTIVE_FILTER'
                ? defaultYear
                : ((fileYear >= 2020 && fileYear <= 2035) ? fileYear : defaultYear);

            return normalizeEmployeeRecord({
                id: 'emp_imp_' + Date.now() + '_' + idx + '_' + Math.random().toString(36).slice(2, 5),
                month: resolvedSubj === 'pegawai_tahunan' ? 12 : finalMonth,
                year: finalYear,
                name: rawName || `Pegawai Impor #${idx + 1}`,
                nik: rawNik || '3171000000000001',
                position: rawPos || (resolvedSubj === 'bukan_pegawai' ? 'Tenaga Ahli' : (resolvedSubj === 'pensiunan' ? 'Pensiunan' : 'Staff')),
                subjectType: resolvedSubj,
                ptkp: PTKP_MAP[rawPtkp] ? rawPtkp : 'K/1',
                taxMethod: ['GROSS', 'GROSS_UP', 'NETT'].includes(rawMethod) ? rawMethod : 'GROSS',
                gajiPokok: rawGaji,
                tunjangan: rawTunj,
                bonus: rawBonus,
                thr: rawThr,
                kompensasi: rawKomp,
                bpjs: rawBpjs,
                iuranPensiun: rawPensiun,
                iuranJht: rawJht,
                workMonths: rawWorkMonths,
                dailyScheme: ['HARIAN', 'BULANAN'].includes(rawDailyScheme) ? rawDailyScheme : 'HARIAN',
                workDays: rawWorkDays,
                finalSubType: ['PESANGON', 'MANFAAT_PENSIUN'].includes(rawFinalSubType) ? rawFinalSubType : 'PESANGON',
                a1ManualPriorTer: rawPriorTer,
                hasNpwp: true
            });
        }

        function extractRowsFromWorksheet(ws) {
            if (!ws || typeof XLSX === 'undefined') return [];
            // Baca sebagai Array of Arrays terlebih dahulu agar dapat menemukan baris header meskipun ada judul di baris 1-2 (seperti format Coretax)
            const aoa = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (!aoa || aoa.length === 0) return [];

            let headerRowIdx = 0;
            const headerKeywords = ['nama', 'nik', 'npwp', 'gaji', 'masa', 'ptkp', 'jabatan', 'posisi', 'penghasilan', 'bruto', 'honorarium'];
            for (let r = 0; r < Math.min(aoa.length, 10); r++) {
                const rowCells = (aoa[r] || []).map(c => String(c).toLowerCase());
                const matches = rowCells.filter(cell => headerKeywords.some(kw => cell.includes(kw)));
                if (matches.length >= 2) {
                    headerRowIdx = r;
                    break;
                }
            }

            const headers = (aoa[headerRowIdx] || []).map((h, i) => String(h || '').trim() || `Col_${i}`);
            const dataRows = [];
            for (let r = headerRowIdx + 1; r < aoa.length; r++) {
                const row = aoa[r];
                if (!row || row.every(c => String(c).trim() === '')) continue;
                const obj = {};
                headers.forEach((h, i) => {
                    obj[h] = row[i] !== undefined ? row[i] : '';
                });
                dataRows.push(obj);
            }
            return dataRows;
        }

        function renderImportPreviewUI(records) {
            stagedImportRecords = records;
            const container = document.getElementById('importPreviewContainer');
            const badge = document.getElementById('importPreviewCountBadge');
            const tbody = document.getElementById('importPreviewTableBody');
            const alertEl = document.getElementById('importStatusAlert');

            if (!records || records.length === 0) {
                container?.classList.add('hidden');
                return;
            }

            alertEl?.classList.add('hidden');
            container?.classList.remove('hidden');
            if (badge) {
                badge.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-600"></i><span>${records.length} data pegawai terdeteksi & siap diimpor</span>`;
            }
            if (tbody) {
                tbody.innerHTML = records.slice(0, 5).map(e => `
                    <tr>
                        <td class="py-1.5 px-3 font-bold text-slate-900">${htmlText(e.name)} <span class="text-slate-400 font-normal">(${htmlText(e.position)})</span></td>
                        <td class="py-1.5 px-2 font-mono text-slate-600">${htmlText(e.nik)}</td>
                        <td class="py-1.5 px-2 text-center font-mono">${String(e.month).padStart(2, '0')}/${e.year}</td>
                        <td class="py-1.5 px-2 text-center font-bold text-blue-700">${e.ptkp}</td>
                        <td class="py-1.5 px-2 text-center font-semibold">${e.taxMethod}</td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold">${formatRupiah(e.gajiPokok)}</td>
                        <td class="py-1.5 px-3 text-right font-mono">${formatRupiah(e.tunjangan)}</td>
                    </tr>
                `).join('');
            }
        }

        function handleExcelFileUpload(e) {
            const file = e?.target?.files?.[0];
            if (!file) return;
            if (typeof XLSX === 'undefined') {
                showImportAlert('Pustaka pembaca Excel belum siap. Silakan gunakan kolom Copy-Paste di bawah.', true);
                return;
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                try {
                    const data = new Uint8Array(evt.target.result);
                    const wb = XLSX.read(data, { type: 'array' });
                    const firstSheet = wb.Sheets[wb.SheetNames[0]];
                    const rawRows = extractRowsFromWorksheet(firstSheet);
                    lastUploadedSheetRows = rawRows;
                    const mapped = rawRows.map((r, i) => mapRawRowToEmployee(r, i)).filter(Boolean);
                    if (mapped.length === 0) {
                        showImportAlert('File Excel terbaca tetapi tidak ditemukan baris pegawai yang valid. Pastikan ada kolom Nama_Pegawai dan Gaji_Pokok.', true);
                        return;
                    }
                    renderImportPreviewUI(mapped);
                    showToast(`${mapped.length} baris dari file "${file.name}" siap diimpor. Klik "Proses & Simpan Import".`);
                } catch (err) {
                    showImportAlert('Gagal membaca file Excel. Pastikan format file adalah .xlsx, .xls, atau .csv.', true);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function parsePastedTextToRawObjects(rawText) {
            const lines = rawText.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            if (lines.length === 0) return [];

            // Deteksi delimiter: Tab (\t), Titik Koma (;), atau Koma (,)
            const firstLine = lines[0];
            const delimiter = firstLine.includes('\t') ? '\t' : (firstLine.includes(';') ? ';' : ',');
            const splitRows = lines.map(l => l.split(delimiter).map(c => c.trim()));

            // Cek apakah baris pertama adalah header
            const headerKeywords = ['nama', 'nik', 'npwp', 'gaji', 'masa', 'tahun', 'ptkp', 'jabatan', 'metode'];
            const firstRowLower = splitRows[0].map(c => c.toLowerCase());
            const isFirstRowHeader = firstRowLower.filter(c => headerKeywords.some(kw => c.includes(kw))).length >= 2;

            if (isFirstRowHeader && splitRows.length > 1) {
                const headers = splitRows[0];
                return splitRows.slice(1).map(cols => {
                    const obj = {};
                    headers.forEach((h, i) => { obj[h] = cols[i] ?? ''; });
                    return obj;
                });
            }

            // Jika tanpa baris header, dukung baik format 14+ kolom (Template) maupun format ringkas (Nama, NIK, Jabatan, PTKP, Metode, Gaji, Tunjangan)
            return splitRows.map(c => {
                if (c.length >= 9 && !isNaN(parseInt(c[0], 10)) && !isNaN(parseInt(c[1], 10))) {
                    return {
                        Masa_Pajak: c[0], Tahun_Pajak: c[1], Nama_Pegawai: c[2], NIK_NPWP: c[3],
                        Jabatan: c[4], Kategori_Subjek: c[5], Status_PTKP: c[6], Metode_Pajak: c[7],
                        Gaji_Pokok: c[8], Tunjangan_Tetap: c[9] || 0, Bonus_Jasa_Produksi: c[10] || 0,
                        THR: c[11] || 0, Kompensasi_Lembur: c[12] || 0, Premi_BPJS: c[13] || 0,
                        Iuran_Pensiun: c[14] || 0, Iuran_JHT: c[15] || 0
                    };
                }
                if (c.length >= 5) {
                    return {
                        Nama_Pegawai: c[0], NIK_NPWP: c[1], Jabatan: c[2], Status_PTKP: c[3],
                        Metode_Pajak: ['GROSS', 'GROSS_UP', 'NETT'].includes(String(c[4]).toUpperCase()) ? c[4] : 'GROSS',
                        Gaji_Pokok: ['GROSS', 'GROSS_UP', 'NETT'].includes(String(c[4]).toUpperCase()) ? (c[5] || 0) : c[4],
                        Tunjangan_Tetap: ['GROSS', 'GROSS_UP', 'NETT'].includes(String(c[4]).toUpperCase()) ? (c[6] || 0) : (c[5] || 0)
                    };
                }
                return {
                    Nama_Pegawai: c[0] || 'Pegawai Impor',
                    Gaji_Pokok: c[1] || 0,
                    Tunjangan_Tetap: c[2] || 0
                };
            });
        }

        function handlePasteImportPreview() {
            const raw = document.getElementById('pasteImportTextarea')?.value.trim() || '';
            if (!raw) {
                if (!lastUploadedSheetRows) renderImportPreviewUI([]);
                return;
            }
            const rawObjs = parsePastedTextToRawObjects(raw);
            const mapped = rawObjs.map((r, i) => mapRawRowToEmployee(r, i)).filter(Boolean);
            renderImportPreviewUI(mapped);
        }

        function refreshImportPreviewFromInputs() {
            if (lastUploadedSheetRows && lastUploadedSheetRows.length > 0) {
                const mapped = lastUploadedSheetRows.map((r, i) => mapRawRowToEmployee(r, i)).filter(Boolean);
                renderImportPreviewUI(mapped);
            } else {
                handlePasteImportPreview();
            }
        }

        function processImportData() {
            refreshImportPreviewFromInputs();
            if (!stagedImportRecords || stagedImportRecords.length === 0) {
                showImportAlert('Silakan pilih file Excel (.xlsx/.csv) terlebih dahulu atau tempel (copy-paste) data pada kotak di atas.', true);
                return;
            }
            commitImportedRecords(stagedImportRecords);
        }

        function commitImportedRecords(imported) {
            if (!imported || imported.length === 0) return;
            const mode = document.getElementById('importModeSelect')?.value || 'APPEND';
            const firstRec = imported[0];

            if (mode === 'REPLACE') {
                const periods = new Set(imported.map(record => `${record.subjectType}:${record.year}:${record.month}`));
                employeesData = [...imported, ...employeesData.filter(record => !periods.has(`${record.subjectType}:${record.year}:${record.month}`))];
            } else {
                employeesData = [...imported, ...employeesData];
            }

            // Pastikan filter Masa & Tahun menyesuaikan data yang baru diimpor agar langsung terlihat di tabel
            if (firstRec) {
                const pMonthEl = document.getElementById('payrollPeriodMonth');
                const pYearEl = document.getElementById('payrollPeriodYear');
                const sMonthEl = document.getElementById('simPeriodMonth');
                const sYearEl = document.getElementById('simPeriodYear');

                if (pMonthEl && pMonthEl.value !== 'ALL') pMonthEl.value = String(firstRec.month);
                if (pYearEl) pYearEl.value = String(firstRec.year);
                if (sMonthEl && sMonthEl.value !== 'ALL') sMonthEl.value = String(firstRec.month);
                if (sYearEl) sYearEl.value = String(firstRec.year);

                if (Number(firstRec.month) === 12 || firstRec.subjectType === 'pegawai_tahunan') {
                    setPayrollPeriodMode('TAHUNAN');
                }
            }

            currentPayrollPage = 1;
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            closeImportModal();
            showToast(`Berhasil mengimpor ${imported.length} data pegawai!`);
        }

        function exportPayrollCsv() {
            const comp = getActiveCompany();
            const npwpPemotong = (comp.npwp || '0127543973027000').replace(/[^0-9]/g, '').padStart(16, '0').slice(0, 16);
            const idTku = (comp.idTkuFull || (npwpPemotong + '000000')).replace(/[^0-9]/g, '').padEnd(22, '0').slice(0, 22);
            const filtered = getFilteredPayrollEmployees();
            const targetList = filtered.length > 0 ? filtered : employeesData;

            const aoa = [];
            aoa.push(['NPWP Pemotong', npwpPemotong, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            aoa.push(['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
            aoa.push([
                '', 'Masa Pajak', 'Tahun Pajak', 'Status Pegawai', 'NPWP/NIK/TIN', 'Nomor Passport',
                'Status', 'Posisi', 'Sertifikat/Fasilitas', 'Kode Objek Pajak', 'Penghasilan Kotor',
                'Tarif', 'ID TKU', 'Tgl Pemotongan', '', 'TER A', 'TER B', 'TER C'
            ]);

            const totalRows = Math.max(targetList.length, 5);
            for (let i = 0; i < totalRows; i++) {
                if (i < targetList.length) {
                    const emp = targetList[i];
                    const c = computeEmployeeTaxRow(emp);
                    const m = Number(emp.month) || 9;
                    const y = Number(emp.year) || 2026;
                    const lastDay = new Date(y, m, 0).getDate();
                    const tglPotong = `${String(lastDay).padStart(2, '0')}/${String(m).padStart(2, '0')}/${y}`;
                    const kodeObj = emp.subjectType === 'pensiunan' ? '21-100-02' : (emp.subjectType === 'tidak_tetap' ? '21-100-03' : (emp.subjectType === 'bukan_pegawai' ? '21-100-07' : (emp.subjectType === 'pesangon_final' ? '21-401-01' : '21-100-01')));

                    const terA = lookupTerBracket('A', c.brutoPajak).rate;
                    const terB = lookupTerBracket('B', c.brutoPajak).rate;
                    const terC = lookupTerBracket('C', c.brutoPajak).rate;

                    aoa.push([
                        '', m, y, 'Resident', String(emp.nik || '000999'), '',
                        emp.ptkp, emp.position, 'N/A', kodeObj, Math.round(c.brutoPajak),
                        c.rateNumeric, idTku, tglPotong, '', terA, terB, terC
                    ]);
                } else {
                    aoa.push(['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 0, 0]);
                }
            }

            if (typeof XLSX !== 'undefined') {
                const ws = XLSX.utils.aoa_to_sheet(aoa);
                if (ws['B1']) ws['B1'].t = 's';
                for (let r = 3; r < 3 + targetList.length; r++) {
                    const nikRef = XLSX.utils.encode_cell({ r, c: 4 });
                    const tkuRef = XLSX.utils.encode_cell({ r, c: 12 });
                    if (ws[nikRef]) ws[nikRef].t = 's';
                    if (ws[tkuRef]) ws[tkuRef].t = 's';
                }
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Coretax_Import');
                XLSX.writeFile(wb, `Coretax_eBupot21_${npwpPemotong}.xlsx`);
                showToast('File Excel Coretax berhasil diunduh!');
            }
        }

        function exportCoretaxXml() {
            const comp = getActiveCompany();
            const filtered = getFilteredPayrollEmployees();
            const list = filtered.length > 0 ? filtered : employeesData;
            let xml = `<?xml version="1.0" encoding="UTF-8"?>\n<eBupot21Coretax npwpPemotong="${comp.npwp}" idTku="${comp.idTkuFull}">\n`;
            list.forEach(emp => {
                const c = computeEmployeeTaxRow(emp);
                xml += `  <BuktiPotong masa="${emp.month}" tahun="${emp.year}" nik="${htmlText(emp.nik)}" nama="${htmlText(emp.name)}" ptkp="${emp.ptkp}" bruto="${Math.round(c.brutoPajak)}" tarif="${c.rateNumeric}" pph21="${Math.round(c.pph21)}" idTku="${comp.idTkuFull}"/>\n`;
            });
            xml += `</eBupot21Coretax>`;
            const blob = new Blob([xml], { type: 'application/xml;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `Coretax_XML_PPh21_${comp.npwp}.xml`;
            a.click();
            showToast('File XML Coretax DJP berhasil diunduh!');
        }

        function updateCyberThemeButtonUI(isDark) {
            const icon = document.getElementById('cyberThemeIcon');
            const label = document.getElementById('cyberThemeLabel');
            if (icon) {
                icon.className = isDark ? 'fa-solid fa-sun text-amber-400' : 'fa-solid fa-moon text-indigo-600';
            }
            if (label) {
                label.textContent = isDark ? 'Executive Light' : 'Cyber Dark';
            }
        }

        function toggleCyberDarkTheme() {
            const isDark = document.body.classList.toggle('cyber-dark');
            safeSetStorage('taxcore_cyber_theme_v1', isDark ? 'dark' : 'light');
            updateCyberThemeButtonUI(isDark);
            showToast(isDark ? 'Mode Cyber Dark Futuristik Diaktifkan' : 'Mode Executive Quantum Light Diaktifkan');
        }

        function generateBulkEmployees(count) {
            const isSim = currentWorkspaceMode === 'simulator';
            const rawM = isSim ? document.getElementById('simPeriodMonth')?.value : document.getElementById('payrollPeriodMonth')?.value;
            const m = rawM && rawM !== 'ALL' ? parseInt(rawM, 10) : (currentSubject === 'pegawai_tahunan' ? 12 : 11);
            const y = parseInt((isSim ? document.getElementById('simPeriodYear')?.value : document.getElementById('payrollPeriodYear')?.value) || '2026', 10);
            const subjType = isSim ? currentSubject : (m === 12 ? 'pegawai_tahunan' : 'pegawai_bulanan');
            const firstNames = ['Adi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hendra', 'Indah', 'Joko', 'Kartika', 'Lukman', 'Maya', 'Nadia', 'Oki', 'Putri', 'Rizky', 'Siti', 'Taufik', 'Wahyu'];
            const lastNames = ['Pratama', 'Santoso', 'Wijaya', 'Kusuma', 'Saputra', 'Lestari', 'Hidayat', 'Nugroho', 'Siregar', 'Halim'];
            const positions = ['Software Engineer', 'Tax Specialist', 'Finance Staff', 'Account Executive', 'Operations Analyst', 'HR Officer'];
            const ptkpKeys = Object.keys(PTKP_MAP);
            const methods = ['GROSS', 'GROSS_UP', 'NETT'];

            const batch = [];
            const usedNiks = new Set(employeesData.map(record => record.nik));
            let sequence = 1000000000;
            for (let i = 0; i < count; i++) {
                const fn = firstNames[i % firstNames.length];
                const ln = lastNames[(i * 3) % lastNames.length];
                const gaji = (7 + (i % 35)) * 1000000;
                while (usedNiks.has('317104' + sequence)) sequence++;
                const generatedNik = '317104' + sequence++;
                usedNiks.add(generatedNik);
                batch.push(normalizeEmployeeRecord({
                    id: 'emp_bulk_' + Date.now() + '_' + i,
                    month: m, year: y,
                    name: `${fn} ${ln} #${i + 1}`,
                    nik: generatedNik,
                    position: positions[i % positions.length],
                    subjectType: subjType,
                    ptkp: ptkpKeys[i % ptkpKeys.length],
                    taxMethod: methods[i % methods.length],
                    gajiPokok: gaji,
                    tunjangan: Math.round(gaji * 0.15),
                    bonus: 0, thr: 0, kompensasi: 0, bpjs: 0, hasNpwp: true
                }));
            }
            employeesData = [...batch, ...employeesData];
            saveEmployeesToStorage();
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            document.getElementById('bulkGeneratorModal').classList.add('hidden');
            showToast(`Berhasil menambahkan ${count} pegawai sekaligus!`);
        }
        function switchSubject(subj) {
            currentSubject = subj;
            ['pegawai_bulanan', 'pegawai_tahunan', 'pensiunan', 'tidak_tetap', 'bukan_pegawai', 'pesangon_final'].forEach(c => {
                document.getElementById('accHeader_' + c)?.classList.toggle('active', c === subj && currentWorkspaceMode === 'simulator');
            });
            const simMonthEl = document.getElementById('simPeriodMonth');
            if (simMonthEl) {
                if (subj === 'pegawai_tahunan') simMonthEl.value = '12';
                else if (simMonthEl.value === '12') simMonthEl.value = '11';
            }
            const badgeEl = document.getElementById('simActiveModuleBadge');
            if (badgeEl) badgeEl.textContent = SUBJECT_TITLES[subj] || subj;
            const hdrTitle = document.getElementById('simSavedListHeaderTitle');
            if (hdrTitle) hdrTitle.textContent = `Daftar Data Tersimpan — ${SUBJECT_TITLES[subj] || subj}`;

            // Update tombol Template & tombol Tarik Data Bulanan sesuai modul aktif
            const tplCfg = MODULE_TEMPLATE_CONFIG[subj] || MODULE_TEMPLATE_CONFIG.pegawai_bulanan;
            const tplBtnLabel = document.getElementById('simDownloadTemplateBtnLabel');
            if (tplBtnLabel) tplBtnLabel.textContent = tplCfg.shortLabel;

            const pullBtn = document.getElementById('simPullMonthlyBtn');
            if (pullBtn) pullBtn.classList.toggle('hidden', subj !== 'pegawai_tahunan');

            if (subj === 'pegawai_tahunan') {
                pullFromMonthlyEmployees(true);
            }
            renderSimulatorSavedList();
        }

        function loadEmployeeIntoSimulator(empId) {
            editEmployee(empId);
        }

        function saveCurrentSimulatorToEmployeeList() {
            openAddEmployeeModal(currentSubject);
        }

        function calculateAll() {
            renderSimulatorSavedList();
        }

        function updateWorksheetA1Field(empId, field, rawVal) {
            const emp = employeesData.find(x => x.id === empId);
            if (!emp) return;

            if (field === 'workMonths') {
                emp.workMonths = Math.min(12, Math.max(1, parseInt(rawVal, 10) || 12));
            } else if (field === 'ptkp') {
                emp.ptkp = PTKP_MAP[rawVal] ? rawVal : emp.ptkp;
            } else if (field === 'taxMethod') {
                emp.taxMethod = ['GROSS', 'GROSS_UP', 'NETT'].includes(rawVal) ? rawVal : emp.taxMethod;
            } else {
                emp[field] = parseCurrency(rawVal);
            }

            saveEmployeesToStorage();
            const updatedCalc = computeEmployeeTaxRow(emp);
            populateWorksheetAndJournalDOM(emp, updatedCalc);
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            showToast('Kertas Kerja 1721-A1 berhasil diperbarui!');
        }

        function resetWorksheetA1Overrides(empId) {
            const emp = employeesData.find(x => x.id === empId);
            if (!emp) return;
            emp.a1ManualRegBruto = null;
            emp.a1ManualIrregBruto = null;
            emp.a1ManualBpjsComp = null;
            emp.a1ManualBiayaJabatan = null;
            emp.a1ManualIuranPensiun = null;
            emp.a1ManualIuranJht = null;
            emp.a1ManualPriorTer = null;
            saveEmployeesToStorage();
            const updatedCalc = computeEmployeeTaxRow(emp);
            populateWorksheetAndJournalDOM(emp, updatedCalc);
            renderEmployeePayrollTable();
            renderSimulatorSavedList();
            showToast('Kertas Kerja dikembalikan ke perhitungan otomatis.');
        }

        function populateWorksheetAndJournalDOM(emp, c) {
            document.getElementById('worksheetTitle').textContent = `Kertas Kerja PPh Pasal 21 — ${emp.name}`;
            document.getElementById('worksheetSubtitle').textContent = `${emp.position} • Status PTKP: ${emp.ptkp} (TER ${c.terCat}) • Metode: ${emp.taxMethod}`;
            const legalTagEl = document.getElementById('worksheetLegalTag');

            if (c.isAnnualA1) {
                if (legalTagEl) legalTagEl.textContent = '1721-A1 (Bisa Diedit)';
                const hasManualOverride = [
                    emp.a1ManualRegBruto, emp.a1ManualIrregBruto, emp.a1ManualBpjsComp,
                    emp.a1ManualBiayaJabatan, emp.a1ManualIuranPensiun, emp.a1ManualIuranJht, emp.a1ManualPriorTer
                ].some(v => v !== null && v !== undefined);

                const activeBrackets = (c.pasal17Breakdown || []).filter(b => b.taxableAmount > 0);
                const bracketRows = activeBrackets.length > 0
                    ? activeBrackets.map(b => ({
                        label: `• Tarif Pasal 17 Lapisan ${b.label} × ${formatRupiah(b.taxableAmount)}`,
                        val: formatRupiah(b.taxAmount)
                    }))
                    : [{ label: '• Tarif Pasal 17 Lapisan s.d. Rp 60.000.000 (5%) × Rp 0', val: 'Rp 0' }];

                const makeEditCurrencyInput = (field, amount, isNegative = false) => `
                    <div class="inline-flex items-center justify-end gap-1.5">
                        <span class="text-slate-400 font-mono text-[11px]">${isNegative ? '- Rp' : 'Rp'}</span>
                        <input type="text"
                            value="${formatRupiah(amount, false)}"
                            oninput="formatCurrencyInput(this)"
                            onchange="updateWorksheetA1Field('${emp.id}', '${field}', this.value)"
                            class="ws-edit-input w-36 px-2.5 py-1 text-right font-mono font-semibold text-xs rounded-lg border border-blue-300 bg-blue-50/40 hover:bg-white focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                            title="Klik untuk mengedit nilai ini secara langsung">
                    </div>
                `;

                const a1ControlBar = `
                    <tr class="bg-blue-50/70 border-b border-blue-200/80 no-print">
                        <td colspan="2" class="py-2.5 px-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-3 text-[11px]">
                                    <span class="font-bold text-blue-800 flex items-center gap-1"><i class="fa-solid fa-pen-to-square"></i> Mode Edit Kertas Kerja Aktif:</span>
                                    <label class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                        <span>Masa Kerja:</span>
                                        <select onchange="updateWorksheetA1Field('${emp.id}', 'workMonths', this.value)" class="px-2 py-0.5 rounded border border-blue-300 bg-white font-bold text-blue-900 cursor-pointer">
                                            ${Array.from({length: 12}, (_, i) => i + 1).map(m => `<option value="${m}" ${c.workMonths === m ? 'selected' : ''}>${m} Bulan</option>`).join('')}
                                        </select>
                                    </label>
                                    <label class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                        <span>PTKP:</span>
                                        <select onchange="updateWorksheetA1Field('${emp.id}', 'ptkp', this.value)" class="px-2 py-0.5 rounded border border-blue-300 bg-white font-bold text-blue-900 cursor-pointer">
                                            ${Object.keys(PTKP_MAP).map(k => `<option value="${k}" ${emp.ptkp === k ? 'selected' : ''}>${k}</option>`).join('')}
                                        </select>
                                    </label>
                                    <label class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                        <span>Metode:</span>
                                        <select onchange="updateWorksheetA1Field('${emp.id}', 'taxMethod', this.value)" class="px-2 py-0.5 rounded border border-blue-300 bg-white font-bold text-blue-900 cursor-pointer">
                                            <option value="GROSS" ${emp.taxMethod === 'GROSS' ? 'selected' : ''}>GROSS</option>
                                            <option value="GROSS_UP" ${emp.taxMethod === 'GROSS_UP' ? 'selected' : ''}>GROSS_UP</option>
                                            <option value="NETT" ${emp.taxMethod === 'NETT' ? 'selected' : ''}>NETT</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    ${hasManualOverride ? `<button type="button" onclick="resetWorksheetA1Overrides('${emp.id}')" class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-[11px] flex items-center gap-1 cursor-pointer"><i class="fa-solid fa-rotate-left"></i> Reset Hitungan Otomatis</button>` : `<span class="text-[11px] text-slate-500">Klik kotak angka untuk mengedit langsung</span>`}
                                </div>
                            </div>
                        </td>
                    </tr>
                `;

                const a1Rows = [
                    { section: `A. PENGHASILAN BRUTO SETAHUN (${c.workMonths} BULAN KERJA — ${c.linkedMonthlyCount > 0 ? 'TERINTEGRASI MODUL BULANAN' : 'AKUMULASI TAHUNAN'})` },
                    { label: `Gaji Pokok, Tunjangan & Kompensasi Setahun (${c.workMonths} bln × ${formatRupiah(c.gaji + c.tunj + c.kompensasi)})`, htmlVal: makeEditCurrencyInput('a1ManualRegBruto', c.annualRegularBruto, false) },
                    { label: 'Bonus, THR & Penghasilan Tidak Teratur Setahun (Akumulasi Jan–Des)', htmlVal: makeEditCurrencyInput('a1ManualIrregBruto', c.annualIrregularBruto, false) },
                    { label: `Premi JKK, JKM & BPJS Kesehatan Perusahaan Setahun (${c.workMonths} bln × ${formatRupiah(c.bpjsComp)})`, htmlVal: makeEditCurrencyInput('a1ManualBpjsComp', c.annualBpjsComp, false) },
                    { label: 'Jumlah Penghasilan Bruto Setahun', val: formatRupiah(c.annualBruto), bold: true },

                    { section: 'B. PENGURANG PENGHASILAN BRUTO SETAHUN' },
                    { label: `1. Biaya Jabatan (5% × Bruto, Maks. Rp 500.000/bln × ${c.workMonths} bln)`, htmlVal: makeEditCurrencyInput('a1ManualBiayaJabatan', c.biayaJabatan, true) },
                    { label: `2. Iuran Pensiun / Jaminan Pensiun (${c.workMonths} bln × ${formatRupiah(c.iuranPensiun)})`, htmlVal: makeEditCurrencyInput('a1ManualIuranPensiun', c.annualIuranPensiun, true) },
                    { label: `3. Iuran Jaminan Hari Tua / JHT (${c.workMonths} bln × ${formatRupiah(c.iuranJht)})`, htmlVal: makeEditCurrencyInput('a1ManualIuranJht', c.annualIuranJht, true) },
                    { label: 'Jumlah Pengurang Penghasilan Bruto (Biaya Jabatan + Pensiun + JHT)', val: `- ${formatRupiah(c.totalPengurang)}`, bold: true },

                    { section: 'C. PENGHASILAN NETO, PTKP & PKP SETAHUN' },
                    { label: 'Jumlah Penghasilan Neto Setahun (Bruto Setahun - Total Pengurang)', val: formatRupiah(c.annualNeto), bold: true },
                    { label: `Dikurangi: Penghasilan Tidak Kena Pajak / PTKP (${emp.ptkp})`, val: `- ${formatRupiah(c.ptkpAmount)}` },
                    { label: 'Penghasilan Kena Pajak (PKP) Setahun', val: formatRupiah(c.pkpSetahun), indigo: true },

                    { section: 'D. PERHITUNGAN TARIF PROGRESIF PASAL 17 UU HPP' },
                    ...bracketRows,
                    { label: 'Total PPh Pasal 21 Terutang Setahun (Pasal 17)', val: formatRupiah(c.pph21Setahun), bold: true },
                    { label: `Dikurangi: PPh Pasal 21 Telah Dipotong Masa Sebelumnya (${c.priorMonths} bln TER ${c.terCat})`, htmlVal: makeEditCurrencyInput('a1ManualPriorTer', c.priorTer, true) },
                    { label: 'PPh Pasal 21 Terutang Masa Desember / Resign (1721-A1)', val: formatRupiah(c.pph21), highlight: true },

                    { section: 'E. TAKE HOME PAY (THP) MASA DESEMBER / RESIGN' },
                    { label: 'Penghasilan Kas Bulan Ini (Gaji + Tunjangan + Bonus + THR + Kompensasi)', val: formatRupiah(c.gaji + c.tunj + c.bonus + c.thr + c.kompensasi) },
                    { label: 'Dikurangi: Iuran Pensiun & Iuran JHT Pegawai Bulan Ini', val: `- ${formatRupiah(c.bpjsEmpAll)}` },
                    { label: `Dikurangi: Potongan PPh Pasal 21 (${emp.taxMethod})`, val: emp.taxMethod === 'GROSS' ? `- ${formatRupiah(c.pph21)}` : 'Rp 0 (Ditanggung Perusahaan)' },
                    { label: 'Take Home Pay (THP) Diterima Pegawai', val: formatRupiah(c.thp), green: true }
                ];

                document.getElementById('worksheetTableBody').innerHTML = a1ControlBar + a1Rows.map(r => {
                    const rightCell = r.htmlVal !== undefined ? r.htmlVal : r.val;
                    if (r.section) return `<tr class="bg-slate-100/90 text-[10px] font-extrabold uppercase tracking-wider text-slate-600"><td colspan="2" class="py-2 px-4">${r.section}</td></tr>`;
                    if (r.indigo) return `<tr class="bg-indigo-50/80 font-bold text-indigo-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${rightCell}</td></tr>`;
                    if (r.highlight) return `<tr class="bg-amber-50 font-bold text-amber-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${rightCell}</td></tr>`;
                    if (r.green) return `<tr class="bg-emerald-50 font-bold text-emerald-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${rightCell}</td></tr>`;
                    if (r.bold) return `<tr class="bg-slate-50 font-bold text-slate-900"><td class="py-2 px-4">${r.label}</td><td class="py-2 px-4 text-right font-mono">${rightCell}</td></tr>`;
                    return `<tr><td class="py-1.5 px-4 text-slate-700 align-middle">${r.label}</td><td class="py-1.5 px-4 text-right font-mono align-middle">${rightCell}</td></tr>`;
                }).join('');
            } else if (emp.subjectType === 'bukan_pegawai' || (emp.subjectType === 'tidak_tetap' && c.dppAmount > 0)) {
                if (legalTagEl) legalTagEl.textContent = emp.taxMethod === 'GROSS_UP' ? '1721-VI (Gross Up Presisi)' : '1721-VI (DPP 50%)';
                const activeLayers = (c.pasal17Breakdown || []).filter(b => b.taxableAmountExact > 0);
                const layerRows = activeLayers.length > 0
                    ? activeLayers.map(b => ({
                        label: `• Lapis ${b.layerNum} (${b.ratePercent}% × ${formatRupiahPrecision(b.taxableAmountExact)})`,
                        val: `${formatRupiah(b.taxAmount)}${hasPrecisionFraction(b.taxAmountExact) ? ` <span class="text-[11px] text-slate-400 italic">(presisi: ${formatRupiahPrecision(b.taxAmountExact)})</span>` : ''}`
                    }))
                    : [{ label: '• Lapis 1 (5% × Rp 0)', val: 'Rp 0' }];

                const brutoPrecisionNote = hasPrecisionFraction(c.exactBruto)
                    ? ` <span class="text-[11px] font-normal text-slate-500 italic">(angka presisi: ${formatRupiahPrecision(c.exactBruto)})</span>`
                    : '';
                const dppPrecisionNote = hasPrecisionFraction(c.exactDpp)
                    ? ` <span class="text-[11px] font-normal text-slate-500 italic">(angka presisi: ${formatRupiahPrecision(c.exactDpp)})</span>`
                    : '';
                const taxPrecisionNote = hasPrecisionFraction(c.exactTax)
                    ? ` <span class="text-[11px] font-normal text-amber-700/80 italic">(angka presisi: ${formatRupiahPrecision(c.exactTax)})</span>`
                    : '';

                const rowsBp = [
                    { section: `1. HASIL PERHITUNGAN ${emp.taxMethod === 'GROSS_UP' ? 'GROSS UP' : emp.taxMethod} (${emp.subjectType === 'bukan_pegawai' ? 'BUKAN PEGAWAI / TENAGA AHLI' : 'PEGAWAI TIDAK TETAP'})` },
                    { label: emp.taxMethod === 'GROSS_UP' ? 'Penghasilan Neto (Terima Bersih Sebelum Gross Up)' : 'Penghasilan Honorarium / Imbalan Jasa', val: formatRupiah(c.netBeforeTax), bold: true },
                    ...(c.tunjanganPajak > 0 ? [{ label: 'Ditambah: Tunjangan PPh Pasal 21 (Gross Up)', val: `${formatRupiah(c.tunjanganPajak)}${taxPrecisionNote}` }] : []),
                    { label: `Penghasilan Bruto (${emp.taxMethod === 'GROSS_UP' ? 'Gross Up' : 'Dasar Pajak'})`, val: `${formatRupiah(c.brutoPajak)}${brutoPrecisionNote}`, bold: true },
                    { label: 'DPP (50% × Penghasilan Bruto)', val: `${formatRupiah(c.dppAmount)}${dppPrecisionNote}`, indigo: true },
                    { section: '2. RINCIAN PERHITUNGAN TARIF PASAL 17 ATAS DPP' },
                    ...layerRows,
                    { label: 'Total PPh Pasal 21 Terutang', val: `${formatRupiah(c.pph21)}${taxPrecisionNote}`, highlight: true },
                    { label: `Cek Terima Bersih (Bruto – PPh 21): ${formatRupiah(c.brutoPajak)} – ${formatRupiah(c.pph21)}`, val: formatRupiah(c.brutoPajak - c.pph21), green: true }
                ];

                document.getElementById('worksheetTableBody').innerHTML = rowsBp.map(r => {
                    if (r.section) return `<tr class="bg-slate-100/90 text-[10px] font-extrabold uppercase tracking-wider text-slate-600"><td colspan="2" class="py-2 px-4">${r.section}</td></tr>`;
                    if (r.indigo) return `<tr class="bg-indigo-50/80 font-bold text-indigo-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    if (r.highlight) return `<tr class="bg-amber-50 font-bold text-amber-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    if (r.green) return `<tr class="bg-emerald-50 font-bold text-emerald-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    if (r.bold) return `<tr class="bg-slate-50 font-bold text-slate-900"><td class="py-2 px-4">${r.label}</td><td class="py-2 px-4 text-right font-mono">${r.val}</td></tr>`;
                    return `<tr><td class="py-2 px-4 text-slate-700">${r.label}</td><td class="py-2 px-4 text-right font-mono">${r.val}</td></tr>`;
                }).join('');
            } else {
                if (legalTagEl) legalTagEl.textContent = '1721-VIII';
                const brutoPrecisionNote = hasPrecisionFraction(c.exactBruto)
                    ? ` <span class="text-[11px] font-normal text-slate-500 italic">(presisi: ${formatRupiahPrecision(c.exactBruto)})</span>`
                    : '';
                const taxPrecisionNote = hasPrecisionFraction(c.exactTax)
                    ? ` <span class="text-[11px] font-normal text-amber-700/80 italic">(presisi: ${formatRupiahPrecision(c.exactTax)})</span>`
                    : '';
                const rows = [
                    { label: 'Gaji Pokok & Penghasilan Utama', val: formatRupiah(c.gaji) },
                    { label: 'Tunjangan, Bonus, THR & Kompensasi', val: formatRupiah(c.tunj + c.bonus + c.thr + c.kompensasi) },
                    { label: 'Premi JKK, JKM & BPJS Kesehatan Ditanggung Perusahaan', val: formatRupiah(c.bpjsComp) },
                    ...(c.tunjanganPajak > 0 ? [{ label: 'Tunjangan PPh Pasal 21 (Gross-Up)', val: `${formatRupiah(c.tunjanganPajak)}${taxPrecisionNote}` }] : []),
                    { label: 'Total Penghasilan Bruto Dasar Pajak (TER)', val: `${formatRupiah(c.brutoPajak)}${brutoPrecisionNote}`, bold: true },
                    { label: `PPh Pasal 21 Terutang (${c.rateLabel})`, val: `${formatRupiah(c.pph21)}${taxPrecisionNote}`, highlight: true },
                    ...(emp.taxMethod === 'GROSS_UP' ? [{ label: `Cek Terima Bersih (Bruto Pajak – PPh 21): ${formatRupiah(c.brutoPajak)} – ${formatRupiah(c.pph21)}`, val: formatRupiah(c.brutoPajak - c.pph21) }] : []),
                    { label: 'Take Home Pay (THP) Diterima Pegawai', val: formatRupiah(c.thp), green: true }
                ];
                document.getElementById('worksheetTableBody').innerHTML = rows.map(r => {
                    if (r.highlight) return `<tr class="bg-amber-50 font-bold text-amber-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    if (r.green) return `<tr class="bg-emerald-50 font-bold text-emerald-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    if (r.bold) return `<tr class="bg-slate-50 font-bold text-slate-900"><td class="py-2.5 px-4">${r.label}</td><td class="py-2.5 px-4 text-right font-mono">${r.val}</td></tr>`;
                    return `<tr><td class="py-2 px-4 text-slate-700">${r.label}</td><td class="py-2 px-4 text-right font-mono">${r.val}</td></tr>`;
                }).join('');
            }

            const cashSalary = c.gaji + c.tunj + c.bonus + c.thr + c.kompensasi;
            const entries = [
                { coa: '6101 - Beban Gaji & Tunjangan', desc: `Gaji & Tunjangan (${htmlText(emp.name)})`, debit: cashSalary, credit: 0 },
                ...(c.tunjanganPajak > 0 ? [{ coa: '6102 - Beban Tunjangan PPh 21', desc: 'Tunjangan Pajak Gross-Up', debit: c.tunjanganPajak, credit: 0 }] : []),
                ...(emp.taxMethod === 'NETT' && c.pph21 > 0 ? [{ coa: '6109 - Beban PPh 21 Ditanggung PT', desc: 'PPh 21 Skema Nett', debit: c.pph21, credit: 0 }] : []),
                ...(c.bpjsComp > 0 ? [{ coa: '6105 - Beban Premi BPJS Perusahaan', desc: 'Premi BPJS Pemberi Kerja', debit: c.bpjsComp, credit: 0 }] : []),
                ...(c.pph21 > 0 ? [{ coa: '2104 - Utang PPh Pasal 21', desc: 'Kewajiban Setor Kas Negara', debit: 0, credit: c.pph21 }] : []),
                ...(c.bpjsComp + c.bpjsEmpAll > 0 ? [{ coa: '2106 - Utang Iuran BPJS', desc: 'Iuran BPJS Perusahaan + Karyawan', debit: 0, credit: c.bpjsComp + c.bpjsEmpAll }] : []),
                { coa: '1102 - Bank Operasional', desc: 'Pembayaran Take Home Pay (THP)', debit: 0, credit: c.thp }
            ];
            const totDeb = entries.reduce((s, e) => s + e.debit, 0);
            const totCred = entries.reduce((s, e) => s + e.credit, 0);
            document.getElementById('journalTableBody').innerHTML = entries.map(e => `
                <tr><td class="py-2 px-3.5 font-semibold">${e.coa}</td><td class="py-2 px-3.5 font-sans text-slate-500">${e.desc}</td><td class="py-2 px-3.5 text-right">${e.debit ? formatRupiah(e.debit,false) : '-'}</td><td class="py-2 px-3.5 text-right">${e.credit ? formatRupiah(e.credit,false) : '-'}</td></tr>
            `).join('');
            document.getElementById('journalTableFoot').innerHTML = `<tr><td colspan="2" class="py-2.5 px-3.5">TOTAL SEIMBANG</td><td class="py-2.5 px-3.5 text-right text-amber-400">${formatRupiah(totDeb)}</td><td class="py-2.5 px-3.5 text-right text-emerald-400">${formatRupiah(totCred)}</td></tr>`;
        }

        function viewEmployeeWorksheet(empId, tab = 'worksheet') {
            const emp = employeesData.find(x => x.id === empId);
            if (!emp) return;
            currentViewingWorksheetEmpId = empId;
            const c = computeEmployeeTaxRow(emp);
            populateWorksheetAndJournalDOM(emp, c);
            switchOutputTab(tab);
            document.getElementById('worksheetViewModal').classList.remove('hidden');
        }

        function openCurrentSimulatorWorksheetModal(tab = 'worksheet') {
            calculateAll();
            switchOutputTab(tab);
            document.getElementById('worksheetViewModal').classList.remove('hidden');
        }

        function closeWorksheetViewModal() {
            document.getElementById('worksheetViewModal').classList.add('hidden');
        }

        function switchOutputTab(tab) {
            ['worksheet', 'journal'].forEach(t => {
                const btn = document.getElementById('outTabBtn_' + t);
                const content = document.getElementById('outTabContent_' + t);
                if (btn) {
                    btn.className = t === tab
                        ? 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-2xs cursor-pointer'
                        : 'px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer';
                }
                if (content) content.classList.toggle('hidden', t !== tab);
            });
            const sel = document.getElementById('printTargetSelect');
            if (sel) sel.value = tab;
        }

        function printActiveWorksheetModal() {
            const target = document.getElementById('printTargetSelect')?.value || 'worksheet';
            switchOutputTab(target);
            window.print();
        }

        function renderSimulatorSavedList() {
            const tbody = document.getElementById('simSavedListTableBody');
            const tfoot = document.getElementById('simSavedListTableFoot');
            if (!tbody) return;
            const mVal = document.getElementById('simPeriodMonth')?.value || '11';
            const y = parseInt(document.getElementById('simPeriodYear')?.value || '2026', 10);
            const showAll = document.getElementById('simSavedShowAllModules')?.checked ?? false;
            const q = (document.getElementById('simSavedSearchInput')?.value || '').toLowerCase().trim();

            const subEl = document.getElementById('simSavedListSubtitle');
            if (subEl) {
                subEl.textContent = mVal === 'ALL'
                    ? `Periode Masa Pajak: Semua Bulan ${y}`
                    : `Periode Masa Pajak: ${MONTH_NAMES[parseInt(mVal, 10)] || ''} ${y}`;
            }

            const filtered = employeesData.filter(e => {
                const matchMonth = mVal === 'ALL' || Number(e.month) === parseInt(mVal, 10);
                if (!matchMonth || Number(e.year) !== y) return false;
                if (!showAll) {
                    if (currentSubject === 'pegawai_tahunan') {
                        if (!(e.subjectType === 'pegawai_tahunan' || Number(e.month) === 12)) return false;
                    } else if (e.subjectType !== currentSubject) {
                        return false;
                    }
                }
                if (q && !(String(e.name || '').toLowerCase().includes(q) || String(e.nik || '').includes(q) || String(e.position || '').toLowerCase().includes(q) || String(e.ptkp || '').toLowerCase().includes(q))) return false;
                return true;
            });

            let sumGaji = 0, sumTunj = 0, sumBonus = 0, sumThr = 0, sumKomp = 0, sumBpjs = 0, sumBruto = 0, sumTax = 0, sumThp = 0;
            const rowsData = filtered.map(emp => {
                const c = computeEmployeeTaxRow(emp);
                sumGaji += c.gaji; sumTunj += c.tunj; sumBonus += c.bonus; sumThr += c.thr; sumKomp += c.kompensasi;
                sumBpjs += c.bpjsComp; sumBruto += c.brutoPajak; sumTax += c.pph21; sumThp += c.thp;
                return { emp, c };
            });

            const kpiTarif = document.getElementById('kpiTarif');
            const kpiBruto = document.getElementById('kpiBruto');
            const kpiTax = document.getElementById('kpiTax');
            const kpiThp = document.getElementById('kpiThp');
            if (kpiTarif) kpiTarif.textContent = `${filtered.length} Pegawai`;
            if (kpiBruto) kpiBruto.textContent = formatRupiah(sumBruto);
            if (kpiTax) kpiTax.textContent = formatRupiah(sumTax);
            if (kpiThp) kpiThp.textContent = formatRupiah(sumThp);

            if (rowsData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="14" class="py-8 text-center text-slate-400">Belum ada data pada modul & periode ini. Klik tombol <b>+ Tambah Pegawai</b> atau <b>Aksi Massal (1.000+ Data)</b> untuk menambahkan data.</td></tr>`;
                if (tfoot) tfoot.innerHTML = '';
                return;
            }

            tbody.innerHTML = rowsData.slice(0, 200).map(({ emp, c }, idx) => {
                const rowNum = idx + 1;
                const monthStr = `${String(emp.month).padStart(2, '0')} - ${MONTH_NAMES[emp.month] || ''}`;

                if (inlineSpreadsheetMode) {
                    return `
                        <tr class="bg-blue-50/10 hover:bg-blue-50/40">
                            <td class="py-2 px-3 sticky-col-left bg-white">
                                <input type="text" value="${htmlText(emp.name)}" onchange="updateInlineEmpField('${emp.id}','name',this.value)" class="w-40 px-2 py-1 rounded border border-slate-300 font-bold text-xs">
                            </td>
                            <td class="py-2 px-2 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <select onchange="updateInlineEmpField('${emp.id}','month',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-semibold text-[11px]">
                                        ${Array.from({length: 12}, (_, i) => i + 1).map(m => `<option value="${m}" ${Number(emp.month)===m?'selected':''}>${String(m).padStart(2,'0')}</option>`).join('')}
                                    </select>
                                    <select onchange="updateInlineEmpField('${emp.id}','year',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-bold text-blue-700 text-[11px]">
                                        ${AVAILABLE_YEARS.map(yr => `<option value="${yr}" ${Number(emp.year)===yr?'selected':''}>${yr}</option>`).join('')}
                                    </select>
                                </div>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <select onchange="updateInlineEmpField('${emp.id}','ptkp',this.value)" class="px-1.5 py-1 rounded border border-slate-300 font-bold text-xs">
                                    ${Object.keys(PTKP_MAP).map(k => `<option value="${k}" ${emp.ptkp===k?'selected':''}>${k}</option>`).join('')}
                                </select>
                            </td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.gaji,false)}" onchange="updateInlineEmpField('${emp.id}','gajiPokok',this.value)" class="w-28 text-right px-2 py-1 rounded border border-slate-300 font-mono font-bold"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.tunj,false)}" onchange="updateInlineEmpField('${emp.id}','tunjangan',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.bonus,false)}" onchange="updateInlineEmpField('${emp.id}','bonus',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.thr,false)}" onchange="updateInlineEmpField('${emp.id}','thr',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.kompensasi,false)}" onchange="updateInlineEmpField('${emp.id}','kompensasi',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-2"><input type="text" value="${formatRupiah(c.bpjsComp,false)}" onchange="updateInlineEmpField('${emp.id}','bpjs',this.value)" class="w-24 text-right px-2 py-1 rounded border border-slate-300 font-mono"></td>
                            <td class="py-2 px-3 text-right font-mono font-bold">${formatRupiah(c.brutoPajak)}</td>
                            <td class="py-2 px-2 text-center font-mono text-[11px]">${c.rateLabel}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-amber-700">${formatRupiah(c.pph21)}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">${formatRupiah(c.thp)}</td>
                            <td class="py-2 px-3 text-center sticky-col-right bg-white">
                                <button type="button" onclick="deleteEmployee('${emp.id}')" class="w-7 h-7 rounded bg-rose-50 text-rose-600 cursor-pointer"><i class="fa-solid fa-trash text-[10px]"></i></button>
                            </td>
                        </tr>
                    `;
                }

                return `
                    <tr class="hover:bg-slate-50/90 transition group">
                        <td class="py-3 px-4 sticky-col-left bg-white group-hover:bg-slate-50/90">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono text-slate-400">#${rowNum}</span>
                                <span class="font-bold text-slate-900">${htmlText(emp.name)}</span>
                                ${(c.isAnnualA1 && (emp.syncedFromMonthly || c.linkedMonthlyCount > 0)) ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Terintegrasi otomatis dari modul Pegawai Tetap Bulanan"><i class="fa-solid fa-link mr-0.5"></i>Terintegrasi Bulanan</span>` : ''}
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="text-blue-600 font-medium">${htmlText(emp.position)}</span>
                                <span>•</span>
                                <span class="font-mono text-[10px] text-slate-400">${htmlText(emp.nik)}</span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">${monthStr}</span>
                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">Tahun ${emp.year}</div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">${emp.ptkp} (TER ${c.terCat})</span>
                            <div class="text-[10px] font-bold text-slate-500 mt-0.5">${emp.taxMethod}</div>
                        </td>
                        <td class="py-3 px-3.5 text-right font-mono font-semibold text-slate-900">${formatRupiah(c.gaji)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.tunj)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.bonus)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.thr)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.kompensasi)}</td>
                        <td class="py-3 px-3.5 text-right font-mono">${formatRupiah(c.bpjsComp)}</td>
                        <td class="py-3 px-3.5 text-right font-mono bg-slate-50/70 font-bold text-slate-900">${formatRupiah(c.brutoPajak)}</td>
                        <td class="py-3 px-3 text-center"><span class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-mono text-[11px] font-semibold">${c.rateLabel}</span></td>
                        <td class="py-3 px-3.5 text-right font-mono font-bold text-amber-700 bg-amber-50/20">${formatRupiah(c.pph21)}</td>
                        <td class="py-3 px-3.5 text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">${formatRupiah(c.thp)}</td>
                        <td class="py-3 px-4 text-center sticky-col-right bg-white group-hover:bg-slate-50/90">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" onclick="viewEmployeeWorksheet('${emp.id}', 'worksheet')" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold flex items-center gap-1 cursor-pointer">
                                    <i class="fa-regular fa-eye"></i><span>Kertas Kerja</span>
                                </button>
                                <button type="button" onclick="editEmployee('${emp.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-amber-100 text-slate-600 flex items-center justify-center cursor-pointer" title="Edit"><i class="fa-solid fa-pen text-[10px]"></i></button>
                                <button type="button" onclick="deleteEmployee('${emp.id}')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-100 text-slate-600 flex items-center justify-center cursor-pointer" title="Hapus"><i class="fa-solid fa-trash text-[10px]"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            if (tfoot) {
                tfoot.innerHTML = `
                    <tr>
                        <td colspan="3" class="py-3.5 px-4 sticky-col-left bg-slate-900">TOTAL MODUL (${rowsData.length} PEGAWAI)</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumGaji)}</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumTunj)}</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumBonus)}</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumThr)}</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumKomp)}</td>
                        <td class="py-3.5 px-3.5 text-right">${formatRupiah(sumBpjs)}</td>
                        <td class="py-3.5 px-3.5 text-right bg-slate-950">${formatRupiah(sumBruto)}</td>
                        <td class="py-3.5 px-3 text-center">-</td>
                        <td class="py-3.5 px-3.5 text-right text-amber-400">${formatRupiah(sumTax)}</td>
                        <td class="py-3.5 px-3.5 text-right text-emerald-400">${formatRupiah(sumThp)}</td>
                        <td class="py-3.5 px-4 sticky-col-right bg-slate-900"></td>
                    </tr>
                `;
            }
        }

        Object.assign(window, {
            toggleMobileSidebar,
            toggleLeftSidebar,
            toggleSimulatorDropdownMenu,
            switchWorkspaceMode,
            switchSubject,
            setTaxMethod,
            openTerModal,
            closeTerModal,
            renderModalTerTable,
            exportPayrollCsv,
            exportCoretaxXml,
            selectActiveCompany,
            onPayrollPeriodChange,
            copyPreviousMonthEmployees,
            setPayrollPeriodMode,
            renderEmployeePayrollTable,
            openAddEmployeeModal,
            closeManualEmployeeModal,
            configureModalFieldsByModule,
            onDailySchemeChange,
            saveManualEmployeeSubmit,
            editEmployee,
            deleteEmployee,
            toggleInlineSpreadsheetMode,
            updateInlineEmpField,
            openBulkGeneratorModal,
            generateBulkEmployees,
            applyBulkThrOrBonus,
            downloadImportTemplateExcel,
            onImportModuleTypeChange,
            openImportModal,
            closeImportModal,
            clearStagedImport,
            handleExcelFileUpload,
            handlePasteImportPreview,
            refreshImportPreviewFromInputs,
            processImportData,
            resetSampleEmployees,
            changePayrollPageSize,
            goToPayrollPage,
            onSimPeriodChange,
            openCurrentSimulatorWorksheetModal,
            saveCurrentSimulatorToEmployeeList,
            loadEmployeeIntoSimulator,
            calculateAll,
            formatCurrencyInput,
            renderSimulatorSavedList,
            autoFormatIdTku,
            resetCompanyForm,
            editCompanyRecord,
            viewEmployeeWorksheet,
            closeWorksheetViewModal,
            switchOutputTab,
            printActiveWorksheetModal,
            pullFromMonthlyEmployees,
            updateWorksheetA1Field,
            resetWorksheetA1Overrides,
            toggleCyberDarkTheme,
        });

const bootstrap = JSON.parse(document.getElementById('taxcore-bootstrap').textContent);
const permissions = new Set(bootstrap.permissions);
let allEmployees = bootstrap.employees;
let savedEmployees = [];
let saving = false;
let queuedToast = null;
const referenceToast = showToast;

function htmlText(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[character]);
}

async function workspaceRequest(url, data, method = 'POST') {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: { Accept:'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(data),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Penyimpanan gagal. Muat ulang dan coba lagi.');
    }
    return result;
}

function refreshWorkspace() {
    populateCompanySelects();
    renderCompanyTable();
    renderEmployeePayrollTable();
    renderSimulatorSavedList();
    applyWorkspacePermissions();
}

function selectBranch(id) {
    activeCompanyId = id;
    employeesData = allEmployees.filter(record => record.companyId === id).map(normalizeEmployeeRecord);
    savedEmployees = structuredClone(employeesData);
    currentPayrollPage = 1;
    refreshWorkspace();
}

showToast = message => {
    if (saving) queuedToast = message;
    else referenceToast(message);
};

saveEmployeesToStorage = () => {
    if (saving) return;
    const branch = companiesData.find(company => company.id === activeCompanyId);
    if (!branch || !permissions.has('payroll.edit')) {
        employeesData = structuredClone(savedEmployees);
        refreshWorkspace();
        referenceToast('Pilih perusahaan yang dapat diedit terlebih dahulu.');
        return;
    }
    saving = true;
    document.body.dataset.saving = 'true';
    document.body.setAttribute('aria-busy', 'true');
    const openModal = ['manualEmployeeModal', 'importExcelModal'].find(id => !document.getElementById(id).classList.contains('hidden'));
    queueMicrotask(async () => {
        try {
            const previous = new Map(savedEmployees.map(record => [record.id, record]));
            const current = employeesData.map(record => normalizeEmployeeRecord({ ...record, companyId:activeCompanyId, _version:previous.get(record.id)?._version }));
            const currentIds = new Set(current.map(record => record.id));
            const upserts = current.filter(record => JSON.stringify(record) !== JSON.stringify(previous.get(record.id)));
            const deletes = savedEmployees.filter(record => !currentIds.has(record.id)).map(record => ({ id:record.id, _version:record._version }));
            if (upserts.length || deletes.length) {
                const result = await workspaceRequest(branch.saveUrl, { upserts, deletes });
                employeesData = result.employees.map(normalizeEmployeeRecord);
                savedEmployees = structuredClone(employeesData);
                allEmployees = [...allEmployees.filter(record => record.companyId !== activeCompanyId), ...employeesData];
            }
            if (queuedToast) referenceToast(queuedToast);
        } catch (error) {
            employeesData = structuredClone(savedEmployees);
            if (openModal) document.getElementById(openModal).classList.remove('hidden');
            referenceToast(error.message);
        } finally {
            queuedToast = null;
            saving = false;
            delete document.body.dataset.saving;
            document.body.removeAttribute('aria-busy');
            refreshWorkspace();
        }
    });
};

selectActiveCompany = id => {
    if (saving) return;
    selectBranch(id);
    rememberSelection();
    calculateAll();
    referenceToast('Perusahaan Pemotong Aktif berhasil diubah.');
};

function fitManagementFrame(frame) {
    const document = frame.contentDocument;
    if (!document) return;
    updateManagementTheme(frame);
    frame.style.height = `${document.documentElement.scrollHeight}px`;
    new ResizeObserver(() => { frame.style.height = `${document.body.scrollHeight}px`; }).observe(document.body);
}

function updateManagementTheme(frame) {
    frame.contentDocument?.documentElement.classList.toggle('dark', document.body.classList.contains('cyber-dark'));
}

const referenceToggleTheme = toggleCyberDarkTheme;
toggleCyberDarkTheme = () => {
    referenceToggleTheme();
    document.querySelectorAll('#accessFrame, #auditFrame').forEach(updateManagementTheme);
};

const referenceSwitchWorkspace = switchWorkspaceMode;
switchWorkspaceMode = (mode, preserveCalc = false) => {
    if (mode === 'access' && !permissions.has('access.manage')) return;
    if (mode === 'audit' && bootstrap.user.role !== 'SUPER_ADMIN') return;
    referenceSwitchWorkspace(mode, preserveCalc);
    rememberSelection();
    if (mode === 'access' || mode === 'audit') {
        const frame = document.getElementById(mode === 'access' ? 'accessFrame' : 'auditFrame');
        const url = mode === 'access' ? frame.dataset.src : companiesData.find(company => company.id === activeCompanyId)?.auditUrl;
        if (frame && url) {
            frame.onload = () => fitManagementFrame(frame);
            frame.src = url;
        }
        if (mode === 'audit') document.getElementById('topHeaderTitle').textContent = 'Log Audit';
    }
    applyWorkspacePermissions();
};

const referenceExcelExport = exportPayrollCsv;
const referenceXmlExport = exportCoretaxXml;
async function auditWorkspaceExport(format) {
    const company = companiesData.find(company => company.id === activeCompanyId);
    if (!company || saving) return;
    try {
        const month = document.getElementById('payrollPeriodMonth').value;
        await workspaceRequest(company.exportUrl, { format, month:month === 'ALL' ? 0 : Number(month), year:Number(document.getElementById('payrollPeriodYear').value) });
        if (format === 'xlsx') referenceExcelExport();
        else referenceXmlExport();
    } catch (error) {
        referenceToast(error.message);
    }
}
exportPayrollCsv = () => auditWorkspaceExport('xlsx');
exportCoretaxXml = () => auditWorkspaceExport('xml');

async function saveCompanyDatabaseSubmit() {
    if (saving) return;
    const value = id => document.getElementById(id).value.trim();
    const record = {
        id:value('compEditId') || null, name:value('compNameInput'), branchName:value('compBranchNameInput'),
        npwp:value('compNpwpInput'), nitkuSuffix:value('compNitkuSuffixInput'), signerName:value('compSignerNameInput'),
        signerNpwp:value('compSignerNpwpInput'), city:value('compCityInput'), address:value('compAddressInput'),
    };
    saving = true;
    document.body.dataset.saving = 'true';
    try {
        const result = await workspaceRequest(bootstrap.urls.saveCompany, record);
        companiesData = result.companies;
        if (document.getElementById('compSetAsActiveCheckbox').checked || !activeCompanyId) selectBranch(result.company.id);
        resetCompanyForm();
        referenceToast(`Profil perusahaan "${record.name}" disimpan!`);
    } catch (error) {
        referenceToast(error.message);
    } finally {
        saving = false;
        delete document.body.dataset.saving;
        refreshWorkspace();
    }
}

async function deleteCompanyRecord(id) {
    if (saving) return;
    if (companiesData.length <= 1) {
        referenceToast('Minimal harus ada 1 perusahaan terdaftar.');
        return;
    }
    const company = companiesData.find(company => company.id === id);
    if (!company) return;
    saving = true;
    document.body.dataset.saving = 'true';
    try {
        const result = await workspaceRequest(company.deleteUrl, {}, 'DELETE');
        companiesData = result.companies;
        allEmployees = allEmployees.filter(record => record.companyId !== id);
        if (activeCompanyId === id) selectBranch(companiesData[0]?.id ?? null);
        referenceToast('Profil perusahaan dihapus.');
    } catch (error) {
        referenceToast(error.message);
    } finally {
        saving = false;
        delete document.body.dataset.saving;
        refreshWorkspace();
    }
}

resetSampleEmployees = () => {
    employeesData = employeesData.filter(record => !record.id.startsWith('emp_bulk_'));
    saveEmployeesToStorage();
    refreshWorkspace();
    showToast('Data contoh generator dihapus.');
};

function applyWorkspacePermissions() {
    document.querySelectorAll('[onclick]').forEach(button => {
        const action = button.getAttribute('onclick');
        if (/openAddEmployeeModal|editEmployee|deleteEmployee|openBulkGeneratorModal|applyBulkThrOrBonus|generateBulkEmployees|resetSampleEmployees|copyPreviousMonthEmployees|pullFromMonthlyEmployees|toggleInlineSpreadsheetMode/.test(action)) {
            button.hidden = !permissions.has('payroll.edit') || !activeCompanyId;
        }
        if (/openImportModal|downloadImportTemplateExcel/.test(action)) button.hidden = !permissions.has('payroll.import') || !activeCompanyId;
        if (/exportPayrollCsv|exportCoretaxXml/.test(action)) button.hidden = !permissions.has('payroll.export') || !activeCompanyId;
        if (/saveCompanyDatabaseSubmit|editCompanyRecord|deleteCompanyRecord/.test(action)) button.hidden = !permissions.has('company.manage');
        if (/switchWorkspaceMode\('access'/.test(action)) button.hidden = !permissions.has('access.manage');
    });
    document.querySelectorAll('#worksheetTableBody input, #worksheetTableBody select').forEach(field => { field.disabled = !permissions.has('payroll.correct'); });
}

function rememberSelection() {
    const url = new URL(bootstrap.urls.dashboard);
    if (activeCompanyId) url.searchParams.set('branch_id', activeCompanyId.replace('branch_', ''));
    url.searchParams.set('mode', currentWorkspaceMode);
    const isSimulator = currentWorkspaceMode === 'simulator';
    const prefix = isSimulator ? 'simPeriod' : 'payrollPeriod';
    const month = document.getElementById(prefix + 'Month')?.value;
    const year = document.getElementById(prefix + 'Year')?.value;
    if (month) url.searchParams.set('month', month === 'ALL' ? '0' : month);
    if (year) url.searchParams.set('year', year);
    if (isSimulator) url.searchParams.set('module', Object.entries({ monthly:'pegawai_bulanan', annual:'pegawai_tahunan', pension:'pensiunan', daily:'tidak_tetap', contractor:'bukan_pegawai', severance:'pesangon_final' }).find(([, subject]) => subject === currentSubject)?.[0] ?? 'monthly');
    history.replaceState(null, '', url);
}

document.addEventListener('click', event => {
    if (saving && event.target.closest('button, input, select, a')) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);
document.addEventListener('keydown', event => {
    if (saving && event.key !== 'Tab') {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

const dialogs = {
    manualEmployeeModal:closeManualEmployeeModal, terModal:closeTerModal, importExcelModal:closeImportModal,
    bulkGeneratorModal:() => document.getElementById('bulkGeneratorModal').classList.add('hidden'), worksheetViewModal:closeWorksheetViewModal,
};
for (const [id, close] of Object.entries(dialogs)) {
    const modal = document.getElementById(id);
    if (!modal) continue;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', modal.querySelector('h3')?.textContent ?? 'TaxCore');
    let previousFocus;
    new MutationObserver(() => {
        if (!modal.classList.contains('hidden')) {
            previousFocus = document.activeElement;
            const focus = modal.querySelector('input:not([type="hidden"]), select, button');
            focus?.focus();
            applyWorkspacePermissions();
        } else previousFocus?.focus();
    }).observe(modal, { attributes:true, attributeFilter:['class'] });
    modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') close();
        if (event.key !== 'Tab') return;
        const fields = [...modal.querySelectorAll('button, input, select, textarea, a[href]')].filter(field => !field.disabled && field.getClientRects().length);
        if (event.shiftKey && document.activeElement === fields[0]) { event.preventDefault(); fields.at(-1)?.focus(); }
        else if (!event.shiftKey && document.activeElement === fields.at(-1)) { event.preventDefault(); fields[0]?.focus(); }
    });
}

companiesData = bootstrap.companies;
document.getElementById('sidebarUserName').textContent = bootstrap.user.name;
document.getElementById('sidebarUserRole').textContent = `${bootstrap.user.role} // VERIFIED`;
document.getElementById('sidebarUserInitial').textContent = bootstrap.user.name.slice(0, 1).toUpperCase();
Object.assign(window, {
    selectActiveCompany, switchWorkspaceMode, saveCompanyDatabaseSubmit, deleteCompanyRecord, resetSampleEmployees, toggleCyberDarkTheme, exportPayrollCsv, exportCoretaxXml,
    logoutSession:() => document.getElementById('workspace-logout').requestSubmit(),
});
selectBranch(bootstrap.activeCompanyId ?? companiesData[0]?.id ?? null);
const selection = bootstrap.selection ?? {};
for (const prefix of ['payrollPeriod', 'simPeriod']) {
    if (selection.year) document.getElementById(prefix + 'Year').value = String(selection.year);
    if (selection.month !== undefined && selection.month !== null) document.getElementById(prefix + 'Month').value = Number(selection.month) === 0 ? 'ALL' : String(selection.month);
}
if (selection.module && (bootstrap.mode === 'simulator')) {
    const subjects = { monthly:'pegawai_bulanan', annual:'pegawai_tahunan', pension:'pensiunan', daily:'tidak_tetap', contractor:'bukan_pegawai', severance:'pesangon_final' };
    switchSubject(subjects[selection.module]);
}
if (selection.module === 'annual' || Number(selection.month) === 12) setPayrollPeriodMode('TAHUNAN');
switchWorkspaceMode(bootstrap.mode ?? (companiesData.length ? 'payroll' : 'company'));
document.addEventListener('change', event => {
    if (/^(payrollPeriod|simPeriod)/.test(event.target.id)) rememberSelection();
});
document.addEventListener('click', event => {
    if (event.target.closest('[onclick*="switchSubject"]')) rememberSelection();
});

