/**
 * Agapornis Gene-Forge v6.8
 * メインアプリケーション JavaScript
 * 6言語対応版 (ja/en/de/fr/it/es)
 */

// ========================================
// 多言語サポート
// ========================================
// Note: LANG (language code string like 'ja') is defined in index.php
// T_DICT holds the translation dictionary
let T_DICT = {};

const LANG_STORAGE_KEY = 'geneforge_last_lang';

function initLang(dict) {
    T_DICT = dict || {};
    // Also ensure window.T is set for compatibility
    if (!window.T) window.T = T_DICT;

    // v7.3.20: 言語変更検出 - 変更時は全キャッシュをクリア
    if (typeof LANG !== 'undefined') {
        const lastLang = localStorage.getItem(LANG_STORAGE_KEY);
        if (lastLang && lastLang !== LANG) {
            console.log(`[App] Language changed: ${lastLang} → ${LANG}, clearing caches`);
            clearAllCachesOnLangChange();
        }
        localStorage.setItem(LANG_STORAGE_KEY, LANG);
    }
}

/**
 * v7.3.20: 言語変更時に全キャッシュをクリア
 * バイリンガル使用を想定しない設計
 */
function clearAllCachesOnLangChange() {
    // FamilyMap関連のキャッシュ
    localStorage.removeItem('familyMapBeforeInfer');
    localStorage.removeItem('familyMapSaved');

    // FamilyMapのデータもクリア（オブジェクトがあれば）
    if (typeof FamilyMap !== 'undefined' && FamilyMap.clearAllData) {
        FamilyMap.clearAllData();
    }

    console.log('[App] All caches cleared due to language change');
}

function t(key) {
    return T_DICT[key] || window.T?.[key] || key;
}

/**
 * v7.3.20: 鳥の表現型表示名を動的に取得
 * observed.baseColor（色キー）から現在の言語でラベルを生成
 * キャッシュされたphenotypeではなく、常に現在の言語設定を使用
 */
function getPhenotypeDisplay(bird) {
    // observed.baseColorがあれば、それを動的に翻訳
    if (bird.observed?.baseColor && typeof keyToLabel === 'function') {
        return keyToLabel(bird.observed.baseColor);
    }
    // フォールバック: 保存済みのphenotypeを使用
    return bird.phenotype || '?';
}

// ========================================
// タブ切替
// ========================================
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.guide-item').forEach(g => g.classList.remove('active'));
    const tab = document.getElementById(tabId);
    if (tab) {
        tab.classList.add('active');
        // v7.3.11: スクロールを復元
        setTimeout(() => {
            tab.scrollIntoView({behavior: 'smooth', block: 'start'});
        }, 50);
    }

    if (tabId === 'birddb') {
        refreshBirdList();
        refreshDBSelectors();
    }
}

// ========================================
// 連鎖設定
// ========================================
const defaultLinkage = {
    link_pld_cin: 3.0,
    link_ino_cin: 3.0,
    link_op_cin: 45.0,
    link_op_pld: 45.0,
    link_ino_op: 45.0,
    link_ino_pld: 0.0
};

function loadLinkageSettings() {
    const saved = localStorage.getItem('geneforge_linkage');
    const settings = saved ? JSON.parse(saved) : defaultLinkage;
    Object.keys(settings).forEach(key => {
        const el = document.getElementById(key);
        if (el) el.value = settings[key];
    });
}

function saveLinkageSettings() {
    const settings = {};
    Object.keys(defaultLinkage).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            let val = parseFloat(el.value);
            if (isNaN(val) || val < 0) val = 0;
            if (val > 50) val = 50;
            el.value = val;
            settings[key] = val;
        }
    });
    localStorage.setItem('geneforge_linkage', JSON.stringify(settings));
    showToast(t('settings_saved'));
}

