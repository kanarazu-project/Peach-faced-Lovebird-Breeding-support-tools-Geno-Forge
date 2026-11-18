/**
 * Agapornis Gene-Forge v7.3
 * 目標逆算計画エンジン (Target Breeding Planner)
 *
 * v7.3変更点:
 * - Z_linkedハプロタイプ形式から伴性遺伝子を正しく読み取るよう修正
 * - getSLRGenotypeFromZLinked()ヘルパー追加
 * - calculateGeneScore, calculateTargetProbability, analyzeGeneGap対応
 * - findBestPairingsForGene Z_linked対応
 *
 * v7.0変更点:
 * - 連鎖遺伝対応: 相（Phase: Cis/Trans）を考慮したペアリング評価
 * - Cis配置個体優先推奨ロジック追加
 * - 組み換え率を考慮した確率計算
 * - LINKAGE_GROUPS, RECOMBINATION_RATES参照
 *
 * v6.7.5変更点:
 * - SSOT化: TARGET_REQUIREMENTSからnameフィールド削除
 * - 表示時はCOLOR_LABELS参照に統一
 * - genetics.phpのAgapornisLoci::labels()がSSOT
 *
 * v6.7.4変更点:
 * - 近親交配フィルタリング追加（12.5%未満のみ出力）
 * - BreedingValidator連携
 * - ルート全世代の近交係数チェック
 */
