/**
 * Agapornis Gene-Forge v7.0
 * breeding.js - 交配実行・結果生成モジュール
 * 
 * 整合性原理：
 * - genetics.php → LOCI_MASTER / COLOR_MASTER が唯一の真実
 * - 座位定義・色定義のハードコードは一切持たない
 * - GeneticsEngine（PHP側）への委譲を優先
 * 
 * 依存:
 * - guardian.js (BreedingValidator)
 * - birds.js (BirdDB)
 * - index.php (LOCI_MASTER, COLOR_MASTER, COLOR_LABELS)
 */

const BreedingEngine = {
    VERSION: '7.0',
    
    // ========================================
    // SSOT参照
    // ========================================
    
    /**
     * 座位一覧を取得（LOCI_MASTERから動的取得）
     */
    getLoci() {
        if (typeof LOCI_MASTER !== 'undefined') {
            return Object.keys(LOCI_MASTER);
        }
        console.warn('[BreedingEngine] LOCI_MASTER未定義');
        return [];
    },
    
    /**
     * 伴性遺伝座位を取得
     */
    getSexLinkedLoci() {
        if (typeof LOCI_MASTER !== 'undefined') {
            return Object.keys(LOCI_MASTER).filter(k => LOCI_MASTER[k].sex_linked === true);
        }
        return ['ino', 'opaline', 'cinnamon'];
    },
    
    /**
     * 常染色体座位を取得
     */
    getAutosomalLoci() {
        if (typeof LOCI_MASTER !== 'undefined') {
            return Object.keys(LOCI_MASTER).filter(k => LOCI_MASTER[k].sex_linked !== true);
        }
        return ['parblue', 'dark', 'violet', 'fallow_pale', 'fallow_bronze', 
                'pied_dom', 'pied_rec', 'dilute', 'edged', 'orangeface', 'pale_headed'];
    },
    
    /**
     * 色名を取得（v7.3: keyToLabel対応）
     */
    getColorLabel(colorKey) {
        // v7.3: keyToLabel関数でローカライズ（動的変換対応）
        if (typeof keyToLabel === 'function') {
            return keyToLabel(colorKey);
        }
        // フォールバック
        const lang = typeof LANG !== 'undefined' ? LANG : 'ja';
        if (typeof COLOR_MASTER !== 'undefined' && COLOR_MASTER[colorKey]) {
            return COLOR_MASTER[colorKey][lang] || COLOR_MASTER[colorKey].en || COLOR_MASTER[colorKey].ja || colorKey;
        }
        if (typeof COLOR_LABELS !== 'undefined' && COLOR_LABELS[colorKey]) {
            return COLOR_LABELS[colorKey];
        }
        return colorKey;
    },
    
    // ========================================
    // 交配実行
    // ========================================
    
    /**
     * 交配を実行（バリデーション付き）
     */
    execute(sireId, damId, options = {}) {
        const mode = options.mode || 'plan';
        const lang = typeof LANG !== 'undefined' ? LANG : 'ja';
        const errBirdDB = { ja: 'BirdDB未定義', en: 'BirdDB not defined', de: 'BirdDB nicht definiert', fr: 'BirdDB non défini', it: 'BirdDB non definito', es: 'BirdDB no definido' };
        const errSire = { ja: '父個体が見つかりません', en: 'Sire not found', de: 'Vater nicht gefunden', fr: 'Père non trouvé', it: 'Padre non trovato', es: 'Padre no encontrado' };
        const errDam = { ja: '母個体が見つかりません', en: 'Dam not found', de: 'Mutter nicht gefunden', fr: 'Mère non trouvée', it: 'Madre non trovata', es: 'Madre no encontrada' };

        if (typeof BirdDB === 'undefined') {
            return { success: false, error: errBirdDB[lang] || errBirdDB.en };
        }

        const sire = BirdDB.getBird(sireId);
        const dam = BirdDB.getBird(damId);

        if (!sire) return { success: false, error: errSire[lang] || errSire.en };
        if (!dam) return { success: false, error: errDam[lang] || errDam.en };
        
        // BreedingValidator によるチェック
        if (typeof BreedingValidator !== 'undefined' && !options.skipValidation) {
            const validation = BreedingValidator.validate(sire, dam, mode);
            if (!validation.allowed) {
                const errCannotBreed = { ja: '交配できません', en: 'Cannot breed', de: 'Zucht nicht möglich', fr: 'Reproduction impossible', it: 'Riproduzione non possibile', es: 'No se puede criar' };
                return {
                    success: false,
                    error: validation.reason || (errCannotBreed[lang] || errCannotBreed.en),
                    blockType: validation.type
                };
            }
            if (validation.warning) {
                options._warnings = [validation.warning];
            }
        }
        
        const result = this.simulate(sire, dam, options);
        if (options._warnings) result.warnings = options._warnings;
        
        return { success: true, result };
    },
    
    /**
     * 交配シミュレーション
     */
    simulate(sire, dam, options = {}) {
        const offspring = this.calculateOffspring(sire, dam);
        
        let inbreedingCoef = 0;
        if (typeof BreedingValidator !== 'undefined') {
            inbreedingCoef = BreedingValidator.calcInbreedingCoefficient(sire, dam);
        } else if (typeof BirdDB !== 'undefined') {
            const ic = BirdDB.calculateInbreedingCoefficient(sire.id, dam.id);
            inbreedingCoef = ic.coefficient || 0;
        }
        
        return {
            sire: { id: sire.id, name: sire.name, code: sire.code, phenotype: sire.phenotype, genotype: sire.genotype },
            dam: { id: dam.id, name: dam.name, code: dam.code, phenotype: dam.phenotype, genotype: dam.genotype },
            offspring,
            inbreedingCoefficient: inbreedingCoef,
            timestamp: new Date().toISOString()
        };
    },
    
    // ========================================
    // 子孫遺伝子型計算
    // ========================================
    
    /**
     * 子孫の遺伝子型と確率を計算
     * 交配計算の正本は genetics.php の GeneticsCalculator（SSOT）。JS 側に計算の写しは持たない。
     */
    calculateOffspring(sire, dam) {
        if (typeof GeneticsEngine !== 'undefined' && GeneticsEngine.crossBirds) {
            return GeneticsEngine.crossBirds(sire, dam);
        }
        console.warn('[BreedingEngine] 交配計算は PHP の GeneticsCalculator（交配計算タブ）で行う');
        return [];
    },

    // ========================================
    // 保存・登録
    // ========================================
    
    saveResult(result, options = {}) {
        if (typeof BirdDB === 'undefined') {
            return { success: false, error: 'BirdDB未定義' };
        }
        const record = BirdDB.saveBreedingResult({
            sire: result.sire,
            dam: result.dam,
            offspring: result.offspring,
            inbreedingCoefficient: result.inbreedingCoefficient,
            notes: options.notes || ''
        });
        return { success: true, record };
    },
    
    registerOffspring(offspringData, parentResult) {
        const lang = typeof LANG !== 'undefined' ? LANG : 'ja';
        const errMsg = { ja: 'BirdDB未定義', en: 'BirdDB not defined', de: 'BirdDB nicht definiert', fr: 'BirdDB non défini', it: 'BirdDB non definito', es: 'BirdDB no definido' };
        if (typeof BirdDB === 'undefined') {
            return { success: false, error: errMsg[lang] || errMsg.en };
        }
        // v7.0: buildPedigreeFromParents存在チェック
        const pedigree = (typeof BirdDB.buildPedigreeFromParents === 'function')
            ? BirdDB.buildPedigreeFromParents(parentResult.sire.id, parentResult.dam.id)
            : null;
        const offspringLabel = { ja: 'の子', en: ' offspring', de: ' Nachkommen', fr: ' descendant', it: ' discendente', es: ' descendiente' };
        const bird = BirdDB.addBird({
            name: offspringData.name || '',
            sex: offspringData.sex,
            genotype: offspringData.genotype,
            phenotype: offspringData.phenotype,
            sire: { id: parentResult.sire.id, name: parentResult.sire.name },
            dam: { id: parentResult.dam.id, name: parentResult.dam.name },
            pedigree,
            birthDate: offspringData.birthDate || new Date().toISOString().split('T')[0],
            notes: offspringData.notes || `${parentResult.sire.name} × ${parentResult.dam.name}${offspringLabel[lang] || offspringLabel.en}`
        });
        return { success: true, bird };
    },
    
    // ========================================
    // ユーティリティ
    // ========================================
    
    canBreed(sireId, damId, mode = 'plan') {
        if (typeof BirdDB === 'undefined') return false;
        const sire = BirdDB.getBird(sireId);
        const dam = BirdDB.getBird(damId);
        if (!sire || !dam) return false;
        
        if (typeof BreedingValidator !== 'undefined') {
            return BreedingValidator.validate(sire, dam, mode).allowed;
        }
        
        return sire.sex === 'male' && dam.sex === 'female' && sire.id !== dam.id;
    },
    
    getInbreedingCoefficient(sireId, damId) {
        if (typeof BirdDB === 'undefined') return 0;
        const sire = BirdDB.getBird(sireId);
        const dam = BirdDB.getBird(damId);
        if (!sire || !dam) return 0;
        
        if (typeof BreedingValidator !== 'undefined') {
            return BreedingValidator.calcInbreedingCoefficient(sire, dam);
        }
        return BirdDB.calculateInbreedingCoefficient(sireId, damId).coefficient || 0;
    }
};

// グローバル公開
if (typeof window !== 'undefined') {
    window.BreedingEngine = BreedingEngine;
}

console.log('[BreedingEngine] v' + BreedingEngine.VERSION + ' loaded (SSOT)');