function resetLinkageSettings() {
    Object.keys(defaultLinkage).forEach(key => {
        const el = document.getElementById(key);
        if (el) el.value = defaultLinkage[key];
    });
    localStorage.removeItem('geneforge_linkage');
    showToast(t('reset_complete'));
}

// ========================================
// トースト通知
// ========================================
function showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    if (type === 'error') toast.classList.add('toast-error');
    else if (type === 'success') toast.classList.add('toast-success');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// ========================================
// 個体DB管理
// ========================================
let currentEditBirdId = null;

function refreshBirdList() {
    const birds = BirdDB.getAllBirds();
    const stats = BirdDB.getStats();
    
    const statsEl = document.getElementById('dbStats');
    if (statsEl) {
        statsEl.innerHTML = `
            <div class="stat-card"><span class="stat-num">${stats.totalBirds}</span><span class="stat-label">${t('total_birds')}</span></div>
            <div class="stat-card"><span class="stat-num">${stats.males}</span><span class="stat-label">♂ ${t('males')}</span></div>
            <div class="stat-card"><span class="stat-num">${stats.females}</span><span class="stat-label">♀ ${t('females')}</span></div>
            <div class="stat-card"><span class="stat-num">${stats.lineages.length}</span><span class="stat-label">${t('lineages')}</span></div>
        `;
    }
    
    const lineageSelect = document.getElementById('birdFilterLineage');
    if (lineageSelect && stats.lineages.length > 0) {
        const currentVal = lineageSelect.value;
        lineageSelect.innerHTML = `<option value="">${t('all_lineage')}</option>`;
        stats.lineages.forEach(l => {
            lineageSelect.innerHTML += `<option value="${l}">${l}</option>`;
        });
        lineageSelect.value = currentVal;
    }
    
    const listEl = document.getElementById('birdList');
    if (!listEl) return;
    
    if (birds.length === 0) {
        listEl.innerHTML = `<div class="empty-state"><div class="empty-icon">🐣</div><p>${t('no_birds')}</p></div>`;
        return;
    }
    
    listEl.innerHTML = birds.map(bird => `
        <div class="bird-card" data-id="${bird.id}">
            <div class="bird-card-header">
                <span class="bird-name">${escapeHtml(bird.name)}</span>
                <span class="bird-code">${escapeHtml(bird.code)}</span>
                <span class="sex-badge sex-${bird.sex}">${bird.sex === 'male' ? '♂' : '♀'}</span>
            </div>
            <div class="bird-phenotype">${escapeHtml(getPhenotypeDisplay(bird))}</div>
            <div class="bird-meta">
                ${bird.lineage ? `<span class="bird-lineage">${escapeHtml(bird.lineage)}</span>` : ''}
                ${bird.birthDate ? `<span class="bird-date">${bird.birthDate}</span>` : ''}
            </div>
            <div class="bird-parents">
                ${bird.sire ? `<span class="parent-info">${t('father')}: ${escapeHtml(bird.sire.name || bird.sire.code || '?')}</span>` : ''}
                ${bird.dam ? `<span class="parent-info">${t('mother')}: ${escapeHtml(bird.dam.name || bird.dam.code || '?')}</span>` : ''}
            </div>
            <div class="bird-actions">
                <button class="btn btn-tiny" onclick="editBird('${bird.id}')">✏️ ${t('edit')}</button>
                <button class="btn btn-tiny" onclick="showPedigree('${bird.id}')">📜 ${t('pedigree')}</button>
                <button class="btn btn-tiny btn-danger" onclick="deleteBird('${bird.id}')">🗑️</button>
            </div>
        </div>
    `).join('');
}