const BreedingPlanner = {

    // v7.0: 翻訳対応ヘルパー
    _t(key, fallback) {
        const T = window.T || {};
        return T[key] || fallback;
    },
    _tp(key, params, fallback) {
        let text = this._t(key, fallback);
        if (params) {
            Object.keys(params).forEach(k => {
                text = text.replace(new RegExp(`\\{${k}\\}`, 'g'), params[k]);
            });
        }
        return text;
    },

    // v6.7.4: 近交係数閾値
    INBREEDING_THRESHOLD: 0.125,  // 12.5%
    
    // SSOT: 繁殖計画の目標（必要な遺伝子型・世代数・難易度・近交制限）は genetics.php が色の定義から作る
    // （AgapornisLoci::genotypeData()['targets']。index.php が GENOTYPE_DATA として注入）
    get TARGET_REQUIREMENTS() {
        return (typeof GENOTYPE_DATA !== 'undefined') ? GENOTYPE_DATA.targets : {};
    },

    /** 繁殖計画の伴性キー（ino / cin / op）→ 座位（GENOTYPE_DATA.plannerSlrKeys） */
    _slrLocus(slrKey) {
        const keys = (GenoData.data() || {}).plannerSlrKeys || {};
        return keys[slrKey] || slrKey;
    },

    /**
     * v7.3: 色名取得ヘルパー（keyToLabel対応）
     * 動的変換で任意のカラーキーをローカライズ
     * @param {string} colorKey - 色キー
     * @returns {string} 表示用色名
     */
    getColorName(colorKey) {
        // v7.3: keyToLabel関数でローカライズ（動的変換対応）
        if (typeof keyToLabel === 'function') {
            return keyToLabel(colorKey);
        }
        // フォールバック
        if (typeof COLOR_LABELS !== 'undefined' && COLOR_LABELS[colorKey]) {
            return COLOR_LABELS[colorKey];
        }
        return colorKey;
    },

    /**
     * v7.0: COLOR_MASTERから動的に要件を生成
     * TARGET_REQUIREMENTSにない色でもCOLOR_MASTERから計算可能
     * @param {string} colorKey - 色キー
     * @returns {object|null} 要件オブジェクト
     */
    generateRequirementsFromMaster(colorKey) {
        return this.TARGET_REQUIREMENTS[colorKey] || null;
    },

    // v7.0: 翻訳対応plan関数
    plan(targetKey) {
        // TARGET_REQUIREMENTSを優先、なければCOLOR_MASTERから動的生成
        let target = this.TARGET_REQUIREMENTS[targetKey];
        if (!target) {
            target = this.generateRequirementsFromMaster(targetKey);
        }
        if (!target) return { error: this._t('bp_unsupported_target', 'Unsupported target trait') };
        const birds = typeof BirdDB !== 'undefined' ? BirdDB.getAllBirds() : [];
        if (birds.length === 0) return { error: this._t('bp_no_birds', 'No birds registered'), suggestion: this._t('bp_register_hint', 'Register birds in the Bird Management tab first') };
        const males = birds.filter(b => b.sex === 'male'), females = birds.filter(b => b.sex === 'female');
        if (males.length === 0 || females.length === 0) return { error: this._t('bp_need_both_sex', 'Both males and females are required'), suggestion: this._tp('bp_current_count', { m: males.length, f: females.length }, `Current: ${males.length} males, ${females.length} females`) };

        let pairings = [];
        males.forEach(m => females.forEach(f => pairings.push(this.evaluatePairing(m, f, target, targetKey))));

        // v6.7.4: 近親交配フィルタリング
        pairings = this.filterByInbreeding(pairings);

        // v7.0: 確率0%のペアを除外（貢献度がないペアは推奨しない）
        const viablePairings = pairings.filter(p => p.probability > 0);

        pairings.sort((a, b) => b.score - a.score);
        viablePairings.sort((a, b) => b.score - a.score);

        // v6.7.4: フィルタリング後に候補がない場合
        if (pairings.length === 0) {
            return {
                error: this._t('bp_no_ethical_pairs', 'No pairs meet ethical standards'),
                suggestion: this._t('bp_introduce_new_blood', 'No pairs with inbreeding coefficient below 12.5%. Introduce unrelated bloodlines.'),
                filteredOut: true
            };
        }

        // v7.0: 確率>0のペアがない場合
        if (viablePairings.length === 0) {
            return {
                error: this._t('bp_no_viable_pairs', 'No pairs can produce target trait'),
                suggestion: this._t('bp_need_carriers', 'Register birds that carry the required genes for this target.'),
                noViable: true
            };
        }
        
        // v6.7.5: targetNameをCOLOR_LABELSから取得
        const targetName = this.getColorName(targetKey);
        
        return {
            target,
            targetKey,
            targetName,  // v6.7.5: SSOT対応
            topPairings: viablePairings.slice(0, 5),  // v7.0: 確率>0のみ
            allPairings: pairings,
            roadmap: this.generateRoadmap(viablePairings[0], target, targetKey, []),
            totalBirds: birds.length,
            maleCount: males.length,
            femaleCount: females.length
        };
    },
    
    /**
     * v6.7.4: 近親交配フィルタリング
     * 近交係数12.5%以上のペアを除外
     * @param {Array} pairings - ペアリング候補
     * @returns {Array} フィルタリング済みペアリング
     */
    filterByInbreeding(pairings) {
        return pairings.filter(pairing => {
            // 近交係数が閾値未満のみ許可
            if (pairing.inbreedingCoef >= this.INBREEDING_THRESHOLD) {
                return false;
            }
            
            // BreedingValidator が利用可能な場合は追加チェック
            if (typeof BreedingValidator !== 'undefined') {
                const validation = BreedingValidator.validate(pairing.male, pairing.female, 'plan');
                if (!validation.allowed) {
                    return false;
                }
            }
            
            return true;
        });
    },
    
    /**
     * v6.7.4: ルート全体の近親交配チェック
     * 複数世代のルートで1回でも12.5%以上があればNG
     * @param {Array} route - 交配ルート（breedings配列を含む）
     * @returns {boolean} 許可されるか
     */
    validateRoute(route) {
        if (!route.breedings || !Array.isArray(route.breedings)) {
            return true;
        }
        
        for (const breeding of route.breedings) {
            let ic;
            
            // BreedingValidator が利用可能な場合
            if (typeof BreedingValidator !== 'undefined') {
                ic = BreedingValidator.calcInbreedingCoefficient(breeding.sire, breeding.dam);
            } else if (typeof BirdDB !== 'undefined') {
                const result = BirdDB.calculateInbreedingCoefficient(breeding.sire.id, breeding.dam.id);
                ic = result.coefficient;
            } else {
                ic = 0;
            }
            
            if (ic >= this.INBREEDING_THRESHOLD) {
                return false;
            }
        }
        
        return true;
    },
    
    /**
     * v6.7.4: 複数ルートのフィルタリング
     * @param {Array} routes - ルート候補
     * @returns {Array} フィルタリング済みルート
     */
    filterRoutes(routes) {
        return routes.filter(route => this.validateRoute(route));
    },
    
    evaluatePairing(male, female, target, targetKey) {
        let score = this.calculateGeneScore(male, target) + this.calculateGeneScore(female, target);
        // 確率は連鎖の相まで含めて計算する（相が不明なら相の並べ方の平均。幅は probabilityRange）
        const detail = this.calculateTargetProbabilityDetail(male, female, target);
        const prob = detail.mean;
        score += prob * 100;

        // 近交係数計算
        let inbreedingCoef = 0;
        let warningLevel = { level: 'safe' };
        
        // BreedingValidator優先、なければBirdDB
        if (typeof BreedingValidator !== 'undefined') {
            inbreedingCoef = BreedingValidator.calcInbreedingCoefficient(male, female);
        } else if (typeof BirdDB !== 'undefined') {
            const result = BirdDB.calculateInbreedingCoefficient(male.id, female.id);
            inbreedingCoef = result.coefficient;
            warningLevel = result.warningLevel;
        }
        
        let canBreed = true, healthRisk = 'safe', warnings = [];
        
        // v6.7.4: BreedingValidator による検証
        if (typeof BreedingValidator !== 'undefined') {
            const validation = BreedingValidator.validate(male, female, 'plan');
            if (!validation.allowed) {
                canBreed = false;
                warnings.push('🚫 ' + validation.reason);
                score = -1000;
            } else if (validation.warning) {
                warnings.push(validation.warning);
            }
        }
        
        // HealthGuardian による追加チェック
        if (canBreed && typeof HealthGuardian !== 'undefined') {
            const health = HealthGuardian.evaluateHealth(male, female, inbreedingCoef);
            if (!health.canBreed) {
                canBreed = false;
                score = -1000;
            }
            healthRisk = health.riskLevel;
            health.blocks.forEach(b => warnings.push('🚫 ' + b.message));
            health.warnings.forEach(w => warnings.push('⚠️ ' + w.message));
        }
        
        // v7.0: 翻訳対応推奨メッセージ
        let recommendation;
        if (!canBreed) {
            recommendation = '🚫 ' + this._t('bp_breeding_prohibited', 'Breeding prohibited');
        } else if (inbreedingCoef >= this.INBREEDING_THRESHOLD) {
            recommendation = '⚠️ ' + this._t('bp_ethics_warning', 'Prohibited in thoroughbred breeding');
        } else if (prob >= 0.5) {
            recommendation = '🌟 ' + this._t('bp_optimal_pair', 'Optimal pair');
        } else if (prob > 0) {
            recommendation = '✓ ' + this._t('bp_possible', 'Possible');
        } else {
            recommendation = '✗ ' + this._t('bp_low_contribution', 'Low contribution to target');
        }

        // v7.0: 連鎖遺伝に関する推奨
        const linkageRec = this.generateLinkageRecommendation(male, female, targetKey);
        if (linkageRec) {
            warnings.push(linkageRec);
        }

        return {
            male,
            female,
            score,
            probability: prob,
            estimatedGenerations: target.minGen + (prob < 0.5 ? 1 : 0),
            inbreedingCoef,
            canBreed,
            healthRisk,
            warnings,
            recommendation,
            targetKey,  // v6.7.5: targetKeyを保持
            probabilityRange: detail.phaseUnknown ? { min: detail.min, max: detail.max } : null,
            maleGenes: { hasRequired: [], carrierOf: [], missing: [] },
            femaleGenes: { hasRequired: [], carrierOf: [], missing: [] }
        };
    },
    
    /**
     * v7.3: Z_linked形式から伴性遺伝子型を読み取る
     * TARGET_REQUIREMENTS.slr のキー (ino, cin, op) → Z_linked のキー (ino, cinnamon, opaline)
     * @param {Object} geno - bird.genotype
     * @param {string} slrKey - slr座位キー (ino, cin, op)
     * @param {string} sex - 'male' or 'female'
     * @returns {string} 遺伝子型 (e.g., 'inoino', '+ino', '+W', '++')
     */
    getSLRGenotypeFromZLinked(geno, slrKey, sex) {
        const zKey = this._slrLocus(slrKey);
        const wildGeno = GenoData.wildGenotype(zKey, sex);

        // v7形式の Z_linked がある場合: ハプロタイプのアレルから正規の遺伝子型を引く（GENOTYPE_DATA）
        if (geno.Z_linked && geno.Z_linked.Z1) {
            const z1 = geno.Z_linked.Z1 || {};
            const z2 = geno.Z_linked.Z2 || null;
            const wildAllele = (GenoData.alleles(zKey, GenoData.wildGenotype(zKey, 'female')) || [])[0];
            const alleles = sex === 'female'
                ? [z1[zKey] || wildAllele]
                : [z1[zKey] || wildAllele, (z2 ? z2[zKey] : null) || wildAllele];
            return GenoData.fromAlleles(zKey, alleles) || wildGeno;
        }

        // 旧形式（座位キーか伴性キーで直接持つ）
        const oldFormatVal = geno[slrKey] || geno[zKey];
        if (oldFormatVal) return GenoData.canonical(zKey, oldFormatVal);
        return wildGeno;
    },

    /**
     * v7.3.19: スプリットのアレルが必要なアレルと互換性があるかチェック
     * INO座位には ino と pld の2つの異なるアレルがあり、混同してはいけない
     * @param {string} val - 鳥の遺伝子型 (e.g., '+ino', 'pldpld', '+pld')
     * @param {Array} requiredVals - 必要な遺伝子型の配列 (e.g., ['pldpld', 'pldW'])
     * @returns {boolean} スプリットが目標アレルと互換性があるか
     */
    isCompatibleSplit(val, requiredVals, slrKey) {
        // SSOT: アレルの中身は GENOTYPE_DATA で引く（ino と pld を区別する）
        const locus = this._slrLocus(slrKey);
        if (GenoData.isWild(locus, val)) return false;
        const required = new Set(requiredVals.flatMap(v => GenoData.mutantAlleles(locus, v)));
        return GenoData.mutantAlleles(locus, val).some(a => required.has(a));
    },

    calculateGeneScore(bird, target) {
        let score = 0;
        const geno = bird.genotype || {};

        // 常染色体遺伝子
        for (const [locus, vals] of Object.entries(target.required)) {
            if (vals.includes(GenoData.canonical(locus, geno[locus]))) score += 100;
            else if (geno[locus] && !GenoData.isWild(locus, geno[locus])) score += 50;
        }

        // v7.3.19: 伴性遺伝子 - Z_linked形式対応 + アレル互換性チェック
        for (const [locus, vals] of Object.entries(target.slr)) {
            const v = this.getSLRGenotypeFromZLinked(geno, locus, bird.sex);
            if (vals.includes(v)) score += 100;
            else if (this.isCompatibleSplit(v, vals, locus)) score += 50;
            // v7.3.19: 互換性のないアレル (例: pld目標にino保有) はスコア0
        }
        return score;
    },

    /** 子が目標の色になる確率（相が不明な親は、相の並べ方の平均） */
    calculateTargetProbability(male, female, target) {
        return this.calculateTargetProbabilityDetail(male, female, target).mean;
    },

    /**
     * 子が目標の色になる確率の詳細 {mean, min, max, phaseUnknown}。
     * 独立した座位は CROSS_TABLE、連鎖する組（Z 上の cin–ino–op、dark–parblue）は LINKED_CROSS_TABLE から引く。
     * どちらも PHP の GeneticsCalculator が計算した表で、JS は雌雄ごとに組どうしを掛け、雌雄を平均するだけ。
     * 親の相が記録に無い組は、相の並べ方を等しい重みで並べ、平均を mean、最小・最大を min・max とする。
     */
    calculateTargetProbabilityDetail(male, female, target) {
        const mGeno = male.genotype || {}, fGeno = female.genotype || {};
        const want = { ...target.required };
        for (const [slrKey, vals] of Object.entries(target.slr)) want[this._slrLocus(slrKey)] = vals;

        const groups = (typeof LINKED_CROSS_TABLE !== 'undefined') ? Object.keys(LINKED_CROSS_TABLE) : [];
        const linked = new Set(groups.flatMap(g => GenoData.linkedGroup(g).loci));
        const usedGroups = groups.filter(g => GenoData.linkedGroup(g).loci.some(l => want[l]));

        // 独立した座位
        let combos = [{ w: 1, male: 1, female: 1 }];
        for (const locus of Object.keys(want).filter(l => !linked.has(l))) {
            const mv = this._locusGenotype(mGeno, locus, 'male'), fv = this._locusGenotype(fGeno, locus, 'female');
            combos[0].male *= GenoData.crossProbability(locus, mv, fv, want[locus], 'male');
            combos[0].female *= GenoData.crossProbability(locus, mv, fv, want[locus], 'female');
        }
        // 連鎖する組（相の並べ方ごとに分ける）
        for (const group of usedGroups) {
            const gWant = {};
            for (const l of GenoData.linkedGroup(group).loci) if (want[l]) gWant[l] = want[l];
            const mOpts = this._haplotypeOptions(mGeno, group, 'male');
            const fOpts = this._haplotypeOptions(fGeno, group, 'female');
            const next = [];
            for (const c of combos) for (const mo of mOpts) for (const fo of fOpts) {
                const q = sex => GenoData.linkedProbability(group, mo.haps, fo.haps, gWant, sex) || 0;
                next.push({ w: c.w * mo.w * fo.w, male: c.male * q('male'), female: c.female * q('female') });
            }
            combos = next;
        }
        const values = combos.map(c => ({ w: c.w, v: (c.male + c.female) / 2 }));
        return {
            mean: values.reduce((a, x) => a + x.w * x.v, 0),
            min: Math.min(...values.map(x => x.v)),
            max: Math.max(...values.map(x => x.v)),
            phaseUnknown: values.length > 1
        };
    },

    /** 個体の座位の遺伝子型（記録の読み方は BirdDB.locusGenotype） */
    _locusGenotype(geno, locus, sex) {
        return BirdDB.locusGenotype(geno, locus, sex);
    },

    /**
     * 親の連鎖する組のハプロタイプの候補 [{haps, w}]。相が確定していればその1通り、
     * 未確定なら GenoData.phaseArrangements の並べ方を等しい重みで返す。
     */
    _haplotypeOptions(geno, group, sex) {
        const loci = GenoData.linkageLoci(group) || [];
        const { pairs, known } = BirdDB.linkedPairs(geno, group, sex);
        if (pairs[0] && pairs[0].length === 1) return [{ haps: [Object.fromEntries(loci.map((l, i) => [l, pairs[i][0]]))], w: 1 }];
        if (known) return [{ haps: [Object.fromEntries(loci.map((l, i) => [l, pairs[i][0]])), Object.fromEntries(loci.map((l, i) => [l, pairs[i][1]]))], w: 1 }];
        const arr = GenoData.phaseArrangements(group, pairs);
        return arr.map(a => ({ haps: a.haps, w: 1 / arr.length }));
    },

    // v7.0: 翻訳対応
    generateRoadmap(topPairing, target, targetKey, missingGenes) {
        if (!topPairing) return [{ generation: 0, action: this._t('bp_no_breedable_pair', 'No breedable pairs available'), goal: this._t('bp_introduce_healthy', 'Introduce birds with low health risk') }];
        // v6.7.5: COLOR_LABELSから色名取得
        const targetName = this.getColorName(targetKey);
        const goalText = this._tp('bp_goal_produce', { name: targetName }, 'Produce ' + targetName);
        return [{ generation: 1, action: `${topPairing.male.name} × ${topPairing.female.name}`, goal: goalText, probability: `${(topPairing.probability * 100).toFixed(1)}%` }];
    },

    // ========================================
    // v7.0: 連鎖遺伝（相/Phase）評価
    // ========================================

    /**
     * v7.0: 個体の連鎖座位の相を評価
     * @param {Object} bird - 個体データ
     * @param {string} targetKey - 目標色キー
     * @returns {Object} 連鎖情報 { phase, advantage, loci }
     */
    evaluateLinkagePhase(bird, targetKey) {
        // LINKAGE_GROUPS/RECOMBINATION_RATES が未定義なら空を返す
        if (typeof LINKAGE_GROUPS === 'undefined' || typeof RECOMBINATION_RATES === 'undefined') {
            return { available: false };
        }

        const target = this.TARGET_REQUIREMENTS[targetKey];
        if (!target) return { available: false };

        const geno = bird.genotype || {};
        const result = {
            available: true,
            Z_linked: this.evaluateZLinkedPhase(bird, geno, target),
            autosomal_1: this.evaluateAutosomal1Phase(bird, geno, target)
        };

        return result;
    },

    /**
     * v7.0: Z染色体連鎖座位の相評価
     */
    evaluateZLinkedPhase(bird, geno, target) {
        const sex = bird.sex;

        // メスはヘミ接合（相の概念なし）
        if (sex === 'female') {
            return { phase: 'hemizygous', note: this._t('bp_female_hemizygous', 'メスは相の概念なし') };
        }

        // 必要な伴性座位を特定
        const slr = target.slr || {};
        const neededLoci = [];
        if (slr.ino) neededLoci.push('ino');
        if (slr.cin) neededLoci.push('cinnamon');
        if (slr.op) neededLoci.push('opaline');

        if (neededLoci.length < 2) {
            return { phase: 'not_applicable', note: this._t('bp_linkage_not_needed', '連鎖考慮不要') };
        }

        // v7形式のZ_linkedハプロタイプがあれば使用
        if (geno.Z_linked && geno.Z_linked.Z1) {
            return this.detectPhaseFromV7Format(geno.Z_linked, neededLoci);
        }

        // 旧形式から推論
        return this.inferPhaseFromOldFormat(geno, neededLoci, sex);
    },

    /**
     * v7.0: v7形式からCis/Trans検出
     */
    detectPhaseFromV7Format(zLinked, neededLoci) {
        const z1 = zLinked.Z1 || {};
        const z2 = zLinked.Z2 || {};

        // Z1とZ2の両方に変異があるか確認
        let z1Mutations = 0, z2Mutations = 0;
        neededLoci.forEach(loc => {
            if (z1[loc] && z1[loc] !== '+') z1Mutations++;
            if (z2[loc] && z2[loc] !== '+') z2Mutations++;
        });

        if (z1Mutations >= 2 && z2Mutations === 0) {
            return {
                phase: 'cis',
                Z1: z1,
                Z2: z2,
                advantage: true,
                note: this._t('bp_phase_cis', 'Cis配置（効率的）')
            };
        }

        if (z1Mutations === 1 && z2Mutations === 1) {
            return {
                phase: 'trans',
                Z1: z1,
                Z2: z2,
                advantage: false,
                note: this._t('bp_phase_trans', 'Trans配置（非効率）')
            };
        }

        return { phase: 'unknown', note: this._t('bp_phase_unknown', '相不明') };
    },

    /**
     * v7.0: 旧形式から相を推論
     */
    inferPhaseFromOldFormat(geno, neededLoci, sex) {
        // 複数伴性形質が同時発現していればCis
        // (表現型からの推論は限定的)
        let expressedCount = 0;
        neededLoci.forEach(loc => {
            if (GenoData.isExpressed(loc, geno[loc])) {
                expressedCount++;
            }
        });

        if (expressedCount >= 2) {
            return {
                phase: 'cis_inferred',
                advantage: true,
                note: this._t('bp_phase_cis_inferred', '複数発現 → Cis推定')
            };
        }

        return { phase: 'unknown', note: this._t('bp_phase_no_info', '相情報なし') };
    },

    /**
     * v7.0: 常染色体連鎖座位の相評価
     */
    evaluateAutosomal1Phase(bird, geno, target) {
        const req = target.required || {};

        // dark + parblue の両方が必要か
        const needsDark = req.dark && req.dark.some(g => !GenoData.isWild('dark', g));
        const needsParblue = req.parblue && req.parblue.some(g => !GenoData.isWild('parblue', g));

        if (!needsDark || !needsParblue) {
            return { phase: 'not_applicable', note: this._t('bp_linkage_not_needed', '連鎖考慮不要') };
        }

        // v7形式確認
        if (geno.autosomal_1 && geno.autosomal_1.chr1) {
            const chr1 = geno.autosomal_1.chr1;
            const chr2 = geno.autosomal_1.chr2;

            const chr1HasBoth = chr1.dark === 'D' && chr1.parblue !== '+';
            const chr2Wild = chr2.dark === 'd' && chr2.parblue === '+';

            if (chr1HasBoth && chr2Wild) {
                return {
                    phase: 'cis',
                    advantage: true,
                    note: this._t('bp_autosomal_cis', 'Dark+Parblue Cis配置')
                };
            }
        }

        return { phase: 'unknown', note: this._t('bp_phase_no_info', '相情報なし') };
    },

    /**
     * v7.0: 連鎖に関する推奨メッセージ生成
     */
    generateLinkageRecommendation(male, female, targetKey) {
        const mPhase = this.evaluateLinkagePhase(male, targetKey);

        if (!mPhase.available || !mPhase.Z_linked) {
            return null;
        }

        if (mPhase.Z_linked.phase === 'cis' || mPhase.Z_linked.phase === 'cis_inferred') {
            return '✓ ' + this._t('bp_male_cis_efficient', 'オスがCis配置 → 効率的');
        }

        if (mPhase.Z_linked.phase === 'trans') {
            return '⚠ ' + this._t('bp_male_trans_inefficient', 'オスがTrans配置 → 非効率（Cis個体推奨）');
        }

        if (mPhase.Z_linked.phase === 'unknown') {
            return '? ' + this._t('bp_male_phase_unknown', 'オスの相不明 → テスト交配で確認推奨');
        }

        return null;
    },

    // ========================================
    // v7.1: 多世代計画エンジン
    // 最大4世代の配合計画を FamilyMap 形式で出力
    // ========================================

    /**
     * v7.1: 目標に必要な遺伝子と現在のストック差分を分析
     * @param {string} targetKey - 目標色キー
     * @param {Array} birds - 登録鳥リスト
     * @returns {Object} { required, available, missing, canProduceInOneGen }
     */
    analyzeGeneGap(targetKey, birds) {
        let target = this.TARGET_REQUIREMENTS[targetKey];
        if (!target) {
            target = this.generateRequirementsFromMaster(targetKey);
        }
        if (!target) {
            // v7.3.12: より詳細なエラーメッセージ
            if (typeof COLOR_MASTER === 'undefined') {
                console.error('[Planner] COLOR_MASTER is undefined');
                return { error: this._t('bp_color_master_undefined', 'System error: COLOR_MASTER not loaded') };
            }
            if (!COLOR_MASTER[targetKey]) {
                console.error('[Planner] Unknown target key:', targetKey, '- Available keys sample:', Object.keys(COLOR_MASTER).slice(0, 5));
                return { error: this._t('bp_unknown_target', 'Unknown target') + ': ' + targetKey };
            }
            return { error: this._t('bp_unknown_target', 'Unknown target') };
        }

        const required = { ...target.required };
        const slr = { ...target.slr };

        // 現在のストックで利用可能な遺伝子を収集
        const available = { autosomal: {}, slr: {} };
        const males = birds.filter(b => b.sex === 'male');
        const females = birds.filter(b => b.sex === 'female');

        // 常染色体遺伝子
        for (const [locus, vals] of Object.entries(required)) {
            available.autosomal[locus] = { homozygous: [], heterozygous: [], absent: [] };

            birds.forEach(b => {
                const geno = b.genotype || {};
                const val = GenoData.canonical(locus, geno[locus]);

                if (vals.includes(val)) {
                    available.autosomal[locus].homozygous.push(b);
                } else if (!GenoData.isWild(locus, val)) {
                    // ヘテロまたはスプリット
                    available.autosomal[locus].heterozygous.push(b);
                } else {
                    available.autosomal[locus].absent.push(b);
                }
            });
        }

        // v7.3.19: 伴性遺伝子 - Z_linked形式対応 + アレル互換性チェック
        for (const [locus, vals] of Object.entries(slr)) {
            available.slr[locus] = { expressed: [], split: [], absent: [] };

            birds.forEach(b => {
                const geno = b.genotype || {};
                const val = this.getSLRGenotypeFromZLinked(geno, locus, b.sex);

                if (vals.includes(val)) {
                    available.slr[locus].expressed.push(b);
                } else if (this.isCompatibleSplit(val, vals, locus)) {
                    // v7.3.19: スプリットが目標アレルと互換性がある場合のみカウント
                    // (例: pld目標に対してino保有は不可、pld保有のみ可)
                    available.slr[locus].split.push(b);
                } else {
                    available.slr[locus].absent.push(b);
                }
            });
        }

        // 不足している遺伝子を特定
        const missing = [];

        for (const [locus, vals] of Object.entries(required)) {
            const avail = available.autosomal[locus];
            if (avail.homozygous.length === 0) {
                const hasHetero = avail.heterozygous.length > 0;
                missing.push({
                    type: 'autosomal',
                    locus,
                    required: vals,
                    status: hasHetero ? 'heterozygous_only' : 'absent',
                    heteroCount: avail.heterozygous.length,
                    // Dd × Dd で 25% DD が出る → 2世代で可能
                    generationsNeeded: hasHetero ? 1 : 2
                });
            }
        }

        for (const [locus, vals] of Object.entries(slr)) {
            const avail = available.slr[locus];
            if (avail.expressed.length === 0) {
                const hasSplit = avail.split.length > 0;
                missing.push({
                    type: 'slr',
                    locus,
                    required: vals,
                    status: hasSplit ? 'split_only' : 'absent',
                    splitCount: avail.split.length,
                    // スプリット♂ × 野生型♀ → 50%発現♀ (1世代)
                    generationsNeeded: hasSplit ? 1 : 2
                });
            }
        }

        // 1世代で作出可能か判定
        const canProduceInOneGen = missing.length === 0;

        // v7.1.1: 正確な世代数計算
        // 遺伝子が別々の個体に散在している場合、組み合わせに追加世代が必要
        let maxGenerationsNeeded = 1;
        if (missing.length > 0) {
            // 各遺伝子の固定に必要な世代
            const fixGenerations = Math.max(...missing.map(m => m.generationsNeeded));

            // 遺伝子が既に同一個体に存在するか確認
            const combinationNeeded = this.checkCombinationNeeded(birds, required, slr, available);

            // 基本世代 + 固定世代 + 組み合わせ世代
            maxGenerationsNeeded = fixGenerations + 1;

            // 組み合わせが必要な場合、追加世代
            if (combinationNeeded.needsCombination) {
                maxGenerationsNeeded += combinationNeeded.additionalGenerations;
            }
        }

        return {
            target,
            required,
            slr,
            available,
            missing,
            canProduceInOneGen,
            maxGenerationsNeeded: Math.min(maxGenerationsNeeded, 4),
            combinationAnalysis: missing.length > 0 ?
                this.checkCombinationNeeded(birds, required, slr, available) : null,
            totalBirds: birds.length,
            males: males.length,
            females: females.length
        };
    },

    /**
     * v7.1.1: 遺伝子の組み合わせが必要かチェック
     * 必要な遺伝子が別々の個体に散在している場合、組み合わせ世代が必要
     */
    checkCombinationNeeded(birds, required, slr, available) {
        const requiredGenes = [
            ...Object.keys(required),
            ...Object.keys(slr)
        ];

        if (requiredGenes.length <= 1) {
            return { needsCombination: false, additionalGenerations: 0 };
        }

        // 全ての必要遺伝子を持つ（または持てる）個体を探す
        let bestBird = null;
        let maxGenesInOneBird = 0;

        birds.forEach(bird => {
            const geno = bird.genotype || {};
            let genesPresent = 0;

            // 常染色体遺伝子
            for (const [locus, vals] of Object.entries(required)) {
                const val = GenoData.canonical(locus, geno[locus]);
                // ホモでもヘテロでも「持っている」とカウント
                if (vals.includes(val) || !GenoData.isWild(locus, val)) {
                    genesPresent++;
                }
            }

            // v7.3.19: 伴性遺伝子 - Z_linked形式対応 + アレル互換性チェック
            for (const [locus, vals] of Object.entries(slr)) {
                const val = this.getSLRGenotypeFromZLinked(geno, locus, bird.sex);
                if (vals.includes(val) || this.isCompatibleSplit(val, vals, locus)) {
                    genesPresent++;
                }
            }

            if (genesPresent > maxGenesInOneBird) {
                maxGenesInOneBird = genesPresent;
                bestBird = bird;
            }
        });

        const totalRequired = requiredGenes.length;
        const genesScattered = totalRequired - maxGenesInOneBird;

        // 散在している遺伝子を組み合わせるのに必要な追加世代
        // 各組み合わせステップで1世代必要
        const additionalGenerations = genesScattered > 0 ? Math.ceil(genesScattered / 2) : 0;

        return {
            needsCombination: genesScattered > 0,
            additionalGenerations,
            totalRequired,
            maxGenesInOneBird,
            genesScattered,
            bestBird: bestBird ? bestBird.name : null
        };
    },

    /**
     * v7.1: 多世代計画を生成
     * @param {string} targetKey - 目標色キー
     * @returns {Object} 多世代計画（FamilyMap互換形式を含む）
     */
    planMultiGeneration(targetKey) {
        const birds = typeof BirdDB !== 'undefined' ? BirdDB.getAllBirds() : [];
        if (birds.length === 0) {
            return {
                error: this._t('bp_no_birds', 'No birds registered'),
                suggestion: this._t('bp_register_hint', 'Register birds first')
            };
        }

        const gap = this.analyzeGeneGap(targetKey, birds);
        if (gap.error) {
            return { error: gap.error };
        }

        const targetName = this.getColorName(targetKey);
        const plan = {
            targetKey,
            targetName,
            analysis: gap,
            generations: [],
            familyMapData: null,
            totalGenerations: gap.maxGenerationsNeeded
        };

        // 1世代で可能な場合は従来のplanを使用
        if (gap.canProduceInOneGen) {
            const singleGenPlan = this.plan(targetKey);
            if (!singleGenPlan.error && singleGenPlan.topPairings.length > 0) {
                plan.generations.push({
                    genNumber: 1,
                    goal: this._tp('bp_goal_produce', { name: targetName }, `Produce ${targetName}`),
                    pairings: singleGenPlan.topPairings,
                    probability: singleGenPlan.topPairings[0].probability
                });
                plan.familyMapData = this.convertToFamilyMapFormat(plan, birds);
                return plan;
            }
        }

        // 多世代計画を構築
        const intermediateGoals = this.generateIntermediateGoals(gap, targetKey);

        let currentGen = gap.maxGenerationsNeeded;

        for (const intGoal of intermediateGoals) {
            const genPlan = {
                genNumber: currentGen,
                goal: intGoal.description,
                targetGene: intGoal.locus,
                targetValue: intGoal.targetValue,
                strategy: intGoal.strategy,
                pairings: this.findBestPairingsForGene(intGoal, birds),
                probability: intGoal.probability
            };
            plan.generations.push(genPlan);
            currentGen--;
        }

        // 最終世代（目標作出）- より具体的な説明を追加
        const finalGenPairings = [];

        // 中間世代で作出する遺伝子型の説明を生成
        const requiredFromPrevious = intermediateGoals.map(g => g.locus.toUpperCase()).join(' + ');

        // 最終世代で必要な親の説明
        const sireDesc = requiredFromPrevious
            ? this._tp('bp_offspring_with_gene', { gene: requiredFromPrevious }, `${requiredFromPrevious} offspring`)
            : this._t('bp_intermediate_bird', 'Intermediate bird');
        const damDesc = requiredFromPrevious
            ? this._tp('bp_offspring_with_gene', { gene: requiredFromPrevious }, `${requiredFromPrevious} offspring`)
            : this._t('bp_intermediate_bird', 'Intermediate bird');

        // v7.3.13: 最終世代の近交係数を推定
        const estimatedIC = this.estimateFinalInbreeding(plan.generations);

        // プレースホルダーペアリングを追加
        finalGenPairings.push({
            male: { name: `Gen${currentGen + 1} ${sireDesc} ♂`, isPlanned: true, geneLabel: requiredFromPrevious },
            female: { name: `Gen${currentGen + 1} ${damDesc} ♀`, isPlanned: true, geneLabel: requiredFromPrevious },
            probability: this.calculateFinalProbability(intermediateGoals),
            inbreedingCoef: estimatedIC,
            isEstimatedIC: true,
            recommendation: this._tp('bp_select_offspring',
                { gene: requiredFromPrevious, gen: currentGen + 1 },
                `Select ${requiredFromPrevious} offspring from Generation ${currentGen + 1}`)
        });

        plan.generations.push({
            genNumber: 1,
            goal: this._tp('bp_goal_produce', { name: targetName }, `Produce ${targetName}`),
            note: this._tp('bp_final_breeding_note',
                { gene: requiredFromPrevious, gen: currentGen + 1 },
                `Breed ${requiredFromPrevious} individuals from Gen ${currentGen + 1} together`),
            pairings: finalGenPairings,
            probability: 'Variable'
        });

        // FamilyMap形式に変換
        plan.familyMapData = this.convertToFamilyMapFormat(plan, birds);

        return plan;
    },

    /**
     * v7.1: 中間目標を生成
     */
    generateIntermediateGoals(gap, targetKey) {
        const goals = [];

        for (const m of gap.missing) {
            if (m.type === 'autosomal') {
                if (m.status === 'heterozygous_only') {
                    // 保因 × 保因 → 目標の遺伝子型（確率は CROSS_TABLE）
                    const carrier = GenoData.carrier(m.locus, m.required[0]);
                    goals.push({
                        locus: m.locus,
                        targetValue: m.required[0],
                        required: m.required,
                        description: this._tp('bp_fix_gene', { gene: m.locus.toUpperCase() },
                            `Fix ${m.locus.toUpperCase()} (homozygous)`),
                        strategy: 'hetero_x_hetero',
                        probability: GenoData.crossProbability(m.locus, carrier, carrier, m.required),
                        generationOffset: 1
                    });
                } else if (m.status === 'absent') {
                    // 遺伝子そのものがない → 導入が必要
                    goals.push({
                        locus: m.locus,
                        targetValue: m.required[0],
                        description: this._tp('bp_introduce_gene', { gene: m.locus.toUpperCase() },
                            `Introduce ${m.locus.toUpperCase()} gene`),
                        strategy: 'need_introduction',
                        probability: 0,
                        generationOffset: 2,
                        needsNewBlood: true
                    });
                }
            } else if (m.type === 'slr') {
                if (m.status === 'split_only') {
                    // スプリット♂ × 野生型♀ → 発現♀（確率は CROSS_TABLE）
                    const slrLocus = this._slrLocus(m.locus);
                    goals.push({
                        locus: m.locus,
                        targetValue: m.required[0],
                        required: m.required,
                        description: this._tp('bp_express_slr', { gene: m.locus.toUpperCase() },
                            `Express ${m.locus.toUpperCase()} in female`),
                        strategy: 'split_male_x_wild_female',
                        probability: GenoData.crossProbability(slrLocus,
                            GenoData.carrier(slrLocus, m.required[0]), GenoData.wildGenotype(slrLocus, 'female'),
                            m.required, 'female'),
                        generationOffset: 1
                    });
                } else if (m.status === 'absent') {
                    goals.push({
                        locus: m.locus,
                        targetValue: m.required[0],
                        description: this._tp('bp_introduce_slr_gene', { gene: m.locus.toUpperCase() },
                            `Introduce ${m.locus.toUpperCase()} (SLR)`),
                        strategy: 'need_introduction',
                        probability: 0,
                        generationOffset: 2,
                        needsNewBlood: true
                    });
                }
            }
        }

        // 優先度でソート（導入が必要なものを先に）
        goals.sort((a, b) => b.generationOffset - a.generationOffset);

        return goals;
    },

    /**
     * v7.3.2: 最終世代の確率を計算
     * 各中間目標の確率を掛け合わせる
     */
    calculateFinalProbability(intermediateGoals) {
        if (!intermediateGoals || intermediateGoals.length === 0) return 1.0;

        // 各段階の確率を掛け合わせる
        let prob = 1.0;
        for (const goal of intermediateGoals) {
            if (goal.probability > 0) {
                prob *= goal.probability;
            }
        }
        return prob;
    },

    /**
     * v7.3.15: 最終世代の近交係数を推定
     * 中間世代のペアリングから、最終世代の交配が
     * 半兄弟婚（同父異母/同母異父）になるか推定
     *
     * 重要: sire用ペアリングとdam用ペアリングの最初の2つだけを見る
     * （3つ目以降は参考表示で、実際の計画では使用しない）
     */
    estimateFinalInbreeding(generations) {
        // 中間世代（最終世代の親を作る世代）のペアリングを取得
        const intermediateGen = generations.find(g => g.genNumber === 2);
        if (!intermediateGen || !intermediateGen.pairings) return 0;

        const pairings = intermediateGen.pairings.filter(p => p.male && p.female && !p.male.isPlanned && !p.isWarning);
        if (pairings.length < 2) return 0;

        // v7.3.15: 最初の2つのペアリング（sire用とdam用）だけを見る
        const sirePairing = pairings[0];
        const damPairing = pairings[1];

        const sireParentMale = sirePairing.male?.id;
        const sireParentFemale = sirePairing.female?.id;
        const damParentMale = damPairing.male?.id;
        const damParentFemale = damPairing.female?.id;

        // 共通の両親 → 全兄弟（F=25%）
        if (sireParentMale === damParentMale && sireParentFemale === damParentFemale) {
            return 0.25;
        }

        // 共通のオス親のみ → 同父異母半兄弟（F=12.5%）
        if (sireParentMale === damParentMale) {
            return 0.125;
        }

        // 共通のメス親のみ → 同母異父半兄弟（F=12.5%）
        if (sireParentFemale === damParentFemale) {
            return 0.125;
        }

        // 共通の親なし → F=0%
        return 0;
    },

    /**
     * v7.1: 特定遺伝子を得るための最適ペアリングを探す
     */
    findBestPairingsForGene(goal, birds) {
        if (goal.needsNewBlood) {
            return [{
                recommendation: '🔴 ' + this._t('bp_need_new_bird', 'Need to acquire bird with this gene'),
                probability: 0
            }];
        }

        const males = birds.filter(b => b.sex === 'male');
        const females = birds.filter(b => b.sex === 'female');
        const pairings = [];

        if (goal.strategy === 'hetero_x_hetero') {
            // Dd × Dd を探す
            const isHetero = (b) => {
                const val = (b.genotype || {})[goal.locus];
                return !GenoData.isWild(goal.locus, val) && !goal.targetValue.includes(GenoData.canonical(goal.locus, val));
            };
            const heteroMales = males.filter(isHetero);
            const heteroFemales = females.filter(isHetero);

            heteroMales.forEach(m => {
                heteroFemales.forEach(f => {
                    // 近交係数チェック
                    let ic = 0;
                    if (typeof BreedingValidator !== 'undefined') {
                        ic = BreedingValidator.calcInbreedingCoefficient(m, f);
                    }
                    if (ic < this.INBREEDING_THRESHOLD) {
                        const prob = GenoData.crossProbability(goal.locus, this._locusGenotype(m.genotype || {}, goal.locus, 'male'),
                            this._locusGenotype(f.genotype || {}, goal.locus, 'female'), goal.required || [goal.targetValue]);
                        pairings.push({
                            male: m,
                            female: f,
                            probability: prob,
                            inbreedingCoef: ic,
                            recommendation: `${m.name} × ${f.name} → ${(prob * 100).toFixed(1)}% ${goal.locus.toUpperCase()}`
                        });
                    }
                });
            });
        }

        if (goal.strategy === 'split_male_x_wild_female') {
            // v7.3.19: スプリット♂を探す - Z_linked形式対応 + アレル互換性チェック
            const requiredVals = [goal.targetValue];  // 例: ['pldpld']
            const splitMales = males.filter(b => {
                const val = this.getSLRGenotypeFromZLinked(b.genotype || {}, goal.locus, 'male');
                return this.isCompatibleSplit(val, requiredVals, goal.locus);
            });

            splitMales.forEach(m => {
                females.forEach(f => {
                    let ic = 0;
                    if (typeof BreedingValidator !== 'undefined') {
                        ic = BreedingValidator.calcInbreedingCoefficient(m, f);
                    }
                    if (ic < this.INBREEDING_THRESHOLD) {
                        const prob = GenoData.crossProbability(this._slrLocus(goal.locus),
                            this.getSLRGenotypeFromZLinked(m.genotype || {}, goal.locus, 'male'),
                            this.getSLRGenotypeFromZLinked(f.genotype || {}, goal.locus, 'female'),
                            goal.required || requiredVals, 'female');
                        pairings.push({
                            male: m,
                            female: f,
                            probability: prob,
                            inbreedingCoef: ic,
                            recommendation: `${m.name} (split) × ${f.name} → ${(prob * 100).toFixed(1)}% ${goal.locus.toUpperCase()} ♀`
                        });
                    }
                });
            });
        }

        // スコア順でソート
        pairings.sort((a, b) => b.probability - a.probability || a.inbreedingCoef - b.inbreedingCoef);

        // v7.3.13: 最終世代の半兄弟婚を避けるため、異なるオス/メスを優先選択
        const selectedPairings = this.selectDiversifiedPairings(pairings, 3);

        return selectedPairings;
    },

    /**
     * v7.3.16: 鳥のpedigreeから全祖先IDを取得
     * @param {Object} bird - 鳥データ
     * @returns {Set} 祖先IDのセット
     */
    getAncestorIds(bird) {
        const ancestors = new Set();
        if (!bird || !bird.pedigree) return ancestors;

        const pedigree = bird.pedigree;

        // 親
        if (pedigree.sire) ancestors.add(pedigree.sire);
        if (pedigree.dam) ancestors.add(pedigree.dam);

        // 祖父母
        if (pedigree.sire_sire) ancestors.add(pedigree.sire_sire);
        if (pedigree.sire_dam) ancestors.add(pedigree.sire_dam);
        if (pedigree.dam_sire) ancestors.add(pedigree.dam_sire);
        if (pedigree.dam_dam) ancestors.add(pedigree.dam_dam);

        // 曾祖父母
        if (pedigree.sire_sire_sire) ancestors.add(pedigree.sire_sire_sire);
        if (pedigree.sire_sire_dam) ancestors.add(pedigree.sire_sire_dam);
        if (pedigree.sire_dam_sire) ancestors.add(pedigree.sire_dam_sire);
        if (pedigree.sire_dam_dam) ancestors.add(pedigree.sire_dam_dam);
        if (pedigree.dam_sire_sire) ancestors.add(pedigree.dam_sire_sire);
        if (pedigree.dam_sire_dam) ancestors.add(pedigree.dam_sire_dam);
        if (pedigree.dam_dam_sire) ancestors.add(pedigree.dam_dam_sire);
        if (pedigree.dam_dam_dam) ancestors.add(pedigree.dam_dam_dam);

        // BirdDBのgetAllAncestorIdsも使用（より深い系統追跡）
        if (typeof BirdDB !== 'undefined' && bird.id) {
            const dbAncestors = BirdDB.getAllAncestorIds(bird.id);
            if (dbAncestors) {
                dbAncestors.forEach(id => ancestors.add(id));
            }
        }

        return ancestors;
    },

    /**
     * v7.3.16: 2つのペアリングの子同士が共有する祖先をチェック
     * ペアリング1の子の祖先 = {ペアリング1のオス, ペアリング1のメス, それぞれの祖先}
     * ペアリング2の子の祖先 = {ペアリング2のオス, ペアリング2のメス, それぞれの祖先}
     *
     * @param {Object} pair1 - 最初のペアリング
     * @param {Object} pair2 - 2番目のペアリング
     * @returns {Object} { sharedCount, sharedIds, estimatedIC }
     */
    calculateSharedAncestors(pair1, pair2) {
        // ペアリング1の子が持つ祖先
        const ancestors1 = new Set();
        if (pair1.male?.id) {
            ancestors1.add(pair1.male.id);
            this.getAncestorIds(pair1.male).forEach(id => ancestors1.add(id));
        }
        if (pair1.female?.id) {
            ancestors1.add(pair1.female.id);
            this.getAncestorIds(pair1.female).forEach(id => ancestors1.add(id));
        }

        // ペアリング2の子が持つ祖先
        const ancestors2 = new Set();
        if (pair2.male?.id) {
            ancestors2.add(pair2.male.id);
            this.getAncestorIds(pair2.male).forEach(id => ancestors2.add(id));
        }
        if (pair2.female?.id) {
            ancestors2.add(pair2.female.id);
            this.getAncestorIds(pair2.female).forEach(id => ancestors2.add(id));
        }

        // 共通祖先を検出
        const sharedIds = [];
        ancestors1.forEach(id => {
            if (ancestors2.has(id)) {
                sharedIds.push(id);
            }
        });

        // 近交係数を推定
        // - 同じ親（直接の親） = 12.5%〜25%
        // - 同じ祖父母 = 6.25%（従兄弟婚）
        // - 同じ曾祖父母 = 3.125%
        let estimatedIC = 0;
        const pair1Parents = new Set([pair1.male?.id, pair1.female?.id].filter(Boolean));
        const pair2Parents = new Set([pair2.male?.id, pair2.female?.id].filter(Boolean));

        // 共通の親をチェック（兄弟/半兄弟）
        let sharedParentCount = 0;
        pair1Parents.forEach(id => {
            if (pair2Parents.has(id)) sharedParentCount++;
        });

        if (sharedParentCount === 2) {
            estimatedIC = 0.25;  // 全兄弟
        } else if (sharedParentCount === 1) {
            estimatedIC = 0.125; // 半兄弟
        } else if (sharedIds.length > 0) {
            // 祖父母以上で共通 → 従兄弟以下
            // 共通祖先の数に基づいて推定
            estimatedIC = Math.min(0.0625 * sharedIds.length, 0.125);
        }

        return {
            sharedCount: sharedIds.length,
            sharedIds,
            estimatedIC,
            relationship: sharedParentCount === 2 ? 'full_siblings' :
                         sharedParentCount === 1 ? 'half_siblings' :
                         sharedIds.length > 0 ? 'cousins' : 'unrelated'
        };
    },

    /**
     * v7.3.16: 最終世代の近親交配を避けるため、祖先データを使って最適なペアリングを選択
     *
     * 優先順位:
     * 1. 共通祖先なし（F=0%）
     * 2. 共通祖先が曾祖父母のみ（F≈3%）
     * 3. 共通祖先が祖父母（従兄弟婚 F=6.25%）
     * 4. 共通親が1つ（半兄弟婚 F=12.5%）
     * 5. 共通親が2つ（全兄弟婚 F=25%）
     */
    selectDiversifiedPairings(pairings, maxCount) {
        if (pairings.length <= 1) return pairings;

        // v7.3.16: 祖先データを使って最適なペアリング組み合わせを選択
        const result = this.selectNonOverlappingPairings(pairings, maxCount);

        return result;
    },

    /**
     * v7.3.16: 共通祖先を持たないペアリングを優先選択
     * pedigreeデータを使用して3世代まで遡り、共通祖先をチェック
     */
    selectNonOverlappingPairings(pairings, maxCount) {
        if (pairings.length <= 1) return pairings;

        const selected = [];
        const firstPair = pairings[0];
        selected.push(firstPair);

        // 各ペアリングと最初のペアリングの共通祖先を計算
        const pairingsWithScore = pairings.slice(1).map(p => {
            const shared = this.calculateSharedAncestors(firstPair, p);
            return {
                pairing: p,
                sharedCount: shared.sharedCount,
                estimatedIC: shared.estimatedIC,
                relationship: shared.relationship
            };
        });

        // 共通祖先が少ない順にソート（近交係数が低い順）
        pairingsWithScore.sort((a, b) => a.estimatedIC - b.estimatedIC);

        // 最も共通祖先が少ないペアリングを選択
        let bestSecondPair = null;
        let bestRelationship = 'unknown';

        for (const item of pairingsWithScore) {
            if (item.estimatedIC === 0) {
                // 共通祖先なし → 最適
                bestSecondPair = item.pairing;
                bestRelationship = 'unrelated';
                break;
            } else if (item.estimatedIC < 0.0625 && !bestSecondPair) {
                // 遠縁（従兄弟未満）
                bestSecondPair = item.pairing;
                bestRelationship = item.relationship;
            } else if (item.estimatedIC < 0.125 && !bestSecondPair) {
                // 従兄弟以下
                bestSecondPair = item.pairing;
                bestRelationship = item.relationship;
            }
        }

        // 見つからなければ最もICが低いものを使用
        if (!bestSecondPair && pairingsWithScore.length > 0) {
            bestSecondPair = pairingsWithScore[0].pairing;
            bestRelationship = pairingsWithScore[0].relationship;
        }

        if (bestSecondPair) {
            selected.push(bestSecondPair);

            // 近親交配の警告を追加
            const shared = this.calculateSharedAncestors(firstPair, bestSecondPair);

            if (shared.relationship === 'full_siblings') {
                selected.push({
                    recommendation: '🚫 ' + this._t('bp_same_parents_warning', 'All pairings use the same parents. Final generation will be full siblings (F=25%). Must introduce new bloodlines.'),
                    probability: 0,
                    isWarning: true
                });
            } else if (shared.relationship === 'half_siblings') {
                selected.push({
                    recommendation: '⚠️ ' + this._t('bp_half_sibling_warning', 'Final generation offspring will be half-siblings (F=12.5%). Consider introducing new bloodlines.'),
                    probability: 0,
                    isWarning: true
                });
            } else if (shared.relationship === 'cousins') {
                selected.push({
                    recommendation: '⚠️ ' + this._t('bp_cousin_warning', 'Final generation offspring will be cousins (F≈6.25%). Acceptable but consider introducing new bloodlines for long-term breeding.'),
                    probability: 0,
                    isWarning: true
                });
            }
        }

        // 残りのスロットを埋める（表示用）
        for (const p of pairings) {
            if (selected.length >= maxCount) break;
            if (!selected.includes(p) && !p.isWarning) {
                selected.push(p);
            }
        }

        return selected;
    },

    /**
     * v7.1.2: 計画をFamilyMap形式に変換（目標を子として逆算）
     * G0: 目標個体（作出予定）
     * G1: 親（最終交配ペア）
     * G2: 祖父母（中間世代1）
     * G3: 曾祖父母（中間世代2）
     */
    convertToFamilyMapFormat(plan, birds) {
        const data = {
            name: `${plan.targetName} ${this._t('bp_breeding_plan', 'Breeding Plan')}`,
            savedAt: new Date().toISOString(),
            isBreedingPlan: true,
            targetKey: plan.targetKey,
            totalGenerations: plan.totalGenerations,
            // G0: 目標（作出予定）
            offspring: [{
                id: 'plan_target',
                name: `🎯 ${plan.targetName}`,
                sex: 'unknown',
                phenotype: { baseColor: plan.targetKey },
                genotype: plan.analysis.target.required,
                isPlanned: true,
                isTarget: true
            }],
            // G1-G3: 初期化
            sire: null, dam: null,
            sire_sire: null, sire_dam: null,
            dam_sire: null, dam_dam: null,
            sire_sire_sire: null, sire_sire_dam: null,
            sire_dam_sire: null, sire_dam_dam: null,
            dam_sire_sire: null, dam_sire_dam: null,
            dam_dam_sire: null, dam_dam_dam: null
        };

        // 世代ごとのペアリングをFamilyMapの位置にマッピング
        // Gen1 → sire/dam
        // Gen2 → sire_sire/sire_dam (sireを作る) または dam_sire/dam_dam (damを作る)
        // Gen3 → great-grandparents

        const generations = plan.generations || [];

        // 最終世代（Gen1）: 目標を直接作出するペア
        const gen1 = generations.find(g => g.genNumber === 1);
        if (gen1 && gen1.pairings && gen1.pairings.length > 0) {
            const topPair = gen1.pairings[0];
            if (topPair.male) {
                // v7.3.2: isPlanned フラグをチェック
                if (topPair.male.isPlanned) {
                    data.sire = this.createPlannedBird('sire', topPair.male.geneLabel || gen1.targetGene, '♂', 1);
                    data.sire.name = topPair.male.name || data.sire.name;
                } else {
                    data.sire = this.birdToFamilyMapFormat(topPair.male, 'sire', false);
                }
            } else {
                // 中間個体（まだ作出されていない）
                data.sire = this.createPlannedBird('sire', gen1.targetGene, '♂', 1);
            }
            if (topPair.female) {
                // v7.3.2: isPlanned フラグをチェック
                if (topPair.female.isPlanned) {
                    data.dam = this.createPlannedBird('dam', topPair.female.geneLabel || gen1.targetGene, '♀', 1);
                    data.dam.name = topPair.female.name || data.dam.name;
                } else {
                    data.dam = this.birdToFamilyMapFormat(topPair.female, 'dam', false);
                }
            } else {
                data.dam = this.createPlannedBird('dam', gen1.targetGene, '♀', 1);
            }
        }

        // 第2世代（Gen2）: G1の親を作出するペア
        const gen2 = generations.find(g => g.genNumber === 2);
        if (gen2 && gen2.pairings && gen2.pairings.length > 0) {
            // v7.3.17: 最初のペアリングをsireの親、2番目をdamの親として配置
            const pair1 = gen2.pairings[0];
            if (pair1.male && pair1.female) {
                // sireの親として配置
                data.sire_sire = this.birdToFamilyMapFormat(pair1.male, 'sire_sire', true);
                data.sire_dam = this.birdToFamilyMapFormat(pair1.female, 'sire_dam', true);

                // sireが未作出の場合、プランとしてマーク
                if (data.sire && !data.sire.isExisting) {
                    data.sire.fromPairing = { sire: pair1.male.name, dam: pair1.female.name };
                }
            } else if (pair1.recommendation) {
                // 導入が必要な場合
                data.sire_sire = this.createPlannedBird('sire_sire', gen2.targetGene, '♂', 2, pair1.recommendation);
            }

            // v7.3.17: 2番目のペアリングがあればdamの親として配置（異なるペアから子を取ることでいとこ婚を回避）
            const pair2 = gen2.pairings.length > 1 ? gen2.pairings[1] : null;
            if (pair2 && pair2.male && pair2.female) {
                data.dam_sire = this.birdToFamilyMapFormat(pair2.male, 'dam_sire', true);
                data.dam_dam = this.birdToFamilyMapFormat(pair2.female, 'dam_dam', true);

                // damが未作出の場合、プランとしてマーク
                if (data.dam && !data.dam.isExisting) {
                    data.dam.fromPairing = { sire: pair2.male.name, dam: pair2.female.name };
                }
            }
        }

        // 第3世代（Gen3）: G2の親を作出するペア
        const gen3 = generations.find(g => g.genNumber === 3);
        if (gen3 && gen3.pairings && gen3.pairings.length > 0) {
            const pair = gen3.pairings[0];
            if (pair.male && pair.female) {
                data.sire_sire_sire = this.birdToFamilyMapFormat(pair.male, 'sire_sire_sire', true);
                data.sire_sire_dam = this.birdToFamilyMapFormat(pair.female, 'sire_sire_dam', true);
            }
        }

        // 第4世代（Gen4）: 最大4世代
        const gen4 = generations.find(g => g.genNumber === 4);
        if (gen4 && gen4.pairings && gen4.pairings.length > 0) {
            // G4は表示限界外だが、メモとして保存
            data.gen4Note = gen4.goal;
        }

        return data;
    },

    /**
     * v7.1.2: 作出予定の個体を作成
     */
    createPlannedBird(position, targetGene, sex, generation, note) {
        const sexLabel = sex === '♂' ? 'male' : 'female';
        return {
            id: `plan_${position}`,
            name: `📋 ${this._t('bp_planned_bird', 'Planned')} (G${generation})`,
            sex: sexLabel,
            phenotype: { baseColor: 'unknown' },
            genotype: {},
            position: position,
            isPlanned: true,
            isExisting: false,
            targetGene: targetGene,
            generation: generation,
            note: note || null
        };
    },

    /**
     * v7.1.2: 鳥データをFamilyMap用にフォーマット
     * v7.3.13: BirdDBのデータは observed に羽色情報を持つため優先
     */
    birdToFamilyMapFormat(bird, position, isFoundation) {
        // BirdDBのデータは observed.baseColor に羽色を持つ
        // phenotype が文字列の場合もある（旧形式）
        let phenotype;
        if (bird.observed && bird.observed.baseColor) {
            phenotype = bird.observed;
        } else if (typeof bird.phenotype === 'string') {
            phenotype = { baseColor: bird.phenotype };
        } else if (bird.phenotype && bird.phenotype.baseColor) {
            phenotype = bird.phenotype;
        } else {
            phenotype = { baseColor: 'green' };
        }

        return {
            id: bird.id,
            name: bird.name,
            sex: bird.sex,
            phenotype: phenotype,
            genotype: bird.genotype || {},
            position: position,
            isExisting: true,
            isFoundation: isFoundation || false
        };
    }
};

