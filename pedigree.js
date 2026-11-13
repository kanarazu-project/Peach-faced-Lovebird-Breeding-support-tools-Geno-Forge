/**
 * Agapornis Gene-Forge v7.0
 * 血統書生成（Pedigree Certificate Generator）
 * 
 * 整合性原理：
 * - 座位定義・色定義は genetics.php → index.php → JavaScript定数 から取得
 * - このファイルはレンダリングロジックのみを担当
 * - ハードコードされた遺伝情報は一切持たない
 */

const PedigreeGenerator = {

    // ========================================
    // SSOT参照（index.php から注入される定数を使用）
    // ========================================
    
    /**
     * 座位一覧を取得（LOCI_MASTER から動的取得）
     */
    getLoci() {
        if (typeof LOCI_MASTER !== 'undefined') {
            return Object.keys(LOCI_MASTER);
        }
        // フォールバック（LOCI_MASTER未定義時）
        console.warn('LOCI_MASTER not defined, using fallback');
        return ['parblue', 'dark', 'violet', 'ino', 'opaline', 'cinnamon', 
                'fallow_pale', 'fallow_bronze', 'pied_dom', 'pied_rec', 
                'dilute', 'edged', 'orangeface', 'pale_headed'];
    },
    
    /**
     * 座位のラベルを取得
     */
    getLocusLabel(locusKey, lang = 'ja') {
        if (typeof LOCI_MASTER !== 'undefined' && LOCI_MASTER[locusKey]) {
            const names = LOCI_MASTER[locusKey].name;
            return names[lang] || names['en'] || locusKey;
        }
        return locusKey;
    },
    
    /**
     * 座位が伴性遺伝かどうか
     */
    isSexLinked(locusKey) {
        if (typeof LOCI_MASTER !== 'undefined' && LOCI_MASTER[locusKey]) {
            return LOCI_MASTER[locusKey].sex_linked === true;
        }
        // フォールバック
        return ['ino', 'opaline', 'cinnamon'].includes(locusKey);
    },
    
    /**
     * 色名を取得（v7.3: keyToLabel対応）
     */
    getColorLabel(colorKey, lang = 'ja') {
        // v7.3: keyToLabel関数でローカライズ（動的変換対応）
        if (typeof keyToLabel === 'function') {
            return keyToLabel(colorKey);
        }
        if (typeof COLOR_MASTER !== 'undefined' && COLOR_MASTER[colorKey]) {
            return COLOR_MASTER[colorKey][lang] || COLOR_MASTER[colorKey]['en'] || colorKey;
        }
        if (typeof COLOR_LABELS !== 'undefined' && COLOR_LABELS[colorKey]) {
            return COLOR_LABELS[colorKey];
        }
        return colorKey;
    },

    // ========================================
    // 学術形式変換（動的生成）
    // ========================================
    
    /**
     * 遺伝子型を学術形式に変換
     */
    toAcademicFormat(locusKey, alleles) {
        if (!alleles) return '+/+';
        
        const isSL = this.isSexLinked(locusKey);
        
        // 伴性遺伝のヘミ接合（メス）
        if (alleles.endsWith('W')) {
            const allele = alleles.replace('W', '');
            if (allele === '+') return '+/W';
            return `Z^${allele}/W`;
        }
        
        // ホモ接合チェック
        const alleleMap = {
            // Parblue
            '++': '+/+', '+aq': '+/b^aq', 'aqaq': 'b^aq/b^aq',
            '+tq': '+/b^tq', 'tqtq': 'b^tq/b^tq', 'tqaq': 'b^tq/b^aq',
            // Dark
            'dd': 'd/d', 'Dd': 'D/d', 'DD': 'D/D',
            // Violet
            'vv': 'v/v', 'Vv': 'V/v', 'VV': 'V/V',
            // INO (オス)
            '+ino': '+/Z^ino', 'inoino': 'Z^ino/Z^ino',
            '+pld': '+/Z^pld', 'pldpld': 'Z^pld/Z^pld', 'pldino': 'Z^pld/Z^ino',
            // Opaline (オス)
            '+op': '+/Z^op', 'opop': 'Z^op/Z^op',
            // Cinnamon (オス)
            '+cin': '+/Z^cin', 'cincin': 'Z^cin/Z^cin',
            // 常染色体劣性
            '+flp': '+/flp', 'flpflp': 'flp/flp',
            '+flb': '+/flb', 'flbflb': 'flb/flb',
            'Pi+': 'Pi/+', 'PiPi': 'Pi/Pi',
            '+pi': '+/pi', 'pipi': 'pi/pi',
            '+dil': '+/dil', 'dildil': 'dil/dil',
            '+ed': '+/ed', 'eded': 'ed/ed',
            '+of': '+/of', 'ofof': 'of/of',
            '+ph': '+/ph', 'phph': 'ph/ph',
        };
        
        return alleleMap[alleles] || alleles;
    },

    // ========================================
    // 確度計算
    // ========================================

    /**
     * 形質ごとの確度を計算
     */
    calculateLocusConfidences(bird) {
        const result = {};
        const geno = bird.genotype || {};
        const sex = bird.sex;
        const loci = this.getLoci();
        
        for (const locus of loci) {
            const alleles = geno[locus] || this.getDefaultAllele(locus, sex);
            const academic = this.toAcademicFormat(locus, alleles);
            const { confidence, source } = this.inferConfidence(bird, locus);

            result[locus] = {
                alleles,
                academic,
                confidence,
                source,
                display: this.formatConfidenceDisplay(confidence, source)
            };
        }

        return result;
    },

    /**
     * デフォルトアレル（野生型）
     */
    getDefaultAllele(locus, sex) {
        if (this.isSexLinked(locus) && sex === 'female') {
            return '+W';
        }
        if (locus === 'dark') return 'dd';
        if (locus === 'violet') return 'vv';
        return '++';
    },

    /**
     * 確度推論
     */
    inferConfidence(bird, locus) {
        const geno = bird.genotype || {};
        const observed = bird.observed || {};
        const alleles = geno[locus];
        
        // 表現型から確定（100）または2通りまで絞れる（50）
        const phenoConfidence = this.phenotypeConfidence(observed, locus, bird.sex);
        if (phenoConfidence > 0) {
            return { confidence: phenoConfidence, source: 'phenotype' };
        }
        
        // 遺伝子型が設定されている
        if (alleles && !this.isWildType(locus, alleles)) {
            return { confidence: 80, source: 'manual' };
        }
        
        return { confidence: 0, source: 'unknown' };
    },
    
    /**
     * 野生型かどうか判定
     */
    isWildType(locus, alleles) {
        const wildTypes = ['++', '+W', 'dd', 'vv'];
        return wildTypes.includes(alleles);
    },

    /**
     * 遺伝子型文字列をアレル配列に分解（genetics.php AgapornisLoci::splitGenotype と同じ規則）
     */
    splitGenotype(locus, genotype) {
        const def = (typeof LOCI_MASTER !== 'undefined') ? LOCI_MASTER[locus] : null;
        const names = def ? Object.keys(def.alleles || {}) : [];
        if (!names.length || typeof genotype !== 'string') return null;
        if (locus === 'edged') genotype = genotype.replace(/ed/g, 'Ed');
        if (genotype.endsWith('W')) {
            const a = genotype.slice(0, -1);
            return names.includes(a) ? [a] : null;
        }
        for (const a of names) {
            for (const b of names) {
                if (a + b === genotype) return [a, b];
            }
        }
        return null;
    },

    /**
     * アレルの組が表現型に現れるか（AgapornisLoci::isExpressedAlleles と同じ規則）
     */
    isExpressedAlleles(locus, alleles) {
        const def = LOCI_MASTER[locus] || {};
        const wild = Object.keys(def.alleles || {}).find(a => def.alleles[a] === 0);
        const mutant = alleles.filter(a => a !== wild);
        if (def.type === 'AD' || def.type === 'AID') return mutant.length > 0;
        return alleles.length > 0 && mutant.length === alleles.length;
    },

    /**
     * 表現型だけから遺伝子型をどこまで確定できるか
     * genetics.php GenotypeEstimator::phenotypeConfidence と同じ規則:
     *   100 確定 / 50 見た目が同じ2通り（Pi+ と PiPi、パリッドのオスの pldpld と pldino）/ 0 判別不能
     */
    phenotypeConfidence(observed, locus, sex) {
        const baseColor = observed.baseColor || '';
        const darkness = observed.darkness || '';

        // ダークファクターは darkness 入力から確定
        if (locus === 'dark' && darkness && darkness !== 'unknown') return 100;
        // 赤目だけでは INO と確定しない（ファローも赤目）。色名の定義から判定する。

        const colorDef = (typeof COLOR_MASTER !== 'undefined') ? COLOR_MASTER[baseColor] : null;
        const lociDef = (typeof LOCI_MASTER !== 'undefined') ? LOCI_MASTER[locus] : null;
        if (!colorDef || !lociDef) return 0;

        const defGeno = colorDef.genotype || {};
        const isFemale = sex === 'female';
        const isSexLinked = !!lociDef.sex_linked;
        const wild = Object.keys(lociDef.alleles || {}).find(a => lociDef.alleles[a] === 0) || '+';
        let value = defGeno[locus] || (isSexLinked && isFemale ? wild + 'W' : wild + wild);
        if (isSexLinked && isFemale) {
            // 色定義はオス表記（inoino など）なので、メスはヘミ接合（inoW）に読み替える
            const homo = this.splitGenotype(locus, value);
            if (homo && homo.length === 2 && homo[0] === homo[1]) value = homo[0] + 'W';
        }
        const alleles = this.splitGenotype(locus, value);
        if (!alleles) return 0;
        const type = lociDef.type || 'AR';

        if (this.isExpressedAlleles(locus, alleles)) {
            if (type === 'AD') return 50;
            if (locus === 'ino' && !isFemale && value === 'pldpld') return 50;
            return 100;
        }
        // INO は他の座位の見た目を覆い隠す
        const inoMasked = ['inoino', 'inoW'].includes(defGeno.ino) && !['ino', 'parblue', 'dark'].includes(locus);
        if (inoMasked) return 0;
        if (type === 'AD' || type === 'AID') return 100;   // 優性が出ていない → 野生型で確定
        if (isSexLinked && isFemale) return 100;             // メスは隠れキャリアがいない
        return 0;                                            // 劣性のスプリットは判別できない
    },

    /**
     * 確度表示フォーマット
     */
    formatConfidenceDisplay(confidence, source) {
        const T = window.T || {};
        if (source === 'phenotype') {
            if (confidence === 50) return T.two_possible || 'One of two';
            return T.confirmed_phenotype || '✓ Confirmed (phenotype)';
        }
        if (confidence === 100) {
            return T.confirmed || '✓ Confirmed';
        }
        if (confidence > 0) {
            return `${confidence}%`;
        }
        if (source === 'manual') {
            return T.manual_input || 'Manual input';
        }
        return T.unknown || '? Unknown';
    },

    // ========================================
    // HTML生成
    // ========================================

    generateHTML(birdId, options = {}) {
        if (typeof BirdDB === 'undefined') {
            console.error('BirdDB not available');
            return null;
        }
        
        const bird = BirdDB.getBird(birdId);
        if (!bird) return null;

        const generations = options.generations || 4;
        const ancestors = BirdDB.getAncestors ? BirdDB.getAncestors(birdId, generations) : null;
        const lang = options.lang || (typeof LANG !== 'undefined' ? LANG : 'ja');
        // v7.0: 言語に応じたロケール
        const localeMap = { ja: 'ja-JP', en: 'en-US', de: 'de-DE', fr: 'fr-FR', it: 'it-IT', es: 'es-ES' };
        const issueDate = new Date().toLocaleDateString(localeMap[lang] || 'ja-JP');
        const labels = this.getLabels(lang);
        const loci = this.getLoci();

        // 形質別確度を計算
        const locusConfidences = this.calculateLocusConfidences(bird);

        return `<!DOCTYPE html>
<html lang="${lang}">
<head>
    <meta charset="UTF-8">
    <title>${labels.title} - ${bird.name}</title>
    <style>${this.getStyles()}</style>
</head>
<body>
    <div class="certificate">
        <header class="cert-header">
            <div class="logo">🦜</div>
            <h1>${labels.title}</h1>
            <div class="subtitle">Agapornis Gene-Forge v7.0</div>
        </header>

        <section class="bird-info">
            <h3>${labels.basicInfo}</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">${labels.name}</span>
                    <span class="info-value">${this.escapeHtml(bird.name)}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${labels.code}</span>
                    <span class="info-value">${this.escapeHtml(bird.code || bird.id || '-')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${labels.sex}</span>
                    <span class="info-value">${bird.sex === 'male' ? labels.male : labels.female}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${labels.birthDate}</span>
                    <span class="info-value">${this.escapeHtml(bird.birthDate || '-')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${labels.phenotype}</span>
                    <span class="info-value">${this.escapeHtml(bird.phenotype || this.getColorLabel(bird.observed?.baseColor, lang) || '-')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${labels.lineage}</span>
                    <span class="info-value">${this.escapeHtml(bird.lineage || '-')}</span>
                </div>
            </div>
        </section>

        <section class="genotype-section">
            <h3>${labels.genotypeDetail}</h3>
            <table class="genotype-table">
                <thead>
                    <tr>
                        <th>${labels.locus}</th>
                        <th>${labels.genotype}</th>
                        <th>${labels.confidence}</th>
                    </tr>
                </thead>
                <tbody>
                    ${loci.map(locus => {
                        const lc = locusConfidences[locus] || { academic: '+/+', display: '?' };
                        const confClass = lc.confidence === 100 ? 'confirmed' : 
                                         lc.confidence >= 50 ? 'estimated' : 'unknown';
                        return `<tr class="${confClass}">
                            <td>${this.getLocusLabel(locus, lang)}</td>
                            <td class="mono">${lc.academic}</td>
                            <td class="confidence ${confClass}">${lc.display}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </section>

        <section class="pedigree-section">
            <h3>${labels.pedigreeChart} (${generations}${labels.generationUnit})</h3>
            <div class="pedigree-tree">
                ${this.renderPedigreeTree(ancestors, generations, labels, lang)}
            </div>
        </section>

        <section class="notes-section">
            <h3>${labels.notes}</h3>
            <p>${this.escapeHtml(bird.notes || labels.noNotes)}</p>
        </section>

        <footer class="cert-footer">
            <div class="footer-grid">
                <div>${labels.issueDate}: ${issueDate}</div>
                <div>${labels.generator}: Agapornis Gene-Forge v7.0</div>
            </div>
            <div class="disclaimer">${labels.disclaimer}</div>
        </footer>
    </div>
</body>
</html>`;
    },

    /**
     * 血統図レンダリング
     */
    renderPedigreeTree(tree, maxGen, labels, lang) {
        if (!tree) return `<div class="no-data">${labels.noAncestors}</div>`;

        const renderNode = (node, position) => {
            if (!node) return `<div class="pedigree-node empty">-</div>`;
            const data = node.bird || node;
            const name = data.name || labels.unknown;
            const code = data.code || data.id || '';
            const pheno = data.phenotype || this.getColorLabel(data.observed?.baseColor, lang) || '';
            const sexClass = data.sex || '';
            
            return `<div class="pedigree-node ${sexClass}">
                <div class="node-name">${this.escapeHtml(name)}</div>
                <div class="node-code">${this.escapeHtml(code)}</div>
                <div class="node-pheno">${this.escapeHtml(pheno)}</div>
            </div>`;
        };

        if (maxGen >= 4) {
            return `<div class="pedigree-4gen">
                <div class="gen-col gen-0">
                    <div class="gen-label">${labels.subject}</div>
                    ${renderNode(tree.bird, 'subject')}
                </div>
                <div class="gen-col gen-1">
                    <div class="gen-label">${labels.parents}</div>
                    ${renderNode(tree.sire, 'sire')}
                    ${renderNode(tree.dam, 'dam')}
                </div>
                <div class="gen-col gen-2">
                    <div class="gen-label">${labels.grandparents}</div>
                    ${renderNode(tree.sire?.sire, 'sire_sire')}
                    ${renderNode(tree.sire?.dam, 'sire_dam')}
                    ${renderNode(tree.dam?.sire, 'dam_sire')}
                    ${renderNode(tree.dam?.dam, 'dam_dam')}
                </div>
                <div class="gen-col gen-3">
                    <div class="gen-label">${labels.greatGrandparents}</div>
                    ${renderNode(tree.sire?.sire?.sire, 'sss')}
                    ${renderNode(tree.sire?.sire?.dam, 'ssd')}
                    ${renderNode(tree.sire?.dam?.sire, 'sds')}
                    ${renderNode(tree.sire?.dam?.dam, 'sdd')}
                    ${renderNode(tree.dam?.sire?.sire, 'dss')}
                    ${renderNode(tree.dam?.sire?.dam, 'dsd')}
                    ${renderNode(tree.dam?.dam?.sire, 'dds')}
                    ${renderNode(tree.dam?.dam?.dam, 'ddd')}
                </div>
            </div>`;
        }

        return `<div class="pedigree-3gen">
            <div class="gen-col gen-0">
                <div class="gen-label">${labels.subject}</div>
                ${renderNode(tree.bird, 'subject')}
            </div>
            <div class="gen-col gen-1">
                <div class="gen-label">${labels.parents}</div>
                ${renderNode(tree.sire, 'sire')}
                ${renderNode(tree.dam, 'dam')}
            </div>
            <div class="gen-col gen-2">
                <div class="gen-label">${labels.grandparents}</div>
                ${renderNode(tree.sire?.sire, 'sire_sire')}
                ${renderNode(tree.sire?.dam, 'sire_dam')}
                ${renderNode(tree.dam?.sire, 'dam_sire')}
                ${renderNode(tree.dam?.dam, 'dam_dam')}
            </div>
        </div>`;
    },

    // ========================================
    // ラベル・スタイル
    // ========================================

    getLabels(lang) {
        return lang === 'en' ? {
            title: 'Pedigree Certificate',
            basicInfo: 'Basic Information',
            name: 'Name',
            code: 'ID Code',
            sex: 'Sex',
            male: '♂ Male',
            female: '♀ Female',
            birthDate: 'Birth Date',
            phenotype: 'Phenotype',
            lineage: 'Lineage',
            genotypeDetail: 'Genotype Details',
            locus: 'Locus',
            genotype: 'Genotype',
            confidence: 'Confidence',
            pedigreeChart: 'Pedigree Chart',
            generationUnit: ' gen',
            subject: 'Subject',
            parents: 'Parents',
            grandparents: 'Grandparents',
            greatGrandparents: 'Great-Grandparents',
            notes: 'Notes',
            noNotes: 'No notes',
            noAncestors: 'No ancestor data',
            issueDate: 'Issue Date',
            generator: 'Generated by',
            disclaimer: 'This certificate is generated based on recorded data. Genetic information may include estimates.',
            unknown: 'Unknown'
        } : {
            title: 'コザクラインコ血統証明書',
            basicInfo: '基本情報',
            name: '個体名',
            code: '識別コード',
            sex: '性別',
            male: '♂ オス',
            female: '♀ メス',
            birthDate: '生年月日',
            phenotype: '表現型',
            lineage: '血統',
            genotypeDetail: '遺伝子型詳細',
            locus: '座位',
            genotype: '遺伝子型',
            confidence: '確度',
            pedigreeChart: '血統図',
            generationUnit: '世代',
            subject: '本鳥',
            parents: '両親',
            grandparents: '祖父母',
            greatGrandparents: '曽祖父母',
            notes: '備考',
            noNotes: '備考なし',
            noAncestors: '祖先データなし',
            issueDate: '発行日',
            generator: '発行元',
            disclaimer: 'この証明書は登録データに基づいて生成されています。遺伝情報には推定値が含まれる場合があります。',
            unknown: '不明'
        };
    },

    getStyles() {
        return `
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', sans-serif; background: #f0f4f8; padding: 20px; color: #1a202c; }
.certificate { max-width: 1000px; margin: 0 auto; background: #fff; border: 3px solid #2c5282; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
.cert-header { text-align: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
.cert-header .logo { font-size: 48px; margin-bottom: 8px; }
.cert-header h1 { font-size: 24px; color: #2c5282; margin-bottom: 4px; }
.cert-header .subtitle { font-size: 12px; color: #718096; }
section { margin-bottom: 24px; }
section h3 { font-size: 14px; color: #2c5282; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px; }
.info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.info-item { display: flex; flex-direction: column; }
.info-label { font-size: 11px; color: #718096; margin-bottom: 2px; }
.info-value { font-size: 14px; font-weight: 600; }
.genotype-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.genotype-table th, .genotype-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.genotype-table th { background: #f7fafc; font-weight: 600; color: #4a5568; }
.genotype-table .mono { font-family: 'Monaco', 'Consolas', monospace; font-size: 12px; }
.genotype-table tr.confirmed { background: #f0fff4; }
.genotype-table tr.estimated { background: #fffff0; }
.genotype-table tr.unknown { background: #fff5f5; }
.confidence.confirmed { color: #276749; font-weight: 600; }
.confidence.estimated { color: #975a16; }
.confidence.unknown { color: #c53030; }
.pedigree-4gen, .pedigree-3gen { display: flex; justify-content: space-between; gap: 8px; overflow-x: auto; }
.gen-col { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 100px; }
.gen-label { font-size: 10px; color: #718096; text-align: center; margin-bottom: 4px; font-weight: 600; }
.pedigree-node { padding: 6px 4px; border: 2px solid #cbd5e0; border-radius: 4px; text-align: center; background: #fff; min-height: 50px; display: flex; flex-direction: column; justify-content: center; }
.pedigree-node.male { border-color: #3182ce; background: #ebf8ff; }
.pedigree-node.female { border-color: #d53f8c; background: #fff5f7; }
.pedigree-node.empty { background: #f7fafc; color: #a0aec0; border-style: dashed; }
.node-name { font-weight: 600; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.node-code { font-size: 9px; color: #4a5568; font-family: monospace; }
.node-pheno { font-size: 9px; color: #718096; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notes-section p { font-size: 13px; color: #4a5568; line-height: 1.6; }
.cert-footer { margin-top: 24px; padding-top: 16px; border-top: 2px solid #e2e8f0; text-align: center; }
.footer-grid { display: flex; justify-content: space-between; font-size: 12px; color: #718096; margin-bottom: 8px; }
.disclaimer { font-size: 10px; color: #a0aec0; font-style: italic; }
.no-data { text-align: center; color: #a0aec0; padding: 20px; }
@media print { body { background: #fff; padding: 0; } .certificate { border: 1px solid #000; box-shadow: none; } }
`;
    },

    escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },

    // ========================================
    // 出力メソッド
    // ========================================

    openInNewWindow(birdId, options = {}) {
        const html = this.generateHTML(birdId, options);
        if (!html) return;
        const win = window.open('', '_blank');
        win.document.write(html);
        win.document.close();
    },

    print(birdId, options = {}) {
        const html = this.generateHTML(birdId, options);
        if (!html) return;
        const win = window.open('', '_blank');
        win.document.write(html);
        win.document.close();
        win.onload = () => win.print();
    },

    downloadHTML(birdId, options = {}) {
        const html = this.generateHTML(birdId, options);
        if (!html) return;
        // v7.0: BirdDB存在チェック追加
        if (typeof BirdDB === 'undefined' || !BirdDB.getBird) {
            console.error('BirdDB not available');
            return;
        }
        const bird = BirdDB.getBird(birdId);
        if (!bird) return;
        const blob = new Blob([html], { type: 'text/html' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `pedigree_${bird.code || bird.id}.html`;
        a.click();
    }
};

// グローバル関数（後方互換）
function renderPedigree(birdId, generations = 3) {
    PedigreeGenerator.openInNewWindow(birdId, { generations });
}

function printPedigree() {
    const birdId = document.getElementById('pedigreePreview')?.dataset?.birdId;
    if (birdId) {
        const gen = document.querySelector('input[name="pedigreeGen"]:checked')?.value || 3;
        PedigreeGenerator.print(birdId, { generations: parseInt(gen) });
    }
}

function downloadPedigreeHTML() {
    const birdId = document.getElementById('pedigreePreview')?.dataset?.birdId;
    if (birdId) {
        const gen = document.querySelector('input[name="pedigreeGen"]:checked')?.value || 3;
        PedigreeGenerator.downloadHTML(birdId, { generations: parseInt(gen) });
    }
}