function filterBirds() {
    const query = document.getElementById('birdSearch')?.value || '';
    const sex = document.getElementById('birdFilterSex')?.value || '';
    const lineage = document.getElementById('birdFilterLineage')?.value || '';
    
    const birds = BirdDB.searchBirds({ query, sex, lineage });
    
    const listEl = document.getElementById('birdList');
    if (!listEl) return;
    
    if (birds.length === 0) {
        listEl.innerHTML = `<div class="empty-state"><p>${t('no_match')}</p></div>`;
        return;
    }
    
    listEl.innerHTML = birds.map(bird => `
        <div class="bird-card" data-id="${bird.id}">
            <div class="bird-card-header">
                <span class="bird-name">${escapeHtml(bird.name)}</span>
                <span class="bird-code">${escapeHtml(bird.code)}</span>
                <span class="sex-badge sex-${bird.sex}">${bird.sex === 'male' ? '♂' : '♀'}</span>
            </div>
            <div class="bird-phenotype">${escapeHtml(getPhenotypeDisplay(bird))}</div>
            <div class="bird-actions">
                <button class="btn btn-tiny" onclick="editBird('${bird.id}')">✏️</button>
                <button class="btn btn-tiny" onclick="showPedigree('${bird.id}')">📜</button>
                <button class="btn btn-tiny btn-danger" onclick="deleteBird('${bird.id}')">🗑️</button>
            </div>
        </div>
    `).join('');
}

function openBirdForm(birdId = null) {
    currentEditBirdId = birdId;
    const modal = document.getElementById('birdModal');
    const title = document.getElementById('birdModalTitle');
    const form = document.getElementById('birdForm');
    
    if (!modal || !form) return;
    
    updateParentSelectors();
    generateGenotypeFields();
    
    if (birdId) {
        title.textContent = t('edit');
        const bird = BirdDB.getBird(birdId);
        if (bird) {
            document.getElementById('birdName').value = bird.name;
            document.getElementById('birdCode').value = bird.code;
            document.getElementById('birdSex').value = bird.sex;
            document.getElementById('birdBirthDate').value = bird.birthDate || '';
            document.getElementById('birdLineage').value = bird.lineage || '';
            document.getElementById('birdPhase').value = bird.phase || 'independent';
            document.getElementById('birdNotes').value = bird.notes || '';
            
            if (bird.sire?.id) document.getElementById('birdSire').value = bird.sire.id;
            if (bird.dam?.id) document.getElementById('birdDam').value = bird.dam.id;
            
            if (bird.genotype) {
                Object.keys(bird.genotype).forEach(key => {
                    const el = document.getElementById('geno_' + key);
                    if (el) el.value = bird.genotype[key];
                });
            }
        }
    } else {
        title.textContent = t('register');
        form.reset();
    }
    
    modal.classList.add('active');
}

function closeBirdForm() {
    const modal = document.getElementById('birdModal');
    if (modal) modal.classList.remove('active');
    currentEditBirdId = null;
}

function generateGenotypeFields() {
    const container = document.getElementById('genotypeFields');
    if (!container) return;

    const sex = document.getElementById('birdSex')?.value || 'male';

    // SSOT: Use global constants from genetics.php
    if (typeof GENOTYPE_OPTIONS === 'undefined' || typeof UI_GENOTYPE_LOCI === 'undefined') {
        console.warn('[App] GENOTYPE_OPTIONS or UI_GENOTYPE_LOCI not defined');
        return;
    }

    const loci = UI_GENOTYPE_LOCI.map(config => {
        const source = GENOTYPE_OPTIONS[config.source];
        let options;
        if (source.male && source.female) {
            // Sex-linked locus
            options = sex === 'male' ? source.male : source.female;
        } else {
            // Autosomal locus
            options = source.options;
        }
        return {
            key: config.key,
            label: config.label,
            options: options
        };
    });

    container.innerHTML = loci.map(locus => `
        <div class="form-group">
            <label class="form-label">${locus.label}</label>
            <select id="geno_${locus.key}">
                ${locus.options.map(([val, label]) => `<option value="${val}">${label}</option>`).join('')}
            </select>
        </div>
    `).join('');
}

function updateGenotypeOptions() {
    generateGenotypeFields();
}