/**
 * v7.1: runPlanner() - 多世代計画対応版
 * 1世代で不可能な場合は多世代計画を表示
 */
function runPlanner() {
    const T = window.T || {};
    const _t = (key, fallback) => T[key] || fallback;

    const targetSelect = document.getElementById('plannerTarget');
    const resultPanel = document.getElementById('plannerResult');
    const emptyPanel = document.getElementById('plannerEmpty');
    if (!targetSelect || !resultPanel) return;
    const targetKey = targetSelect.value;
    if (!targetKey) { alert(_t('bp_select_target', 'Please select a target trait')); return; }

    // v7.0: 空パネルを非表示
    if (emptyPanel) emptyPanel.style.display = 'none';

    // v7.1: まず多世代計画を試行
    const multiGenPlan = BreedingPlanner.planMultiGeneration(targetKey);

    if (multiGenPlan.error) {
        let errorHtml = `<div class="empty-state" style="color: #333 !important;"><p style="color: #333 !important;">⚠️ ${multiGenPlan.error}</p>`;
        if (multiGenPlan.suggestion) errorHtml += `<p style="color: #333 !important;">${multiGenPlan.suggestion}</p>`;
        errorHtml += '</div>';
        resultPanel.innerHTML = errorHtml;
        resultPanel.style.display = 'block';
        return;
    }

    const targetName = multiGenPlan.targetName;
    const analysis = multiGenPlan.analysis;

    let html = `<div class="output-header"><span class="output-title" style="color: #4fc3f7 !important;">🎯 ${targetName} ${_t('bp_production_plan', 'Production Plan')}</span></div>`;

    // v7.1: 遺伝子ギャップ分析を表示
    if (analysis && analysis.missing && analysis.missing.length > 0) {
        html += `<div class="gene-gap-analysis" style="background: #fff3cd !important; border: 1px solid #ffc107 !important; padding: 12px; border-radius: 8px; margin-bottom: 15px; color: #333 !important;">`;
        html += `<h4 style="margin-top:0; color: #856404 !important;">📊 ${_t('bp_gene_analysis', 'Gene Analysis')}</h4>`;
        html += `<p style="color: #333 !important;"><strong style="color: #333 !important;">${_t('bp_generations_needed', 'Generations needed')}:</strong> ${multiGenPlan.totalGenerations}</p>`;
        html += `<p style="color: #333 !important;"><strong style="color: #333 !important;">${_t('bp_missing_genes', 'Missing genes')}:</strong></p><ul style="margin: 5px 0; padding-left: 20px; color: #333 !important;">`;

        analysis.missing.forEach(m => {
            const locusName = m.locus.toUpperCase();
            const statusText = m.status === 'heterozygous_only'
                ? _t('bp_hetero_only', 'heterozygous only (can fix in 1 gen)')
                : m.status === 'split_only'
                    ? _t('bp_split_only', 'split males only (can express in 1 gen)')
                    : _t('bp_gene_absent', 'absent (need to introduce)');
            html += `<li style="color: #333 !important;"><strong style="color: #333 !important;">${locusName}</strong>: ${statusText}</li>`;
        });
        html += `</ul>`;

        // v7.1.1: 組み合わせ分析を表示
        if (analysis.combinationAnalysis && analysis.combinationAnalysis.needsCombination) {
            const combo = analysis.combinationAnalysis;
            html += `<div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ffc107 !important;">`;
            html += `<p style="color: #333 !important;"><strong style="color: #333 !important;">🔗 ${_t('bp_combination_needed', 'Gene combination needed')}:</strong></p>`;
            html += `<p style="font-size: 0.9em; color: #856404 !important;">`;
            html += _t('bp_genes_scattered', '{scattered} genes are on different birds. Need {gens} extra generation(s) to combine.')
                .replace('{scattered}', combo.genesScattered)
                .replace('{gens}', combo.additionalGenerations);
            html += `</p>`;
            if (combo.bestBird) {
                html += `<p style="font-size: 0.9em; color: #333 !important;">`;
                html += `${_t('bp_best_foundation', 'Best foundation bird')}: <strong style="color: #333 !important;">${combo.bestBird}</strong> (${combo.maxGenesInOneBird}/${combo.totalRequired} ${_t('bp_genes', 'genes')})`;
                html += `</p>`;
            }
            html += `</div>`;
        }
        html += `</div>`;
    } else if (analysis && analysis.canProduceInOneGen) {
        html += `<div style="background: #d4edda !important; border: 1px solid #28a745 !important; padding: 10px; border-radius: 8px; margin-bottom: 15px;">`;
        html += `<p style="margin:0; color: #155724 !important;">✅ ${_t('bp_one_gen_possible', 'Can be produced in 1 generation!')}</p>`;
        html += `</div>`;
    }

    // v7.1: 各世代の計画を表示
    if (multiGenPlan.generations && multiGenPlan.generations.length > 0) {
        html += `<div class="generation-plans">`;

        // 世代番号の大きい順（早い世代から）に表示
        const sortedGens = [...multiGenPlan.generations].sort((a, b) => b.genNumber - a.genNumber);

        sortedGens.forEach(gen => {
            const genLabel = gen.genNumber === 1
                ? _t('bp_final_generation', 'Final Generation')
                : _t('bp_generation_n', `Generation ${gen.genNumber}`).replace('{n}', gen.genNumber);

            html += `<div class="generation-card" style="border: 1px solid #dee2e6 !important; border-radius: 8px; padding: 12px; margin-bottom: 10px; background: #fff !important; color: #333 !important;">`;
            html += `<h4 style="margin-top: 0; border-bottom: 1px solid #eee !important; padding-bottom: 8px; color: #333 !important;">📅 ${genLabel}</h4>`;
            html += `<p style="color: #333 !important;"><strong style="color: #333 !important;">${_t('bp_goal', 'Goal')}:</strong> ${gen.goal}</p>`;

            if (gen.note) {
                html += `<p style="color: #666 !important; font-style: italic;">${gen.note}</p>`;
            }

            if (gen.pairings && gen.pairings.length > 0) {
                html += `<div class="pairing-list">`;
                gen.pairings.forEach((p, i) => {
                    if (p.male && p.female) {
                        const icPercent = ((p.inbreedingCoef || 0) * 100).toFixed(2);
                        // インラインスタイルでIC値の色を設定（CSSクラスに依存しない）
                        const icColor = (p.inbreedingCoef || 0) >= 0.125 ? '#d32f2f' : ((p.inbreedingCoef || 0) >= 0.0625 ? '#f57c00' : '#388e3c');

                        html += `<div class="pairing-card" style="background: #f8f9fa !important; padding: 10px; margin: 5px 0; border-radius: 5px; color: #333 !important;">`;
                        html += `<div class="pairing-header" style="color: #333 !important; font-weight: bold;">#${i+1} ♂${p.male.name} × ♀${p.female.name}</div>`;
                        const icLabel = p.isEstimatedIC ? `${_t('bp_f_value', 'F-value')}: ${icPercent}% (${_t('bp_estimated', '推定')})` : `${_t('bp_f_value', 'F-value')}: ${icPercent}%`;
                        html += `<div class="pairing-stats" style="color: #333 !important;">${_t('bp_probability', 'Probability')}: ${((p.probability || 0)*100).toFixed(1)}%${p.probabilityRange ? ` (? ${(p.probabilityRange.min*100).toFixed(1)}–${(p.probabilityRange.max*100).toFixed(1)}%)` : ''} | <span style="color: ${icColor} !important; font-weight: bold;">${icLabel}</span></div>`;
                        if (p.recommendation) {
                            html += `<div class="pairing-recommendation" style="color: #495057 !important;">${p.recommendation}</div>`;
                        }
                        html += '</div>';
                    } else if (p.recommendation) {
                        html += `<div class="pairing-card" style="background: #f8d7da !important; padding: 10px; margin: 5px 0; border-radius: 5px; color: #333 !important;">`;
                        html += `<p style="margin: 0; color: #333 !important;">${p.recommendation}</p>`;
                        html += '</div>';
                    }
                });
                html += '</div>';
            }
            html += '</div>';
        });

        html += '</div>';
    }

    // v7.1: FamilyMapで開くボタン
    if (multiGenPlan.familyMapData) {
        html += `<div style="margin-top: 15px; text-align: center;">`;
        html += `<button onclick="openPlanInFamilyMap()" class="btn btn-primary" style="padding: 10px 20px; background: #1565c0 !important; color: #fff !important; border: none;">`;
        html += `📊 ${_t('bp_open_in_familymap', 'Open in FamilyMap')}</button>`;
        html += `</div>`;

        // グローバルに保存して FamilyMap で使えるように
        window._currentBreedingPlan = multiGenPlan.familyMapData;
    }

    // v6.7.4: 倫理基準の警告を追加（12.5%以上のペアが存在する場合のみ）
    let hasHighInbreeding = false;
    if (multiGenPlan.generations && multiGenPlan.generations.length > 0) {
        for (const gen of multiGenPlan.generations) {
            if (gen.pairings && gen.pairings.length > 0) {
                for (const p of gen.pairings) {
                    if ((p.inbreedingCoef || 0) >= 0.125) {
                        hasHighInbreeding = true;
                        break;
                    }
                }
            }
            if (hasHighInbreeding) break;
        }
    }

    if (hasHighInbreeding) {
        html += `<div class="ethics-note" style="margin-top: 15px; padding: 10px; background: #f8d7da !important; border-radius: 5px; font-size: 0.85em; border: 1px solid #f5c6cb !important; color: #721c24 !important;">`;
        html += `<p style="margin: 0; color: #721c24 !important;"><strong>${_t('bp_ethics_standard', '⚠️ Warning')}:</strong> ${_t('bp_ethics_description', 'Contains pairs violating ethical standards (IC ≥12.5% prohibited). High risk of deformity, weakness, and cognitive impairment')}</p>`;
        html += `</div>`;
    }

    // v7.3.19: 多世代計画（2世代以上）では2系統維持のアドバイスを表示
    // 兄弟婚・従兄弟婚を避けるため、最終世代直前で合流させる戦略を推奨
    if (multiGenPlan.totalGenerations >= 2) {
        html += `<div class="lineage-advice" style="margin-top: 15px; padding: 12px; background: #e3f2fd !important; border-radius: 8px; font-size: 0.9em; border: 1px solid #90caf9 !important; color: #1565c0 !important;">`;
        html += `<p style="margin: 0; color: #0d47a1 !important;">${_t('pf_lineage_advice', '💡 Inbreeding avoidance tip: Create 2 separate lineages (sire-line and dam-line) in Phase 1-2, then merge them in Phase 3 to avoid sibling/cousin mating.')}</p>`;
        html += `</div>`;
    }

    // v7.3.20: 近親交配制限セクションを追加
    html += renderInbreedingLimitSectionJS(_t);

    resultPanel.innerHTML = html;
    resultPanel.style.display = 'block';
}

