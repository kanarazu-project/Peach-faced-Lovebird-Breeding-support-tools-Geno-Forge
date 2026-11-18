/**
 * Agapornis Gene-Forge — genodata.js
 * 遺伝の事実の引き出し（GENOTYPE_DATA の表引きだけを行う）
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
