/**
 * FamilyMap V3.7 - 一族マップUI
 * Build: 2024-v675-ALBS-FIXED
 * v7.3.22: UI改善 - コマンド整理、デモ家系図セレクタ、モード制限
 */
if (typeof customConfirm === 'undefined') {
    window.customConfirm = function(msg) { return Promise.resolve(confirm(msg)); };
}

/**
 * v7.3.22: FamilyMap用多言語翻訳（6言語対応）
 * T オブジェクトにないキーのフォールバック
 */
const FamilyMapI18n = {
    family_clear_section: {
        ja: '家系図のクリア', en: 'Clear Family Map', de: 'Stammbaum löschen',
        fr: 'Effacer l\'arbre', it: 'Cancella albero', es: 'Borrar árbol'
    },
    family_clear_hint: {
        ja: 'すべてクリアして最初から', en: 'Clear all and start fresh', de: 'Alles löschen und neu beginnen',
        fr: 'Tout effacer et recommencer', it: 'Cancella tutto e ricomincia', es: 'Borrar todo y empezar de nuevo'
    },
    family_snapshot_section: {
        ja: '家系図スナップショット', en: 'Family Map Snapshot', de: 'Stammbaum-Snapshot',
        fr: 'Instantané de l\'arbre', it: 'Snapshot dell\'albero', es: 'Instantánea del árbol'
    },
    family_save_load: {
        ja: 'セーブ/ロード（WBローカル）', en: 'Save/Load (WB Local)', de: 'Speichern/Laden (WB Lokal)',
        fr: 'Sauv./Charger (WB Local)', it: 'Salva/Carica (WB Locale)', es: 'Guardar/Cargar (WB Local)'
    },
    family_export_import: {
        ja: 'エクスポート/インポート（JSON）', en: 'Export/Import (JSON)', de: 'Export/Import (JSON)',
        fr: 'Export/Import (JSON)', it: 'Esporta/Importa (JSON)', es: 'Exportar/Importar (JSON)'
    },
    family_sample_dl: {
        ja: 'JSONサンプルDL', en: 'Download JSON Sample', de: 'JSON-Vorlage herunterladen',
        fr: 'Télécharger modèle JSON', it: 'Scarica modello JSON', es: 'Descargar plantilla JSON'
    },
    demo_family_empty_c: {
        ja: '遺伝型が空の推論サンプル用一族(C)', en: 'Inference Sample (C) - Empty Genotypes', de: 'Inferenz-Probe (C) - Leere Genotypen',
        fr: 'Échantillon d\'inférence (C) - Génotypes vides', it: 'Campione inferenza (C) - Genotipi vuoti', es: 'Muestra inferencia (C) - Genotipos vacíos'
    },
    demo_family_known_a: {
        ja: '遺伝型が自明の一族(A)', en: 'Known Genotype Family (A)', de: 'Bekannter Genotyp Familie (A)',
        fr: 'Famille à génotype connu (A)', it: 'Famiglia genotipo noto (A)', es: 'Familia genotipo conocido (A)'
    },
    demo_family_known_b: {
        ja: '遺伝型が自明の一族(B)', en: 'Known Genotype Family (B)', de: 'Bekannter Genotyp Familie (B)',
        fr: 'Famille à génotype connu (B)', it: 'Famiglia genotipo noto (B)', es: 'Familia genotipo conocido (B)'
    },
    demo_family_select: {
        ja: 'デモ家系図を選択', en: 'Select Demo Family', de: 'Demo-Familie auswählen',
        fr: 'Sélectionner famille démo', it: 'Seleziona famiglia demo', es: 'Seleccionar familia demo'
    },
    demo_disabled: {
        ja: 'デモモードでは使用不可', en: 'Disabled in demo mode', de: 'Im Demo-Modus deaktiviert',
        fr: 'Désactivé en mode démo', it: 'Disabilitato in modalità demo', es: 'Deshabilitado en modo demo'
    },
    auto_populate: {
        ja: '自動配置', en: 'Auto Populate', de: 'Auto-Ausfüllen',
        fr: 'Remplissage auto', it: 'Auto-compilazione', es: 'Auto-rellenar'
    },
    auto_populate_hint: {
        ja: '子を1羽セットして実行→家系図を自動構築', en: 'Set 1 offspring, then run → auto-build pedigree', de: '1 Nachkommen setzen → Stammbaum automatisch erstellen',
        fr: 'Définir 1 descendant → construire arbre auto', it: 'Imposta 1 figlio → costruisci albero auto', es: 'Establecer 1 cría → construir árbol auto'
    },
    auto_populate_no_offspring: {
        ja: '子レイヤーに1羽以上セットしてください', en: 'Please set at least 1 offspring', de: 'Bitte mindestens 1 Nachkommen setzen',
        fr: 'Veuillez définir au moins 1 descendant', it: 'Impostare almeno 1 figlio', es: 'Por favor establezca al menos 1 cría'
    },
    auto_populate_no_pedigree: {
        ja: 'この個体にはpedigree情報がありません', en: 'This bird has no pedigree data', de: 'Dieser Vogel hat keine Stammbaumdaten',
        fr: 'Cet oiseau n\'a pas de données de pedigree', it: 'Questo uccello non ha dati di pedigree', es: 'Este pájaro no tiene datos de pedigrí'
    },
    auto_populate_success: {
        ja: '家系図を自動配置しました', en: 'Pedigree auto-populated', de: 'Stammbaum automatisch ausgefüllt',
        fr: 'Pedigree rempli automatiquement', it: 'Pedigree compilato automaticamente', es: 'Pedigrí auto-rellenado'
    }
};

/**
 * v7.3.22: 翻訳取得ヘルパー
 */
function fmT(key) {
    const lang = (typeof LANG !== 'undefined') ? LANG : 'en';
    if (FamilyMapI18n[key]) {
        return FamilyMapI18n[key][lang] || FamilyMapI18n[key].en;
    }
    // Tオブジェクトにフォールバック
    if (typeof T !== 'undefined' && T[key]) {
        return T[key];
    }
    return key;
}