function updateParentSelectors() {
    const birds = BirdDB.getAllBirds();
    const males = birds.filter(b => b.sex === 'male');
    const females = birds.filter(b => b.sex === 'female');
    
    const sireSelect = document.getElementById('birdSire');
    const damSelect = document.getElementById('birdDam');
    
    if (sireSelect) {
        sireSelect.innerHTML = `<option value="">${t('unknown_or_external')}</option>` + 
            males.map(b => `<option value="${b.id}">${escapeHtml(b.name)} (${escapeHtml(b.code)})</option>`).join('');
    }
    
    if (damSelect) {
        damSelect.innerHTML = `<option value="">${t('unknown_or_external')}</option>` + 
            females.map(b => `<option value="${b.id}">${escapeHtml(b.name)} (${escapeHtml(b.code)})</option>`).join('');
    }
    
    const stats = BirdDB.getStats();
    const lineageList = document.getElementById('lineageList');
    if (lineageList) {
        lineageList.innerHTML = stats.lineages.map(l => `<option value="${l}">`).join('');
    }
}

function saveBird(event) {
    event.preventDefault();
    
    const name = document.getElementById('birdName').value.trim();
    const sex = document.getElementById('birdSex').value;
    
    if (!name) {
        showToast(t('enter_name_required'));
        return;
    }
    
    const genotype = {};
    ['parblue', 'ino', 'op', 'cin', 'dark', 'vio', 'fl', 'dil', 'pi'].forEach(key => {
        const el = document.getElementById('geno_' + key);
        if (el) genotype[key] = el.value;
    });
    
    const sireId = document.getElementById('birdSire').value;
    const damId = document.getElementById('birdDam').value;

    // v7.0: 循環参照チェック（親子関係のループ防止）
    if (typeof BirdDB.validatePedigree === 'function') {
        const loopError = BirdDB.validatePedigree(currentEditBirdId || null, sireId, damId);
        if (loopError) {
            showToast(loopError.error, 'error');
            alert(loopError.error + '\n\n' + loopError.details);
            return;
        }
    }

    // v7.3.13: 拡張血統情報を取得
    const getPedigreeField = (fieldId) => {
        const el = document.getElementById(fieldId);
        return el ? el.value.trim() || null : null;
    };

    const pedigree = {
        sire: sireId || null,
        dam: damId || null,
        sire_sire: getPedigreeField('pedigree_sire_sire'),
        sire_dam: getPedigreeField('pedigree_sire_dam'),
        dam_sire: getPedigreeField('pedigree_dam_sire'),
        dam_dam: getPedigreeField('pedigree_dam_dam'),
        sire_sire_sire: getPedigreeField('pedigree_sire_sire_sire'),
        sire_sire_dam: getPedigreeField('pedigree_sire_sire_dam'),
        sire_dam_sire: getPedigreeField('pedigree_sire_dam_sire'),
        sire_dam_dam: getPedigreeField('pedigree_sire_dam_dam'),
        dam_sire_sire: getPedigreeField('pedigree_dam_sire_sire'),
        dam_sire_dam: getPedigreeField('pedigree_dam_sire_dam'),
        dam_dam_sire: getPedigreeField('pedigree_dam_dam_sire'),
        dam_dam_dam: getPedigreeField('pedigree_dam_dam_dam')
    };

    const birdData = {
        name,
        code: document.getElementById('birdCode').value.trim(),
        sex,
        birthDate: document.getElementById('birdBirthDate').value,
        lineage: document.getElementById('birdLineage').value.trim(),
        genotype,
        phase: document.getElementById('birdPhase').value,
        notes: document.getElementById('birdNotes').value.trim(),
        sire: sireId ? { id: sireId, ...BirdDB.getBird(sireId) } : null,
        dam: damId ? { id: damId, ...BirdDB.getBird(damId) } : null,
        pedigree: pedigree
    };

    if (currentEditBirdId) {
        BirdDB.updateBird(currentEditBirdId, birdData);
        showToast(t('updated'));
    } else {
        BirdDB.addBird(birdData);
        showToast(t('registered'));
    }
    
    closeBirdForm();
    refreshBirdList();
    refreshDBSelectors();
}