/**
 * v7.3.20: JavaScript用近親交配制限セクション生成
 * @param {Function} _t - 翻訳関数
 * @returns {string} HTML文字列
 */
function renderInbreedingLimitSectionJS(_t) {
    return `
    <div class="card inbreeding-limit-section" style="margin-top: 1.5rem; background: #1a2332 !important; border-radius: 8px; padding: 1rem;">
        <h3 class="card-title" style="color: #fff !important; margin-bottom: 1rem;">${_t('inbreeding_limit', 'Inbreeding Limits')}</h3>
        <div class="health-recommendations">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li class="urgency-critical" style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 4px solid #f44336; background: rgba(244, 67, 54, 0.1); border-radius: 4px;">
                    <strong style="color: #fff !important;">INO (${_t('lutino', 'Lutino')}/${_t('creamino', 'Creamino')}/${_t('pure_white', 'Pure White')}): 2 ${_t('generation', 'gen')}</strong>
                    <span class="rec-detail" style="display: block; margin-top: 0.25rem; color: rgba(255,255,255,0.7); font-size: 0.9em;">${_t('static_ino_limit_desc', 'INO: Immune weakness risk after 2 generations of inbreeding')}</span>
                </li>
                <li class="urgency-critical" style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 4px solid #f44336; background: rgba(244, 67, 54, 0.1); border-radius: 4px;">
                    <strong style="color: #fff !important;">${_t('pallid', 'Pallid')}: 2 ${_t('generation', 'gen')}</strong>
                    <span class="rec-detail" style="display: block; margin-top: 0.25rem; color: rgba(255,255,255,0.7); font-size: 0.9em;">${_t('static_pallid_limit_desc', 'Pallid: 2 generation limit')}</span>
                </li>
                <li class="urgency-high" style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 4px solid #ff9800; background: rgba(255, 152, 0, 0.1); border-radius: 4px;">
                    <strong style="color: #fff !important;">${_t('fallow', 'Fallow')}: 2 ${_t('generation', 'gen')}</strong>
                    <span class="rec-detail" style="display: block; margin-top: 0.25rem; color: rgba(255,255,255,0.7); font-size: 0.9em;">${_t('static_fallow_limit_desc', 'Fallow: Watch for vision problems')}</span>
                </li>
                <li class="urgency-moderate" style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 4px solid #ffc107; background: rgba(255, 193, 7, 0.1); border-radius: 4px;">
                    <strong style="color: #fff !important;">Dark DF: 3 ${_t('generation', 'gen')}</strong>
                    <span class="rec-detail" style="display: block; margin-top: 0.25rem; color: rgba(255,255,255,0.7); font-size: 0.9em;">${_t('static_dark_df_desc', 'Dark DF x DF: Up to 3 generations')}</span>
                </li>
                <li style="padding: 0.75rem; border-left: 4px solid #4caf50; background: rgba(76, 175, 80, 0.1); border-radius: 4px;">
                    <strong style="color: #fff !important;">${_t('static_general_limit', 'Others: 5 generations')}</strong>
                </li>
            </ul>
        </div>
    </div>`;
}