const FamilyMap = {
    demoMode: false,
    familyMode: 'plan',
    readOnly: false,  // v7.3.18: Planner出力からの表示時は true
    currentDemoFamily: 'C',  // v7.3.22: 現在読み込んでいるデモ家系図 (A/B/C)
    FAMILY_MODES: { FACT: 'fact', PLAN: 'plan' },
    data: {
        name: '', savedAt: null,
        sire: null, sire_sire: null, sire_dam: null,
        sire_sire_sire: null, sire_sire_dam: null, sire_dam_sire: null, sire_dam_dam: null,
        dam: null, dam_sire: null, dam_dam: null,
        dam_sire_sire: null, dam_sire_dam: null, dam_dam_sire: null, dam_dam_dam: null,
        offspring: [],
    },
    targetPosition: null,

    /**
     * UIに表示する色のキーリスト（よく使う色を厳選）
     * ラベルはCOLOR_MASTERから動的に取得（SSOT）
     */
    UI_COLOR_KEYS: [
        // グリーン系
        'green', 'darkgreen', 'olive',
        // アクア系
        'aqua', 'aqua_dark', 'aqua_olive',
        // ターコイズ系
        'turquoise', 'turquoise_dark',
        // シーグリーン系
        'seagreen', 'seagreen_dark',
        // INO系
        'lutino', 'creamino', 'pure_white', 'creamino_seagreen',
        // パリッド系
        'pallid_green', 'pallid_aqua', 'pallid_turquoise', 'pallid_seagreen',
        // シナモン系
        'cinnamon_green', 'cinnamon_aqua', 'cinnamon_turquoise', 'cinnamon_seagreen',
        // オパーリン系
        'opaline_green', 'opaline_aqua', 'opaline_turquoise', 'opaline_seagreen',
        // ファロー系
        'fallow_pale_green', 'fallow_pale_aqua',
        // パイド系
        'pied_rec_green', 'pied_rec_aqua', 'pied_rec_turquoise', 'pied_rec_seagreen',
    ],

    /**
     * カテゴリ名のラベル（UI表示用）
     * TODO: 将来的にgenetics.phpに移動してSSOT化
     */
    CATEGORY_LABELS: {
        'green': { ja: 'グリーン系（野生型）', en: 'Green (Wild)' },
        'aqua': { ja: 'アクア系', en: 'Aqua' },
        'turquoise': { ja: 'ターコイズ系（Whitefaced）', en: 'Turquoise (Whitefaced)' },
        'seagreen': { ja: 'シーグリーン系', en: 'Seagreen' },
        'ino': { ja: 'INO系（メラニン欠落・赤目）', en: 'INO (Melanin Absent)' },
        'pallid': { ja: 'パリッド系（メラニン減少・黒目）', en: 'Pallid (Melanin Reduced)' },
        'cinnamon': { ja: 'シナモン系（茶色メラニン）', en: 'Cinnamon' },
        'opaline': { ja: 'オパーリン系（模様変化）', en: 'Opaline' },
        'fallow_pale': { ja: 'ペールファロー系（赤目）', en: 'Pale Fallow' },
        'fallow_bronze': { ja: 'ブロンズファロー系（赤目）', en: 'Bronze Fallow' },
        'pied_dom': { ja: 'ドミナントパイド系', en: 'Dominant Pied' },
        'pied_rec': { ja: 'レセッシブパイド系', en: 'Recessive Pied' },
        'dilute': { ja: 'ダイリュート系', en: 'Dilute' },
        'edged': { ja: 'エッジド系', en: 'Edged' },
        'orangeface': { ja: 'オレンジフェイス系', en: 'Orangeface' },
        'pale_headed': { ja: 'ペールヘッド系', en: 'Pale Headed' },
        'violet': { ja: 'バイオレット系', en: 'Violet' },
    },

    get baseColorOptions() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');

        // COLOR_MASTERが定義されていない場合のフォールバック
        if (typeof COLOR_MASTER === 'undefined') {
            console.warn('[FamilyMap] COLOR_MASTER not defined, using empty options');
            return [];
        }

        const options = [];
        for (const key of this.UI_COLOR_KEYS) {
            const def = COLOR_MASTER[key];
            if (!def) {
                console.warn(`[FamilyMap] Color key "${key}" not found in COLOR_MASTER`);
                continue;
            }

            const category = def.category || 'other';
            const categoryLabel = this.CATEGORY_LABELS[category];
            const groupLabel = categoryLabel
                ? (isJa ? categoryLabel.ja : categoryLabel.en)
                : category;

            options.push({
                value: key,
                label: isJa ? def.ja : def.en,
                group: groupLabel,
                eye: def.eye,
                category: category,
            });
        }

        return options;
    },

    /**
     * v7.3.14: COLOR_MASTERから色ラベルを取得（SSOT準拠、6言語対応）
     * baseColorOptionsに含まれない色も正しく表示できるようにする
     * COLOR_MASTERはja/enのみなので、非日本語はenをフォールバック
     */
    getColorLabel(colorKey) {
        if (!colorKey) return '?';
        const langKey = (typeof LANG !== 'undefined' && LANG === 'ja') ? 'ja' : 'en';

        // 1. COLOR_MASTERから直接取得（SSOT）
        if (typeof COLOR_MASTER !== 'undefined' && COLOR_MASTER[colorKey]) {
            return COLOR_MASTER[colorKey][langKey] || COLOR_MASTER[colorKey].en || COLOR_MASTER[colorKey].ja || colorKey;
        }

        // 2. keyToLabel関数があれば使用
        if (typeof keyToLabel === 'function') {
            const label = keyToLabel(colorKey);
            if (label && label !== colorKey) return label;
        }

        // 3. COLOR_LABELSにあれば使用
        if (typeof COLOR_LABELS !== 'undefined' && COLOR_LABELS[colorKey]) {
            return COLOR_LABELS[colorKey];
        }

        // 4. フォールバック: キーをそのまま返す（デバッグ用に明示）
        console.warn(`[FamilyMap] Unknown color key: ${colorKey}`);
        return colorKey;
    },

    get darknessOptions() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        return [
            { value: 'none', label: isJa ? 'なし（ライト）' : 'None (Light)' },
            { value: 'sf', label: isJa ? '1つ（ダークグリーン/アクアダーク）' : 'Single (Dark Green/Aqua Dark)' },
            { value: 'df', label: isJa ? '2つ（オリーブ/アクアDD）' : 'Double (Olive/Aqua DD)' },
            { value: 'unknown', label: isJa ? '不明' : 'Unknown' },
        ];
    },

    get eyeColorOptions() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        return [
            { value: 'black', label: isJa ? '黒目（通常）' : 'Black (Normal)' },
            { value: 'red', label: isJa ? '赤目（ルチノー/クリーミノ/ピュアホワイト等）' : 'Red (Lutino/Creamino/Pure White etc.)' },
            { value: 'plum', label: isJa ? 'プラム色（パリッド幼鳥）' : 'Plum (Pallid juvenile)' },
        ];
    },

    get melaninOptions() {
        return [
            { value: '', label: T.unknown },
            { value: 'normal', label: T.normal },
            { value: 'diluted', label: T.diluted },
            { value: 'absent', label: T.absent },
            { value: 'brown', label: T.brown },
        ];
    },

    get patternOptions() {
        return [
            { value: '', label: T.unknown },
            { value: 'normal', label: T.normal },
            { value: 'opaline', label: T.opaline },
            { value: 'pied', label: T.pied },
        ];
    },

    init() {
        // v7.3.20: まず現在のBirdDBモードを取得（これが正しいモード）
        let currentMode = 'user';
        if (typeof BirdDB !== 'undefined' && BirdDB.getMode) {
            currentMode = BirdDB.getMode();
        }
        const shouldBeDemo = (currentMode === 'demo');

        // キャッシュ復元を試みるが、モードが一致しない場合は無視
        const restored = this.restoreAfterInference(shouldBeDemo);

        // BirdDBモードと同期
        this.demoMode = shouldBeDemo;

        // デモモードで、まだデータが空の場合は自動読み込み
        if (this.demoMode && !restored && this.isDataEmpty()) {
            this.loadDemoPedigree();
        }

        this.renderUI();
        this.loadSavedMaps();
        this.bindEvents();
    },

    /**
     * v7.3.20: データが空かどうかをチェック
     */
    isDataEmpty() {
        return !this.data.sire && !this.data.dam && (!this.data.offspring || this.data.offspring.length === 0);
    },

    /**
     * v7.3.20: 推論後の状態を復元（モードが一致する場合のみ）
     * @param {boolean} expectedDemoMode - 期待されるdemoMode
     * @returns {boolean} 復元に成功したかどうか
     */
    restoreAfterInference(expectedDemoMode) {
        const saved = localStorage.getItem('familyMapBeforeInfer');
        if (saved) {
            try {
                const state = JSON.parse(saved);

                // v7.3.20: モードが一致しない場合はキャッシュを無効化
                const cachedDemoMode = state.demoMode || false;
                if (cachedDemoMode !== expectedDemoMode) {
                    console.log('[FamilyMap] Cache mode mismatch, ignoring cached state');
                    localStorage.removeItem('familyMapBeforeInfer');
                    return false;
                }

                this.data = state.data;
                this.targetPosition = state.targetPosition;
                this.demoMode = state.demoMode || false;
                this.familyMode = state.familyMode || 'plan';
                localStorage.removeItem('familyMapBeforeInfer');
                console.log('[FamilyMap] State restored from cache');
                return true;
            } catch (e) {
                console.error('Failed to restore state:', e);
                localStorage.removeItem('familyMapBeforeInfer');
            }
        }
        return false;
    },

    toggleMode() { this.setDemoMode(!this.demoMode); },

    /**
     * v3.7: デモ用プリセット家系図をロード
     * v7.0: Family C（推論デモ用）をロード - genotype空、pedigreeあり
     */
    loadDemoPedigree() {
        this.loadDemoFamily('C');
    },

    /**
     * v7.3.22: 指定したデモ家系図を読み込む
     * @param {string} familyId - 'A', 'B', または 'C'
     */
    loadDemoFamily(familyId) {
        if (typeof BirdDB !== 'undefined' && BirdDB.getDemoPedigreeForFamilyMap) {
            const demoData = BirdDB.getDemoPedigreeForFamilyMap(familyId);
            if (demoData) {
                this.data = demoData;
                this.targetPosition = null;
                this.currentDemoFamily = familyId;
                console.log(`[FamilyMap] Demo pedigree loaded (Family ${familyId})`);
                this.renderUI();
            } else {
                console.warn(`[FamilyMap] Failed to load demo family ${familyId}`);
            }
        }
    },

    /**
     * v7.3.22: 自動配置 - 子レイヤーの個体からpedigreeを辿って家系図を自動構築
     * ユーザーモード専用機能
     */
    autoPopulate() {
        // 子レイヤーに1羽以上セットされているか確認
        const offspring = this.data.offspring || [];
        if (offspring.length === 0 || !offspring[0]) {
            if (typeof showToast !== 'undefined') {
                showToast(fmT('auto_populate_no_offspring'), 'warning');
            } else {
                alert(fmT('auto_populate_no_offspring'));
            }
            return;
        }

        // 最初の子のDBIDを取得
        const firstChild = offspring[0];
        const birdId = firstChild.dbId || firstChild.id;

        if (!birdId) {
            if (typeof showToast !== 'undefined') {
                showToast(fmT('auto_populate_no_pedigree'), 'warning');
            } else {
                alert(fmT('auto_populate_no_pedigree'));
            }
            return;
        }

        // BirdDB.autoPopulatePedigreeを呼び出し
        if (typeof BirdDB !== 'undefined' && BirdDB.autoPopulatePedigree) {
            const pedigreeData = BirdDB.autoPopulatePedigree(birdId);
            if (pedigreeData) {
                // 現在のoffspringを維持しつつ、pedigreeデータをマージ
                const currentOffspring = this.data.offspring;
                this.data = pedigreeData;
                // 子は現在セットされているものを優先（ユーザーが複数セットしている場合）
                if (currentOffspring && currentOffspring.length > 0) {
                    this.data.offspring = currentOffspring;
                }
                this.targetPosition = null;
                console.log(`[FamilyMap] Auto-populated pedigree from bird: ${birdId}`);
                this.renderUI();
                if (typeof showToast !== 'undefined') {
                    showToast(fmT('auto_populate_success'), 'success');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(fmT('auto_populate_no_pedigree'), 'warning');
                } else {
                    alert(fmT('auto_populate_no_pedigree'));
                }
            }
        }
    },


    setFamilyMode(mode) {
        if (mode !== this.FAMILY_MODES.FACT && mode !== this.FAMILY_MODES.PLAN) {
            console.error('Invalid family mode:', mode);
            return;
        }
        this.familyMode = mode;
        this.renderUI();
    },

    toggleFamilyMode() {
        const newMode = this.familyMode === this.FAMILY_MODES.FACT ? this.FAMILY_MODES.PLAN : this.FAMILY_MODES.FACT;
        this.setFamilyMode(newMode);
    },

    /**
     * v7.3.18: readOnlyモードを解除して編集モードに戻る
     */
    exitReadOnly() {
        this.readOnly = false;
        this.renderUI();
    },

    canUseGeneticEstimation() {
        if (this.familyMode === this.FAMILY_MODES.FACT) return false;
        if (typeof BreedingValidator !== 'undefined') return BreedingValidator.canEstimateGenetics(this.familyMode);
        return true;
    },

    validateBreeding(sire, dam) {
        if (!sire || !dam) return { allowed: true };
        if (typeof BreedingValidator !== 'undefined') return BreedingValidator.validate(sire, dam, this.familyMode);
        if (sire.sex !== 'male') return { allowed: false, reason: '父には♂を指定してください', type: 'fact' };
        if (dam.sex !== 'female') return { allowed: false, reason: '母には♀を指定してください', type: 'fact' };
        if (sire.id === dam.id) return { allowed: false, reason: '同一個体です', type: 'fact' };
        return { allowed: true };
    },

    /**
     * v7.3.20: 全データをクリア（モード切替時に使用）
     */
    clearAllData() {
        this.data = {
            name: '', savedAt: null,
            sire: null, sire_sire: null, sire_dam: null,
            sire_sire_sire: null, sire_sire_dam: null, sire_dam_sire: null, sire_dam_dam: null,
            dam: null, dam_sire: null, dam_dam: null,
            dam_sire_sire: null, dam_sire_dam: null, dam_dam_sire: null, dam_dam_dam: null,
            offspring: [],
        };
        this.targetPosition = null;
        this.readOnly = false;
        // 推論用キャッシュもクリア
        localStorage.removeItem('familyMapBeforeInfer');
        console.log('[FamilyMap] All data cleared');
    },

    /**
     * v7.3.20: BirdDBモード変更時のコールバック
     * モード切替時に全キャッシュをクリアして再初期化
     */
    onModeChange(newMode) {
        console.log('[FamilyMap] Mode change notification received:', newMode);
        const isDemo = (newMode === 'demo');

        // キャッシュとデータをクリア
        this.clearAllData();
        this.demoMode = isDemo;

        // デモモードなら自動読み込み
        if (isDemo) {
            this.loadDemoPedigree();
        }

        this.renderUI();
    },

    setDemoMode(isDemo) {
        if (this.demoMode === isDemo) return;
        this.demoMode = isDemo;
        const newMode = isDemo ? 'demo' : 'user';
        if (typeof BirdDB !== 'undefined' && BirdDB.setModeFromFamilyMap) BirdDB.setModeFromFamilyMap(newMode);
        if (isDemo) {
            this.loadDemoPedigree();
        } else {
            this.clearAllData();
        }
        this.renderUI();
    },


    renderUI() {
        const container = document.getElementById('familyMapContainer');
        if (!container) return;
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const familyModeLabel = this.familyMode === this.FAMILY_MODES.FACT
            ? (isJa ? '📝 事実モード' : '📝 Fact Mode')
            : (isJa ? '🎯 倫理計画モード' : '🎯 Ethics Plan Mode');
        const familyModeClass = this.familyMode === this.FAMILY_MODES.FACT ? 'fact-mode' : 'plan-mode';
        const canInfer = this.canUseGeneticEstimation();
        const inferBtnDisabled = !canInfer || !this.targetPosition;
        const inferBtnClass = canInfer ? '' : 'disabled-mode';
        const inferBtnTitle = canInfer ? '' : (isJa ? '事実モードでは遺伝推定は利用できません' : 'Genetic estimation is not available in Fact Mode');

        // v7.3.21: 現在のマップ名を表示
        const currentMapName = this.getCurrentMapDisplayName();

        // v7.3.22: デモモードでのボタン制限
        // デモモードで使用不可: クリア、セーブ、ロード、インポート（横線処理）
        // デモモードで使用可能: エクスポート、JSONサンプルDL
        const demoDisabled = this.demoMode;
        const demoDisabledClass = demoDisabled ? 'fmap-btn-disabled' : '';
        const demoDisabledTitle = demoDisabled ? fmT('demo_disabled') : '';

        // v7.3.22: デモ家系図セレクタ（デモモード時のみ表示）
        const demoFamilySelector = this.demoMode ? `
            <div class="demo-family-selector">
                <label>📁 ${fmT('demo_family_select')}</label>
                <select class="form-select" onchange="FamilyMap.loadDemoFamily(this.value)">
                    <option value="C" ${this.currentDemoFamily === 'C' ? 'selected' : ''}>${fmT('demo_family_empty_c')}</option>
                    <option value="A" ${this.currentDemoFamily === 'A' ? 'selected' : ''}>${fmT('demo_family_known_a')}</option>
                    <option value="B" ${this.currentDemoFamily === 'B' ? 'selected' : ''}>${fmT('demo_family_known_b')}</option>
                </select>
            </div>` : '';

        // v7.3.18: readOnlyモード時は編集用UIを非表示
        // v7.3.19: Planner出力時はヘッダーバッジと編集モードボタンを非表示
        const readOnlyHeader = this.readOnly ? `
            <div class="family-map-header">
                <h2>🦜 ${T.family_map}</h2>
                ${currentMapName ? `<div class="current-map-badge">📋 ${currentMapName}</div>` : ''}
            </div>` : `
            <div class="family-map-header">
                <h2>🦜 ${T.family_map}</h2>
                ${demoFamilySelector}
                ${currentMapName && !this.demoMode ? `<div class="current-map-badge">📋 ${currentMapName}</div>` : ''}
                <div class="family-mode-toggle">
                    <button class="fmap-btn ${familyModeClass}" onclick="FamilyMap.toggleFamilyMode()" title="${isJa ? 'クリックでモード切替' : 'Click to toggle mode'}">${familyModeLabel}</button>
                    <span class="mode-hint">${this.familyMode === this.FAMILY_MODES.FACT ? (isJa ? '※遺伝推定不可・近親交配記録可' : '※No inference, inbreeding recordable') : (isJa ? '※遺伝推定可・近親交配12.5%未満のみ' : '※Inference OK, <12.5% inbreeding only')}</span>
                </div>

                <!-- v7.3.22: 家系図のクリア -->
                <div class="family-action-section">
                    <div class="action-section-header">🗑️ ${fmT('family_clear_section')}</div>
                    <div class="action-section-hint">${fmT('family_clear_hint')}</div>
                    <button class="fmap-btn fmap-btn-outline fmap-btn-full ${demoDisabledClass}" onclick="FamilyMap.clearAll()" ${demoDisabled ? 'disabled' : ''} title="${demoDisabledTitle}">🗑️ ${T.clear}</button>
                </div>

                <!-- v7.3.22: 家系図スナップショット -->
                <div class="family-action-section">
                    <div class="action-section-header">📸 ${fmT('family_snapshot_section')}</div>

                    <div class="action-subsection">
                        <div class="action-subsection-label">${fmT('family_save_load')}</div>
                        <div class="family-map-actions-row">
                            <button class="fmap-btn fmap-btn-primary ${demoDisabledClass}" onclick="FamilyMap.saveSnapshot()" ${demoDisabled ? 'disabled' : ''} title="${demoDisabledTitle}">💾 ${T.save}</button>
                            <button class="fmap-btn fmap-btn-outline ${demoDisabledClass}" onclick="FamilyMap.showLoadModal()" ${demoDisabled ? 'disabled' : ''} title="${demoDisabledTitle}">📂 ${T.load}</button>
                        </div>
                    </div>

                    <div class="action-subsection">
                        <div class="action-subsection-label">${fmT('family_export_import')}</div>
                        <div class="family-map-actions-row">
                            <button class="fmap-btn fmap-btn-outline" onclick="FamilyMap.exportJSON()">📤 ${T.export}</button>
                            <button class="fmap-btn fmap-btn-outline ${demoDisabledClass}" onclick="FamilyMap.showImportModal()" ${demoDisabled ? 'disabled' : ''} title="${demoDisabledTitle}">📥 ${T.import}</button>
                        </div>
                    </div>

                    <div class="action-subsection">
                        <div class="action-subsection-label">${fmT('family_sample_dl')}</div>
                        <button class="fmap-btn fmap-btn-outline fmap-btn-full" onclick="FamilyMap.downloadTemplate()">📄 ${T.template}</button>
                    </div>
                </div>

                <!-- v7.3.22: 自動配置（ユーザーモード専用） -->
                ${!this.demoMode ? `
                <div class="family-action-section family-action-auto-populate">
                    <div class="action-section-header">🔄 ${fmT('auto_populate')}</div>
                    <div class="action-section-hint">${fmT('auto_populate_hint')}</div>
                    <button class="fmap-btn fmap-btn-primary fmap-btn-full" onclick="FamilyMap.autoPopulate()">🔄 ${fmT('auto_populate')}</button>
                </div>
                ` : ''}
            </div>`;

        container.innerHTML = `
            ${readOnlyHeader}
            <div class="pedigree-chart">
                <section class="lineage paternal-lineage">
                    <div class="lineage-label">${T.paternal_line}</div>
                    <div class="gen-row ggp-row">
                        ${this.renderCard('sire_sire_sire', T.gf_father, 'male')}
                        ${this.renderCard('sire_sire_dam', T.gf_mother, 'female')}
                        <div class="gen-spacer"></div>
                        ${this.renderCard('sire_dam_sire', T.gm_father, 'male')}
                        ${this.renderCard('sire_dam_dam', T.gm_mother, 'female')}
                    </div>
                    <div class="tree-lines dual"><div class="branch left"></div><div class="branch right"></div></div>
                    <div class="gen-row gp-row">
                        ${this.renderCard('sire_sire', T.grandfather, 'male')}
                        ${this.renderCard('sire_dam', T.grandmother, 'female')}
                    </div>
                    <div class="tree-lines single"></div>
                    <div class="gen-row parent-row">${this.renderCard('sire', T.sire, 'male', true)}</div>
                </section>
                <div class="main-stem top"></div>
                <section class="offspring-strip">
                    <div class="offspring-label">🐣 ${T.offspring}</div>
                    <div class="offspring-scroll"><div class="offspring-row" id="offspringGrid">${this.renderOffspringSlots()}</div></div>
                    ${this.readOnly ? '' : '<button class="btn-add-child" onclick="FamilyMap.addOffspring()">➕</button>'}
                </section>
                <div class="main-stem bottom"></div>
                <section class="lineage maternal-lineage">
                    <div class="gen-row parent-row">${this.renderCard('dam', T.dam, 'female', true)}</div>
                    <div class="tree-lines single"></div>
                    <div class="gen-row gp-row">
                        ${this.renderCard('dam_sire', T.grandfather, 'male')}
                        ${this.renderCard('dam_dam', T.grandmother, 'female')}
                    </div>
                    <div class="tree-lines dual"><div class="branch left"></div><div class="branch right"></div></div>
                    <div class="gen-row ggp-row">
                        ${this.renderCard('dam_sire_sire', T.gf_father, 'male')}
                        ${this.renderCard('dam_sire_dam', T.gf_mother, 'female')}
                        <div class="gen-spacer"></div>
                        ${this.renderCard('dam_dam_sire', T.gm_father, 'male')}
                        ${this.renderCard('dam_dam_dam', T.gm_mother, 'female')}
                    </div>
                    <div class="lineage-label">${T.maternal_line}</div>
                </section>
            </div>
            <div class="inference-panel">
                <div class="target-info"><span class="target-label">${T.target}:</span><strong id="targetDisplay" class="target-name">${T.not_selected}</strong></div>
                <button class="btn btn-primary btn-infer ${inferBtnClass}" onclick="FamilyMap.runInference()" id="inferBtn" ${inferBtnDisabled ? 'disabled' : ''} title="${inferBtnTitle}">🔬 ${T.run_inference}</button>
                ${!canInfer ? `<div class="infer-mode-notice">${isJa ? '※事実モードでは遺伝推定は利用できません' : '※Inference not available in Fact Mode'}</div>` : ''}
            </div>
            <div id="inferenceResult" class="inference-result"></div>
            <div id="inbreedingWarning" class="inbreeding-warning"></div>
        `;
        if (!this.readOnly) this.checkParentInbreeding();
    },

    checkParentInbreeding() {
        const sire = this.data.sire, dam = this.data.dam;
        if (!sire || !dam) return;
        const validation = this.validateBreeding(sire, dam);
        if (!validation.allowed) this.displayBreedingWarning(validation);
        else if (validation.warning) this.displayBreedingWarning({ warning: validation.warning });
    },

    displayBreedingWarning(validation) {
        const warningEl = document.getElementById('inbreedingWarning');
        if (!warningEl) return;
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        if (validation.reason) {
            const typeLabel = validation.type === 'absolute' ? (isJa ? '🚫 絶対禁止' : '🚫 Absolutely Prohibited') : validation.type === 'ethics' ? (isJa ? '⚠️ 倫理基準違反' : '⚠️ Ethics Violation') : (isJa ? '❌ エラー' : '❌ Error');
            warningEl.innerHTML = `<div class="breeding-block ${validation.type}"><div class="block-header">${typeLabel}</div><div class="block-message">${validation.reason}</div>${this.familyMode === this.FAMILY_MODES.FACT && validation.type !== 'absolute' ? `<div class="block-hint">${isJa ? '※事実モードでは警告のみで記録可能です' : '※Recordable with warning in Fact Mode'}</div>` : ''}</div>`;
            warningEl.style.display = 'block';
        } else if (validation.warning) {
            warningEl.innerHTML = `<div class="breeding-warning"><div class="warning-message">${validation.warning}</div></div>`;
            warningEl.style.display = 'block';
        } else {
            warningEl.innerHTML = '';
            warningEl.style.display = 'none';
        }
    },

    renderCard(position, label, defaultSex, isParent = false) {
        const bird = this.data[position];
        const isTarget = this.targetPosition === position;
        const isEmpty = !bird;
        const sexSymbol = defaultSex === 'male' ? '♂' : '♀';
        let content = '';
        if (isEmpty) {
            content = `<div class="card-empty"><span class="sex-icon">${sexSymbol}</span><span class="empty-text">${T.click_to_input}</span></div>`;
        } else {
            const pheno = bird.phenotype || {}, geno = bird.genotype || {};
            let colorLabel;
            // v7.3.17: phenotype.baseColorを優先（v7ハプロタイプ形式の遺伝子型はcalculatePhenotypeが未対応のため）
            if (pheno.baseColor) colorLabel = this.getColorLabel(pheno.baseColor);
            else if (Object.keys(geno).length > 0 && typeof BirdDB !== 'undefined' && BirdDB.calculatePhenotype) colorLabel = BirdDB.calculatePhenotype(geno, bird.sex);
            else colorLabel = '?';
            const idDisplay = bird.dbId ? `<span class="bird-id">#${bird.dbId}</span>` : '';
            content = `<div class="card-filled"><span class="sex-icon">${bird.sex === 'male' ? '♂' : '♀'}</span><span class="pheno-color">${colorLabel}</span>${bird.name ? `<span class="bird-name">${bird.name}</span>` : ''}${idDisplay}</div>`;
        }
        const canSetTarget = this.canUseGeneticEstimation();
        const targetBtnDisabled = !canSetTarget ? 'disabled' : '';
        const targetBtnClass = !canSetTarget ? 'target-btn-disabled' : '';
        // v7.3.18: readOnlyモード時は編集ボタンを非表示
        const actionsHtml = this.readOnly ? '' : `<div class="card-actions"><button class="act-btn" onclick="event.stopPropagation(); FamilyMap.loadFromDB('${position}')" title="DB">📂</button>${!isEmpty ? `<button class="act-btn del" onclick="event.stopPropagation(); FamilyMap.clearSlot('${position}')" title="${T.clear}">×</button>` : ''}</div>`;
        // v7.3.19: readOnlyモードでも父/母には対象設定ボタンを表示（祖父母には不要）
        const isParentPosition = position === 'sire' || position === 'dam';
        const showTargetBtn = !this.readOnly || isParentPosition;
        const targetBtnHtml = showTargetBtn ? `<button class="target-select-btn ${isTarget ? 'active' : ''} ${targetBtnClass}" onclick="event.stopPropagation(); FamilyMap.setAsTarget('${position}')" ${targetBtnDisabled}>🎯 ${isTarget ? T.is_inference_target : T.set_as_target}</button>` : '';
        const cardClickHandler = this.readOnly ? '' : `onclick="FamilyMap.selectSlot('${position}')"`;
        return `<div class="bird-card ${isEmpty ? 'empty' : 'filled'} ${isTarget ? 'target' : ''} ${isParent ? 'parent' : ''} ${this.readOnly ? 'readonly' : ''}" data-position="${position}">
            <div class="card-header" ${cardClickHandler}><span class="card-label">${label}${sexSymbol}</span>${actionsHtml}</div>
            <div class="card-body" ${cardClickHandler}>${content}</div>
            ${targetBtnHtml}
        </div>`;
    },

    renderOffspringSlots() {
        let html = '';
        const offspring = this.data.offspring || [];
        const canSetTarget = this.canUseGeneticEstimation();
        const targetBtnDisabled = !canSetTarget ? 'disabled' : '';
        const targetBtnClass = !canSetTarget ? 'target-btn-disabled' : '';
        offspring.forEach((bird, idx) => {
            const isTarget = this.targetPosition === `offspring_${idx}`;
            const pheno = bird.phenotype || {}, geno = bird.genotype || {};
            let colorLabel;
            // v7.3.17: phenotype.baseColorを優先（v7ハプロタイプ形式の遺伝子型はcalculatePhenotypeが未対応のため）
            if (pheno.baseColor) colorLabel = this.getColorLabel(pheno.baseColor);
            else if (Object.keys(geno).length > 0 && typeof BirdDB !== 'undefined' && BirdDB.calculatePhenotype) colorLabel = BirdDB.calculatePhenotype(geno, bird.sex);
            else colorLabel = '?';
            const idDisplay = bird.dbId ? `<span class="bird-id">#${bird.dbId}</span>` : '';
            // v7.3.18: readOnlyモード時は編集ボタンを非表示
            const delBtnHtml = this.readOnly ? '' : `<button class="act-btn del" onclick="event.stopPropagation(); FamilyMap.removeOffspring(${idx})">×</button>`;
            // v7.3.19: readOnlyモードでも子には対象設定ボタンを表示
            const targetBtnHtml = `<button class="child-target-btn ${isTarget ? 'active' : ''} ${targetBtnClass}" onclick="event.stopPropagation(); FamilyMap.setAsTarget('offspring_${idx}')" ${targetBtnDisabled}>🎯${isTarget ? T.target : ''}</button>`;
            const clickHandler = this.readOnly ? '' : `onclick="FamilyMap.selectSlot('offspring_${idx}')"`;
            html += `<div class="child-card ${isTarget ? 'target' : ''} ${this.readOnly ? 'readonly' : ''}" data-position="offspring_${idx}">
                <div class="child-header" ${clickHandler}><span>${T.child}${idx + 1}</span>${delBtnHtml}</div>
                <div class="child-body" ${clickHandler}><span class="sex-icon">${bird.sex === 'male' ? '♂' : '♀'}</span><span class="pheno-color">${colorLabel}</span>${bird.name ? `<span class="bird-name">${bird.name}</span>` : ''}${idDisplay}</div>
                ${targetBtnHtml}
            </div>`;
        });
        return html;
    },

    setAsTarget(position) {
        if (!this.canUseGeneticEstimation()) {
            const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
            alert(isJa ? '事実モードでは遺伝推定は利用できません' : 'Genetic estimation is not available in Fact Mode');
            return;
        }

        const previousTarget = this.targetPosition;
        this.targetPosition = position;

        // v7.3.11: renderUI()を呼ばず、部分更新のみでスクロール位置を保持
        // 前のターゲットからtarget/activeクラスを除去
        if (previousTarget) {
            const prevCard = document.querySelector(`[data-position="${previousTarget}"]`);
            if (prevCard) {
                prevCard.classList.remove('target');
                const prevBtn = prevCard.querySelector('.target-select-btn, .child-target-btn');
                if (prevBtn) {
                    prevBtn.classList.remove('active');
                    prevBtn.innerHTML = '🎯 ' + T.set_as_target;
                }
            }
        }

        // 新しいターゲットにtarget/activeクラスを追加
        const newCard = document.querySelector(`[data-position="${position}"]`);
        if (newCard) {
            newCard.classList.add('target');
            const newBtn = newCard.querySelector('.target-select-btn, .child-target-btn');
            if (newBtn) {
                newBtn.classList.add('active');
                // v7.3.14: 6言語対応 - T オブジェクトから翻訳を取得
                if (newBtn.classList.contains('child-target-btn')) {
                    newBtn.innerHTML = '🎯' + T.target;
                } else {
                    newBtn.innerHTML = '🎯 ' + T.is_inference_target;
                }
            }
        }

        const inferBtn = document.getElementById('inferBtn');
        if (inferBtn) inferBtn.disabled = !this.targetPosition || !this.canUseGeneticEstimation();
        const targetDisplay = document.getElementById('targetDisplay');
        if (targetDisplay) targetDisplay.textContent = this.getPositionLabelDetailed(position);
    },

    selectSlot(position) { this.openInputModal(position); },

    openInputModal(position) {
        const bird = position.startsWith('offspring_') ? this.data.offspring[parseInt(position.split('_')[1])] : this.data[position];
        let modal = document.getElementById('birdInputModal');
        if (!modal) { modal = this.createInputModal(); document.body.appendChild(modal); }
        modal.dataset.position = position;
        const form = modal.querySelector('form');
        const defaultSex = this.getDefaultSex(position);
        const sexSelect = form.elements['bird_sex'];
        const sexDisplay = document.getElementById('sexDisplayOnly');
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        if (sexSelect && sexDisplay) {
            sexSelect.value = defaultSex;
            if (position.startsWith('offspring_')) { sexSelect.style.display = 'block'; sexDisplay.style.display = 'none'; }
            else { sexSelect.style.display = 'none'; sexDisplay.style.display = 'block'; sexDisplay.textContent = defaultSex === 'male' ? `♂ ${isJa ? 'オス（固定）' : 'Male (fixed)'}` : `♀ ${isJa ? 'メス（固定）' : 'Female (fixed)'}`; }
        }
        this.updateModalGenotypeOptions();
        if (bird) {
            if (form.elements['bird_name']) form.elements['bird_name'].value = bird.name || '';
            if (form.elements['bird_baseColor']) form.elements['bird_baseColor'].value = bird.phenotype?.baseColor || 'green';
            if (form.elements['bird_darkness']) form.elements['bird_darkness'].value = bird.phenotype?.darkness || 'none';
            if (form.elements['bird_eyeColor']) form.elements['bird_eyeColor'].value = bird.phenotype?.eyeColor || 'black';
            const geno = bird.genotype || {};
            // SSOT: genetics.phpのLOCI定義に準拠
            ['parblue', 'ino', 'opaline', 'cinnamon', 'dark', 'violet', 'fallow_pale', 'fallow_bronze', 'dilute', 'pied_rec', 'pied_dom', 'edged', 'pale_headed'].forEach(key => { const el = form.elements['geno_' + key]; if (el && geno[key]) el.value = geno[key]; });
        } else {
            if (form.elements['bird_name']) form.elements['bird_name'].value = '';
            if (form.elements['bird_baseColor']) form.elements['bird_baseColor'].value = 'green';
            if (form.elements['bird_darkness']) form.elements['bird_darkness'].value = 'none';
            if (form.elements['bird_eyeColor']) form.elements['bird_eyeColor'].value = 'black';
        }
        modal.classList.add('active');
    },

    getDefaultSex(position) {
        if (position === 'sire') return 'male';
        if (position === 'dam') return 'female';
        if (position.endsWith('_sire')) return 'male';
        if (position.endsWith('_dam')) return 'female';
        return 'male';
    },

    createInputModal() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const modal = document.createElement('div');
        modal.id = 'birdInputModal';
        modal.className = 'modal';
        const baseColorOptions = this.baseColorOptions;
        const groups = {};
        baseColorOptions.forEach(opt => { if (!groups[opt.group]) groups[opt.group] = []; groups[opt.group].push(opt); });
        let baseColorHTML = '<select name="bird_baseColor" class="form-select">';
        for (const [groupName, opts] of Object.entries(groups)) {
            baseColorHTML += `<optgroup label="${groupName}">`;
            opts.forEach(opt => { baseColorHTML += `<option value="${opt.value}">${opt.label}</option>`; });
            baseColorHTML += '</optgroup>';
        }
        baseColorHTML += '</select>';
        let eyeColorHTML = '<select name="bird_eyeColor" class="form-select">';
        this.eyeColorOptions.forEach(opt => { eyeColorHTML += `<option value="${opt.value}">${opt.label}</option>`; });
        eyeColorHTML += '</select>';
        let darknessHTML = '<select name="bird_darkness" class="form-select">';
        this.darknessOptions.forEach(opt => { darknessHTML += `<option value="${opt.value}">${opt.label}</option>`; });
        darknessHTML += '</select>';
        modal.innerHTML = `<div class="modal-content"><div class="modal-header"><h3>🐦 ${isJa ? '個体情報入力' : 'Bird Information'}</h3><button class="modal-close" onclick="FamilyMap.closeInputModal()">×</button></div>
            <form onsubmit="FamilyMap.saveBirdInput(event)"><div class="form-grid"><div class="form-group" id="sexFieldContainer"><label>${isJa ? '性別' : 'Sex'}</label><select name="bird_sex" class="form-select" onchange="FamilyMap.updateModalGenotypeOptions()"><option value="male">♂ ${isJa ? 'オス' : 'Male'}</option><option value="female">♀ ${isJa ? 'メス' : 'Female'}</option></select><div id="sexDisplayOnly" style="display:none;padding:.5rem;background:var(--bg-secondary);border-radius:4px;font-size:1.1rem;"></div></div>
            <div class="form-group"><label>${isJa ? '名前（任意）' : 'Name (optional)'}</label><input type="text" name="bird_name" class="form-input" placeholder="${isJa ? '例: 太郎' : 'e.g. Taro'}"></div></div>
            <h4 class="section-title">👁️ ${isJa ? '観察情報（表現型）' : 'Observed (Phenotype)'}</h4><div class="form-grid"><div class="form-group"><label>${isJa ? '基本色（観察した羽の色）' : 'Base Color'}</label>${baseColorHTML}</div><div class="form-group"><label>${isJa ? '眼の色' : 'Eye Color'}</label>${eyeColorHTML}</div><div class="form-group"><label>${isJa ? 'ダーク因子（色の濃さ）' : 'Dark Factor'}</label>${darknessHTML}</div></div>
            <h4 class="section-title">🧬 ${isJa ? '遺伝子型（判明している場合）' : 'Genotype (if known)'}</h4><div class="form-grid genotype-grid" id="familyGenotypeFields"></div>
            <div class="btn-group"><button type="submit" class="btn btn-primary">✓ ${isJa ? '保存' : 'Save'}</button><button type="button" class="btn btn-outline" onclick="FamilyMap.closeInputModal()">${isJa ? 'キャンセル' : 'Cancel'}</button></div></form></div>`;
        return modal;
    },

    updateModalGenotypeOptions() {
        const modal = document.getElementById('birdInputModal');
        if (!modal) return;
        const container = modal.querySelector('#familyGenotypeFields');
        if (!container) return;
        const sex = modal.querySelector('[name="bird_sex"]')?.value || 'male';
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const unknownOption = ['', isJa ? '-- 不明 --' : '-- Unknown --'];

        // SSOT: Use global constants from genetics.php
        if (typeof GENOTYPE_OPTIONS === 'undefined' || typeof UI_GENOTYPE_LOCI === 'undefined') {
            console.warn('[FamilyMap] GENOTYPE_OPTIONS or UI_GENOTYPE_LOCI not defined');
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
                options: [unknownOption, ...options]
            };
        });

        container.innerHTML = loci.map(locus => `<div class="form-group"><label class="form-label">${locus.label}</label><select name="geno_${locus.key}" class="form-select">${locus.options.map(([val, label]) => `<option value="${val}">${label}</option>`).join('')}</select></div>`).join('');
    },

    closeInputModal() { const modal = document.getElementById('birdInputModal'); if (modal) modal.classList.remove('active'); },

    async saveBirdInput(event) {
        event.preventDefault();
        const modal = document.getElementById('birdInputModal');
        const position = modal.dataset.position;
        const form = event.target;
        let sex;
        if (position.startsWith('offspring_')) sex = form.elements['bird_sex'].value;
        else sex = this.getDefaultSex(position);
        const inputName = form.elements['bird_name'].value.trim();
        const genotype = {};
        // SSOT: genetics.phpのLOCI定義に準拠
        ['parblue', 'ino', 'opaline', 'cinnamon', 'dark', 'violet', 'fallow_pale', 'fallow_bronze', 'dilute', 'pied_rec', 'pied_dom', 'edged', 'pale_headed'].forEach(key => { const el = form.elements['geno_' + key]; if (el && el.value) genotype[key] = el.value; });
        const phenotype = { baseColor: form.elements['bird_baseColor'].value, darkness: form.elements['bird_darkness'].value, eyeColor: form.elements['bird_eyeColor'].value };
        const geneticError = await this.checkGeneticConsistency(position, phenotype, genotype);
        if (geneticError) { alert(geneticError); return; }
        const bird = { sex: sex, name: inputName, phenotype: phenotype, genotype: genotype, tentativeGeno: {} };
        if (position === 'sire' || position === 'dam') {
            const otherParent = position === 'sire' ? this.data.dam : this.data.sire;
            if (otherParent) {
                const sire = position === 'sire' ? bird : otherParent;
                const dam = position === 'dam' ? bird : otherParent;
                const validation = this.validateBreeding(sire, dam);
                if (!validation.allowed) {
                    const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
                    if (validation.type === 'absolute') { alert(validation.reason); return; }
                    if (validation.type === 'ethics' && this.familyMode === this.FAMILY_MODES.PLAN) { alert(validation.reason); return; }
                    if (this.familyMode === this.FAMILY_MODES.FACT) {
                        const proceed = confirm(`${validation.reason}\n\n${isJa ? '事実モードでは記録できます。続行しますか？' : 'Recordable in Fact Mode. Continue?'}`);
                        if (!proceed) return;
                    }
                }
            }
        }
        if (position.startsWith('offspring_')) { const idx = parseInt(position.split('_')[1]); if (!this.data.offspring) this.data.offspring = []; this.data.offspring[idx] = bird; }
        else this.data[position] = bird;
        this.closeInputModal();
        this.renderUI();
    },

    addOffspring() {
        if (!this.data.offspring) this.data.offspring = [];
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        if (this.data.offspring.length >= 30) { alert(isJa ? '子供は最大30羽までです' : 'Maximum 30 offspring'); return; }
        this.data.offspring.push({ sex: 'male', name: '', phenotype: {}, tentativeGeno: {} });
        this.renderUI();
        const idx = this.data.offspring.length - 1;
        this.openInputModal(`offspring_${idx}`);
    },

    async removeOffspring(idx) {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const msg = isJa ? `子${idx + 1}を削除しますか？` : `Delete ${T.child}${idx + 1}?`;
        if (await customConfirm(msg)) {
            this.data.offspring.splice(idx, 1);
            if (this.targetPosition === `offspring_${idx}`) this.targetPosition = null;
            this.renderUI();
        }
    },

    clearSlot(position) {
        if (position.startsWith('offspring_')) this.removeOffspring(parseInt(position.split('_')[1]));
        else { this.data[position] = null; if (this.targetPosition === position) this.targetPosition = null; this.renderUI(); }
    },

    loadFromDB(position) {
        if (typeof BirdDB === 'undefined') { alert('BirdDBが利用できません'); return; }
        const birds = BirdDB.getAllBirds();
        if (!birds || birds.length === 0) { const isJa = (typeof LANG !== 'undefined' && LANG === 'ja'); alert(isJa ? '登録されている個体がありません' : 'No birds registered'); return; }
        let expectedSex = null;
        if (position === 'sire' || position.endsWith('_sire')) expectedSex = 'male';
        else if (position === 'dam' || position.endsWith('_dam')) expectedSex = 'female';
        const filtered = expectedSex ? birds.filter(b => b.sex === expectedSex) : birds;
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        if (filtered.length === 0) { alert(isJa ? `該当する${expectedSex === 'male' ? 'オス' : 'メス'}がいません` : `No ${expectedSex} birds found`); return; }
        const overlay = document.createElement('div');
        overlay.className = 'custom-confirm-overlay';
        overlay.id = 'birdSelectOverlay';
        const sexLabel = expectedSex === 'male' ? (isJa ? 'オス' : 'Male') : expectedSex === 'female' ? (isJa ? 'メス' : 'Female') : '';
        overlay.innerHTML = `<div class="custom-confirm-modal" style="width:360px;max-height:80vh;"><div class="custom-confirm-message">${isJa ? `${sexLabel}個体を選択` : `Select ${sexLabel} bird`}</div><div style="max-height:50vh;overflow-y:auto;margin-bottom:1rem;">${filtered.map((b, i) => { const pheno = typeof b.phenotype === 'string' ? b.phenotype : this.getColorLabel(b.observed?.baseColor || b.phenotype?.baseColor) || '?'; return `<div class="custom-select-option" data-index="${i}" style="padding:0.75rem 1rem;border-radius:8px;cursor:pointer;margin-bottom:0.25rem;border:1px solid rgba(255,255,255,0.1);"><strong>${b.name || b.code || b.id}</strong><span style="color:#aaa;margin-left:0.5rem;">- ${pheno}</span></div>`; }).join('')}</div><div class="custom-confirm-buttons"><button type="button" class="btn-confirm-cancel">${isJa ? 'キャンセル' : 'Cancel'}</button></div></div>`;
        document.body.appendChild(overlay);
        const self = this;
        overlay.querySelectorAll('.custom-select-option').forEach(el => {
            el.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                const selected = filtered[idx];
                const birdData = { id: selected.id, dbId: selected.id, name: selected.name || '', sex: selected.sex, phenotype: typeof selected.phenotype === 'string' ? { baseColor: selected.phenotype } : (selected.observed || selected.phenotype || { baseColor: 'green' }), genotype: selected.genotype || {}, fromDB: true };
                if (position.startsWith('offspring_')) { const offIdx = parseInt(position.split('_')[1]); if (!self.data.offspring) self.data.offspring = []; self.data.offspring[offIdx] = birdData; }
                else self.data[position] = birdData;
                overlay.remove();
                self.renderUI();
            });
            el.addEventListener('mouseenter', function() { this.style.background = 'rgba(0,229,255,0.15)'; this.style.borderColor = '#00e5ff'; });
            el.addEventListener('mouseleave', function() { this.style.background = ''; this.style.borderColor = 'rgba(255,255,255,0.1)'; });
        });
        overlay.querySelector('.btn-confirm-cancel').addEventListener('click', () => { overlay.remove(); });
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    },

    async clearAll() {
        if (await customConfirm(T.confirm_clear)) {
            this.data = { name: '', savedAt: null, sire: null, sire_sire: null, sire_dam: null, sire_sire_sire: null, sire_sire_dam: null, sire_dam_sire: null, sire_dam_dam: null, dam: null, dam_sire: null, dam_dam: null, dam_sire_sire: null, dam_sire_dam: null, dam_dam_sire: null, dam_dam_dam: null, offspring: [] };
            this.targetPosition = null;
            this.renderUI();
        }
    },

    getPositionLabel(position) {
        const labels = { 'sire': T.sire, 'dam': T.dam, 'sire_sire': T.paternal_gf, 'sire_dam': T.paternal_gm, 'dam_sire': T.maternal_gf, 'dam_dam': T.maternal_gm };
        if (position.startsWith('offspring_')) return `${T.child}${parseInt(position.split('_')[1]) + 1}`;
        return labels[position] || position;
    },

    getPositionLabelDetailed(position) {
        const bird = position.startsWith('offspring_') ? this.data.offspring[parseInt(position.split('_')[1])] : this.data[position];
        const posLabel = this.getPositionLabel(position);
        if (!bird) return posLabel;
        let detail = posLabel;
        if (bird.name) detail += ` (${bird.name})`;
        if (bird.dbId) detail += ` #${bird.dbId}`;
        return detail;
    },

    async runInference() {
        if (!this.canUseGeneticEstimation()) { const isJa = (typeof LANG !== 'undefined' && LANG === 'ja'); alert(isJa ? '事実モードでは遺伝推定は利用できません' : 'Genetic estimation is not available in Fact Mode'); return; }
        if (!this.targetPosition) { alert(T.select_target); return; }
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const inferData = { target: this.targetPosition, birds: this.collectBirdsForInference() };
        if (inferData.birds.length < 2) { alert(isJa ? '推論には対象と最低1羽の親または子の情報が必要です' : 'At least the target and one parent or child is required'); return; }
        const resultEl = document.getElementById('inferenceResult');
        resultEl.innerHTML = `<div class="loading">🔬 ${T.inferring}...</div>`;
        try {
            localStorage.setItem('familyMapBeforeInfer', JSON.stringify({ data: this.data, targetPosition: this.targetPosition, demoMode: this.demoMode, familyMode: this.familyMode }));
            const response = await fetch('infer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(inferData) });
            const result = await response.json();
            if (result.success) this.displayInferenceResult(result);
            else resultEl.innerHTML = `<div class="error">❌ ${result.error || T.inference_error}</div>`;
        } catch (e) { resultEl.innerHTML = `<div class="error">❌ ${T.inference_error}: ${e.message}</div>`; }
    },

    collectBirdsForInference() {
        const birds = [];
        const positions = ['sire', 'dam', 'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam', 'sire_sire_sire', 'sire_sire_dam', 'sire_dam_sire', 'sire_dam_dam', 'dam_sire_sire', 'dam_sire_dam', 'dam_dam_sire', 'dam_dam_dam'];
        positions.forEach(pos => { if (this.data[pos]) birds.push({ position: pos, ...this.data[pos] }); });
        (this.data.offspring || []).forEach((bird, idx) => { if (bird) birds.push({ position: `offspring_${idx}`, ...bird }); });
        return birds;
    },

    displayInferenceResult(result) {
        const resultEl = document.getElementById('inferenceResult');
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        let html = `<div class="inference-success"><h3>🔬 ${isJa ? '推論結果' : 'Inference Result'}</h3><div class="target-info"><strong>${isJa ? '対象' : 'Target'}:</strong> ${this.getPositionLabelDetailed(this.targetPosition)}</div>`;
        if (result.possibleGenotypes && result.possibleGenotypes.length > 0) {
            html += `<h4>${isJa ? '可能な遺伝子型' : 'Possible Genotypes'}</h4><ul>`;
            result.possibleGenotypes.forEach(g => { html += `<li>${this.formatGenotype(g.genotype)} <span class="prob">(${(g.probability * 100).toFixed(1)}%)</span></li>`; });
            html += '</ul>';
        }
        if (result.confirmedAlleles) html += `<h4>${isJa ? '確定アレル' : 'Confirmed Alleles'}</h4><div class="confirmed">${this.formatConfirmedAlleles(result.confirmedAlleles)}</div>`;
        html += '</div>';
        resultEl.innerHTML = html;
    },

    formatGenotype(geno) { return Object.entries(geno).map(([locus, alleles]) => `${locus}: ${alleles}`).join(', '); },
    formatConfirmedAlleles(alleles) { return Object.entries(alleles).map(([locus, val]) => `<span class="allele">${locus}=${val}</span>`).join(' '); },

    async saveSnapshot() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        // v7.0: customPrompt未定義時はpromptにフォールバック
        const promptFn = typeof customPrompt === 'function' ? customPrompt : (msg) => Promise.resolve(prompt(msg));
        const name = await promptFn(isJa ? 'スナップショット名を入力:' : 'Enter snapshot name:');
        if (!name) return;
        const snapshot = { name: name, savedAt: new Date().toISOString(), ...this.data, targetPosition: this.targetPosition, familyMode: this.familyMode };
        const maps = JSON.parse(localStorage.getItem('familyMaps') || '[]');
        maps.push(snapshot);
        localStorage.setItem('familyMaps', JSON.stringify(maps));
        alert(isJa ? '保存しました' : 'Saved!');
        this.loadSavedMaps();
    },

    showLoadModal() {
        const maps = JSON.parse(localStorage.getItem('familyMaps') || '[]');
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const untitledLabel = isJa ? '(無題)' : '(Untitled)';
        if (maps.length === 0) { alert(isJa ? '保存されたマップがありません' : 'No saved maps'); return; }
        const options = maps.map((m, i) => { const date = m.savedAt ? new Date(m.savedAt).toLocaleString() : ''; return { label: `${m.name || untitledLabel} - ${date}`, value: i }; });
        // v7.0: customSelect未定義時は簡易セレクトにフォールバック
        if (typeof customSelect === 'function') {
            customSelect(isJa ? '読み込むマップを選択:' : 'Select map to load:', options).then(idx => { if (idx !== null) this.loadSnapshot(idx); });
        } else {
            const idx = parseInt(prompt((isJa ? '読み込むマップ番号を入力 (0-' : 'Enter map number (0-') + (options.length - 1) + '):'), '0');
            if (!isNaN(idx) && idx >= 0 && idx < options.length) this.loadSnapshot(idx);
        }
    },

    loadSnapshot(idx) {
        const maps = JSON.parse(localStorage.getItem('familyMaps') || '[]');
        const snapshot = maps[idx];
        if (!snapshot) return;
        this.data = { name: snapshot.name || '', savedAt: snapshot.savedAt, sire: snapshot.sire, sire_sire: snapshot.sire_sire, sire_dam: snapshot.sire_dam, sire_sire_sire: snapshot.sire_sire_sire, sire_sire_dam: snapshot.sire_sire_dam, sire_dam_sire: snapshot.sire_dam_sire, sire_dam_dam: snapshot.sire_dam_dam, dam: snapshot.dam, dam_sire: snapshot.dam_sire, dam_dam: snapshot.dam_dam, dam_sire_sire: snapshot.dam_sire_sire, dam_sire_dam: snapshot.dam_sire_dam, dam_dam_sire: snapshot.dam_dam_sire, dam_dam_dam: snapshot.dam_dam_dam, offspring: snapshot.offspring || [] };
        this.targetPosition = snapshot.targetPosition || null;
        this.familyMode = snapshot.familyMode || 'plan';
        this.renderUI();
    },

    loadSavedMaps() {},

    exportJSON() {
        const json = JSON.stringify({ ...this.data, familyMode: this.familyMode }, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `family_map_${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
    },

    /**
     * v7.3.21: 現在のマップ表示名を取得
     * @returns {string} 表示名（名前がない場合は空文字）
     */
    getCurrentMapDisplayName() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');

        // 保存された名前がある場合
        if (this.data.name) {
            return this.data.name;
        }

        // デモモードでデータがある場合
        if (this.demoMode && !this.isDataEmpty()) {
            return isJa ? 'Family C（推論デモ）' : 'Family C (Inference Demo)';
        }

        // データがある場合は「未保存」表示
        if (!this.isDataEmpty()) {
            return isJa ? '未保存のマップ' : 'Unsaved Map';
        }

        return '';
    },

    /**
     * v7.3.21: JSONインポートモーダルを表示
     */
    showImportModal() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');

        // 既存のモーダルがあれば削除
        const existingModal = document.getElementById('familyImportModal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'familyImportModal';
        modal.className = 'modal active';
        modal.innerHTML = `
            <div class="modal-content" style="max-width:500px;">
                <div class="modal-header">
                    <h3>📥 ${isJa ? '家系図インポート' : 'Import Family Map'}</h3>
                    <button class="modal-close" onclick="FamilyMap.closeImportModal()">×</button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);margin-bottom:1rem;font-size:.9rem;">
                        ${isJa ? 'JSONファイルをインポートして家系図を読み込みます。' : 'Import a JSON file to load a family map.'}
                    </p>
                    <div class="form-group">
                        <label>${isJa ? 'JSONファイルを選択' : 'Select JSON File'}</label>
                        <input type="file" id="familyImportFile" accept=".json" class="form-input" style="padding:.5rem;">
                    </div>
                    <div class="form-group" style="margin-top:1rem;">
                        <label>${isJa ? 'インポートモード' : 'Import Mode'}</label>
                        <select id="familyImportMode" class="form-select">
                            <option value="replace">${isJa ? '置換（現在のマップを上書き）' : 'Replace (Overwrite current map)'}</option>
                        </select>
                    </div>
                    <div style="margin-top:1.5rem;padding:1rem;background:var(--bg-tertiary);border-radius:8px;border-left:3px solid var(--accent-turquoise);">
                        <p style="font-size:.85rem;color:var(--text-secondary);margin:0;">
                            💡 ${isJa ? 'テンプレートファイルは「📄 見本」ボタンからダウンロードできます' : 'Download template file using "📄 Template" button'}
                        </p>
                    </div>
                </div>
                <div class="btn-group" style="margin-top:1.5rem;">
                    <button type="button" class="btn btn-primary" onclick="FamilyMap.executeImport()">📥 ${isJa ? 'インポート' : 'Import'}</button>
                    <button type="button" class="btn btn-outline" onclick="FamilyMap.closeImportModal()">${isJa ? 'キャンセル' : 'Cancel'}</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    },

    /**
     * v7.3.21: インポートモーダルを閉じる
     */
    closeImportModal() {
        const modal = document.getElementById('familyImportModal');
        if (modal) modal.remove();
    },

    /**
     * v7.3.21: JSONファイルのインポートを実行
     */
    async executeImport() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const fileInput = document.getElementById('familyImportFile');

        if (!fileInput.files || fileInput.files.length === 0) {
            alert(isJa ? 'ファイルを選択してください' : 'Please select a file');
            return;
        }

        const file = fileInput.files[0];

        try {
            const text = await file.text();
            const imported = JSON.parse(text);

            // 基本的なバリデーション
            if (typeof imported !== 'object') {
                throw new Error(isJa ? '無効なJSON形式です' : 'Invalid JSON format');
            }

            // データをマージ（位置情報を復元）
            this.data = {
                name: imported.name || file.name.replace(/\.json$/, ''),
                savedAt: imported.savedAt || new Date().toISOString(),
                sire: imported.sire || null,
                sire_sire: imported.sire_sire || null,
                sire_dam: imported.sire_dam || null,
                sire_sire_sire: imported.sire_sire_sire || null,
                sire_sire_dam: imported.sire_sire_dam || null,
                sire_dam_sire: imported.sire_dam_sire || null,
                sire_dam_dam: imported.sire_dam_dam || null,
                dam: imported.dam || null,
                dam_sire: imported.dam_sire || null,
                dam_dam: imported.dam_dam || null,
                dam_sire_sire: imported.dam_sire_sire || null,
                dam_sire_dam: imported.dam_sire_dam || null,
                dam_dam_sire: imported.dam_dam_sire || null,
                dam_dam_dam: imported.dam_dam_dam || null,
                offspring: imported.offspring || []
            };

            this.familyMode = imported.familyMode || 'plan';
            this.targetPosition = null;

            this.closeImportModal();
            this.renderUI();

            alert(isJa ? 'インポートしました' : 'Import successful');
        } catch (e) {
            alert((isJa ? 'インポートエラー: ' : 'Import error: ') + e.message);
        }
    },

    /**
     * v7.3.21: サンプルテンプレートJSONをダウンロード
     */
    downloadTemplate() {
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');

        // サンプルデータ（最小限の家系図）
        const template = {
            name: isJa ? 'サンプル家系図' : 'Sample Family Map',
            savedAt: new Date().toISOString(),
            familyMode: 'plan',
            sire: {
                sex: 'male',
                name: isJa ? '父太郎' : 'Father Taro',
                phenotype: { baseColor: 'green', eyeColor: 'black', darkness: 'none' },
                genotype: { parblue: '+aq', ino: '++', dark: 'dd', opaline: '++', cinnamon: '++' }
            },
            dam: {
                sex: 'female',
                name: isJa ? '母花子' : 'Mother Hanako',
                phenotype: { baseColor: 'aqua', eyeColor: 'black', darkness: 'none' },
                genotype: { parblue: 'aqaq', ino: '+W', dark: 'dd', opaline: '+W', cinnamon: '+W' }
            },
            sire_sire: {
                sex: 'male',
                name: isJa ? '父方祖父' : 'Paternal Grandfather',
                phenotype: { baseColor: 'green', eyeColor: 'black', darkness: 'none' },
                genotype: { parblue: '++', ino: '++' }
            },
            sire_dam: {
                sex: 'female',
                name: isJa ? '父方祖母' : 'Paternal Grandmother',
                phenotype: { baseColor: 'aqua', eyeColor: 'black', darkness: 'none' },
                genotype: { parblue: 'aqaq', ino: '+W' }
            },
            dam_sire: null,
            dam_dam: null,
            sire_sire_sire: null,
            sire_sire_dam: null,
            sire_dam_sire: null,
            sire_dam_dam: null,
            dam_sire_sire: null,
            dam_sire_dam: null,
            dam_dam_sire: null,
            dam_dam_dam: null,
            offspring: [
                {
                    sex: 'male',
                    name: isJa ? '子1（オス）' : 'Child 1 (Male)',
                    phenotype: { baseColor: 'aqua', eyeColor: 'black', darkness: 'none' },
                    genotype: { parblue: 'aqaq', ino: '++' }
                },
                {
                    sex: 'female',
                    name: isJa ? '子2（メス）' : 'Child 2 (Female)',
                    phenotype: { baseColor: 'green', eyeColor: 'black', darkness: 'none' },
                    genotype: { parblue: '+aq', ino: '+W' }
                }
            ]
        };

        const json = JSON.stringify(template, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'family_map_template.json';
        a.click();
        URL.revokeObjectURL(url);
    },

    checkCircularReference(position, dbId) {
        if (!dbId) return null;
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        const getDescendantPositions = (pos) => {
            const map = { 'sire_sire_sire': ['sire_sire', 'sire', 'offspring'], 'sire_sire_dam': ['sire_sire', 'sire', 'offspring'], 'sire_dam_sire': ['sire_dam', 'sire', 'offspring'], 'sire_dam_dam': ['sire_dam', 'sire', 'offspring'], 'dam_sire_sire': ['dam_sire', 'dam', 'offspring'], 'dam_sire_dam': ['dam_sire', 'dam', 'offspring'], 'dam_dam_sire': ['dam_dam', 'dam', 'offspring'], 'dam_dam_dam': ['dam_dam', 'dam', 'offspring'], 'sire_sire': ['sire', 'offspring'], 'sire_dam': ['sire', 'offspring'], 'dam_sire': ['dam', 'offspring'], 'dam_dam': ['dam', 'offspring'], 'sire': ['offspring'], 'dam': ['offspring'] };
            return map[pos] || [];
        };
        const descendantPositions = getDescendantPositions(position);
        for (const descPos of descendantPositions) {
            if (descPos === 'offspring') { for (const child of (this.data.offspring || [])) { if (child?.dbId === dbId) return isJa ? 'この個体は既に子孫として登録されています' : 'This bird is already registered as a descendant'; } }
            else { if (this.data[descPos]?.dbId === dbId) return isJa ? `この個体は既に${this.getPositionLabel(descPos)}として登録されています` : `This bird is already registered as ${this.getPositionLabel(descPos)}`; }
        }
        return null;
    },

    getColorLabel(colorCode) {
        // v7.3.11: SSOT - COLOR_MASTER/keyToLabelを使用（ハードコード排除）
        if (typeof keyToLabel === 'function') {
            return keyToLabel(colorCode);
        }
        // フォールバック: COLOR_MASTERから直接取得
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        if (typeof COLOR_MASTER !== 'undefined' && COLOR_MASTER[colorCode]) {
            return isJa ? COLOR_MASTER[colorCode].ja : COLOR_MASTER[colorCode].en;
        }
        return colorCode;
    },

    formatGenotypeShort(geno) {
        if (!geno || typeof geno !== 'object') return '';
        const parts = [];
        // SSOT: genetics.phpのLOCI定義に準拠
        if (geno.parblue && geno.parblue !== '++') parts.push(`pb:${geno.parblue}`);
        if (geno.ino && geno.ino !== '++' && geno.ino !== '+W') parts.push(`ino:${geno.ino}`);
        if (geno.dark && geno.dark !== 'dd') parts.push(`D:${geno.dark}`);
        if (geno.violet && geno.violet !== 'vv') parts.push(`vi:${geno.violet}`);
        if (geno.opaline && geno.opaline !== '++' && geno.opaline !== '+W') parts.push(`op:${geno.opaline}`);
        if (geno.cinnamon && geno.cinnamon !== '++' && geno.cinnamon !== '+W') parts.push(`cin:${geno.cinnamon}`);
        if (geno.fallow_pale && geno.fallow_pale !== '++') parts.push(`fl:${geno.fallow_pale}`);
        if (geno.fallow_bronze && geno.fallow_bronze !== '++') parts.push(`flb:${geno.fallow_bronze}`);
        if (geno.dilute && geno.dilute !== '++') parts.push(`dil:${geno.dilute}`);
        if (geno.pied_rec && geno.pied_rec !== '++') parts.push(`pi:${geno.pied_rec}`);
        if (geno.pied_dom && geno.pied_dom !== '++') parts.push(`Pi:${geno.pied_dom}`);
        if (geno.edged && geno.edged !== '++') parts.push(`ed:${geno.edged}`);
        if (geno.pale_headed && geno.pale_headed !== '++') parts.push(`ph:${geno.pale_headed}`);
        return parts.join(' ');
    },

    /**
     * 遺伝的整合性チェック（SSOT: PHP API経由）
     * @param {string} position - 対象位置
     * @param {Object} phenotype - 表現型
     * @param {Object} genotype - 遺伝子型
     * @returns {Promise<string|null>} エラーメッセージまたはnull
     */
    async checkGeneticConsistency(position, phenotype, genotype = {}) {
        const parentPositions = {
            'sire_sire': ['sire_sire_sire', 'sire_sire_dam'],
            'sire_dam': ['sire_dam_sire', 'sire_dam_dam'],
            'dam_sire': ['dam_sire_sire', 'dam_sire_dam'],
            'dam_dam': ['dam_dam_sire', 'dam_dam_dam'],
            'sire': ['sire_sire', 'sire_dam'],
            'dam': ['dam_sire', 'dam_dam']
        };

        // 親を取得
        let parents;
        if (position.startsWith('offspring_')) {
            parents = [this.data.sire, this.data.dam];
        } else if (parentPositions[position]) {
            const [fatherPos, motherPos] = parentPositions[position];
            parents = [this.data[fatherPos], this.data[motherPos]];
        } else {
            return null;
        }

        if (!parents[0] || !parents[1]) return null;

        const father = parents[0], mother = parents[1];
        const childSex = position.startsWith('offspring_')
            ? (this.data.offspring?.[parseInt(position.split('_')[1])]?.sex || 'male')
            : this.getDefaultSex(position);

        // 親の遺伝子型を構築（確定 > 推定 > 表現型から推論）
        const buildGenotype = (bird, sex) => {
            const geno = { ...bird.genotype };
            // tentativeGenoで補完
            if (bird.tentativeGeno) {
                for (const [k, v] of Object.entries(bird.tentativeGeno)) {
                    if (!geno[k]) geno[k] = v;
                }
            }
            // 表現型から推論で補完
            const pheno = bird.phenotype?.baseColor || 'green';
            const eyeColor = bird.phenotype?.eyeColor || 'black';
            const isFemale = sex === 'female';

            // parblue
            if (!geno.parblue) {
                if (['aqua', 'aqua_dark', 'aqua_dd', 'blue', 'cobalt', 'mauve', 'pure_white', 'albino', 'creamino'].includes(pheno)) geno.parblue = 'aqaq';
                else if (['turquoise', 'turquoise_dark'].includes(pheno)) geno.parblue = 'tqtq';
                else if (['seagreen', 'seagreen_dark', 'creamino_seagreen'].includes(pheno)) geno.parblue = 'tqaq';
            }
            // ino
            if (!geno.ino) {
                if (['lutino', 'creamino', 'pure_white', 'creamino_seagreen', 'albino'].includes(pheno) || eyeColor === 'red') geno.ino = isFemale ? 'inoW' : 'inoino';
                else if (pheno.includes('pallid')) geno.ino = isFemale ? 'pldW' : 'pldpld';
            }
            // opaline
            if (!geno.opaline && pheno.includes('opaline')) geno.opaline = isFemale ? 'opW' : 'opop';
            // cinnamon
            if (!geno.cinnamon && pheno.includes('cinnamon')) geno.cinnamon = isFemale ? 'cinW' : 'cincin';

            return geno;
        };

        const sireGeno = buildGenotype(father, 'male');
        const damGeno = buildGenotype(mother, 'female');

        // 子の遺伝子型を構築
        const childGeno = { ...genotype };
        const childPheno = phenotype?.baseColor || 'green';
        const childEye = phenotype?.eyeColor || 'black';
        const childIsFemale = childSex === 'female';

        if (!childGeno.parblue) {
            if (['aqua', 'aqua_dark', 'aqua_dd', 'blue', 'cobalt', 'mauve', 'pure_white', 'albino', 'creamino'].includes(childPheno)) childGeno.parblue = 'aqaq';
            else if (['turquoise', 'turquoise_dark'].includes(childPheno)) childGeno.parblue = 'tqtq';
            else if (['seagreen', 'seagreen_dark', 'creamino_seagreen'].includes(childPheno)) childGeno.parblue = 'tqaq';
        }
        if (!childGeno.ino) {
            if (['lutino', 'creamino', 'pure_white', 'creamino_seagreen', 'albino'].includes(childPheno) || childEye === 'red') childGeno.ino = childIsFemale ? 'inoW' : 'inoino';
            else if (childPheno.includes('pallid')) childGeno.ino = childIsFemale ? 'pldW' : 'pldpld';
        }
        if (!childGeno.opaline && childPheno.includes('opaline')) childGeno.opaline = childIsFemale ? 'opW' : 'opop';
        if (!childGeno.cinnamon && childPheno.includes('cinnamon')) childGeno.cinnamon = childIsFemale ? 'cinW' : 'cincin';

        // API呼び出し
        try {
            const response = await fetch('infer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'checkConsistency',
                    sireGenotype: sireGeno,
                    damGenotype: damGeno,
                    childGenotype: childGeno,
                    childSex: childSex
                })
            });

            const result = await response.json();

            if (!result.consistent) {
                return result.message || (typeof LANG !== 'undefined' && LANG === 'ja'
                    ? '前世代の構成からはこの遺伝子型の子は生まれません'
                    : 'This genotype cannot be produced from the parent combination');
            }
            return null;
        } catch (error) {
            console.error('Consistency check API error:', error);
            // API失敗時はエラーを返さず許可（オフライン対応）
            return null;
        }
    },

    analyzeInbreeding() {
        const ancestorPositions = ['sire_sire_sire', 'sire_sire_dam', 'sire_dam_sire', 'sire_dam_dam', 'dam_sire_sire', 'dam_sire_dam', 'dam_dam_sire', 'dam_dam_dam'];
        const idOccurrences = {};
        ancestorPositions.forEach(pos => { const id = this.data[pos]?.dbId; if (id) { if (!idOccurrences[id]) idOccurrences[id] = []; idOccurrences[id].push(pos); } });
        const gpPositions = ['sire_sire', 'sire_dam', 'dam_sire', 'dam_dam'];
        gpPositions.forEach(pos => { const id = this.data[pos]?.dbId; if (id) { if (!idOccurrences[id]) idOccurrences[id] = []; idOccurrences[id].push(pos); } });
        const crossings = [];
        for (const [id, positions] of Object.entries(idOccurrences)) {
            if (positions.length > 1) {
                const bird = this.data[positions[0]];
                const name = bird?.name || `#${id}`;
                const fatherSide = positions.filter(p => p.startsWith('sire_'));
                const motherSide = positions.filter(p => p.startsWith('dam_'));
                if (fatherSide.length > 0 && motherSide.length > 0) crossings.push({ id, name, positions, type: 'cross', severity: positions.length });
            }
        }
        let inbreedingCoef = 0;
        crossings.forEach(c => { const n = c.positions[0].split('_').length; inbreedingCoef += Math.pow(0.5, 2 * n + 1) * (c.severity - 1); });
        this.displayInbreedingAnalysis(crossings, inbreedingCoef);
    },

    displayInbreedingAnalysis(crossings, coef) {
        const warningEl = document.getElementById('inbreedingWarning');
        if (!warningEl) return;
        if (crossings.length === 0) { warningEl.innerHTML = ''; warningEl.style.display = 'none'; return; }
        const isJa = (typeof LANG !== 'undefined' && LANG === 'ja');
        let riskLevel, riskLabel, riskClass;
        if (coef >= 0.25) { riskLevel = 'critical'; riskLabel = isJa ? '🚫 危険な配合です。生存率低下は不可避です。' : '🚫 Dangerous. Survival rate decline is inevitable.'; riskClass = 'risk-critical'; }
        else if (coef >= 0.125) { riskLevel = 'high'; riskLabel = isJa ? '⚠️ 競走馬では禁忌とされる配合です' : '⚠️ Prohibited in thoroughbred breeding'; riskClass = 'risk-high'; }
        else if (coef >= 0.0625) { riskLevel = 'moderate'; riskLabel = isJa ? '⚡ 注意が必要です' : '⚡ Caution required'; riskClass = 'risk-moderate'; }
        else { riskLevel = 'low'; riskLabel = isJa ? '💡 低リスク' : '💡 Low risk'; riskClass = 'risk-low'; }
        let modeNote = '';
        if (this.familyMode === this.FAMILY_MODES.FACT) modeNote = `<div class="mode-note">${isJa ? '※事実モード: 記録可能ですが遺伝推定は利用できません' : '※Fact Mode: Recordable but no inference'}</div>`;
        else if (coef >= 0.125) modeNote = `<div class="mode-note">${isJa ? '※倫理計画モード: この配合は許可されません' : '※Plan Mode: This breeding is not allowed'}</div>`;
        let html = `<div class="inbreeding-alert ${riskClass}"><h4>🧬 ${isJa ? '近交分析' : 'Inbreeding Analysis'}</h4><div class="coef">${isJa ? '近交係数' : 'Inbreeding Coefficient'} F = ${(coef * 100).toFixed(2)}%</div><div class="risk">${riskLabel}</div>${modeNote}<div class="crossings"><strong>${isJa ? '共通祖先' : 'Common Ancestors'}:</strong><ul>${crossings.map(c => `<li>${c.name} ${isJa ? 'が' : 'appears in'} ${c.positions.map(p => this.getPositionLabel(p)).join('、')}</li>`).join('')}</ul></div></div>`;
        warningEl.innerHTML = html;
        warningEl.style.display = 'block';
    },

    registerToDB() {
        const modal = document.getElementById('birdInputModal');
        const form = modal.querySelector('form');
        if (typeof BirdDB === 'undefined') { alert('個体DBが読み込まれていません'); return; }
        const bird = { name: form.elements['bird_name'].value || `個体_${Date.now()}`, sex: form.elements['bird_sex'].value, phenotype: form.elements['bird_baseColor'].value };
        BirdDB.addBird(bird);
        alert(`「${bird.name}」をDBに登録しました`);
    },

    bindEvents() {}
};

document.addEventListener('DOMContentLoaded', () => { if (document.getElementById('familyMapContainer')) FamilyMap.init(); });