function editBird(id) {
    openBirdForm(id);
}

function deleteBird(id) {
    if (!confirm(t('confirm_delete'))) return;
    BirdDB.deleteBird(id);
    showToast(t('deleted'));
    refreshBirdList();
    refreshDBSelectors();
}

// ========================================
// 血統書表示
// ========================================
let currentPedigreeBirdId = null;

function showPedigree(birdId) {
    currentPedigreeBirdId = birdId;
    const modal = document.getElementById('pedigreeModal');
    const preview = document.getElementById('pedigreePreview');
    
    if (!modal || !preview) return;
    
    const gen = document.querySelector('input[name="pedigreeGen"]:checked')?.value || '3';
    const html = PedigreeGenerator.generateHTML(birdId, { generations: parseInt(gen) });
    
    if (html) {
        preview.innerHTML = `<iframe srcdoc="${escapeHtml(html)}" style="width:100%;height:600px;border:none;"></iframe>`;
    }
    
    modal.classList.add('active');
}

function closePedigreeModal() {
    const modal = document.getElementById('pedigreeModal');
    if (modal) modal.classList.remove('active');
    currentPedigreeBirdId = null;
}

function printPedigree() {
    if (currentPedigreeBirdId) {
        PedigreeGenerator.print(currentPedigreeBirdId);
    }
}

function downloadPedigreeHTML() {
    if (currentPedigreeBirdId) {
        PedigreeGenerator.downloadHTML(currentPedigreeBirdId);
    }
}

// ========================================
// インポート/エクスポート
// ========================================
function exportBirdDB() {
    const json = BirdDB.exportJSON();
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `geneforge_birds_${Date.now()}.json`;
    a.click();
    
    URL.revokeObjectURL(url);
    showToast(t('exported'));
}

function importBirdDB(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const result = BirdDB.importJSON(e.target.result, 'merge');
        if (result.success) {
            showToast(`${result.count} ${t('imported_count')}`);
            refreshBirdList();
            refreshDBSelectors();
        } else {
            showToast(t('error') + ': ' + result.error, 'error');
        }
    };
    reader.readAsText(file);
    input.value = '';
}

