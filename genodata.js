/**
 * Agapornis Gene-Forge — genodata.js
 * 遺伝の事実の引き出し（GENOTYPE_DATA・CROSS_TABLE・LINKED_CROSS_TABLE の表引きだけを行う）
 *
 * 整合性原理（SSOT）:
 * - 遺伝の事実と判定規則は genetics.php にだけある（AgapornisLoci::genotypeData()）
 * - index.php がそれを GENOTYPE_DATA として注入し、このファイルは表を引くだけ
 * - 遺伝子型の文字列・規則をこのファイルに書かない（scripts/ssot-literal-check が検査する）
 */
const GenoData = {
    data() {
        return (typeof GENOTYPE_DATA !== 'undefined') ? GENOTYPE_DATA : null;
    },

    locus(locusKey) {
        const d = this.data();
        return d ? (d.loci[locusKey] || null) : null;
    },

    /** 遺伝子型の情報 {canonical, alleles, mutant, dose, wild, expressed, hemi}。未知の表記は null */
    info(locusKey, genotype) {
        const l = this.locus(locusKey);
        return (l && genotype) ? (l.genotypes[genotype] || null) : null;
    },

    /** 正規の表記（並び順違い・旧表記をそろえる）。未知の表記はそのまま返す */
    canonical(locusKey, genotype) {
        const i = this.info(locusKey, genotype);
        return i ? i.canonical : genotype;
    },

    /** 野生型か。未入力は野生型、未知の表記は野生型ではないとみなす */
    isWild(locusKey, genotype) {
        if (!genotype) return true;
        const i = this.info(locusKey, genotype);
        return i ? i.wild : false;
    },

    /** 表現型に現れるか */
    isExpressed(locusKey, genotype) {
        const i = this.info(locusKey, genotype);
        return i ? i.expressed : false;
    },

    /** 変異アレルの数 */
    dose(locusKey, genotype) {
        const i = this.info(locusKey, genotype);
        return i ? i.dose : 0;
    },

    /** 変異アレルの一覧 */
    mutantAlleles(locusKey, genotype) {
        const i = this.info(locusKey, genotype);
        return i ? i.mutant : [];
    },

    /** アレルの一覧（正規の並び） */
    alleles(locusKey, genotype) {
        const i = this.info(locusKey, genotype);
        return i ? i.alleles : null;
    },

    /** 野生型の遺伝子型 */
    wildGenotype(locusKey, sex = 'male') {
        const l = this.locus(locusKey);
        return l ? (sex === 'female' ? l.wild.female : l.wild.male) : '';
    },

    /** アレルの組から正規の遺伝子型を引く（伴性メスは1アレル） */
    fromAlleles(locusKey, alleles) {
        const l = this.locus(locusKey);
        if (!l || !alleles || !alleles.length) return null;
        const key = alleles.slice().sort().join('|') + (alleles.length === 1 ? '|W' : '');
        return l.byAlleles[key] || null;
    },

    /**
     * 1座位の交配の子の分布 {male: {遺伝子型: 確率}, female: {...}}（性別ごとに合計1）。
     * 表は PHP の GeneticsCalculator が計算し、index.php が CROSS_TABLE として注入する。未入力は野生型、未知の表記は null。
     */
    cross(locusKey, male, female) {
        if (typeof CROSS_TABLE === 'undefined') return null;
        const m = this.canonical(locusKey, male || this.wildGenotype(locusKey, 'male'));
        const f = this.canonical(locusKey, female || this.wildGenotype(locusKey, 'female'));
        const row = (CROSS_TABLE[locusKey] || {})[m];
        return row ? (row[f] || null) : null;
    },

    /** 子の遺伝子型が vals のどれかになる確率。sex を省くと雌雄の平均。未知の表記は 0 */
    crossProbability(locusKey, male, female, vals, sex) {
        const c = this.cross(locusKey, male, female);
        if (!c) return 0;
        const want = new Set((vals || []).map(v => this.canonical(locusKey, v)));
        const p = s => Object.entries(c[s] || {}).reduce((a, [g, q]) => a + (want.has(g) ? q : 0), 0);
        return sex ? p(sex) : (p('male') + p('female')) / 2;
    },

    /** 連鎖する組の表（LINKED_CROSS_TABLE。PHP の GeneticsCalculator::linkedCrossTable が計算）。無ければ null */
    linkedGroup(group) {
        return (typeof LINKED_CROSS_TABLE !== 'undefined') ? (LINKED_CROSS_TABLE[group] || null) : null;
    },

    /** ハプロタイプ {座位: アレル} の鍵（表の loci の順に sep でつなぐ）。欠けたアレルは野生型 */
    haplotypeKey(group, haplotype) {
        const g = this.linkedGroup(group);
        if (!g) return null;
        return g.loci.map(l => (haplotype && haplotype[l]) || (this.alleles(l, this.wildGenotype(l, 'male')) || [])[0]).join(g.sep);
    },

    /**
     * 親のハプロタイプから、子の連鎖する座位の遺伝子型が want を満たす確率（性別 sex の子のうち）。
     * maleHaps はオスの2本、femaleHaps はメスの1本（伴性）か2本。want は {座位: [遺伝子型...]}（載っていない座位は問わない）。
     * 表に無い組は null。
     */
    linkedProbability(group, maleHaps, femaleHaps, want, sex) {
        const g = this.linkedGroup(group);
        if (!g) return null;
        const mk = maleHaps.map(h => this.haplotypeKey(group, h)).sort((a, b) => (a < b ? -1 : a > b ? 1 : 0)).join(g.pairSep);
        const fk = femaleHaps.map(h => this.haplotypeKey(group, h)).sort((a, b) => (a < b ? -1 : a > b ? 1 : 0)).join(g.pairSep);
        const dist = ((g.cross[mk] || {})[fk] || {})[sex];
        if (!dist) return null;
        const wantSets = g.loci.map(l => want[l] ? new Set(want[l].map(v => this.canonical(l, v))) : null);
        let p = 0;
        for (const [tuple, q] of Object.entries(dist)) {
            const parts = tuple.split(g.sep);
            if (wantSets.every((w, i) => !w || w.has(parts[i]))) p += q;
        }
        return p;
    },

    /** 連鎖する組の座位（LINKAGE_GROUPS の順）。group を省くと {組: [座位...]} */
    linkageLoci(group) {
        const d = this.data();
        const groups = d ? (d.linkageGroups || {}) : {};
        return group ? (groups[group] || null) : groups;
    },

    /** 座位が属する連鎖する組。無ければ null */
    linkageGroupOf(locusKey) {
        const groups = this.linkageLoci();
        return Object.keys(groups).find(g => groups[g].includes(locusKey)) || null;
    },

    /**
     * 相の並べ方。pairs は組の座位の順に [アレル, アレル]。ヘテロの座位のアレルを2本の染色体に振り分ける並べ方を、
     * 最初のヘテロの座位の向きを固定してすべて返す（ヘテロが1座位以下なら1通り）。各要素は {haps: [h1, h2]}。
     */
    phaseArrangements(group, pairs) {
        const loci = this.linkageLoci(group) || [];
        const het = pairs.map((p, i) => (p[0] !== p[1] ? i : -1)).filter(i => i >= 0);
        const n = Math.max(0, het.length - 1);
        const out = [];
        for (let mask = 0; mask < (1 << n); mask++) {
            const h1 = {}, h2 = {};
            loci.forEach((l, i) => {
                const k = het.indexOf(i);
                const flip = k > 0 && ((mask >> (k - 1)) & 1);
                h1[l] = flip ? pairs[i][1] : pairs[i][0];
                h2[l] = flip ? pairs[i][0] : pairs[i][1];
            });
            out.push({ haps: [h1, h2] });
        }
        return out;
    },

    /** 変異アレルを1つ持つ保因の遺伝子型（targetGenotype の変異アレルと野生型アレルの組。伴性メスはヘミ接合） */
    carrier(locusKey, targetGenotype, sex = 'male') {
        const mutant = this.mutantAlleles(locusKey, targetGenotype)[0];
        const wild = (this.alleles(locusKey, this.wildGenotype(locusKey, 'male')) || [])[0];
        if (!mutant || !wild) return null;
        const l = this.locus(locusKey);
        if (l && l.sexLinked && sex === 'female') return this.fromAlleles(locusKey, [mutant]);
        return this.fromAlleles(locusKey, [wild, mutant]);
    },

    /** 色の定義の遺伝子型（伴性メスはヘミ接合） */
    colorGenotype(colorKey, sex = 'male') {
        const d = this.data();
        const row = d ? d.byColor[colorKey] : null;
        return row ? (sex === 'female' ? row.female : row.male) : null;
    }
};

if (typeof window !== 'undefined') {
    window.GenoData = GenoData;
}