/**
 * v7.1: 計画をFamilyMapタブで開く
 * v7.3.18: readOnlyモードで開く（閲覧専用）
 * v7.3.24: デモモード時もプランデータを正しくロード（v7.3.19のバグ修正）
 */
function openPlanInFamilyMap() {
    if (!window._currentBreedingPlan) {
        alert('No breeding plan available');
        return;
    }

    // FamilyMapにデータをロード
    if (typeof FamilyMap !== 'undefined') {
        // v7.3.24: デモモード判定（Family Cは読み込まない - プランデータを使用）
        const isDemo = typeof BirdDB !== 'undefined' && BirdDB.getMode && BirdDB.getMode() === 'demo';
        if (isDemo) {
            FamilyMap.demoMode = true;
        }
        // v7.3.24: 常にプランデータをロード（デモ/ユーザー両対応）
        FamilyMap.data = window._currentBreedingPlan;
        FamilyMap.familyMode = 'plan';
        FamilyMap.readOnly = true;  // v7.3.18: Planner出力は閲覧専用
        FamilyMap.renderUI();

        // FamilyMapタブに切り替え
        if (typeof showTab === 'function') {
            showTab('family');
        }
    }
}

if (typeof window !== 'undefined') {
    window.BreedingPlanner = BreedingPlanner;
}