function exportBirdsCSV() {
    const birds = BirdDB.getAllBirds();
    if (birds.length === 0) {
        showToast(t('no_export_data'));
        return;
    }
    
    const headers = ['ID', t('name'), t('code'), t('sex'), t('birth_date'), t('lineage'), 'Phenotype', t('notes')];
    const rows = birds.map(b => [
        b.id,
        b.name || '',
        b.code || '',
        b.sex === 'male' ? t('male') : t('female'),
        b.birthDate || '',
        b.lineage || '',
        getPhenotypeDisplay(b),
        (b.notes || '').replace(/"/g, '""')
    ]);
    
    const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${c}"`).join(','))].join('\n');
    const bom = '\uFEFF';
    const blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `geneforge_birds_${Date.now()}.csv`;
    a.click();
    
    URL.revokeObjectURL(url);
    showToast(t('csv_exported'));
}

/**
 * v7.3.20: インポート用テンプレートファイルをダウンロード
 * @param {string} format - 'json' or 'csv'
 */
function downloadImportTemplate(format) {
    // 5羽分のサンプルデータ
    const sampleBirds = [
        {
            id: 'sample-001',
            name: 'サンプル太郎',
            code: 'M001',
            sex: 'male',
            birthDate: '2024-01-15',
            lineage: 'Sample Line A',
            observed: { baseColor: 'green', eyeColor: 'black', darkness: 'none' },
            genotype: { parblue: '++', ino: '++', dark: 'dd', opaline: '++', cinnamon: '++', pied_rec: '++' },
            notes: 'Sample male bird'
        },
        {
            id: 'sample-002',
            name: 'サンプル花子',
            code: 'F001',
            sex: 'female',
            birthDate: '2024-02-20',
            lineage: 'Sample Line A',
            observed: { baseColor: 'aqua', eyeColor: 'black', darkness: 'none' },
            genotype: { parblue: 'aqaq', ino: '+W', dark: 'dd', opaline: '+W', cinnamon: '+W', pied_rec: '++' },
            notes: 'Sample female bird (Aqua)'
        },
        {
            id: 'sample-003',
            name: 'ルチノー君',
            code: 'M002',
            sex: 'male',
            birthDate: '2024-03-10',
            lineage: 'Sample Line B',
            observed: { baseColor: 'lutino', eyeColor: 'red', darkness: 'none' },
            genotype: { parblue: '++', ino: 'inoino', dark: 'dd', opaline: '++', cinnamon: '++', pied_rec: '++' },
            notes: 'Lutino male'
        },
        {
            id: 'sample-004',
            name: 'オパーリンちゃん',
            code: 'F002',
            sex: 'female',
            birthDate: '2024-04-05',
            lineage: 'Sample Line B',
            observed: { baseColor: 'opaline_green', eyeColor: 'black', darkness: 'none' },
            genotype: { parblue: '++', ino: '+W', dark: 'dd', opaline: 'opW', cinnamon: '+W', pied_rec: '++' },
            notes: 'Opaline female'
        },
        {
            id: 'sample-005',
            name: 'ダークグリーン',
            code: 'M003',
            sex: 'male',
            birthDate: '2024-05-01',
            lineage: 'Sample Line A',
            observed: { baseColor: 'dark_green', eyeColor: 'black', darkness: 'sf' },
            genotype: { parblue: '++', ino: '++', dark: 'Dd', opaline: '++', cinnamon: '++', pied_rec: '++' },
            sire: { id: 'sample-001', name: 'サンプル太郎' },
            dam: { id: 'sample-002', name: 'サンプル花子' },
            notes: 'Offspring of sample-001 and sample-002'
        }
    ];

    if (format === 'json') {
        // JSON形式
        const data = {
            version: BirdDB.VERSION,
            mode: BirdDB.getMode(),
            exportedAt: new Date().toISOString(),
            description: 'Gene-Forge Import Template - 5 sample birds',
            birds: sampleBirds
        };
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'geneforge_import_template.json';
        a.click();
        URL.revokeObjectURL(url);
    } else if (format === 'csv') {
        // CSV形式（簡易版）
        const headers = ['id', 'name', 'code', 'sex', 'birthDate', 'lineage', 'baseColor', 'eyeColor', 'darkness', 'notes'];
        const rows = sampleBirds.map(b => [
            b.id,
            b.name,
            b.code,
            b.sex,
            b.birthDate || '',
            b.lineage || '',
            b.observed?.baseColor || '',
            b.observed?.eyeColor || '',
            b.observed?.darkness || '',
            (b.notes || '').replace(/"/g, '""')
        ]);
        const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${c}"`).join(','))].join('\n');
        const bom = '\uFEFF';
        const blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'geneforge_import_template.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    showToast(t('exported'));
}

// ========================================
// 計算結果からの登録
// ========================================
function saveBreedingResult() {
    const sireId = document.getElementById('dbSelectSire')?.value;
    const damId = document.getElementById('dbSelectDam')?.value;
    
    const offspring = [];
    document.querySelectorAll('.offspring-card').forEach(card => {
        offspring.push({
            sex: card.dataset.sex,
            phenotype: card.dataset.pheno,
            geno: (() => { try { return JSON.parse(card.dataset.geno || '{}'); } catch(e) { return {}; } })()
        });
    });
    
    if (offspring.length === 0) {
        showToast(t('no_result'));
        return;
    }
    
    BirdDB.saveBreedingResult({
        sire: sireId ? BirdDB.getBird(sireId) : null,
        dam: damId ? BirdDB.getBird(damId) : null,
        offspring
    });
    
    showToast(t('save') + ' ' + t('ok'), 'success');
}

function registerOffspring(button) {
    const card = button.closest('.offspring-card');
    if (!card) return;
    
    const sex = card.dataset.sex;
    let geno = {};
    try { geno = JSON.parse(card.dataset.geno || '{}'); } catch(e) { console.warn('Invalid geno data:', e); }
    
    const sireId = document.getElementById('dbSelectSire')?.value;
    const damId = document.getElementById('dbSelectDam')?.value;
    
    openBirdForm();
    
    document.getElementById('birdSex').value = sex;
    generateGenotypeFields();
    
    setTimeout(() => {
        Object.keys(geno).forEach(key => {
            const el = document.getElementById('geno_' + key);
            if (el) { /* genotype conversion */ }
        });
        
        if (sireId) document.getElementById('birdSire').value = sireId;
        if (damId) document.getElementById('birdDam').value = damId;
    }, 100);
    
    showToast(t('set_info'));
}

// ========================================
// DB連携セレクタ更新
// ========================================
function refreshHealthSelectors() {
    const birds = BirdDB.getAllBirds();
    const males = birds.filter(b => b.sex === 'male');
    const females = birds.filter(b => b.sex === 'female');
    
    const sireSelect = document.getElementById('healthSire');
    const damSelect = document.getElementById('healthDam');
    
    if (sireSelect) {
        const currentVal = sireSelect.value;
        sireSelect.innerHTML = `<option value="">${t('select_placeholder')}</option>` +
            males.map(b => `<option value="${b.id}">${escapeHtml(b.name)} - ${escapeHtml(getPhenotypeDisplay(b))}</option>`).join('');
        sireSelect.value = currentVal;
    }

    if (damSelect) {
        const currentVal = damSelect.value;
        damSelect.innerHTML = `<option value="">${t('select_placeholder')}</option>` +
            females.map(b => `<option value="${b.id}">${escapeHtml(b.name)} - ${escapeHtml(getPhenotypeDisplay(b))}</option>`).join('');
        damSelect.value = currentVal;
    }
}

function refreshDBSelectors() {
    const birds = BirdDB.getAllBirds();
    const males = birds.filter(b => b.sex === 'male');
    const females = birds.filter(b => b.sex === 'female');

    const sireSelect = document.getElementById('dbSelectSire');
    const damSelect = document.getElementById('dbSelectDam');

    if (sireSelect) {
        const currentVal = sireSelect.value;
        sireSelect.innerHTML = `<option value="">${t('manual_input')}</option>` +
            males.map(b => `<option value="${b.id}">${escapeHtml(b.name)} - ${escapeHtml(getPhenotypeDisplay(b))}</option>`).join('');
        sireSelect.value = currentVal;
    }

    if (damSelect) {
        const currentVal = damSelect.value;
        damSelect.innerHTML = `<option value="">${t('manual_input')}</option>` +
            females.map(b => `<option value="${b.id}">${escapeHtml(b.name)} - ${escapeHtml(getPhenotypeDisplay(b))}</option>`).join('');
        damSelect.value = currentVal;
    }
}

// ========================================
// 健康評価（Health Guardian連携）
// ========================================
function checkPairingHealth() {
    const sireId = document.getElementById('healthSire')?.value;
    const damId = document.getElementById('healthDam')?.value;
    const resultEl = document.getElementById('healthCheckResult');
    
    if (!resultEl) {
        console.error('healthCheckResult element not found');
        return;
    }
    
    if (!sireId || !damId) {
        resultEl.innerHTML = `<div class="warning-box">${t('select_both_parents')}</div>`;
        return;
    }
    
    const sire = BirdDB.getBird(sireId);
    const dam = BirdDB.getBird(damId);
    
    if (!sire || !dam) {
        resultEl.innerHTML = `<div class="warning-box">${t('bird_not_found')}</div>`;
        return;
    }
    
    let inbreedingCoef = 0;
    
    const ssId = sire.sire?.id;
    const sdId = sire.dam?.id;
    const dsId = dam.sire?.id;
    const ddId = dam.dam?.id;

    if (ssId && dsId && ssId === dsId && sdId && ddId && sdId === ddId) {
        inbreedingCoef = 0.25;
    } else if ((ssId && dsId && ssId === dsId) || (sdId && ddId && sdId === ddId)) {
        inbreedingCoef = 0.125;
    }
    
    if (typeof HealthGuardian === 'undefined') {
        resultEl.innerHTML = `<div class="warning-box">${t('health_guardian_missing')}</div>`;
        return;
    }
    
    const evaluation = HealthGuardian.evaluateHealth(sire, dam, inbreedingCoef);
    
    const style = evaluation.riskStyle;
    let html = `
        <div class="health-result" style="background:${style.bg};border-left:4px solid ${style.color};padding:1rem;border-radius:8px;margin-top:1rem;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">${style.icon} ${style.label}</div>
            <div style="color:#e0e0e0;margin-bottom:1rem;">${evaluation.summary}</div>
    `;
    
    if (evaluation.blocks.length > 0) {
        html += '<div class="health-blocks" style="margin-bottom:1rem;">';
        html += `<h4 style="color:#ef4444;margin-bottom:0.5rem;">⛔ ${t('breeding_prohibited')}</h4>`;
        evaluation.blocks.forEach(b => {
            html += `<div style="background:rgba(239,68,68,0.2);padding:0.5rem;border-radius:4px;margin-bottom:0.5rem;">
                <strong>${b.message}</strong><br>
                <span style="font-size:0.85rem;color:#aaa;">${b.detail}</span><br>
                <span style="font-size:0.85rem;color:#4ecdc4;">💡 ${b.action}</span>
            </div>`;
        });
        html += '</div>';
    }
    
    if (evaluation.warnings.length > 0) {
        html += '<div class="health-warnings" style="margin-bottom:1rem;">';
        html += `<h4 style="color:#f59e0b;margin-bottom:0.5rem;">⚠️ ${t('health_warning')}</h4>`;
        evaluation.warnings.forEach(w => {
            html += `<div style="background:rgba(245,158,11,0.2);padding:0.5rem;border-radius:4px;margin-bottom:0.5rem;">
                <strong>${w.message}</strong><br>
                <span style="font-size:0.85rem;color:#aaa;">${w.detail}</span><br>
                <span style="font-size:0.85rem;color:#4ecdc4;">💡 ${w.action}</span>
            </div>`;
        });
        html += '</div>';
    }
    
    if (evaluation.risks.length > 0) {
        html += '<div class="health-risks">';
        html += `<h4 style="color:#eab308;margin-bottom:0.5rem;">⚡ ${t('risk_moderate')}</h4>`;
        evaluation.risks.forEach(r => {
            html += `<div style="background:rgba(234,179,8,0.2);padding:0.5rem;border-radius:4px;margin-bottom:0.5rem;">
                ${r.message}
            </div>`;
        });
        html += '</div>';
    }
    
    if (evaluation.canBreed && evaluation.riskLevel === 'safe') {
        html += `<div style="color:#10b981;font-weight:bold;margin-top:1rem;">✓ ${t('low_health_risk')}</div>`;
    }
    
    html += '</div>';
    
    resultEl.innerHTML = html;
}

// ========================================
// ユーティリティ
// ========================================
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ========================================
// 初期化
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    loadLinkageSettings();
    refreshDBSelectors();
    refreshHealthSelectors();
    
    document.querySelectorAll('input[name="pedigreeGen"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (currentPedigreeBirdId) {
                showPedigree(currentPedigreeBirdId);
            }
        });
    });
});
