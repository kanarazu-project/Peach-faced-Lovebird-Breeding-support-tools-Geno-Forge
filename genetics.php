<?php
/**
 * Agapornis Gene-Forge v7.3
 * Genetics Calculation Engine (314 colours; ALBS show-class names where ALBS has a class, AOC otherwise)
 *
 * Chief Product Officer: Shohei T(Homo repugnans)
 * Tactical Decision Intelligence: Sirius (Electronic Spirit)
 *
 * @license CC BY-NC-SA 4.0
 */
declare(strict_types=1);

// PHP 7.x 互換ポリフィル
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

final class AgapornisLoci
{
    public const LOCI = [
        'parblue' => ['name'=>['ja'=>'パーブルー','en'=>'Parblue'],'type'=>'AR_MULTI','sex_linked'=>false,'alleles'=>['+'=>0,'tq'=>1,'aq'=>2]],
        'dark' => ['name'=>['ja'=>'ダークファクター','en'=>'Dark Factor'],'type'=>'AID','sex_linked'=>false,'alleles'=>['d'=>0,'D'=>1]],
        'violet' => ['name'=>['ja'=>'バイオレット','en'=>'Violet'],'type'=>'AID','sex_linked'=>false,'alleles'=>['v'=>0,'V'=>1]],
        'fallow_pale' => ['name'=>['ja'=>'ペールファロー','en'=>'Pale Fallow'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'flp'=>1]],
        'fallow_bronze' => ['name'=>['ja'=>'ブロンズファロー','en'=>'Bronze Fallow'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'flb'=>1]],
        'pied_dom' => ['name'=>['ja'=>'ドミナントパイド','en'=>'Dominant Pied'],'type'=>'AD','sex_linked'=>false,'alleles'=>['+'=>0,'Pi'=>1]],
        'pied_rec' => ['name'=>['ja'=>'レセッシブパイド','en'=>'Recessive Pied'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'pi'=>1]],
        'dilute' => ['name'=>['ja'=>'ダイリュート','en'=>'Dilute'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'dil'=>1]],
        'edged' => ['name'=>['ja'=>'エッジド','en'=>'Edged'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'Ed'=>1]],
        'orangeface' => ['name'=>['ja'=>'オレンジフェイス','en'=>'Orangeface'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'of'=>1]],
        'pale_headed' => ['name'=>['ja'=>'ペールヘッド','en'=>'Pale Headed'],'type'=>'AR','sex_linked'=>false,'alleles'=>['+'=>0,'ph'=>1]],
        'ino' => ['name'=>['ja'=>'INO','en'=>'INO'],'type'=>'SL_MULTI','sex_linked'=>true,'alleles'=>['+'=>0,'pld'=>1,'ino'=>2]],
        'opaline' => ['name'=>['ja'=>'オパーリン','en'=>'Opaline'],'type'=>'SLR','sex_linked'=>true,'alleles'=>['+'=>0,'op'=>1]],
        'cinnamon' => ['name'=>['ja'=>'シナモン','en'=>'Cinnamon'],'type'=>'SLR','sex_linked'=>true,'alleles'=>['+'=>0,'cin'=>1]],
    ];

    /**
     * GENOTYPE_OPTIONS - UI用遺伝型選択オプション（SSOT）
     *
     * 構造:
     * - 常染色体座位: 'options' => [[値, ラベル], ...]
     * - 伴性座位: 'male' => [...], 'female' => [...]
     *
     * family.js, app.js で使用
     */
    public const GENOTYPE_OPTIONS = [
        // === 常染色体座位 ===
        'parblue' => [
            'options' => [
                ['++', 'B⁺/B⁺'],
                ['+aq', 'B⁺/b^aq'],
                ['+tq', 'B⁺/b^tq'],
                ['aqaq', 'b^aq/b^aq'],
                ['tqtq', 'b^tq/b^tq'],
                ['tqaq', 'b^tq/b^aq'],
            ],
        ],
        'dark' => [
            'options' => [
                ['dd', 'd/d'],
                ['Dd', 'D/d (SF)'],
                ['DD', 'D/D (DF)'],
            ],
        ],
        'violet' => [
            'options' => [
                ['vv', 'v/v'],
                ['Vv', 'V/v (SF)'],
                ['VV', 'V/V (DF)'],
            ],
        ],
        'fallow_pale' => [
            'options' => [
                ['++', 'Flp⁺/Flp⁺'],
                ['+flp', 'Flp⁺/flp'],
                ['flpflp', 'flp/flp'],
            ],
        ],
        'fallow_bronze' => [
            'options' => [
                ['++', 'Flb⁺/Flb⁺'],
                ['+flb', 'Flb⁺/flb'],
                ['flbflb', 'flb/flb'],
            ],
        ],
        'pied_dom' => [
            'options' => [
                ['++', '+/+'],
                ['Pi+', 'Pi/+'],
                ['PiPi', 'Pi/Pi'],
            ],
        ],
        'pied_rec' => [
            'options' => [
                ['++', 'Pi⁺/Pi⁺'],
                ['+pi', 'Pi⁺/pi'],
                ['pipi', 'pi/pi'],
            ],
        ],
        'dilute' => [
            'options' => [
                ['++', 'Dil⁺/Dil⁺'],
                ['+dil', 'Dil⁺/dil'],
                ['dildil', 'dil/dil'],
            ],
        ],
        'edged' => [
            'options' => [
                ['++', '+/+'],
                ['Ed+', 'Ed/+'],
                ['EdEd', 'Ed/Ed'],
            ],
        ],
        'orangeface' => [
            'options' => [
                ['++', 'Of⁺/Of⁺'],
                ['+of', 'Of⁺/of'],
                ['ofof', 'of/of'],
            ],
        ],
        'pale_headed' => [
            'options' => [
                ['++', 'Ph⁺/Ph⁺'],
                ['+ph', 'Ph⁺/ph'],
                ['phph', 'ph/ph'],
            ],
        ],
        // === 伴性座位（性別で異なる） ===
        'ino' => [
            'male' => [
                ['++', 'Z⁺/Z⁺'],
                ['+pld', 'Z⁺/Z^pld'],
                ['+ino', 'Z⁺/Z^ino'],
                ['pldpld', 'Z^pld/Z^pld'],
                ['inoino', 'Z^ino/Z^ino'],
                ['pldino', 'Z^pld/Z^ino'],
            ],
            'female' => [
                ['+W', 'Z⁺/W'],
                ['pldW', 'Z^pld/W'],
                ['inoW', 'Z^ino/W'],
            ],
        ],
        'opaline' => [
            'male' => [
                ['++', 'Z⁺/Z⁺'],
                ['+op', 'Z⁺/Z^op'],
                ['opop', 'Z^op/Z^op'],
            ],
            'female' => [
                ['+W', 'Z⁺/W'],
                ['opW', 'Z^op/W'],
            ],
        ],
        'cinnamon' => [
            'male' => [
                ['++', 'Z⁺/Z⁺'],
                ['+cin', 'Z⁺/Z^cin'],
                ['cincin', 'Z^cin/Z^cin'],
            ],
            'female' => [
                ['+W', 'Z⁺/W'],
                ['cinW', 'Z^cin/W'],
            ],
        ],
    ];

    /**
     * UI_GENOTYPE_LOCI - UI用遺伝型フォームの座位設定（SSOT）
     *
     * family.js と app.js で共通使用
     * key: フォームフィールド名/データ保存キー
     * source: GENOTYPE_OPTIONS のキー
     * label: 表示ラベル
     */
    public const UI_GENOTYPE_LOCI = [
        ['key' => 'parblue', 'source' => 'parblue', 'label' => 'Parblue'],
        ['key' => 'ino', 'source' => 'ino', 'label' => 'INO'],
        ['key' => 'op', 'source' => 'opaline', 'label' => 'Opaline'],
        ['key' => 'cin', 'source' => 'cinnamon', 'label' => 'Cinnamon'],
        ['key' => 'dark', 'source' => 'dark', 'label' => 'Dark'],
        ['key' => 'vio', 'source' => 'violet', 'label' => 'Violet'],
        ['key' => 'fl', 'source' => 'fallow_pale', 'label' => 'Fallow'],
        ['key' => 'dil', 'source' => 'dilute', 'label' => 'Dilute'],
        ['key' => 'pi', 'source' => 'pied_rec', 'label' => 'Pied'],
    ];

    /**
     * LINKAGE_GROUPS - 連鎖遺伝子群の定義（v7.0 SSOT）
     *
     * 同一染色体上で連鎖している座位のグループを定義。
     * これらの座位は独立分離せず、組み換え率に基づいて遺伝する。
     *
     * 構造:
     * - 'loci': 連鎖している座位のキー名（LOCIと同じキー）
     * - 'chromosome': 染色体タイプ（'Z' = 伴性, 'autosome' = 常染色体）
     */
    public const LINKAGE_GROUPS = [
        'Z_linked' => [
            'loci' => ['cinnamon', 'ino', 'opaline'],
            'chromosome' => 'Z',
        ],
        'autosomal_1' => [
            'loci' => ['dark', 'parblue'],
            'chromosome' => 'autosome',
        ],
    ];

    /**
     * RECOMBINATION_RATES - 組み換え率の定義（v7.0 SSOT）
     *
     * 連鎖した座位間の組み換え率を定義。
     * 出典: Lovebirds Compendium (Dirk Van den Abeele, 2016) p.228-231
     *
     * キー形式: 'locus1-locus2'（アルファベット順）
     * 値: 組み換え率（0.0〜0.5、0.5で独立分離と等価）
     *
     * 注: Pallid(pld)はino座位の複対立遺伝子のため、
     *     ino関連の組み換え率がそのまま適用される
     */
    public const RECOMBINATION_RATES = [
        // Z染色体（伴性）
        'cinnamon-ino' => 0.03,      // 3% - ほぼ完全連鎖
        'ino-opaline' => 0.30,       // 30%
        // cin–op 間は cin–ino(3%) と ino–op(30%) から干渉なしで導出する:
        // r = r1 + r2 − 2·r1·r2 = 0.03 + 0.30 − 0.018 = 0.312
        // 子の計算本体（Z連鎖ハプロタイプ計算）が r1・r2 を独立に掛け合わせるのと同じモデル。
        // 単純な足し算（33%）は、cin–op 間では元の組合せに戻る二重組換えまで組換えとして数えてしまうので使わない。
        'cinnamon-opaline' => 0.03 + 0.30 - 2 * 0.03 * 0.30,  // 31.2%
        // 常染色体
        'dark-parblue' => 0.07,      // 7%
    ];

    /**
     * 遺伝子型文字列をアレル配列に分解する（LOCI のアレル名が唯一の基準）
     * 例: 'Pi+' → ['Pi','+'], 'inoW' → ['ino'], 'tqaq' → ['tq','aq']
     * 解析できない文字列は null を返す。
     */
    public static function splitGenotype(string $locusKey, string $genotype): ?array
    {
        $alleles = array_map('strval', array_keys(self::LOCI[$locusKey]['alleles'] ?? []));
        if (!$alleles) return null;
        if ($locusKey === 'edged') {
            // 旧表記（小文字 ed）を正規の Ed に読み替える
            $genotype = str_replace('ed', 'Ed', $genotype);
        }
        if (str_ends_with($genotype, 'W')) {
            $a = substr($genotype, 0, -1);
            return in_array($a, $alleles, true) ? [$a] : null;
        }
        foreach ($alleles as $a) {
            foreach ($alleles as $b) {
                if ($a . $b === $genotype) return [$a, $b];
            }
        }
        return null;
    }

    /**
     * 表現型からダーク因子の遺伝子型を決める（全機能で共通の規則）
     *
     * 1. 濃さ欄（darkness）が sf / df なら、それを優先する（大文字小文字は問わない）。
     * 2. なし・不明・未入力なら、色名の定義（COLOR_DEFINITIONS の dark）に従う。
     * 3. 色名に dark の定義がない（INO 系の12色）なら判断できないので $default を返す。
     *    INO ではダーク因子は黄色の体色に影響せず、腰の青みが濃くなるだけのため
     *    （Van den Abeele, Ornitho-Genetics VZW FAQ, 2023）。
     *
     * ダーク因子は不完全優性で、dd / Dd / DD は見た目で区別できる。
     */
    public static function resolveDarkGenotype(array $phenotype, ?string $default = null): ?string
    {
        $darkness = strtolower((string)($phenotype['darkness'] ?? ''));
        if (isset(self::DARKNESS_TO_DARK[$darkness])) return self::DARKNESS_TO_DARK[$darkness];
        $colorDef = self::COLOR_DEFINITIONS[strtolower((string)($phenotype['baseColor'] ?? ''))] ?? null;
        return $colorDef['genotype']['dark'] ?? $default;
    }

    /** 濃さ欄の値 → ダーク因子の遺伝子型（resolveDarkGenotype と JS の GENOTYPE_DATA.darknessByDark の正本） */
    public const DARKNESS_TO_DARK = ['sf' => 'Dd', 'df' => 'DD'];

    /** 繁殖計画（planner.js）が伴性座位に使う短いキー → 座位 */
    public const PLANNER_SLR_KEYS = ['ino' => 'ino', 'cin' => 'cinnamon', 'op' => 'opaline'];

    /**
     * 定義に無い組み合わせの色名の部品（この順に並べ、最後に基本色の名前を置く）。
     * allele の変異アレルを、mode=all なら全アレルが、mode=any なら1つでも持つと付く。
     * noIno: INO の発現時は付けない。parblueOnly: パーブルー系列だけ。parblueName: パーブルー系列での名前。eye: 目の色。
     * generateColorName と birds.js（GENOTYPE_DATA.colorName 経由）が同じこの表で名前を作る。
     */
    private const COLOR_NAME_PARTS = [
        ['locus' => 'opaline', 'allele' => 'op', 'mode' => 'all', 'ja' => 'オパーリン', 'en' => 'Opaline'],
        ['locus' => 'cinnamon', 'allele' => 'cin', 'mode' => 'all', 'noIno' => true, 'ja' => 'シナモン', 'en' => 'Cinnamon'],
        ['locus' => 'ino', 'allele' => 'pld', 'mode' => 'all', 'noIno' => true, 'ja' => 'パリッド', 'en' => 'Pallid'],
        ['locus' => 'fallow_pale', 'allele' => 'flp', 'mode' => 'all', 'noIno' => true, 'eye' => 'red', 'ja' => 'ペールファロー', 'en' => 'Pale Fallow'],
        ['locus' => 'fallow_bronze', 'allele' => 'flb', 'mode' => 'all', 'noIno' => true, 'eye' => 'red', 'ja' => 'ブロンズファロー', 'en' => 'Bronze Fallow'],
        ['locus' => 'dilute', 'allele' => 'dil', 'mode' => 'all', 'noIno' => true, 'ja' => 'ダイリュート', 'en' => 'Dilute'],
        ['locus' => 'edged', 'allele' => 'Ed', 'mode' => 'all', 'noIno' => true, 'ja' => 'エッジド', 'en' => 'Edged'],
        ['locus' => 'violet', 'allele' => 'V', 'mode' => 'any', 'parblueOnly' => true, 'ja' => 'バイオレット', 'en' => 'Violet'],
        ['locus' => 'pied_dom', 'allele' => 'Pi', 'mode' => 'any', 'ja' => 'ドミナントパイド', 'en' => 'Dominant Pied'],
        ['locus' => 'pied_rec', 'allele' => 'pi', 'mode' => 'all', 'ja' => 'レセッシブパイド', 'en' => 'Recessive Pied'],
        ['locus' => 'orangeface', 'allele' => 'of', 'mode' => 'all', 'ja' => 'オレンジフェイス', 'en' => 'Orangefaced',
         'parblueName' => ['ja' => 'イエローフェイス', 'en' => 'Yellowfaced']],
        ['locus' => 'pale_headed', 'allele' => 'ph', 'mode' => 'all', 'ja' => 'ペールヘッド', 'en' => 'Pale Headed'],
    ];

    /** 色名の基本色を決める INO の発現（ino アレルだけの遺伝子型） */
    private const COLOR_NAME_INO_ALLELE = 'ino';

    /**
     * 見た目のそろえ方（色の判定・色名・確信度が共通に使う）。座位 → [優位なアレル => 劣位のアレル]。
     * ino 座位では pld が ino より優位で、pldino はパリッドに見える。優性（type AD）はヘテロとホモが同じ見た目（別に扱う）。
     */
    private const ALLELE_DOMINANCE = ['ino' => ['pld' => 'ino']];

    /** 色の判定で、INO の発現に隠される座位（INO の色は濃さで名前が変わらない） */
    private const COLOR_LOCI_HIDDEN_BY_INO = ['dark'];

    /** INO 型の表示の優先順（健康評価の文言で、両親のうちより白い系統を示す） */
    private const INO_TYPE_PRIORITY = ['pure_white', 'creamino', 'creamino_seagreen', 'lutino'];

    /**
     * 色の署名（表現している座位の「座位=表現型の形」を LOCI の順に ; でつなぐ）。JS（birds.js）も同じ並べ方で作る。
     */
    private static function colorSignature(array $genotype, array $loci): string
    {
        $formOf = fn(string $locus) => $loci[$locus]['genotypes'][(string)($genotype[$locus] ?? '')]['phenoForm'] ?? null;
        // INO が発現していると（見た目が ino だけのとき）、COLOR_LOCI_HIDDEN_BY_INO の座位は色を変えない
        $inoForm = $formOf('ino');
        $inoExpressed = $inoForm !== null && array_unique(self::splitGenotype('ino', $inoForm) ?? []) === [self::COLOR_NAME_INO_ALLELE];
        $parts = [];
        foreach (array_keys(self::LOCI) as $locus) {
            if ($inoExpressed && in_array($locus, self::COLOR_LOCI_HIDDEN_BY_INO, true)) continue;
            $form = $formOf($locus);
            if ($form !== null) $parts[] = $locus . '=' . $form;
        }
        return implode(';', $parts);
    }

    /**
     * 国際名（ALBS の対照表の右列の書き方）の部品。この順に並べ、最後に濃さ（D / DD）と基本色を置く。
     * allele・mode の意味は COLOR_NAME_PARTS と同じ（見た目のアレルで判定）。ALBS の対照表に無い変異
     * （recessive pied・pale fallow・bronze fallow・dilute・pale headed）の名前は、ここで決めた呼び方。
     */
    private const INTL_NAME_PARTS = [
        ['locus' => 'opaline', 'allele' => 'op', 'mode' => 'all', 'name' => 'opaline'],
        ['locus' => 'pied_dom', 'allele' => 'Pi', 'mode' => 'any', 'name' => 'dominant pied'],
        ['locus' => 'pied_rec', 'allele' => 'pi', 'mode' => 'all', 'name' => 'recessive pied'],
        ['locus' => 'cinnamon', 'allele' => 'cin', 'mode' => 'all', 'name' => 'cinnamon'],
        ['locus' => 'ino', 'allele' => 'pld', 'mode' => 'all', 'name' => 'pallid'],
        ['locus' => 'ino', 'allele' => 'ino', 'mode' => 'all', 'name' => 'ino'],
        ['locus' => 'fallow_pale', 'allele' => 'flp', 'mode' => 'all', 'name' => 'pale fallow'],
        ['locus' => 'fallow_bronze', 'allele' => 'flb', 'mode' => 'all', 'name' => 'bronze fallow'],
        ['locus' => 'dilute', 'allele' => 'dil', 'mode' => 'all', 'name' => 'dilute'],
        ['locus' => 'pale_headed', 'allele' => 'ph', 'mode' => 'all', 'name' => 'pale headed'],
        ['locus' => 'violet', 'allele' => 'V', 'mode' => 'any', 'name' => 'violet'],
        ['locus' => 'orangeface', 'allele' => 'of', 'mode' => 'all', 'name' => 'orange face'],
        ['locus' => 'edged', 'allele' => 'Ed', 'mode' => 'all', 'name' => 'marbled'],
    ];
    /** 国際名の基本色（パーブルーの見た目 → 名前）と濃さ（ダークの見た目 → 前置き） */
    private const INTL_BASE_NAMES = ['++' => 'green', 'aqaq' => 'aqua', 'tqtq' => 'turquoise', 'tqaq' => 'AquaTurquoise'];
    private const INTL_DARK_NAMES = ['Dd' => 'D', 'DD' => 'DD'];

    /**
     * 遺伝子型の国際名（例: "opaline cinnamon D aqua"、"cinnamon ino green"）。ALBS に区分の無い色（AOC）の名前に使う。
     * 見た目（phenoForm）で判定するので、スプリットは名前に出ない。INO の発現時は濃さを付けない。
     */
    public static function internationalName(array $genotype): string
    {
        $loci = self::genotypeData()['loci'];
        $look = function (string $l) use ($genotype, $loci): array {
            $i = $loci[$l]['genotypes'][self::canonicalGenotype($l, (string)($genotype[$l] ?? self::wildGenotype($l)))] ?? null;
            if ($i === null || $i['phenoForm'] === null) return [];
            return $loci[$l]['genotypes'][$i['phenoForm']]['alleles'] ?? [];
        };
        $parts = [];
        foreach (self::INTL_NAME_PARTS as $p) {
            $al = $look($p['locus']);
            $hits = count(array_filter($al, fn($a) => $a === $p['allele']));
            if ($al && ($p['mode'] === 'any' ? $hits > 0 : $hits === count($al))) $parts[] = $p['name'];
        }
        $inoExpressed = in_array('ino', $parts, true);
        $dark = $look('dark');
        $darkForm = $dark ? (self::genotypeData()['loci']['dark']['byAlleles'][implode('|', (function ($a) { sort($a); return $a; })($dark))] ?? null) : null;
        if (!$inoExpressed && $darkForm !== null && isset(self::INTL_DARK_NAMES[$darkForm])) $parts[] = self::INTL_DARK_NAMES[$darkForm];
        $pb = $look('parblue');
        $pbForm = $pb ? (self::genotypeData()['loci']['parblue']['byAlleles'][implode('|', (function ($a) { sort($a); return $a; })($pb))] ?? null) : null;
        $parts[] = self::INTL_BASE_NAMES[$pbForm ?? self::wildGenotype('parblue')] ?? self::INTL_BASE_NAMES[self::wildGenotype('parblue')];
        return implode(' ', $parts);
    }

    /**
     * 色の ALBS 区分（ALBS_PEACHFACED_CLASSES の区分名）。'albs' が null の色、区分に当たらない色は null（AOC）。
     * 'albs' の名前と区分名を、語の集合で照合する（Medium / Dark は「Medium and Dark 〜」か「〜 - All」の区分に入る）。
     */
    public static function albsClass(string $colorKey): ?string
    {
        $name = self::COLOR_DEFINITIONS[$colorKey]['albs'] ?? null;
        if ($name === null) return null;
        $norm = function (string $n): array {
            $n = strtolower(str_replace(['Orangfaced', '( True )'], ['Orangefaced', '(True)'], $n));
            $all = (bool)preg_match('/\s-\s*all$/', $n);
            $n = preg_replace('/\s-\s*all$/', '', $n);
            $w = preg_split('/\s+/', trim($n));
            $md = in_array('medium', $w, true) || in_array('dark', $w, true);
            $core = array_values(array_diff($w, ['medium', 'and', 'dark']));
            sort($core);
            return [implode(' ', $core), $md, $all];
        };
        [$core, $md] = $norm($name);
        foreach (self::ALBS_PEACHFACED_CLASSES as $list) {
            foreach ($list as $c) {
                if (str_starts_with($c, 'AOC') || str_starts_with($c, 'Heavy') || str_contains($c, 'Dark-Eyed')) continue;
                [$cc, $cmd, $all] = $norm($c);
                // 「Pied Opaline Blue - All」の Blue は青系の系列（Par-Blue など）を指す
                if ($c === 'Pied Opaline Blue - All') {
                    $w = array_diff(explode(' ', $core), ['whitefaced', 'seagreen']);
                    sort($w);
                    if (implode(' ', $w) === 'opaline par-blue pied') return $c;
                    continue;
                }
                if ($cc !== $core) continue;
                if ($md ? ($cmd || $all) : !$cmd) return $c;
            }
        }
        return null;
    }

    /** 遺伝子型の色の署名（座位ごとの見た目）。genotypeData の colorKey.bySignature を引く鍵 */
    public static function genotypeSignature(array $genotype): string
    {
        return self::colorSignature($genotype, self::genotypeData()['loci']);
    }

    /**
     * 座位ごとの正規の遺伝子型（GENOTYPE_OPTIONS が正本）
     * @return array{male: string[], female: string[]}
     */
    public static function canonicalGenotypes(string $locusKey): array
    {
        $opt = self::GENOTYPE_OPTIONS[$locusKey] ?? [];
        $pick = fn($list) => array_map(fn($o) => (string)$o[0], $list ?? []);
        if (isset($opt['male'])) return ['male' => $pick($opt['male']), 'female' => $pick($opt['female'] ?? [])];
        $all = $pick($opt['options'] ?? []);
        return ['male' => $all, 'female' => $all];
    }

    /** 座位の野生型の遺伝子型（オス/メス） */
    public static function wildGenotype(string $locusKey, string $sex = 'male'): string
    {
        $w = self::wildAllele($locusKey) ?? '+';
        $sl = (bool)(self::LOCI[$locusKey]['sex_linked'] ?? false);
        return ($sl && $sex === 'female') ? $w . 'W' : $w . $w;
    }

    /** 遺伝子型を正規の表記にそろえる（例: ino+ → +ino、eded → EdEd）。解析できなければそのまま返す。 */
    public static function canonicalGenotype(string $locusKey, string $genotype): string
    {
        return self::genotypeData()['loci'][$locusKey]['genotypes'][$genotype]['canonical'] ?? $genotype;
    }

    /**
     * JS が遺伝の事実を引くための表（index.php が GENOTYPE_DATA として注入。JS 側の引き出しは genodata.js）
     *
     * - loci[座位]: sexLinked / wild{male,female} / genotypes[表記] / byAlleles[アレルの組]
     *     genotypes[表記] = {canonical, alleles, mutant, dose, wild, expressed, hemi}
     *     表記は正規形に加えて、並び順違い（ino+）と旧表記（eded）も引けるようにする
     * - byColor[色][性別]: 色の定義の遺伝子型（伴性メスはヘミ接合に変換）
     * - targets[色]: 繁殖計画の目標（required / slr / tier / minGen / difficulty / inbreedingLimit）
     * - plannerSlrKeys: 繁殖計画の伴性キー → 座位
     * - inoTypes: [[パーブルーの遺伝子型, INO 色キー], ...]（表示の優先順）
     * - colorKey: 遺伝子型から色キーを組み立てる規則（birds.js の calculatePhenotype が解釈する）
     * - darknessByDark: ダーク因子の遺伝子型 → 濃さ欄の値
     */
    public static function genotypeData(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $loci = [];
        foreach (self::LOCI as $key => $cfg) {
            $names = array_map('strval', array_keys($cfg['alleles'] ?? []));
            $wild = self::wildAllele($key) ?? '+';
            $sl = (bool)($cfg['sex_linked'] ?? false);
            $canon = self::canonicalGenotypes($key);
            $sortKey = function (array $al): string { sort($al); return implode('|', $al) . (count($al) === 1 ? '|W' : ''); };
            $byAlleles = [];
            foreach (array_merge($canon['male'], $canon['female']) as $g) {
                $al = self::splitGenotype($key, $g);
                if ($al !== null) $byAlleles[$sortKey($al)] = $g;
            }
            $info = function (array $al) use ($key, $wild, $byAlleles, $sortKey): array {
                $canonical = $byAlleles[$sortKey($al)] ?? implode('', $al) . (count($al) === 1 ? 'W' : '');
                $calleles = self::splitGenotype($key, $canonical) ?? $al;
                $mutant = array_values(array_filter($calleles, fn($a) => $a !== $wild));
                $hemi = (count($calleles) === 2 && $calleles[0] === $calleles[1] && $calleles[0] !== $wild)
                    ? ($byAlleles[$sortKey([$calleles[0]])] ?? null) : null;
                $expressed = self::isExpressedAlleles($key, $calleles);
                // 表現型の形（見た目）: 表現している遺伝子型を、同じ見た目の代表（色の定義の書き方）にそろえたもの。
                // 伴性メスの aW は aa、優位なアレルがあれば劣位のアレルを置き換え（pldino → pldpld）、優性はヘテロの形（PiPi → Pi+）
                $phenoForm = null;
                if ($expressed) {
                    $look = count($calleles) === 1 ? [$calleles[0], $calleles[0]] : $calleles;
                    foreach (self::ALLELE_DOMINANCE[$key] ?? [] as $dom => $rec) {
                        if (in_array($dom, $look, true)) $look = array_map(fn($a) => $a === $rec ? $dom : $a, $look);
                    }
                    if ((self::LOCI[$key]['type'] ?? '') === 'AD') {
                        $mut = current(array_filter($look, fn($a) => $a !== $wild));
                        $look = [$wild, $mut];
                    }
                    $phenoForm = $byAlleles[$sortKey($look)] ?? $canonical;
                }
                return [
                    'canonical' => $canonical,
                    'alleles' => $calleles,
                    'mutant' => $mutant,
                    'dose' => count($mutant),
                    'wild' => count($mutant) === 0,
                    'expressed' => $expressed,
                    'hemi' => $hemi,
                    'phenoForm' => $phenoForm,
                ];
            };
            $genotypes = [];
            foreach ($names as $a) {
                foreach ($names as $b) $genotypes[$a . $b] = $info([$a, $b]);
                if ($sl) $genotypes[$a . 'W'] = $info([$a]);
            }
            // 旧表記（小文字の ed など）も正規形に引けるようにする
            foreach (array_keys($genotypes) as $g) {
                foreach ([strtolower($g)] as $alias) {
                    if ($alias !== $g && !isset($genotypes[$alias]) && self::splitGenotype($key, $alias) !== null) {
                        $genotypes[$alias] = $genotypes[$g];
                    }
                }
            }
            $loci[$key] = [
                'sexLinked' => $sl,
                'wild' => ['male' => self::wildGenotype($key, 'male'), 'female' => self::wildGenotype($key, 'female')],
                'genotypes' => $genotypes,
                'byAlleles' => $byAlleles,
            ];
        }

        // 色ごとの遺伝子型（伴性メスはヘミ接合）
        $byColor = [];
        foreach (self::COLOR_DEFINITIONS as $color => $def) {
            $male = $def['genotype'] ?? [];
            $female = $male;
            foreach ($male as $l => $g) {
                if (!empty($loci[$l]['sexLinked'])) {
                    $female[$l] = $loci[$l]['genotypes'][$g]['hemi'] ?? $loci[$l]['wild']['female'];
                    if (!empty($loci[$l]['genotypes'][$g]['wild'])) $female[$l] = $loci[$l]['wild']['female'];
                }
            }
            $byColor[$color] = ['male' => $male, 'female' => $female];
        }

        // 繁殖計画の目標
        $slrByLocus = array_flip(self::PLANNER_SLR_KEYS);
        $pathFinder = new PathFinder();
        $targets = [];
        foreach (self::COLOR_DEFINITIONS as $color => $def) {
            $required = []; $slr = []; $count = 0; $limit = false;
            foreach (($def['genotype'] ?? []) as $l => $g) {
                $i = $loci[$l]['genotypes'][$g] ?? null;
                if ($i === null) continue;
                if (isset($slrByLocus[$l])) {
                    if (!$i['wild']) { $slr[$slrByLocus[$l]] = array_values(array_filter([$i['canonical'], $i['hemi']])); $count++; }
                } elseif ($l === 'parblue' || $l === 'dark' || !$i['wild']) {
                    $required[$l] = [$i['canonical']];
                    if (!$i['wild']) $count++;
                }
                // INO・パリッド・ファローの発現は近親交配の世代制限（2世代）の対象
                if (!$i['wild'] && $i['expressed'] && in_array($l, ['ino', 'fallow_pale', 'fallow_bronze'], true)) $limit = true;
            }
            $difficulty = ['none', 'low', 'mid', 'high'][$count] ?? 'extreme';
            // 子がこの色になる条件（見た目の完全一致）: 座位ごとに、色の定義と見た目（phenoForm）が同じ遺伝子型。
            // 定義に無い座位は表現していないこと。INO の色では COLOR_LOCI_HIDDEN_BY_INO の座位は問わない
            $form = fn(string $l, ?string $g) => $g === null ? null : ($loci[$l]['genotypes'][$g]['phenoForm'] ?? null);
            $inoForm = $form('ino', $def['genotype']['ino'] ?? null);
            $inoColor = $inoForm !== null && array_unique(self::splitGenotype('ino', $inoForm) ?? []) === [self::COLOR_NAME_INO_ALLELE];
            $allowed = [];
            foreach ($loci as $l => $info) {
                if ($inoColor && in_array($l, self::COLOR_LOCI_HIDDEN_BY_INO, true)) continue;
                $want = $form($l, $def['genotype'][$l] ?? null);
                $allowed[$l] = array_values(array_keys(array_filter($info['genotypes'],
                    fn($i, $g) => $i['canonical'] === $g && $i['phenoForm'] === $want, ARRAY_FILTER_USE_BOTH)));
            }
            $targets[$color] = [
                'required' => (object)$required,
                'slr' => (object)$slr,
                'allowed' => $allowed,
                'tier' => $def['tier'] ?? 1,
                'minGen' => $pathFinder->findPath((string)$color)['minGenerations'] ?? 0,
                'difficulty' => $difficulty,
                'inbreedingLimit' => $limit ? 2 : null,
            ];
        }

        // INO 型（パーブルー → INO 色）と、色キーの組み立て規則（COLOR_DEFINITIONS から導く）
        $inoByParblue = []; $baseByParblue = []; $darkByParblue = []; $cinnamonInoByParblue = [];
        foreach (self::COLOR_DEFINITIONS as $color => $def) {
            $g = $def['genotype'] ?? [];
            $keys = array_keys($g); sort($keys);
            $pb = $g['parblue'] ?? null;
            if ($pb === null) continue;
            if ($keys === ['ino', 'parblue'] && ($loci['ino']['genotypes'][$g['ino']]['mutant'] ?? []) === ['ino', 'ino']) {
                $inoByParblue[$pb] = (string)$color;
            }
            // シナモンイノ（cinnamon + ino）: INO の発現時にシナモンも出ていれば、基本色はこの色の名前
            if ($keys === ['cinnamon', 'ino', 'parblue'] && ($loci['ino']['genotypes'][$g['ino']]['mutant'] ?? []) === ['ino', 'ino']
                && ($loci['cinnamon']['genotypes'][$g['cinnamon']]['expressed'] ?? false)) {
                $cinnamonInoByParblue[$pb] = (string)$color;
            }
            if ($keys === ['dark', 'parblue']) {
                $dose = $loci['dark']['genotypes'][$g['dark']]['dose'] ?? null;
                if ($dose === 0) $baseByParblue[$pb] = (string)$color;
                elseif ($dose !== null) $darkByParblue[$pb][$g['dark']] = (string)$color;
            }
        }
        $inoTypes = [];
        foreach (self::INO_TYPE_PRIORITY as $color) {
            $pb = array_search($color, $inoByParblue, true);
            if ($pb !== false) $inoTypes[] = [$pb, $color];
        }
        // 定義に無い組み合わせの色名の規則（部品は COLOR_NAME_PARTS、基本色の名前は COLOR_DEFINITIONS から）
        // 見た目（phenoForm、表現していなければ遺伝子型そのもの）のアレルで判定する（pldino はパリッド、PiPi は Pi+ と同じ）
        $withAllele = function (string $l, string $allele, string $mode) use ($loci): array {
            return array_values(array_keys(array_filter($loci[$l]['genotypes'], function ($i, $g) use ($allele, $mode, $l, $loci) {
                if ($i['canonical'] !== $g) return false;
                $look = $i['phenoForm'] !== null ? ($loci[$l]['genotypes'][$i['phenoForm']]['alleles'] ?? $i['alleles']) : $i['alleles'];
                $hits = count(array_filter($look, fn($a) => $a === $allele));
                return $mode === 'any' ? $hits > 0 : $hits === count($look);
            }, ARRAY_FILTER_USE_BOTH)));
        };
        $nameOf = fn(string $color) => ['ja' => self::COLOR_DEFINITIONS[$color]['ja'], 'en' => self::COLOR_DEFINITIONS[$color]['en']];
        $parts = [];
        foreach (self::COLOR_NAME_PARTS as $part) {
            $parts[] = [
                'locus' => $part['locus'],
                'genotypes' => $withAllele($part['locus'], $part['allele'], $part['mode']),
                'noIno' => $part['noIno'] ?? false,
                'parblueOnly' => $part['parblueOnly'] ?? false,
                'eye' => $part['eye'] ?? null,
                'name' => ['ja' => $part['ja'], 'en' => $part['en']],
                'parblueName' => $part['parblueName'] ?? null,
            ];
        }
        $baseNames = [];
        foreach ($baseByParblue as $pb => $color) {
            $baseNames[$pb][self::wildGenotype('dark')] = $nameOf($color);
            foreach ($darkByParblue[$pb] ?? [] as $dg => $dc) $baseNames[$pb][$dg] = $nameOf($dc);
        }
        $wildParblue = self::wildGenotype('parblue');
        $colorName = [
            'parts' => $parts,
            'inoGenotypes' => $withAllele('ino', self::COLOR_NAME_INO_ALLELE, 'all'),
            'inoNames' => array_map($nameOf, $inoByParblue),
            'inoDefault' => $nameOf($inoByParblue[$wildParblue]),
            'cinnamonInoGenotypes' => $withAllele('cinnamon', 'cin', 'all'),
            'cinnamonInoNames' => array_map($nameOf, $cinnamonInoByParblue),
            'baseNames' => $baseNames,
            'baseDefault' => $nameOf($baseByParblue[$wildParblue]),
            'wildParblue' => $wildParblue,
            'wildDark' => self::wildGenotype('dark'),
            'eyeDefault' => 'black',
            'eyeIno' => 'red',
        ];

        $darknessByDark = [self::wildGenotype('dark') => 'none'] + array_flip(self::DARKNESS_TO_DARK);

        // 色の署名 → 色キー（定義済みの色を遺伝子型から引く。署名は表現している座位の「座位=表現型の形」を LOCI の順に並べたもの）
        $colorBySignature = [];
        foreach (self::COLOR_DEFINITIONS as $color => $def) {
            $sig = self::colorSignature($def['genotype'] ?? [], $loci);
            if (!isset($colorBySignature[$sig])) $colorBySignature[$sig] = (string)$color;
        }

        return $cache = [
            'loci' => $loci,
            'byColor' => $byColor,
            'targets' => $targets,
            'plannerSlrKeys' => self::PLANNER_SLR_KEYS,
            'inoTypes' => $inoTypes,
            'colorKey' => [
                'bySignature' => $colorBySignature,
                // 署名の作り方: INO が発現していると（ino の見た目が inoGenotypes）、hiddenByIno の座位を署名に入れない
                'hiddenByIno' => self::COLOR_LOCI_HIDDEN_BY_INO,
            ],
            'colorName' => $colorName,
            // 連鎖する組 → 座位（LINKAGE_GROUPS の順）と、登録画面の欄のキー → 座位（UI_GENOTYPE_LOCI）
            'linkageGroups' => array_map(fn($g) => $g['loci'], self::LINKAGE_GROUPS),
            'uiKeys' => array_column(self::UI_GENOTYPE_LOCI, 'source', 'key'),
            'darknessByDark' => $darknessByDark,
        ];
    }

    /**
     * 座位の野生型アレル（LOCI でコード 0 のアレル）
     */
    public static function wildAllele(string $locusKey): ?string
    {
        $key = array_search(0, self::LOCI[$locusKey]['alleles'] ?? [], true);
        return $key === false ? null : (string)$key;
    }

    /**
     * アレルの組が表現型に現れるか
     * 優性（AD/AID）は変異アレルが1つでもあれば、劣性は全アレルが変異のとき表現する。
     * 伴性のメス（ヘミ接合）は1アレルで判定する。
     */
    public static function isExpressedAlleles(string $locusKey, array $alleles): bool
    {
        if (!$alleles) return false;
        $wild = self::wildAllele($locusKey);
        $type = self::LOCI[$locusKey]['type'] ?? 'AR';
        $mutant = array_filter($alleles, fn($a) => $a !== $wild);
        if ($type === 'AD' || $type === 'AID') {
            return count($mutant) > 0;
        }
        return count($mutant) === count($alleles);
    }

    /**
     * INDEPENDENT_LOCI - 独立遺伝座位（v7.0 SSOT）
     *
     * 連鎖グループに属さず、独立分離する座位。
     * v6.8以前と同じ計算方法を維持。
     */
    public const INDEPENDENT_LOCI = [
        'violet',
        'fallow_pale',
        'fallow_bronze',
        'pied_dom',
        'pied_rec',
        'dilute',
        'edged',
        'orangeface',
        'pale_headed',
    ];

    /**
     * MARKET_AVAILABILITY - 市場での入手性分類（v7.0 SSOT）
     *
     * PathFinderで使用。祖となる発現個体の入手難易度を定義。
     *
     * 分類:
     * - 'easy': 容易に入手可能（一般的なブリーダーで常時入手可）
     * - 'normal': 普通（専門ブリーダーで入手可、やや待ち時間あり）
     * - 'difficult': 困難（希少、高価、入手に時間を要する）
     *
     * キー: 座位または変異を特定するキー
     *   - 基本: 座位名（例: 'ino', 'opaline'）
     *   - ダーク: 'dark_sf'（SF）, 'dark_df'（DF）で区別
     *   - パーブルー: 'parblue_aq'（アクア）, 'parblue_tq'（ターコイズ）で区別
     */
    public const MARKET_AVAILABILITY = [
        // 容易 (easy) - 市場で常時入手可能
        'green' => 'easy',           // グリーン（野生型）
        'ino' => 'easy',             // ルチノー
        'opaline' => 'easy',         // オパーリン
        'parblue_tq' => 'easy',      // ターコイズ
        'parblue_aq' => 'easy',      // アクア
        'dark_sf' => 'easy',         // ダークグリーン（SF）
        'cinnamon' => 'easy',        // シナモン

        // 普通 (normal) - 専門店で入手可能
        'pallid' => 'normal',        // パリッド
        'dark_df' => 'normal',       // オリーブ（DF）
        'parblue_sg' => 'normal',    // シーグリーン（aq/tq）

        // 困難 (difficult) - 希少、入手に時間を要する
        'violet' => 'difficult',     // バイオレット
        'fallow_pale' => 'difficult',    // ペールファロー
        'fallow_bronze' => 'difficult',  // ブロンズファロー
        'dilute' => 'difficult',     // ダイリュート
        'edged' => 'difficult',      // エッジド
    ];

    /**
     * ALBS（African Love Bird Society）の Peachfaced の出陳区分（2016 Show Classifications, Section 5〜13。区分名は原文どおり）。
     * 出典: https://africanlovebirdsociety.org/new-page →「2016-Classifications-One-Division」。
     * 同サイトの国際名との対照表（2019-08-05 作成の PDF）も同じ区分名。区分に無い色は各区分の AOC（Any Other Color）に入る。
     * 色の定義の 'albs' は、ここに区分がある色だけが持つ（無い色は null。albsClass() と internationalName() を参照）。
     */
    public const ALBS_PEACHFACED_CLASSES = [
        'Green Peachfaced' => [
            'Green',
            'Medium and Dark Green',
            'Orangefaced Green',
            'Orangefaced Medium and Dark Green',
            'Violet Green - All',
            'Edged Dilute Green - All',
            'Orangefaced Edged Dilute Green - All',
            'Lutino',
            'Orangefaced Lutino',
            'AOC Green Peachfaced',
        ],
        'Blue Peachfaced' => [
            'Par-Blue',
            'Medium and Dark Par-Blue',
            'Blue (True)',
            'Medium and Dark Blue (True)',
            'Whitefaced Par-Blue',
            'Medium and Dark Whitefaced Par-Blue',
            'Seagreen Par-Blue - All',
            'Yellowfaced Par-Blue - All',
            'Edged Dilute Par-Blue - All',
            'Whitefaced Edged Dilute Par-Blue - All',
            'Edged Dilute Blue (True) - All',
            'Creamino Par-Blue',
            'Whitefaced Par-Blue Creamino',
            'Seagreen Par-Blue Creamino',
            'Albino (True)',
            'AOC Blue Peachfaced',
        ],
        'Green Pied Peachfaced' => [
            'Pied Green',
            'Pied Medium and Dark Green',
            'Pied Orangefaced Green - All',
            'Heavy Pied Green',
            'Heavy Pied Medium and Dark Green',
            'Heavy Pied Orangefaced Green - All',
            'Pied Violet Green - All',
            'Pied American Cinnamon Green - All',
            'Pied Australian Cinnamon Green - All',
            'Dark-Eyed Clear Yellow (> 90 % Yellow)',
            'Orangefaced Dark-Eyed Clear Yellow (> 90 % Yellow)',
            'Pied Edged Dilute Green - All',
            'Pied Opaline Green - All',
            'AOC Green Pied Peachfaced',
        ],
        'Blue Pied Peachfaced' => [
            'Pied Par-Blue',
            'Pied Medium and Dark Par-Blue',
            'Pied Blue (True) - All',
            'Pied Whitefaced Par-Blue - All',
            'Pied Seagreen Par-Blue - All',
            'Heavy Pied Par-Blue',
            'Heavy Pied Medium and Dark Par-Blue',
            'Heavy Pied Whitefaced Par-Blue - All',
            'Heavy Pied Seagreen Par-Blue - All',
            'Pied Yellowfaced Par-Blue - All',
            'Pied Violet Par-Blue - All',
            'Pied Violet Blue (True) - All',
            'Pied American Cinnamon Par-Blue - All',
            'Pied American Cinnamon Blue (True) - All',
            'Pied Australian Cinnamon Par-Blue - All',
            'Pied Australian Cinnamon Blue (True) - All',
            'Dark-Eyed Clear Par-Blue ( >90% Cream ) - All',
            'Dark-Eyed Clear Blue (True) ( >90% Clear ) - All',
            'Pied Edged Dilute Par-Blue - All',
            'Pied Edged Dilute Blue (True) - All',
            'Pied Opaline Blue - All',
            'AOC Blue Pied Peachfaced',
        ],
        'American Cinnamon Peachfaced' => [
            'American Cinnamon Green',
            'American Cinnamon Medium and Dark Green',
            'American Cinnamon Orangefaced Green - All',
            'American Cinnamon Violet Green - All',
            'American Cinnamon Par-Blue',
            'American Cinnamon Medium and Dark Par-Blue',
            'American Cinnamon Blue (True) - All',
            'American Cinnamon Whitefaced Par-Blue - All',
            'American Cinnamon Seagreen Par-Blue - All',
            'American Cinnamon Yellowfaced Par-Blue - All',
            'American Cinnamon Violet Par-Blue - All',
            'American Cinnamon Violet Blue ( True ) - All',
            'AOC American Cinnamon Peachfaced',
        ],
        'Australian Cinnamon Peachfaced' => [
            'Australian Cinnamon Green',
            'Australian Cinnamon Medium and Dark Green',
            'Australian Cinnamon Orangefaced Green - All',
            'Australian Cinnamon Violet Green - All',
            'Australian Cinnamon Par-Blue',
            'Australian Cinnamon Medium and Dark Par-Blue',
            'Australian Cinnamon Blue (True) - All',
            'Australian Cinnamon Whitefaced Par-Blue - All',
            'Australian Cinnamon Seagreen Par-Blue - All',
            'Australian Cinnamon Yellowfaced Par-Blue - All',
            'Australian Cinnamon Violet Par-Blue - All',
            'Australian Cinnamon Violet Blue (True) - All',
            'AOC Australian Cinnamon Peachfaced',
        ],
        'Violet Blue Peachfaced' => [
            'Violet Par-Blue - All',
            'Violet Blue ( True ) - All',
            'Violet Whitefaced Par-Blue',
            'Violet Whitefaced Medium and Dark Par-Blue',
            'Violet Seagreen Par-Blue - All',
            'Violet Yellowfaced Par-Blue - All',
            'Violet Yellowfaced Seagreen Par-Blue - All',
            'Violet Edged Dilute Par-Blue - All',
            'Violet Edged Dilute Blue (True) - All',
            'Violet Seagreen Par-Blue Edged Dilute - All',
            'Violet Whitefaced Par-Blue Edged Dilute - All',
            'AOC Violet Blue Peachfaced',
        ],
        'Green Opaline Peachfaced' => [
            'Opaline Green',
            'Opaline Medium and Dark Green',
            'Opaline Orangfaced Green',
            'Opaline Orangefaced Medium and Dark Green',
            'Opaline Violet Green - All',
            'Opaline Edged Dilute Green - All',
            'Opaline Lutino',
            'Opaline Orangefaced Lutino',
            'Opaline American Cinnamon Green - All',
            'Opaline American Cinnamon Orangefaced Green - All',
            'Opaline Australian Cinnamon Green - All',
            'Opaline Australian Cinnamon Orangefaced Green - All',
            'AOC Green Opaline Peachfaced',
        ],
        'Blue Opaline Peachfaced' => [
            'Opaline Par-Blue',
            'Opaline Medium and Dark Par-Blue',
            'Opaline Blue (True)',
            'Opaline Medium and Dark Blue (True)',
            'Opaline Whitefaced Par-Blue',
            'Opaline Medium and Dark Whitefaced Par-Blue',
            'Opaline Seagreen Par-Blue - All',
            'Opaline Yellowfaced Par-Blue - All',
            'Opaline Yellowfaced Seagreen Par-Blue - All',
            'Opaline Edged Dilute Par-Blue - All',
            'Opaline Whitefaced Edged Dilute Par-Blue - All',
            'Opaline Edged Dilute Blue (True) - All',
            'Opaline Creamino',
            'Opaline Whitefaced Par-Blue Creamino',
            'Opaline Seagreen Par-Blue Creamino',
            'Opaline Violet Par-Blue - All',
            'Opaline Violet Blue (True) - All',
            'Opaline American Cinnamon Par-Blue - All',
            'Opaline American Cinnamon Blue (True) - All',
            'Opaline Australian Cinnamon Par-Blue - All',
            'Opaline Australian Cinnamon Blue (True) - All',
            'AOC Blue Opaline Peachfaced',
        ],
    ];

    public const COLOR_DEFINITIONS = [
        // 基底色（12色）
        'green'=>['ja'=>'グリーン','en'=>'Green','albs'=>'Green','genotype'=>['parblue'=>'++','dark'=>'dd'],'eye'=>'black','category'=>'green'],
        'darkgreen'=>['ja'=>'ダークグリーン','en'=>'Dark Green','albs'=>'Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd'],'eye'=>'black','category'=>'green'],
        'olive'=>['ja'=>'オリーブ','en'=>'Olive','albs'=>'Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD'],'eye'=>'black','category'=>'green'],
        'aqua'=>['ja'=>'アクア','en'=>'Aqua','albs'=>'Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd'],'eye'=>'black','category'=>'aqua'],
        'aqua_dark'=>['ja'=>'アクアダーク','en'=>'Aqua Dark','albs'=>'Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd'],'eye'=>'black','category'=>'aqua'],
        'aqua_olive'=>['ja'=>'アクアオリーブ','en'=>'Aqua Olive','albs'=>'Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD'],'eye'=>'black','category'=>'aqua'],
        'turquoise'=>['ja'=>'ターコイズ','en'=>'Turquoise','albs'=>'Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd'],'eye'=>'black','category'=>'turquoise'],
        'turquoise_dark'=>['ja'=>'ターコイズダーク','en'=>'Turquoise Dark','albs'=>'Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd'],'eye'=>'black','category'=>'turquoise'],
        'turquoise_olive'=>['ja'=>'ターコイズオリーブ','en'=>'Turquoise Olive','albs'=>'Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD'],'eye'=>'black','category'=>'turquoise'],
        'seagreen'=>['ja'=>'シーグリーン','en'=>'Seagreen','albs'=>'Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd'],'eye'=>'black','category'=>'seagreen'],
        'seagreen_dark'=>['ja'=>'シーグリーンダーク','en'=>'Seagreen Dark','albs'=>'Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd'],'eye'=>'black','category'=>'seagreen'],
        'seagreen_olive'=>['ja'=>'シーグリーンオリーブ','en'=>'Seagreen Olive','albs'=>'Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD'],'eye'=>'black','category'=>'seagreen'],
        // INO（4色）
        'lutino'=>['ja'=>'ルチノー','en'=>'Lutino','albs'=>'Lutino','genotype'=>['parblue'=>'++','ino'=>'inoino'],'eye'=>'red','category'=>'ino'],
        'creamino'=>['ja'=>'クリーミノ','en'=>'Creamino','albs'=>'Creamino Par-Blue','genotype'=>['parblue'=>'aqaq','ino'=>'inoino'],'eye'=>'red','category'=>'ino'],
        'creamino_seagreen'=>['ja'=>'クリーミノシーグリーン','en'=>'Creamino Seagreen','albs'=>'Seagreen Par-Blue Creamino','genotype'=>['parblue'=>'tqaq','ino'=>'inoino'],'eye'=>'red','category'=>'ino'],
        'pure_white'=>['ja'=>'ピュアホワイト','en'=>'Pure White','albs'=>'Whitefaced Par-Blue Creamino','genotype'=>['parblue'=>'tqtq','ino'=>'inoino'],'eye'=>'red','category'=>'ino'],
        // シナモンイノ（4色。cinnamon + ino。ALBS に区分が無く AOC。名前は国際名 cinnamon ino に合わせる）
        'cinnamon_ino_green'=>['ja'=>'シナモンイノグリーン','en'=>'Cinnamon Ino Green','albs'=>null,'genotype'=>['parblue'=>'++','cinnamon'=>'cincin','ino'=>'inoino'],'eye'=>'red','category'=>'ino','tier'=>2],
        'cinnamon_ino_aqua'=>['ja'=>'シナモンイノアクア','en'=>'Cinnamon Ino Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','cinnamon'=>'cincin','ino'=>'inoino'],'eye'=>'red','category'=>'ino','tier'=>2],
        'cinnamon_ino_turquoise'=>['ja'=>'シナモンイノターコイズ','en'=>'Cinnamon Ino Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','cinnamon'=>'cincin','ino'=>'inoino'],'eye'=>'red','category'=>'ino','tier'=>2],
        'cinnamon_ino_seagreen'=>['ja'=>'シナモンイノシーグリーン','en'=>'Cinnamon Ino Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','cinnamon'=>'cincin','ino'=>'inoino'],'eye'=>'red','category'=>'ino','tier'=>2],
        // Pallid（12色）
        'pallid_green'=>['ja'=>'パリッドグリーン','en'=>'Pallid Green','albs'=>'Australian Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_darkgreen'=>['ja'=>'パリッドダークグリーン','en'=>'Pallid Dark Green','albs'=>'Australian Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_olive'=>['ja'=>'パリッドオリーブ','en'=>'Pallid Olive','albs'=>'Australian Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_aqua'=>['ja'=>'パリッドアクア','en'=>'Pallid Aqua','albs'=>'Australian Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_aqua_dark'=>['ja'=>'パリッドアクアダーク','en'=>'Pallid Aqua Dark','albs'=>'Australian Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_aqua_olive'=>['ja'=>'パリッドアクアオリーブ','en'=>'Pallid Aqua Olive','albs'=>'Australian Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_turquoise'=>['ja'=>'パリッドターコイズ','en'=>'Pallid Turquoise','albs'=>'Australian Cinnamon Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_turquoise_dark'=>['ja'=>'パリッドターコイズダーク','en'=>'Pallid Turquoise Dark','albs'=>'Australian Cinnamon Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_turquoise_olive'=>['ja'=>'パリッドターコイズオリーブ','en'=>'Pallid Turquoise Olive','albs'=>'Australian Cinnamon Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_seagreen'=>['ja'=>'パリッドシーグリーン','en'=>'Pallid Seagreen','albs'=>'Australian Cinnamon Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_seagreen_dark'=>['ja'=>'パリッドシーグリーンダーク','en'=>'Pallid Seagreen Dark','albs'=>'Australian Cinnamon Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        'pallid_seagreen_olive'=>['ja'=>'パリッドシーグリーンオリーブ','en'=>'Pallid Seagreen Olive','albs'=>'Australian Cinnamon Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','ino'=>'pldpld'],'eye'=>'black','category'=>'pallid'],
        // Cinnamon（12色）
        'cinnamon_green'=>['ja'=>'シナモングリーン','en'=>'Cinnamon Green','albs'=>'American Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_darkgreen'=>['ja'=>'シナモンダークグリーン','en'=>'Cinnamon Dark Green','albs'=>'American Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_olive'=>['ja'=>'シナモンオリーブ','en'=>'Cinnamon Olive','albs'=>'American Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_aqua'=>['ja'=>'シナモンアクア','en'=>'Cinnamon Aqua','albs'=>'American Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_aqua_dark'=>['ja'=>'シナモンアクアダーク','en'=>'Cinnamon Aqua Dark','albs'=>'American Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_aqua_olive'=>['ja'=>'シナモンアクアオリーブ','en'=>'Cinnamon Aqua Olive','albs'=>'American Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_turquoise'=>['ja'=>'シナモンターコイズ','en'=>'Cinnamon Turquoise','albs'=>'American Cinnamon Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_turquoise_dark'=>['ja'=>'シナモンターコイズダーク','en'=>'Cinnamon Turquoise Dark','albs'=>'American Cinnamon Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_turquoise_olive'=>['ja'=>'シナモンターコイズオリーブ','en'=>'Cinnamon Turquoise Olive','albs'=>'American Cinnamon Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_seagreen'=>['ja'=>'シナモンシーグリーン','en'=>'Cinnamon Seagreen','albs'=>'American Cinnamon Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_seagreen_dark'=>['ja'=>'シナモンシーグリーンダーク','en'=>'Cinnamon Seagreen Dark','albs'=>'American Cinnamon Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        'cinnamon_seagreen_olive'=>['ja'=>'シナモンシーグリーンオリーブ','en'=>'Cinnamon Seagreen Olive','albs'=>'American Cinnamon Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','cinnamon'=>'cincin'],'eye'=>'black','category'=>'cinnamon'],
        // Opaline（12色）
        'opaline_green'=>['ja'=>'オパーリングリーン','en'=>'Opaline Green','albs'=>'Opaline Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_darkgreen'=>['ja'=>'オパーリンダークグリーン','en'=>'Opaline Dark Green','albs'=>'Opaline Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_olive'=>['ja'=>'オパーリンオリーブ','en'=>'Opaline Olive','albs'=>'Opaline Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_aqua'=>['ja'=>'オパーリンアクア','en'=>'Opaline Aqua','albs'=>'Opaline Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_aqua_dark'=>['ja'=>'オパーリンアクアダーク','en'=>'Opaline Aqua Dark','albs'=>'Opaline Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_aqua_olive'=>['ja'=>'オパーリンアクアオリーブ','en'=>'Opaline Aqua Olive','albs'=>'Opaline Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_turquoise'=>['ja'=>'オパーリンターコイズ','en'=>'Opaline Turquoise','albs'=>'Opaline Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_turquoise_dark'=>['ja'=>'オパーリンターコイズダーク','en'=>'Opaline Turquoise Dark','albs'=>'Opaline Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_turquoise_olive'=>['ja'=>'オパーリンターコイズオリーブ','en'=>'Opaline Turquoise Olive','albs'=>'Opaline Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_seagreen'=>['ja'=>'オパーリンシーグリーン','en'=>'Opaline Seagreen','albs'=>'Opaline Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_seagreen_dark'=>['ja'=>'オパーリンシーグリーンダーク','en'=>'Opaline Seagreen Dark','albs'=>'Opaline Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        'opaline_seagreen_olive'=>['ja'=>'オパーリンシーグリーンオリーブ','en'=>'Opaline Seagreen Olive','albs'=>'Opaline Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop'],'eye'=>'black','category'=>'opaline'],
        // Violet（8色）- parblue系のみ視覚発現
        'violet_aqua'=>['ja'=>'バイオレットアクア','en'=>'Violet Aqua','albs'=>'Violet Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_aqua_dark'=>['ja'=>'バイオレットアクアダーク','en'=>'Violet Aqua Dark','albs'=>'Violet Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_aqua_olive'=>['ja'=>'バイオレットアクアオリーブ','en'=>'Violet Aqua Olive','albs'=>'Violet Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_turquoise'=>['ja'=>'バイオレットターコイズ','en'=>'Violet Turquoise','albs'=>'Violet Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_turquoise_dark'=>['ja'=>'バイオレットターコイズダーク','en'=>'Violet Turquoise Dark','albs'=>'Violet Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_turquoise_olive'=>['ja'=>'バイオレットターコイズオリーブ','en'=>'Violet Turquoise Olive','albs'=>'Violet Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_seagreen'=>['ja'=>'バイオレットシーグリーン','en'=>'Violet Seagreen','albs'=>'Violet Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_seagreen_dark'=>['ja'=>'バイオレットシーグリーンダーク','en'=>'Violet Seagreen Dark','albs'=>'Violet Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        'violet_seagreen_olive'=>['ja'=>'バイオレットシーグリーンオリーブ','en'=>'Violet Seagreen Olive','albs'=>'Violet Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','violet'=>'Vv'],'eye'=>'black','category'=>'violet'],
        // Pale Fallow（12色）
        'fallow_pale_green'=>['ja'=>'ペールファローグリーン','en'=>'Pale Fallow Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_darkgreen'=>['ja'=>'ペールファローダークグリーン','en'=>'Pale Fallow Dark Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'Dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_olive'=>['ja'=>'ペールファローオリーブ','en'=>'Pale Fallow Olive','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'DD','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_aqua'=>['ja'=>'ペールファローアクア','en'=>'Pale Fallow Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_aqua_dark'=>['ja'=>'ペールファローアクアダーク','en'=>'Pale Fallow Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_aqua_olive'=>['ja'=>'ペールファローアクアオリーブ','en'=>'Pale Fallow Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        
                'fallow_pale_turquoise'=>['ja'=>'ペールファローターコイズ','en'=>'Pale Fallow Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_turquoise_dark'=>['ja'=>'ペールファローターコイズダーク','en'=>'Pale Fallow Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_turquoise_olive'=>['ja'=>'ペールファローターコイズオリーブ','en'=>'Pale Fallow Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_seagreen'=>['ja'=>'ペールファローシーグリーン','en'=>'Pale Fallow Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_seagreen_dark'=>['ja'=>'ペールファローシーグリーンダーク','en'=>'Pale Fallow Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],
        'fallow_pale_seagreen_olive'=>['ja'=>'ペールファローシーグリーンオリーブ','en'=>'Pale Fallow Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','fallow_pale'=>'flpflp'],'eye'=>'red','category'=>'fallow_pale'],

        // Bronze Fallow（12色）
        'fallow_bronze_green'=>['ja'=>'ブロンズファローグリーン','en'=>'Bronze Fallow Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_darkgreen'=>['ja'=>'ブロンズファローダークグリーン','en'=>'Bronze Fallow Dark Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'Dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_olive'=>['ja'=>'ブロンズファローオリーブ','en'=>'Bronze Fallow Olive','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'DD','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_aqua'=>['ja'=>'ブロンズファローアクア','en'=>'Bronze Fallow Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_aqua_dark'=>['ja'=>'ブロンズファローアクアダーク','en'=>'Bronze Fallow Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_aqua_olive'=>['ja'=>'ブロンズファローアクアオリーブ','en'=>'Bronze Fallow Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_turquoise'=>['ja'=>'ブロンズファローターコイズ','en'=>'Bronze Fallow Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_turquoise_dark'=>['ja'=>'ブロンズファローターコイズダーク','en'=>'Bronze Fallow Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_turquoise_olive'=>['ja'=>'ブロンズファローターコイズオリーブ','en'=>'Bronze Fallow Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_seagreen'=>['ja'=>'ブロンズファローシーグリーン','en'=>'Bronze Fallow Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_seagreen_dark'=>['ja'=>'ブロンズファローシーグリーンダーク','en'=>'Bronze Fallow Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        'fallow_bronze_seagreen_olive'=>['ja'=>'ブロンズファローシーグリーンオリーブ','en'=>'Bronze Fallow Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','fallow_bronze'=>'flbflb'],'eye'=>'red','category'=>'fallow_bronze'],
        // Dominant Pied（12色）
        'pied_dom_green'=>['ja'=>'ドミナントパイドグリーン','en'=>'Dominant Pied Green','albs'=>'Pied Green','genotype'=>['parblue'=>'++','dark'=>'dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_darkgreen'=>['ja'=>'ドミナントパイドダークグリーン','en'=>'Dominant Pied Dark Green','albs'=>'Pied Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_olive'=>['ja'=>'ドミナントパイドオリーブ','en'=>'Dominant Pied Olive','albs'=>'Pied Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_aqua'=>['ja'=>'ドミナントパイドアクア','en'=>'Dominant Pied Aqua','albs'=>'Pied Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_aqua_dark'=>['ja'=>'ドミナントパイドアクアダーク','en'=>'Dominant Pied Aqua Dark','albs'=>'Pied Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_aqua_olive'=>['ja'=>'ドミナントパイドアクアオリーブ','en'=>'Dominant Pied Aqua Olive','albs'=>'Pied Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_turquoise'=>['ja'=>'ドミナントパイドターコイズ','en'=>'Dominant Pied Turquoise','albs'=>'Pied Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_turquoise_dark'=>['ja'=>'ドミナントパイドターコイズダーク','en'=>'Dominant Pied Turquoise Dark','albs'=>'Pied Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_turquoise_olive'=>['ja'=>'ドミナントパイドターコイズオリーブ','en'=>'Dominant Pied Turquoise Olive','albs'=>'Pied Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_seagreen'=>['ja'=>'ドミナントパイドシーグリーン','en'=>'Dominant Pied Seagreen','albs'=>'Pied Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_seagreen_dark'=>['ja'=>'ドミナントパイドシーグリーンダーク','en'=>'Dominant Pied Seagreen Dark','albs'=>'Pied Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        'pied_dom_seagreen_olive'=>['ja'=>'ドミナントパイドシーグリーンオリーブ','en'=>'Dominant Pied Seagreen Olive','albs'=>'Pied Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'pied_dom'],
        // Recessive Pied（12色）
        'pied_rec_green'=>['ja'=>'レセッシブパイドグリーン','en'=>'Recessive Pied Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_darkgreen'=>['ja'=>'レセッシブパイドダークグリーン','en'=>'Recessive Pied Dark Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'Dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_olive'=>['ja'=>'レセッシブパイドオリーブ','en'=>'Recessive Pied Olive','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'DD','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_aqua'=>['ja'=>'レセッシブパイドアクア','en'=>'Recessive Pied Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_aqua_dark'=>['ja'=>'レセッシブパイドアクアダーク','en'=>'Recessive Pied Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_aqua_olive'=>['ja'=>'レセッシブパイドアクアオリーブ','en'=>'Recessive Pied Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_turquoise'=>['ja'=>'レセッシブパイドターコイズ','en'=>'Recessive Pied Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_turquoise_dark'=>['ja'=>'レセッシブパイドターコイズダーク','en'=>'Recessive Pied Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_turquoise_olive'=>['ja'=>'レセッシブパイドターコイズオリーブ','en'=>'Recessive Pied Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_seagreen'=>['ja'=>'レセッシブパイドシーグリーン','en'=>'Recessive Pied Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_seagreen_dark'=>['ja'=>'レセッシブパイドシーグリーンダーク','en'=>'Recessive Pied Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        'pied_rec_seagreen_olive'=>['ja'=>'レセッシブパイドシーグリーンオリーブ','en'=>'Recessive Pied Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_rec'=>'pipi'],'eye'=>'black','category'=>'pied_rec'],
        // Dilute（12色）
        'dilute_green'=>['ja'=>'ダイリュートグリーン','en'=>'Dilute Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_darkgreen'=>['ja'=>'ダイリュートダークグリーン','en'=>'Dilute Dark Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'Dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_olive'=>['ja'=>'ダイリュートオリーブ','en'=>'Dilute Olive','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'DD','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_aqua'=>['ja'=>'ダイリュートアクア','en'=>'Dilute Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_aqua_dark'=>['ja'=>'ダイリュートアクアダーク','en'=>'Dilute Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_aqua_olive'=>['ja'=>'ダイリュートアクアオリーブ','en'=>'Dilute Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_turquoise'=>['ja'=>'ダイリュートターコイズ','en'=>'Dilute Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_turquoise_dark'=>['ja'=>'ダイリュートターコイズダーク','en'=>'Dilute Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_turquoise_olive'=>['ja'=>'ダイリュートターコイズオリーブ','en'=>'Dilute Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_seagreen'=>['ja'=>'ダイリュートシーグリーン','en'=>'Dilute Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_seagreen_dark'=>['ja'=>'ダイリュートシーグリーンダーク','en'=>'Dilute Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        'dilute_seagreen_olive'=>['ja'=>'ダイリュートシーグリーンオリーブ','en'=>'Dilute Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','dilute'=>'dildil'],'eye'=>'black','category'=>'dilute'],
        // Orangefaced（12色）- parblue系はYellowfaced
        'orangefaced_green'=>['ja'=>'オレンジフェイスグリーン','en'=>'Orangefaced Green','albs'=>'Orangefaced Green','genotype'=>['parblue'=>'++','dark'=>'dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'orangefaced_darkgreen'=>['ja'=>'オレンジフェイスダークグリーン','en'=>'Orangefaced Dark Green','albs'=>'Orangefaced Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'orangefaced_olive'=>['ja'=>'オレンジフェイスオリーブ','en'=>'Orangefaced Olive','albs'=>'Orangefaced Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_aqua'=>['ja'=>'イエローフェイスアクア','en'=>'Yellowfaced Aqua','albs'=>'Yellowfaced Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_aqua_dark'=>['ja'=>'イエローフェイスアクアダーク','en'=>'Yellowfaced Aqua Dark','albs'=>'Yellowfaced Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_aqua_olive'=>['ja'=>'イエローフェイスアクアオリーブ','en'=>'Yellowfaced Aqua Olive','albs'=>'Yellowfaced Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_turquoise'=>['ja'=>'イエローフェイスターコイズ','en'=>'Yellowfaced Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_turquoise_dark'=>['ja'=>'イエローフェイスターコイズダーク','en'=>'Yellowfaced Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_turquoise_olive'=>['ja'=>'イエローフェイスターコイズオリーブ','en'=>'Yellowfaced Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_seagreen'=>['ja'=>'イエローフェイスシーグリーン','en'=>'Yellowfaced Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_seagreen_dark'=>['ja'=>'イエローフェイスシーグリーンダーク','en'=>'Yellowfaced Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        'yellowfaced_seagreen_olive'=>['ja'=>'イエローフェイスシーグリーンオリーブ','en'=>'Yellowfaced Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','orangeface'=>'ofof'],'eye'=>'black','category'=>'orangeface'],
        // Edged（12色）
        'edged_green'=>['ja'=>'エッジドグリーン','en'=>'Edged Green','albs'=>'Edged Dilute Green','genotype'=>['parblue'=>'++','dark'=>'dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_darkgreen'=>['ja'=>'エッジドダークグリーン','en'=>'Edged Dark Green','albs'=>'Edged Dilute Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_olive'=>['ja'=>'エッジドオリーブ','en'=>'Edged Olive','albs'=>'Edged Dilute Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_aqua'=>['ja'=>'エッジドアクア','en'=>'Edged Aqua','albs'=>'Edged Dilute Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_aqua_dark'=>['ja'=>'エッジドアクアダーク','en'=>'Edged Aqua Dark','albs'=>'Edged Dilute Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_aqua_olive'=>['ja'=>'エッジドアクアオリーブ','en'=>'Edged Aqua Olive','albs'=>'Edged Dilute Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_turquoise'=>['ja'=>'エッジドターコイズ','en'=>'Edged Turquoise','albs'=>'Edged Dilute Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_turquoise_dark'=>['ja'=>'エッジドターコイズダーク','en'=>'Edged Turquoise Dark','albs'=>'Edged Dilute Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_turquoise_olive'=>['ja'=>'エッジドターコイズオリーブ','en'=>'Edged Turquoise Olive','albs'=>'Edged Dilute Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_seagreen'=>['ja'=>'エッジドシーグリーン','en'=>'Edged Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_seagreen_dark'=>['ja'=>'エッジドシーグリーンダーク','en'=>'Edged Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        'edged_seagreen_olive'=>['ja'=>'エッジドシーグリーンオリーブ','en'=>'Edged Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','edged'=>'EdEd'],'eye'=>'black','category'=>'edged'],
        // Pale Headed（12色）
        'paleheaded_green'=>['ja'=>'ペールヘッドグリーン','en'=>'Pale Headed Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_darkgreen'=>['ja'=>'ペールヘッドダークグリーン','en'=>'Pale Headed Dark Green','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'Dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_olive'=>['ja'=>'ペールヘッドオリーブ','en'=>'Pale Headed Olive','albs'=>null,'genotype'=>['parblue'=>'++','dark'=>'DD','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_aqua'=>['ja'=>'ペールヘッドアクア','en'=>'Pale Headed Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_aqua_dark'=>['ja'=>'ペールヘッドアクアダーク','en'=>'Pale Headed Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_aqua_olive'=>['ja'=>'ペールヘッドアクアオリーブ','en'=>'Pale Headed Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_turquoise'=>['ja'=>'ペールヘッドターコイズ','en'=>'Pale Headed Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_turquoise_dark'=>['ja'=>'ペールヘッドターコイズダーク','en'=>'Pale Headed Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_turquoise_olive'=>['ja'=>'ペールヘッドターコイズオリーブ','en'=>'Pale Headed Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_seagreen'=>['ja'=>'ペールヘッドシーグリーン','en'=>'Pale Headed Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_seagreen_dark'=>['ja'=>'ペールヘッドシーグリーンダーク','en'=>'Pale Headed Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
        'paleheaded_seagreen_olive'=>['ja'=>'ペールヘッドシーグリーンオリーブ','en'=>'Pale Headed Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pale_headed'=>'phph'],'eye'=>'black','category'=>'pale_headed'],
            // ============================================================
        // TIER 2: Opaline + INO（4色）#157-160
        // ============================================================
        'opaline_lutino'=>['ja'=>'オパーリンルチノー','en'=>'Opaline Lutino','albs'=>'Opaline Lutino','genotype'=>['parblue'=>'++','opaline'=>'opop','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_opaline_ino','tier'=>2],
        'opaline_creamino'=>['ja'=>'オパーリンクリーミノ','en'=>'Opaline Creamino','albs'=>'Opaline Creamino','genotype'=>['parblue'=>'aqaq','opaline'=>'opop','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_opaline_ino','tier'=>2],
        'opaline_creamino_seagreen'=>['ja'=>'オパーリンクリーミノシーグリーン','en'=>'Opaline Creamino Seagreen','albs'=>'Opaline Seagreen Par-Blue Creamino','genotype'=>['parblue'=>'tqaq','opaline'=>'opop','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_opaline_ino','tier'=>2],
        'opaline_pure_white'=>['ja'=>'オパーリンピュアホワイト','en'=>'Opaline Pure White','albs'=>'Opaline Whitefaced Par-Blue Creamino','genotype'=>['parblue'=>'tqtq','opaline'=>'opop','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_opaline_ino','tier'=>2],
        // ============================================================
        // TIER 2: Opaline + Cinnamon（12色）#161-172
        // ============================================================
        'opaline_cinnamon_green'=>['ja'=>'オパーリンシナモングリーン','en'=>'Opaline Cinnamon Green','albs'=>'Opaline American Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_darkgreen'=>['ja'=>'オパーリンシナモンダークグリーン','en'=>'Opaline Cinnamon Dark Green','albs'=>'Opaline American Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_olive'=>['ja'=>'オパーリンシナモンオリーブ','en'=>'Opaline Cinnamon Olive','albs'=>'Opaline American Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_aqua'=>['ja'=>'オパーリンシナモンアクア','en'=>'Opaline Cinnamon Aqua','albs'=>'Opaline American Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_aqua_dark'=>['ja'=>'オパーリンシナモンアクアダーク','en'=>'Opaline Cinnamon Aqua Dark','albs'=>'Opaline American Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_aqua_olive'=>['ja'=>'オパーリンシナモンアクアオリーブ','en'=>'Opaline Cinnamon Aqua Olive','albs'=>'Opaline American Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_turquoise'=>['ja'=>'オパーリンシナモンターコイズ','en'=>'Opaline Cinnamon Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_turquoise_dark'=>['ja'=>'オパーリンシナモンターコイズダーク','en'=>'Opaline Cinnamon Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_turquoise_olive'=>['ja'=>'オパーリンシナモンターコイズオリーブ','en'=>'Opaline Cinnamon Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_seagreen'=>['ja'=>'オパーリンシナモンシーグリーン','en'=>'Opaline Cinnamon Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_seagreen_dark'=>['ja'=>'オパーリンシナモンシーグリーンダーク','en'=>'Opaline Cinnamon Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        'opaline_cinnamon_seagreen_olive'=>['ja'=>'オパーリンシナモンシーグリーンオリーブ','en'=>'Opaline Cinnamon Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_opaline_cinnamon','tier'=>2],
        // ============================================================
        // TIER 2: Opaline + Pallid（12色）#173-184
        // ============================================================
        'opaline_pallid_green'=>['ja'=>'オパーリンパリッドグリーン','en'=>'Opaline Pallid Green','albs'=>'Opaline Australian Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_darkgreen'=>['ja'=>'オパーリンパリッドダークグリーン','en'=>'Opaline Pallid Dark Green','albs'=>'Opaline Australian Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_olive'=>['ja'=>'オパーリンパリッドオリーブ','en'=>'Opaline Pallid Olive','albs'=>'Opaline Australian Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_aqua'=>['ja'=>'オパーリンパリッドアクア','en'=>'Opaline Pallid Aqua','albs'=>'Opaline Australian Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_aqua_dark'=>['ja'=>'オパーリンパリッドアクアダーク','en'=>'Opaline Pallid Aqua Dark','albs'=>'Opaline Australian Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_aqua_olive'=>['ja'=>'オパーリンパリッドアクアオリーブ','en'=>'Opaline Pallid Aqua Olive','albs'=>'Opaline Australian Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_turquoise'=>['ja'=>'オパーリンパリッドターコイズ','en'=>'Opaline Pallid Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_turquoise_dark'=>['ja'=>'オパーリンパリッドターコイズダーク','en'=>'Opaline Pallid Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_turquoise_olive'=>['ja'=>'オパーリンパリッドターコイズオリーブ','en'=>'Opaline Pallid Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_seagreen'=>['ja'=>'オパーリンパリッドシーグリーン','en'=>'Opaline Pallid Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_seagreen_dark'=>['ja'=>'オパーリンパリッドシーグリーンダーク','en'=>'Opaline Pallid Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        'opaline_pallid_seagreen_olive'=>['ja'=>'オパーリンパリッドシーグリーンオリーブ','en'=>'Opaline Pallid Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_opaline_pallid','tier'=>2],
        // ============================================================
        // TIER 2: Opaline + Violet（8色）#185-192
        // ============================================================
        'opaline_violet_aqua'=>['ja'=>'オパーリンバイオレットアクア','en'=>'Opaline Violet Aqua','albs'=>'Opaline Violet Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_aqua_dark'=>['ja'=>'オパーリンバイオレットアクアダーク','en'=>'Opaline Violet Aqua Dark','albs'=>'Opaline Violet Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_aqua_olive'=>['ja'=>'オパーリンバイオレットアクアオリーブ','en'=>'Opaline Violet Aqua Olive','albs'=>'Opaline Violet Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_turquoise'=>['ja'=>'オパーリンバイオレットターコイズ','en'=>'Opaline Violet Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_turquoise_dark'=>['ja'=>'オパーリンバイオレットターコイズダーク','en'=>'Opaline Violet Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_turquoise_olive'=>['ja'=>'オパーリンバイオレットターコイズオリーブ','en'=>'Opaline Violet Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_seagreen'=>['ja'=>'オパーリンバイオレットシーグリーン','en'=>'Opaline Violet Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_seagreen_dark'=>['ja'=>'オパーリンバイオレットシーグリーンダーク','en'=>'Opaline Violet Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],
        'opaline_violet_seagreen_olive'=>['ja'=>'オパーリンバイオレットシーグリーンオリーブ','en'=>'Opaline Violet Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','violet'=>'Vv'],'eye'=>'black','category'=>'tier2_opaline_violet','tier'=>2],

        // ============================================================
        // TIER 2: Opaline + Pied Dom（12色）#193-204
        // ============================================================
        'opaline_pied_dom_green'=>['ja'=>'オパーリンドミナントパイドグリーン','en'=>'Opaline Dominant Pied Green','albs'=>'Opaline Pied Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_darkgreen'=>['ja'=>'オパーリンドミナントパイドダークグリーン','en'=>'Opaline Dominant Pied Dark Green','albs'=>'Opaline Pied Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_olive'=>['ja'=>'オパーリンドミナントパイドオリーブ','en'=>'Opaline Dominant Pied Olive','albs'=>'Opaline Pied Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_aqua'=>['ja'=>'オパーリンドミナントパイドアクア','en'=>'Opaline Dominant Pied Aqua','albs'=>'Opaline Pied Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_aqua_dark'=>['ja'=>'オパーリンドミナントパイドアクアダーク','en'=>'Opaline Dominant Pied Aqua Dark','albs'=>'Opaline Pied Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_aqua_olive'=>['ja'=>'オパーリンドミナントパイドアクアオリーブ','en'=>'Opaline Dominant Pied Aqua Olive','albs'=>'Opaline Pied Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_turquoise'=>['ja'=>'オパーリンドミナントパイドターコイズ','en'=>'Opaline Dominant Pied Turquoise','albs'=>'Opaline Pied Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_turquoise_dark'=>['ja'=>'オパーリンドミナントパイドターコイズダーク','en'=>'Opaline Dominant Pied Turquoise Dark','albs'=>'Opaline Pied Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_turquoise_olive'=>['ja'=>'オパーリンドミナントパイドターコイズオリーブ','en'=>'Opaline Dominant Pied Turquoise Olive','albs'=>'Opaline Pied Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_seagreen'=>['ja'=>'オパーリンドミナントパイドシーグリーン','en'=>'Opaline Dominant Pied Seagreen','albs'=>'Opaline Pied Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_seagreen_dark'=>['ja'=>'オパーリンドミナントパイドシーグリーンダーク','en'=>'Opaline Dominant Pied Seagreen Dark','albs'=>'Opaline Pied Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        'opaline_pied_dom_seagreen_olive'=>['ja'=>'オパーリンドミナントパイドシーグリーンオリーブ','en'=>'Opaline Dominant Pied Seagreen Olive','albs'=>'Opaline Pied Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_opaline_pied_dom','tier'=>2],
        // ============================================================
        // TIER 2: Opaline + Orangefaced（12色）#205-216
        // ============================================================
        'opaline_orangefaced_green'=>['ja'=>'オパーリンオレンジフェイスグリーン','en'=>'Opaline Orangefaced Green','albs'=>'Opaline Orangefaced Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_orangefaced_darkgreen'=>['ja'=>'オパーリンオレンジフェイスダークグリーン','en'=>'Opaline Orangefaced Dark Green','albs'=>'Opaline Orangefaced Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_orangefaced_olive'=>['ja'=>'オパーリンオレンジフェイスオリーブ','en'=>'Opaline Orangefaced Olive','albs'=>'Opaline Orangefaced Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_aqua'=>['ja'=>'オパーリンイエローフェイスアクア','en'=>'Opaline Yellowfaced Aqua','albs'=>'Opaline Yellowfaced Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_aqua_dark'=>['ja'=>'オパーリンイエローフェイスアクアダーク','en'=>'Opaline Yellowfaced Aqua Dark','albs'=>'Opaline Yellowfaced Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_aqua_olive'=>['ja'=>'オパーリンイエローフェイスアクアオリーブ','en'=>'Opaline Yellowfaced Aqua Olive','albs'=>'Opaline Yellowfaced Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_turquoise'=>['ja'=>'オパーリンイエローフェイスターコイズ','en'=>'Opaline Yellowfaced Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_turquoise_dark'=>['ja'=>'オパーリンイエローフェイスターコイズダーク','en'=>'Opaline Yellowfaced Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_turquoise_olive'=>['ja'=>'オパーリンイエローフェイスターコイズオリーブ','en'=>'Opaline Yellowfaced Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_seagreen'=>['ja'=>'オパーリンイエローフェイスシーグリーン','en'=>'Opaline Yellowfaced Seagreen','albs'=>'Opaline Yellowfaced Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_seagreen_dark'=>['ja'=>'オパーリンイエローフェイスシーグリーンダーク','en'=>'Opaline Yellowfaced Seagreen Dark','albs'=>'Opaline Yellowfaced Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        'opaline_yellowfaced_seagreen_olive'=>['ja'=>'オパーリンイエローフェイスシーグリーンオリーブ','en'=>'Opaline Yellowfaced Seagreen Olive','albs'=>'Opaline Yellowfaced Dark Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_opaline_orangeface','tier'=>2],
        // ============================================================
        // TIER 2: Opaline + Edged（12色）#217-228
        // ============================================================
        'opaline_edged_green'=>['ja'=>'オパーリンエッジドグリーン','en'=>'Opaline Edged Green','albs'=>'Opaline Edged Dilute Green','genotype'=>['parblue'=>'++','dark'=>'dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_darkgreen'=>['ja'=>'オパーリンエッジドダークグリーン','en'=>'Opaline Edged Dark Green','albs'=>'Opaline Edged Dilute Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_olive'=>['ja'=>'オパーリンエッジドオリーブ','en'=>'Opaline Edged Olive','albs'=>'Opaline Edged Dilute Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_aqua'=>['ja'=>'オパーリンエッジドアクア','en'=>'Opaline Edged Aqua','albs'=>'Opaline Edged Dilute Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_aqua_dark'=>['ja'=>'オパーリンエッジドアクアダーク','en'=>'Opaline Edged Aqua Dark','albs'=>'Opaline Edged Dilute Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_aqua_olive'=>['ja'=>'オパーリンエッジドアクアオリーブ','en'=>'Opaline Edged Aqua Olive','albs'=>'Opaline Edged Dilute Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_turquoise'=>['ja'=>'オパーリンエッジドターコイズ','en'=>'Opaline Edged Turquoise','albs'=>'Opaline Edged Dilute Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_turquoise_dark'=>['ja'=>'オパーリンエッジドターコイズダーク','en'=>'Opaline Edged Turquoise Dark','albs'=>'Opaline Edged Dilute Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_turquoise_olive'=>['ja'=>'オパーリンエッジドターコイズオリーブ','en'=>'Opaline Edged Turquoise Olive','albs'=>'Opaline Edged Dilute Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_seagreen'=>['ja'=>'オパーリンエッジドシーグリーン','en'=>'Opaline Edged Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_seagreen_dark'=>['ja'=>'オパーリンエッジドシーグリーンダーク','en'=>'Opaline Edged Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        'opaline_edged_seagreen_olive'=>['ja'=>'オパーリンエッジドシーグリーンオリーブ','en'=>'Opaline Edged Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','opaline'=>'opop','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_opaline_edged','tier'=>2],
        // ============================================================
        // TIER 2: Violet + Pied Dom（8色）#229-236
        // ============================================================
        'violet_pied_dom_aqua'=>['ja'=>'バイオレットドミナントパイドアクア','en'=>'Violet Dominant Pied Aqua','albs'=>'Violet Pied Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_aqua_dark'=>['ja'=>'バイオレットドミナントパイドアクアダーク','en'=>'Violet Dominant Pied Aqua Dark','albs'=>'Violet Pied Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_aqua_olive'=>['ja'=>'バイオレットドミナントパイドアクアオリーブ','en'=>'Violet Dominant Pied Aqua Olive','albs'=>'Violet Pied Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_turquoise'=>['ja'=>'バイオレットドミナントパイドターコイズ','en'=>'Violet Dominant Pied Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_turquoise_dark'=>['ja'=>'バイオレットドミナントパイドターコイズダーク','en'=>'Violet Dominant Pied Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_turquoise_olive'=>['ja'=>'バイオレットドミナントパイドターコイズオリーブ','en'=>'Violet Dominant Pied Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_seagreen'=>['ja'=>'バイオレットドミナントパイドシーグリーン','en'=>'Violet Dominant Pied Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        'violet_pied_dom_seagreen_dark'=>['ja'=>'バイオレットドミナントパイドシーグリーンダーク','en'=>'Violet Dominant Pied Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','violet'=>'Vv','pied_dom'=>'Pi+'],'eye'=>'black','category'=>'tier2_violet_pied_dom','tier'=>2],
        // ============================================================
        // TIER 2: Violet + Edged（8色）#237-244
        // ============================================================
        'violet_edged_aqua'=>['ja'=>'バイオレットエッジドアクア','en'=>'Violet Edged Aqua','albs'=>'Violet Edged Dilute Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_aqua_dark'=>['ja'=>'バイオレットエッジドアクアダーク','en'=>'Violet Edged Aqua Dark','albs'=>'Violet Edged Dilute Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_aqua_olive'=>['ja'=>'バイオレットエッジドアクアオリーブ','en'=>'Violet Edged Aqua Olive','albs'=>'Violet Edged Dilute Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_turquoise'=>['ja'=>'バイオレットエッジドターコイズ','en'=>'Violet Edged Turquoise','albs'=>'Violet Edged Dilute Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_turquoise_dark'=>['ja'=>'バイオレットエッジドターコイズダーク','en'=>'Violet Edged Turquoise Dark','albs'=>'Violet Edged Dilute Medium Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'Dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_turquoise_olive'=>['ja'=>'バイオレットエッジドターコイズオリーブ','en'=>'Violet Edged Turquoise Olive','albs'=>'Violet Edged Dilute Dark Whitefaced Par-Blue','genotype'=>['parblue'=>'tqtq','dark'=>'DD','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_seagreen'=>['ja'=>'バイオレットエッジドシーグリーン','en'=>'Violet Edged Seagreen','albs'=>'Violet Edged Dilute Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        'violet_edged_seagreen_dark'=>['ja'=>'バイオレットエッジドシーグリーンダーク','en'=>'Violet Edged Seagreen Dark','albs'=>'Violet Edged Dilute Medium Seagreen Par-Blue','genotype'=>['parblue'=>'tqaq','dark'=>'Dd','violet'=>'Vv','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_violet_edged','tier'=>2],
        // ============================================================
        // TIER 2: Pied Dom + Cinnamon（12色）#245-256
        // ============================================================
        'pied_dom_cinnamon_green'=>['ja'=>'ドミナントパイドシナモングリーン','en'=>'Dominant Pied Cinnamon Green','albs'=>'Pied American Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_darkgreen'=>['ja'=>'ドミナントパイドシナモンダークグリーン','en'=>'Dominant Pied Cinnamon Dark Green','albs'=>'Pied American Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_olive'=>['ja'=>'ドミナントパイドシナモンオリーブ','en'=>'Dominant Pied Cinnamon Olive','albs'=>'Pied American Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_aqua'=>['ja'=>'ドミナントパイドシナモンアクア','en'=>'Dominant Pied Cinnamon Aqua','albs'=>'Pied American Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_aqua_dark'=>['ja'=>'ドミナントパイドシナモンアクアダーク','en'=>'Dominant Pied Cinnamon Aqua Dark','albs'=>'Pied American Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_aqua_olive'=>['ja'=>'ドミナントパイドシナモンアクアオリーブ','en'=>'Dominant Pied Cinnamon Aqua Olive','albs'=>'Pied American Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_turquoise'=>['ja'=>'ドミナントパイドシナモンターコイズ','en'=>'Dominant Pied Cinnamon Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_turquoise_dark'=>['ja'=>'ドミナントパイドシナモンターコイズダーク','en'=>'Dominant Pied Cinnamon Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_turquoise_olive'=>['ja'=>'ドミナントパイドシナモンターコイズオリーブ','en'=>'Dominant Pied Cinnamon Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_seagreen'=>['ja'=>'ドミナントパイドシナモンシーグリーン','en'=>'Dominant Pied Cinnamon Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_seagreen_dark'=>['ja'=>'ドミナントパイドシナモンシーグリーンダーク','en'=>'Dominant Pied Cinnamon Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        'pied_dom_cinnamon_seagreen_olive'=>['ja'=>'ドミナントパイドシナモンシーグリーンオリーブ','en'=>'Dominant Pied Cinnamon Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_dom'=>'Pi+','cinnamon'=>'cincin'],'eye'=>'black','category'=>'tier2_pied_dom_cinnamon','tier'=>2],
        // ============================================================
        // TIER 2: Pied Dom + Pallid（12色）#257-268
        // ============================================================
        'pied_dom_pallid_green'=>['ja'=>'ドミナントパイドパリッドグリーン','en'=>'Dominant Pied Pallid Green','albs'=>'Pied Australian Cinnamon Green','genotype'=>['parblue'=>'++','dark'=>'dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_darkgreen'=>['ja'=>'ドミナントパイドパリッドダークグリーン','en'=>'Dominant Pied Pallid Dark Green','albs'=>'Pied Australian Cinnamon Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_olive'=>['ja'=>'ドミナントパイドパリッドオリーブ','en'=>'Dominant Pied Pallid Olive','albs'=>'Pied Australian Cinnamon Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_aqua'=>['ja'=>'ドミナントパイドパリッドアクア','en'=>'Dominant Pied Pallid Aqua','albs'=>'Pied Australian Cinnamon Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_aqua_dark'=>['ja'=>'ドミナントパイドパリッドアクアダーク','en'=>'Dominant Pied Pallid Aqua Dark','albs'=>'Pied Australian Cinnamon Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_aqua_olive'=>['ja'=>'ドミナントパイドパリッドアクアオリーブ','en'=>'Dominant Pied Pallid Aqua Olive','albs'=>'Pied Australian Cinnamon Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_turquoise'=>['ja'=>'ドミナントパイドパリッドターコイズ','en'=>'Dominant Pied Pallid Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_turquoise_dark'=>['ja'=>'ドミナントパイドパリッドターコイズダーク','en'=>'Dominant Pied Pallid Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_turquoise_olive'=>['ja'=>'ドミナントパイドパリッドターコイズオリーブ','en'=>'Dominant Pied Pallid Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_seagreen'=>['ja'=>'ドミナントパイドパリッドシーグリーン','en'=>'Dominant Pied Pallid Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_seagreen_dark'=>['ja'=>'ドミナントパイドパリッドシーグリーンダーク','en'=>'Dominant Pied Pallid Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        'pied_dom_pallid_seagreen_olive'=>['ja'=>'ドミナントパイドパリッドシーグリーンオリーブ','en'=>'Dominant Pied Pallid Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_dom'=>'Pi+','ino'=>'pldpld'],'eye'=>'black','category'=>'tier2_pied_dom_pallid','tier'=>2],
        // ============================================================
        // TIER 2: Pied Dom + Orangefaced（12色）#269-280
        // ============================================================
        'pied_dom_orangefaced_green'=>['ja'=>'ドミナントパイドオレンジフェイスグリーン','en'=>'Dominant Pied Orangefaced Green','albs'=>'Pied Orangefaced Green','genotype'=>['parblue'=>'++','dark'=>'dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_orangefaced_darkgreen'=>['ja'=>'ドミナントパイドオレンジフェイスダークグリーン','en'=>'Dominant Pied Orangefaced Dark Green','albs'=>'Pied Orangefaced Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_orangefaced_olive'=>['ja'=>'ドミナントパイドオレンジフェイスオリーブ','en'=>'Dominant Pied Orangefaced Olive','albs'=>'Pied Orangefaced Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_aqua'=>['ja'=>'ドミナントパイドイエローフェイスアクア','en'=>'Dominant Pied Yellowfaced Aqua','albs'=>'Pied Yellowfaced Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_aqua_dark'=>['ja'=>'ドミナントパイドイエローフェイスアクアダーク','en'=>'Dominant Pied Yellowfaced Aqua Dark','albs'=>'Pied Yellowfaced Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_aqua_olive'=>['ja'=>'ドミナントパイドイエローフェイスアクアオリーブ','en'=>'Dominant Pied Yellowfaced Aqua Olive','albs'=>'Pied Yellowfaced Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_turquoise'=>['ja'=>'ドミナントパイドイエローフェイスターコイズ','en'=>'Dominant Pied Yellowfaced Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_turquoise_dark'=>['ja'=>'ドミナントパイドイエローフェイスターコイズダーク','en'=>'Dominant Pied Yellowfaced Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_turquoise_olive'=>['ja'=>'ドミナントパイドイエローフェイスターコイズオリーブ','en'=>'Dominant Pied Yellowfaced Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_seagreen'=>['ja'=>'ドミナントパイドイエローフェイスシーグリーン','en'=>'Dominant Pied Yellowfaced Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_seagreen_dark'=>['ja'=>'ドミナントパイドイエローフェイスシーグリーンダーク','en'=>'Dominant Pied Yellowfaced Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        'pied_dom_yellowfaced_seagreen_olive'=>['ja'=>'ドミナントパイドイエローフェイスシーグリーンオリーブ','en'=>'Dominant Pied Yellowfaced Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_dom'=>'Pi+','orangeface'=>'ofof'],'eye'=>'black','category'=>'tier2_pied_dom_orangeface','tier'=>2],
        // ============================================================
        // TIER 2: Pied Dom + Edged（12色）#281-292
        // ============================================================
        'pied_dom_edged_green'=>['ja'=>'ドミナントパイドエッジドグリーン','en'=>'Dominant Pied Edged Green','albs'=>'Pied Edged Dilute Green','genotype'=>['parblue'=>'++','dark'=>'dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_darkgreen'=>['ja'=>'ドミナントパイドエッジドダークグリーン','en'=>'Dominant Pied Edged Dark Green','albs'=>'Pied Edged Dilute Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_olive'=>['ja'=>'ドミナントパイドエッジドオリーブ','en'=>'Dominant Pied Edged Olive','albs'=>'Pied Edged Dilute Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_aqua'=>['ja'=>'ドミナントパイドエッジドアクア','en'=>'Dominant Pied Edged Aqua','albs'=>'Pied Edged Dilute Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_aqua_dark'=>['ja'=>'ドミナントパイドエッジドアクアダーク','en'=>'Dominant Pied Edged Aqua Dark','albs'=>'Pied Edged Dilute Medium Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'Dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_aqua_olive'=>['ja'=>'ドミナントパイドエッジドアクアオリーブ','en'=>'Dominant Pied Edged Aqua Olive','albs'=>'Pied Edged Dilute Dark Par-Blue','genotype'=>['parblue'=>'aqaq','dark'=>'DD','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_turquoise'=>['ja'=>'ドミナントパイドエッジドターコイズ','en'=>'Dominant Pied Edged Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_turquoise_dark'=>['ja'=>'ドミナントパイドエッジドターコイズダーク','en'=>'Dominant Pied Edged Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_turquoise_olive'=>['ja'=>'ドミナントパイドエッジドターコイズオリーブ','en'=>'Dominant Pied Edged Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_seagreen'=>['ja'=>'ドミナントパイドエッジドシーグリーン','en'=>'Dominant Pied Edged Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_seagreen_dark'=>['ja'=>'ドミナントパイドエッジドシーグリーンダーク','en'=>'Dominant Pied Edged Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        'pied_dom_edged_seagreen_olive'=>['ja'=>'ドミナントパイドエッジドシーグリーンオリーブ','en'=>'Dominant Pied Edged Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','pied_dom'=>'Pi+','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_pied_dom_edged','tier'=>2],
        // ============================================================
        // TIER 2: Orangefaced + INO（4色）#293-296
        // ============================================================
        'orangefaced_lutino'=>['ja'=>'オレンジフェイスルチノー','en'=>'Orangefaced Lutino','albs'=>'Orangefaced Lutino','genotype'=>['parblue'=>'++','orangeface'=>'ofof','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_orangeface_ino','tier'=>2],
        'yellowfaced_creamino'=>['ja'=>'イエローフェイスクリーミノ','en'=>'Yellowfaced Creamino','albs'=>null,'genotype'=>['parblue'=>'aqaq','orangeface'=>'ofof','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_orangeface_ino','tier'=>2],
        'yellowfaced_creamino_seagreen'=>['ja'=>'イエローフェイスクリーミノシーグリーン','en'=>'Yellowfaced Creamino Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','orangeface'=>'ofof','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_orangeface_ino','tier'=>2],
        'yellowfaced_pure_white'=>['ja'=>'イエローフェイスピュアホワイト','en'=>'Yellowfaced Pure White','albs'=>null,'genotype'=>['parblue'=>'tqtq','orangeface'=>'ofof','ino'=>'inoino'],'eye'=>'red','category'=>'tier2_orangeface_ino','tier'=>2],
        // ============================================================
        // TIER 2: Orangefaced + Edged（12色）#297-308
        // ============================================================
        'orangefaced_edged_green'=>['ja'=>'オレンジフェイスエッジドグリーン','en'=>'Orangefaced Edged Green','albs'=>'Orangefaced Edged Dilute Green','genotype'=>['parblue'=>'++','dark'=>'dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'orangefaced_edged_darkgreen'=>['ja'=>'オレンジフェイスエッジドダークグリーン','en'=>'Orangefaced Edged Dark Green','albs'=>'Orangefaced Edged Dilute Medium Green','genotype'=>['parblue'=>'++','dark'=>'Dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'orangefaced_edged_olive'=>['ja'=>'オレンジフェイスエッジドオリーブ','en'=>'Orangefaced Edged Olive','albs'=>'Orangefaced Edged Dilute Dark Green','genotype'=>['parblue'=>'++','dark'=>'DD','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_aqua'=>['ja'=>'イエローフェイスエッジドアクア','en'=>'Yellowfaced Edged Aqua','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_aqua_dark'=>['ja'=>'イエローフェイスエッジドアクアダーク','en'=>'Yellowfaced Edged Aqua Dark','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'Dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_aqua_olive'=>['ja'=>'イエローフェイスエッジドアクアオリーブ','en'=>'Yellowfaced Edged Aqua Olive','albs'=>null,'genotype'=>['parblue'=>'aqaq','dark'=>'DD','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_turquoise'=>['ja'=>'イエローフェイスエッジドターコイズ','en'=>'Yellowfaced Edged Turquoise','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_turquoise_dark'=>['ja'=>'イエローフェイスエッジドターコイズダーク','en'=>'Yellowfaced Edged Turquoise Dark','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'Dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_turquoise_olive'=>['ja'=>'イエローフェイスエッジドターコイズオリーブ','en'=>'Yellowfaced Edged Turquoise Olive','albs'=>null,'genotype'=>['parblue'=>'tqtq','dark'=>'DD','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_seagreen'=>['ja'=>'イエローフェイスエッジドシーグリーン','en'=>'Yellowfaced Edged Seagreen','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_seagreen_dark'=>['ja'=>'イエローフェイスエッジドシーグリーンダーク','en'=>'Yellowfaced Edged Seagreen Dark','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'Dd','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
        'yellowfaced_edged_seagreen_olive'=>['ja'=>'イエローフェイスエッジドシーグリーンオリーブ','en'=>'Yellowfaced Edged Seagreen Olive','albs'=>null,'genotype'=>['parblue'=>'tqaq','dark'=>'DD','orangeface'=>'ofof','edged'=>'EdEd'],'eye'=>'black','category'=>'tier2_orangeface_edged','tier'=>2],
]; // COLOR_DEFINITIONS終了

    /**
     * v7.3.11: 色キー部品の翻訳マップ（SSOT）
     * keyToLabel関数で使用 - PHPとJavaScript両方から参照
     */
    public const COLOR_PART_LABELS = [
        // 基本色
        'green' => ['ja' => 'グリーン', 'en' => 'Green'],
        'darkgreen' => ['ja' => 'ダークグリーン', 'en' => 'Dark Green'],
        'dark' => ['ja' => 'ダーク', 'en' => 'Dark'],
        'olive' => ['ja' => 'オリーブ', 'en' => 'Olive'],
        'aqua' => ['ja' => 'アクア', 'en' => 'Aqua'],
        'turquoise' => ['ja' => 'ターコイズ', 'en' => 'Turquoise'],
        'seagreen' => ['ja' => 'シーグリーン', 'en' => 'Seagreen'],
        // INO系
        'lutino' => ['ja' => 'ルチノー', 'en' => 'Lutino'],
        'creamino' => ['ja' => 'クリーミノ', 'en' => 'Creamino'],
        'pure' => ['ja' => 'ピュア', 'en' => 'Pure'],
        'white' => ['ja' => 'ホワイト', 'en' => 'White'],
        'ino' => ['ja' => 'イノ', 'en' => 'Ino'],
        // 変異
        'opaline' => ['ja' => 'オパーリン', 'en' => 'Opaline'],
        'cinnamon' => ['ja' => 'シナモン', 'en' => 'Cinnamon'],
        'pallid' => ['ja' => 'パリッド', 'en' => 'Pallid'],
        'fallow' => ['ja' => 'ファロー', 'en' => 'Fallow'],
        'pale' => ['ja' => 'ペール', 'en' => 'Pale'],
        'bronze' => ['ja' => 'ブロンズ', 'en' => 'Bronze'],
        'dilute' => ['ja' => 'ダイリュート', 'en' => 'Dilute'],
        'edged' => ['ja' => 'エッジド', 'en' => 'Edged'],
        'violet' => ['ja' => 'バイオレット', 'en' => 'Violet'],
        'pied' => ['ja' => 'パイド', 'en' => 'Pied'],
        'rec' => ['ja' => 'レセッシブ', 'en' => 'Recessive'],
        'dom' => ['ja' => 'ドミナント', 'en' => 'Dominant'],
        'orangeface' => ['ja' => 'オレンジフェイス', 'en' => 'Orangefaced'],
        'yellowface' => ['ja' => 'イエローフェイス', 'en' => 'Yellowfaced'],
        'headed' => ['ja' => 'ヘッド', 'en' => 'Headed'],
        'sf' => ['ja' => 'SF', 'en' => 'SF'],
        'df' => ['ja' => 'DF', 'en' => 'DF'],
        'dd' => ['ja' => 'DD', 'en' => 'DD'],
    ];

    /**
     * Tier2色を動的生成
     */
    public static function generateTier2Color(array $factors, string $baseKey): array
    {
        $base = self::COLOR_DEFINITIONS[$baseKey] ?? null;
        if (!$base) return [];
        
        $ja = '';
        $en = '';
        $genotype = $base['genotype'];
        $eye = $base['eye'];
        
        // 因子を優先順に処理
        $priority = ['opaline','cinnamon','pallid','fallow_pale','fallow_bronze','dilute','edged','violet','pied_dom','pied_rec','orangeface','pale_headed','ino'];
        
        foreach ($priority as $factor) {
            if (!in_array($factor, $factors)) continue;
            
            switch ($factor) {
                case 'opaline':
                    $ja .= 'オパーリン';
                    $en .= 'Opaline ';
                    $genotype['opaline'] = 'opop';
                    break;
                case 'cinnamon':
                    $ja .= 'シナモン';
                    $en .= 'Cinnamon ';
                    $genotype['cinnamon'] = 'cincin';
                    break;
                case 'pallid':
                    $ja .= 'パリッド';
                    $en .= 'Pallid ';
                    $genotype['ino'] = 'pldpld';
                    break;
                case 'violet':
                    $ja .= 'バイオレット';
                    $en .= 'Violet ';
                    $genotype['violet'] = 'Vv';
                    break;
                case 'pied_dom':
                    $ja .= 'ドミナントパイド';
                    $en .= 'Dominant Pied ';
                    $genotype['pied_dom'] = 'Pi+';
                    break;
                case 'orangeface':
                    $isParblue = ($genotype['parblue'] ?? '++') !== '++';
                    $ja .= $isParblue ? 'イエローフェイス' : 'オレンジフェイス';
                    $en .= $isParblue ? 'Yellowfaced ' : 'Orangefaced ';
                    $genotype['orangeface'] = 'ofof';
                    break;
                case 'edged':
                    $ja .= 'エッジド';
                    $en .= 'Edged ';
                    $genotype['edged'] = 'EdEd';
                    break;
                case 'ino':
                    $genotype['ino'] = 'inoino';
                    $eye = 'red';
                    break;
            }
        }
        
        $ja .= $base['ja'];
        $en .= $base['en'];
        
        return [
            'ja' => $ja,
            'en' => trim($en),
            'genotype' => $genotype,
            'eye' => $eye,
            'category' => 'tier2_generated',
        ];
    }
    
    /**
     * 全色取得（Tier1 + キャッシュ済みTier2）
     */
    public static function getAllColors(): array
    {
        return self::COLOR_DEFINITIONS;
    }
    
    /**
     * 色数取得
     */
    public static function getColorCount(): int
    {
        return count(self::COLOR_DEFINITIONS);
    }
    
    /**
     * カテゴリ別にグループ化
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];
        foreach (self::COLOR_DEFINITIONS as $key => $def) {
            $cat = $def['category'] ?? 'other';
            $grouped[$cat][$key] = $def;
        }
        return $grouped;
    }

    /**
     * カテゴリラベル取得
     */
        public static function categoryLabel(string $category, bool $isJa = true): string
    {
        $labels = [
            'green' => ['ja' => 'グリーン系', 'en' => 'Green Series'],
            'aqua' => ['ja' => 'アクア系', 'en' => 'Aqua Series'],
            'turquoise' => ['ja' => 'ターコイズ系', 'en' => 'Turquoise Series'],
            'seagreen' => ['ja' => 'シーグリーン系', 'en' => 'Seagreen Series'],
            'ino' => ['ja' => 'INO系', 'en' => 'INO Series'],
            'pallid' => ['ja' => 'パリッド系', 'en' => 'Pallid Series'],
            'cinnamon' => ['ja' => 'シナモン系', 'en' => 'Cinnamon Series'],
            'opaline' => ['ja' => 'オパーリン系', 'en' => 'Opaline Series'],
            'violet' => ['ja' => 'バイオレット系', 'en' => 'Violet Series'],
            'fallow_pale' => ['ja' => 'ペールファロー系', 'en' => 'Pale Fallow Series'],
            'fallow_bronze' => ['ja' => 'ブロンズファロー系', 'en' => 'Bronze Fallow Series'],
            'pied_dom' => ['ja' => 'ドミナントパイド系', 'en' => 'Dominant Pied Series'],
            'pied_rec' => ['ja' => 'レセッシブパイド系', 'en' => 'Recessive Pied Series'],
            'dilute' => ['ja' => 'ダイリュート系', 'en' => 'Dilute Series'],
            'orangeface' => ['ja' => 'オレンジ/イエローフェイス系', 'en' => 'Orange/Yellowfaced Series'],
            'edged' => ['ja' => 'エッジド系', 'en' => 'Edged Series'],
            'pale_headed' => ['ja' => 'ペールヘッド系', 'en' => 'Pale Headed Series'],
            'tier2_opaline_ino' => ['ja' => 'オパーリン+INO系', 'en' => 'Opaline + INO'],
            'tier2_opaline_cinnamon' => ['ja' => 'オパーリン+シナモン系', 'en' => 'Opaline + Cinnamon'],
            'tier2_opaline_pallid' => ['ja' => 'オパーリン+パリッド系', 'en' => 'Opaline + Pallid'],
            'tier2_opaline_violet' => ['ja' => 'オパーリン+バイオレット系', 'en' => 'Opaline + Violet'],
            'tier2_opaline_pied_dom' => ['ja' => 'オパーリン+ドミナントパイド系', 'en' => 'Opaline + Dominant Pied'],
            'tier2_opaline_orangeface' => ['ja' => 'オパーリン+オレンジフェイス系', 'en' => 'Opaline + Orangefaced'],
            'tier2_opaline_edged' => ['ja' => 'オパーリン+エッジド系', 'en' => 'Opaline + Edged'],
            'tier2_violet_pied_dom' => ['ja' => 'バイオレット+ドミナントパイド系', 'en' => 'Violet + Dominant Pied'],
            'tier2_violet_edged' => ['ja' => 'バイオレット+エッジド系', 'en' => 'Violet + Edged'],
            'tier2_pied_dom_cinnamon' => ['ja' => 'ドミナントパイド+シナモン系', 'en' => 'Dominant Pied + Cinnamon'],
            'tier2_pied_dom_pallid' => ['ja' => 'ドミナントパイド+パリッド系', 'en' => 'Dominant Pied + Pallid'],
            'tier2_pied_dom_orangeface' => ['ja' => 'ドミナントパイド+オレンジフェイス系', 'en' => 'Dominant Pied + Orangefaced'],
            'tier2_pied_dom_edged' => ['ja' => 'ドミナントパイド+エッジド系', 'en' => 'Dominant Pied + Edged'],
            'tier2_orangeface_ino' => ['ja' => 'オレンジフェイス+INO系', 'en' => 'Orangefaced + INO'],
            'tier2_orangeface_edged' => ['ja' => 'オレンジフェイス+エッジド系', 'en' => 'Orangefaced + Edged'],
            'tier3_dynamic' => ['ja' => '複合色（動的生成）', 'en' => 'Complex (Dynamic)'],
        ];
        return $labels[$category][$isJa ? 'ja' : 'en'] ?? $category;
    }
    /**
     * 全カテゴリラベル取得
     */
    public static function categoryLabels(bool $isJa = true): array
    {
        $cats = array_unique(array_column(self::COLOR_DEFINITIONS, 'category'));
        $result = [];
        foreach ($cats as $cat) {
            $result[$cat] = self::categoryLabel($cat, $isJa);
        }
        return $result;
    }

    /**
     * 色ラベル一覧（key => label）
     */
    public static function labels(bool $isJa = true): array
    {
        $result = [];
        foreach (self::COLOR_DEFINITIONS as $key => $def) {
            $result[$key] = $isJa ? $def['ja'] : $def['en'];
        }
        return $result;
    }

    /**
     * 任意のカラーキーをローカライズされたラベルに変換
     * COLOR_DEFINITIONSに存在しないキーも動的に変換する
     *
     * @param string $key カラーキー（例: 'cinnamon_violet_aqua'）
     * @param bool $isJa 日本語フラグ
     * @return string ローカライズされたラベル
     */
    public static function keyToLabel(string $key, bool $isJa = true): string
    {
        // 1. COLOR_DEFINITIONSに存在すればそれを返す
        if (isset(self::COLOR_DEFINITIONS[$key])) {
            return $isJa ? self::COLOR_DEFINITIONS[$key]['ja'] : self::COLOR_DEFINITIONS[$key]['en'];
        }

        // 2. キーを分解してパーツごとに変換（SSOT: COLOR_PART_LABELSを使用）
        $parts = explode('_', $key);
        $result = [];

        foreach ($parts as $part) {
            $partLower = strtolower($part);
            if (isset(self::COLOR_PART_LABELS[$partLower])) {
                $result[] = $isJa ? self::COLOR_PART_LABELS[$partLower]['ja'] : self::COLOR_PART_LABELS[$partLower]['en'];
            } else {
                // 未知のパーツはそのまま（先頭大文字化）
                $result[] = ucfirst($part);
            }
        }

        return implode($isJa ? '' : ' ', $result);
    }

    /**
     * ラベル→キー逆引き
     */
    public static function labelToKey(bool $isJa = true): array
    {
        $result = [];
        foreach (self::COLOR_DEFINITIONS as $key => $def) {
            $label = $isJa ? $def['ja'] : $def['en'];
            $result[$label] = $key;
        }
        return $result;
    }

    /**
     * カテゴリ別キー一覧
     */
    public static function groupedKeys(): array
    {
        $result = [];
        foreach (self::COLOR_DEFINITIONS as $key => $def) {
            $cat = $def['category'] ?? 'other';
            $result[$cat][] = $key;
        }
        return $result;
    }
        /**
     * Tier 3: 遺伝子型から動的に色名を生成
     * @param array $genotype 遺伝子型配列
     * @param bool $isJa 日本語フラグ
     * @return array ['ja'=>string, 'en'=>string, 'eye'=>string]
     */
    public static function generateColorName(array $genotype, bool $isJa = true): array
    {
        // 規則は genotypeData()['colorName']（COLOR_NAME_PARTS と COLOR_DEFINITIONS から導く）。birds.js も同じ表で名前を作る
        $r = self::genotypeData()['colorName'];
        $g = fn(string $l) => self::canonicalGenotype($l, (string)($genotype[$l] ?? self::wildGenotype($l)));
        $parblue = $g('parblue');
        $isParblue = $parblue !== $r['wildParblue'];
        $hasIno = in_array($g('ino'), $r['inoGenotypes'], true);
        $parts_ja = []; $parts_en = []; $eye = $r['eyeDefault'];
        foreach ($r['parts'] as $part) {
            if (!isset($genotype[$part['locus']]) || !in_array($g($part['locus']), $part['genotypes'], true)) continue;
            if ($part['noIno'] && $hasIno) continue;
            if ($part['parblueOnly'] && !$isParblue) continue;
            $name = ($isParblue && $part['parblueName']) ? $part['parblueName'] : $part['name'];
            $parts_ja[] = $name['ja'];
            $parts_en[] = $name['en'];
            if ($part['eye']) $eye = $part['eye'];
        }
        if ($hasIno) {
            $eye = $r['eyeIno'];
            $cinnamonIno = isset($genotype['cinnamon']) && in_array($g('cinnamon'), $r['cinnamonInoGenotypes'], true);
            $base = $cinnamonIno
                ? ($r['cinnamonInoNames'][$parblue] ?? $r['cinnamonInoNames'][$r['wildParblue']])
                : ($r['inoNames'][$parblue] ?? $r['inoDefault']);
        } else {
            $base = $r['baseNames'][$parblue][$g('dark')] ?? $r['baseDefault'];
        }
        $parts_ja[] = $base['ja'];
        $parts_en[] = $base['en'];

        return [
            'ja' => implode('', $parts_ja),
            'en' => implode(' ', $parts_en),
            'eye' => $eye,
        ];
    }

    /**
     * 遺伝子型から色キーを逆引き
     */
    public static function findColorKey(array $genotype): ?string
    {
        // 座位ごとの見た目（phenoForm）の署名で、色の定義を引く。表記の違い（ino+ / +ino、+Ed / Ed+、aq+ / +aq）と
        // 表現しないスプリットは見た目に影響しないので、どの書き方でも同じ色になる
        $canon = [];
        foreach ($genotype as $locus => $value) {
            if (isset(self::LOCI[$locus]) && is_string($value)) $canon[$locus] = self::canonicalGenotype($locus, $value);
        }
        $key = self::genotypeData()['colorKey']['bySignature'][self::genotypeSignature($canon)] ?? null;
        return $key === null ? null : (string)$key;
    }

    /**
     * 遺伝子型から色情報を解決（Tier1/2 → Tier3フォールバック）
     * @param array $genotype 遺伝子型配列
     * @return array 色定義配列
     */
    public static function resolveColor(array $genotype): array
    {
        // 1. 定義済み色を検索（Tier1/2）
        $key = self::findColorKey($genotype);
        if ($key !== null) {
            $color = self::COLOR_DEFINITIONS[$key];
            $color['key'] = $key;
            $color['tier'] = $color['tier'] ?? 1;
            return $color;
        }
        
        // 2. Tier 3: 動的生成
        $generated = self::generateColorName($genotype);
        return [
            'key' => null,
            'ja' => $generated['ja'],
            'en' => $generated['en'],
            'albs' => $generated['en'],
            'genotype' => $genotype,
            'eye' => $generated['eye'],
            'category' => 'tier3_dynamic',
            'tier' => 3,
        ];
    }
}

/**
 * GametesGenerator - 配偶子確率分布生成エンジン（v7.0）
 *
 * ハプロタイプ構成と組み換え率から配偶子の確率分布を計算。
 * 連鎖遺伝計算の共通エンジンとして、GeneticsCalculator, FamilyEstimatorV3,
 * PathFinder, Planner の4エンジンで使用される。
 *
 * @version 7.0
 */
final class GametesGenerator
{
    /**
     * Z染色体連鎖群から配偶子確率分布を生成（オス用）
     *
     * @param array $Z1 Z1染色体のハプロタイプ {cinnamon, ino, opaline}
     * @param array $Z2 Z2染色体のハプロタイプ {cinnamon, ino, opaline}
     * @return array 配偶子 => 確率 の連想配列
     */
    public static function generateZLinkedMale(array $Z1, array $Z2): array
    {
        $rates = AgapornisLoci::RECOMBINATION_RATES;
        $r1 = $rates['cinnamon-ino'];      // 0.03
        $r2 = $rates['ino-opaline'];       // 0.30

        // 組み換えなし（親型）
        $noRecomb = (1 - $r1) * (1 - $r2);  // 0.679

        // cin-ino間のみ組み換え
        $recomb1Only = $r1 * (1 - $r2);     // 0.021

        // ino-op間のみ組み換え
        $recomb2Only = (1 - $r1) * $r2;     // 0.291

        // 両方で組み換え（二重組み換え）
        $doubleRecomb = $r1 * $r2;          // 0.009

        $gametes = [];

        // 親型配偶子（組み換えなし）
        // Z1そのまま、Z2そのまま
        $gametes[self::haplotypeKey($Z1)] = ($gametes[self::haplotypeKey($Z1)] ?? 0) + $noRecomb / 2;
        $gametes[self::haplotypeKey($Z2)] = ($gametes[self::haplotypeKey($Z2)] ?? 0) + $noRecomb / 2;

        // cin-ino間組み換え型
        // Z1のcin + Z2のino,op → 新ハプロタイプ
        // Z2のcin + Z1のino,op → 新ハプロタイプ
        $recomb1_from_Z1 = ['cinnamon' => $Z1['cinnamon'], 'ino' => $Z2['ino'], 'opaline' => $Z2['opaline']];
        $recomb1_from_Z2 = ['cinnamon' => $Z2['cinnamon'], 'ino' => $Z1['ino'], 'opaline' => $Z1['opaline']];
        $gametes[self::haplotypeKey($recomb1_from_Z1)] = ($gametes[self::haplotypeKey($recomb1_from_Z1)] ?? 0) + $recomb1Only / 2;
        $gametes[self::haplotypeKey($recomb1_from_Z2)] = ($gametes[self::haplotypeKey($recomb1_from_Z2)] ?? 0) + $recomb1Only / 2;

        // ino-op間組み換え型
        // Z1のcin,ino + Z2のop → 新ハプロタイプ
        // Z2のcin,ino + Z1のop → 新ハプロタイプ
        $recomb2_from_Z1 = ['cinnamon' => $Z1['cinnamon'], 'ino' => $Z1['ino'], 'opaline' => $Z2['opaline']];
        $recomb2_from_Z2 = ['cinnamon' => $Z2['cinnamon'], 'ino' => $Z2['ino'], 'opaline' => $Z1['opaline']];
        $gametes[self::haplotypeKey($recomb2_from_Z1)] = ($gametes[self::haplotypeKey($recomb2_from_Z1)] ?? 0) + $recomb2Only / 2;
        $gametes[self::haplotypeKey($recomb2_from_Z2)] = ($gametes[self::haplotypeKey($recomb2_from_Z2)] ?? 0) + $recomb2Only / 2;

        // 二重組み換え型
        // Z1のcin + Z2のino + Z1のop
        // Z2のcin + Z1のino + Z2のop
        $doubleRecomb_from_Z1 = ['cinnamon' => $Z1['cinnamon'], 'ino' => $Z2['ino'], 'opaline' => $Z1['opaline']];
        $doubleRecomb_from_Z2 = ['cinnamon' => $Z2['cinnamon'], 'ino' => $Z1['ino'], 'opaline' => $Z2['opaline']];
        $gametes[self::haplotypeKey($doubleRecomb_from_Z1)] = ($gametes[self::haplotypeKey($doubleRecomb_from_Z1)] ?? 0) + $doubleRecomb / 2;
        $gametes[self::haplotypeKey($doubleRecomb_from_Z2)] = ($gametes[self::haplotypeKey($doubleRecomb_from_Z2)] ?? 0) + $doubleRecomb / 2;

        return $gametes;
    }

    /**
     * Z染色体連鎖群から配偶子確率分布を生成（メス用）
     *
     * メスはZ染色体が1本のため組み換えなし。
     * Z配偶子（50%）とW配偶子（50%）を生成。
     *
     * @param array $Z1 Z染色体のハプロタイプ {cinnamon, ino, opaline}
     * @return array 配偶子 => 確率 の連想配列
     */
    public static function generateZLinkedFemale(array $Z1): array
    {
        return [
            self::haplotypeKey($Z1) => 0.5,
            'W' => 0.5,  // W染色体（伴性座位なし）
        ];
    }

    /**
     * 常染色体連鎖群から配偶子確率分布を生成
     *
     * @param array $chr1 染色体1のハプロタイプ {dark, parblue}
     * @param array $chr2 染色体2のハプロタイプ {dark, parblue}
     * @return array 配偶子 => 確率 の連想配列
     */
    public static function generateAutosomal1(array $chr1, array $chr2): array
    {
        $rates = AgapornisLoci::RECOMBINATION_RATES;
        $r = $rates['dark-parblue'];  // 0.07

        $gametes = [];

        // 親型（組み換えなし）: 各50% × (1-r)
        $noRecomb = 1 - $r;  // 0.93
        $gametes[self::autosomalKey($chr1)] = ($gametes[self::autosomalKey($chr1)] ?? 0) + $noRecomb / 2;
        $gametes[self::autosomalKey($chr2)] = ($gametes[self::autosomalKey($chr2)] ?? 0) + $noRecomb / 2;

        // 組み換え型: 各50% × r
        $recomb1 = ['dark' => $chr1['dark'], 'parblue' => $chr2['parblue']];
        $recomb2 = ['dark' => $chr2['dark'], 'parblue' => $chr1['parblue']];
        $gametes[self::autosomalKey($recomb1)] = ($gametes[self::autosomalKey($recomb1)] ?? 0) + $r / 2;
        $gametes[self::autosomalKey($recomb2)] = ($gametes[self::autosomalKey($recomb2)] ?? 0) + $r / 2;

        return $gametes;
    }

    /**
     * 独立座位の配偶子確率分布を生成
     *
     * 連鎖しない座位は従来通り独立分離。
     *
     * @param string $genotype 遺伝型文字列（例: 'Vv', '++', 'flpflp'）
     * @param string $locusKey 座位キー（LOCI定義に準拠）
     * @return array 配偶子 => 確率 の連想配列
     */
    public static function generateIndependent(string $genotype, string $locusKey): array
    {
        $locus = AgapornisLoci::LOCI[$locusKey] ?? null;
        if (!$locus) {
            return ['+' => 1.0];
        }

        $alleles = array_keys($locus['alleles']);

        // 遺伝型をパース（簡易版）
        $parsed = self::parseGenotype($genotype, $alleles);

        if ($parsed['a1'] === $parsed['a2']) {
            // ホモ接合
            return [$parsed['a1'] => 1.0];
        } else {
            // ヘテロ接合
            return [
                $parsed['a1'] => 0.5,
                $parsed['a2'] => 0.5,
            ];
        }
    }

    /**
     * ハプロタイプを一意のキー文字列に変換
     */
    private static function haplotypeKey(array $haplotype): string
    {
        return implode('|', [
            $haplotype['cinnamon'] ?? '+',
            $haplotype['ino'] ?? '+',
            $haplotype['opaline'] ?? '+',
        ]);
    }

    /**
     * 常染色体ハプロタイプを一意のキー文字列に変換
     */
    private static function autosomalKey(array $haplotype): string
    {
        return implode('|', [
            $haplotype['dark'] ?? 'd',
            $haplotype['parblue'] ?? '+',
        ]);
    }

    /**
     * キー文字列からハプロタイプ配列に復元
     */
    public static function keyToHaplotype(string $key): array
    {
        $parts = explode('|', $key);
        if (count($parts) === 3) {
            return [
                'cinnamon' => $parts[0],
                'ino' => $parts[1],
                'opaline' => $parts[2],
            ];
        } elseif (count($parts) === 2) {
            return [
                'dark' => $parts[0],
                'parblue' => $parts[1],
            ];
        }
        return [];
    }

    /**
     * 遺伝型文字列をパース
     */
    private static function parseGenotype(string $genotype, array $alleles): array
    {
        // 代表的なパターンをハンドル
        if ($genotype === '++') {
            return ['a1' => '+', 'a2' => '+'];
        }

        // VV, Vv, vv パターン
        if (preg_match('/^([A-Z])([A-Z])$/i', $genotype, $m)) {
            return ['a1' => $m[1], 'a2' => $m[2]];
        }

        // +x, xx パターン
        if (preg_match('/^\+([a-z]+)$/', $genotype, $m)) {
            return ['a1' => '+', 'a2' => $m[1]];
        }
        if (preg_match('/^([a-z]+)([a-z]+)$/', $genotype, $m)) {
            return ['a1' => $m[1], 'a2' => $m[2]];
        }

        return ['a1' => '+', 'a2' => '+'];
    }

    /**
     * 配偶子確率分布を正規化（合計が1.0になるように）
     */
    public static function normalize(array $gametes): array
    {
        $total = array_sum($gametes);
        if ($total <= 0) {
            return $gametes;
        }
        return array_map(fn($p) => $p / $total, $gametes);
    }

    /**
     * 2つの配偶子分布を掛け合わせて子の遺伝型分布を生成
     *
     * @param array $sireGametes 父の配偶子分布
     * @param array $damGametes 母の配偶子分布
     * @return array 子の遺伝型 => 確率 の連想配列
     */
    public static function combineGametes(array $sireGametes, array $damGametes): array
    {
        $offspring = [];
        foreach ($sireGametes as $sireG => $sireP) {
            foreach ($damGametes as $damG => $damP) {
                // 遺伝型キーを生成（順序を正規化）
                $genotypeKey = self::makeGenotypeKey($sireG, $damG);
                $offspring[$genotypeKey] = ($offspring[$genotypeKey] ?? 0) + $sireP * $damP;
            }
        }
        return $offspring;
    }

    /**
     * 2つの配偶子から遺伝型キーを生成
     */
    private static function makeGenotypeKey(string $g1, string $g2): string
    {
        // Wを含む場合は特別処理（メスのZ/W）
        if ($g1 === 'W') {
            return $g2 . '/W';
        }
        if ($g2 === 'W') {
            return $g1 . '/W';
        }
        // アルファベット順にソートして一貫性を保つ
        return $g1 <= $g2 ? "$g1/$g2" : "$g2/$g1";
    }
}

/**
 * FamilyEstimatorV3 - 一族マップからの遺伝子型推論エンジン
 * @version 6.8
 */
class FamilyEstimatorV3
{
    private array $familyMap = [];
    private ?array $targetBird = null;
    private string $targetPosition = '';

    /**
     * 家族情報から遺伝子型を推論
     *
     * @param array $familyMap 家族マップ
     * @param string $targetPosition 推論対象の位置
     * @return array 推論結果
     */
    public function estimate(array $familyMap, string $targetPosition): array
    {
        // v7.3.15: 空の家系図チェック
        if (empty($familyMap)) {
            return ['error' => '家系図データが空です'];
        }

        $this->familyMap = $familyMap;
        $this->targetPosition = $targetPosition;
        $this->targetBird = $this->getBirdAt($targetPosition);

        if (empty($this->targetBird)) {
            return ['error' => '推論対象の個体が見つかりません: ' . $targetPosition];
        }

        if (empty($this->targetBird['sex'])) {
            return ['error' => '推論対象の性別が必要です'];
        }

        $results = [];
        $notes = [];
        $confidenceSum = 0;

        // v6.8対応: AgapornisLoci::LOCI の全座位を処理
        foreach (AgapornisLoci::LOCI as $locusKey => $config) {
            $inference = $this->inferLocus($locusKey, $config);
            $results[] = $inference;
            $confidenceSum += $inference['topProbability'];
            
            if (!empty($inference['note'])) {
                $notes[] = $inference['note'];
            }
        }

        // テスト交配提案を生成
        $testBreedings = $this->generateTestBreedingProposals($results);

        // v7.0: 連鎖座位の相（Phase）を推論
        $linkagePhase = $this->inferLinkagePhase();

        return [
            'success' => true,
            'target' => [
                'position' => $targetPosition,
                'sex' => $this->targetBird['sex'],
                'phenotype' => $this->targetBird['phenotype'] ?? [],
            ],
            'loci' => $results,
            'notes' => $notes,
            'testBreedings' => $testBreedings,
            'overallConfidence' => round($confidenceSum / count(AgapornisLoci::LOCI), 1),
            // v7.0: 連鎖遺伝情報
            'linkage' => $linkagePhase,
        ];
    }

    /**
     * 位置から個体を取得
     */
    private function getBirdAt(string $position): ?array
    {
        // offspring_N 形式
        if (preg_match('/^offspring_(\d+)$/', $position, $m)) {
            $idx = (int)$m[1];
            return $this->familyMap['offspring'][$idx] ?? null;
        }
        // それ以外は直接キー
        return $this->familyMap[$position] ?? null;
    }

    /**
     * 単一座位の推論
     */
    private function inferLocus(string $locusKey, array $config): array
    {
        $sex = $this->targetBird['sex'];
        $isSexLinked = $config['sex_linked'] ?? false;

        // ===== 証拠収集 =====
        $selfEvidence = $this->getSelfEvidence($locusKey);
        $parentEvidence = $this->getParentEvidence($locusKey);
        $grandparentEvidence = $this->getGrandparentEvidence($locusKey);
        $offspringEvidence = $this->getOffspringEvidence($locusKey);
        $siblingEvidence = $this->getSiblingEvidence($locusKey);

        // ===== 確率計算 =====
        $candidates = $this->calculateProbabilities(
            $locusKey, $config, $sex, $isSexLinked,
            $selfEvidence, $parentEvidence, $grandparentEvidence,
            $offspringEvidence, $siblingEvidence
        );

        return $this->formatLocusResult($locusKey, $config, $candidates);
    }

    /**
     * 自身の表現型から証拠を抽出
     */
    private function getSelfEvidence(string $locusKey): array
    {
        $pheno = $this->targetBird['phenotype'] ?? [];
        $genotype = $this->targetBird['genotype'][$locusKey] ?? null;
        $tentative = $this->targetBird['tentativeGeno'][$locusKey] ?? null;

        return [
            'expressedAllele' => $this->detectAlleleFromPhenotype($locusKey, $pheno),
            'rulesOutExpression' => $this->phenotypeRulesOutExpression($locusKey, $pheno),
            'knownGenotype' => $genotype ?? $this->genotypeFromPhenotype($locusKey, $pheno),
            'tentativeGeno' => $tentative,
        ];
    }

    /**
     * 親からの証拠
     */
    private function getParentEvidence(string $locusKey): array
    {
        $evidence = ['sire' => null, 'dam' => null];
        $parents = $this->getParentsOf($this->targetPosition);

        foreach (['sire', 'dam'] as $parentType) {
            $parent = $parents[$parentType] ?? null;
            if ($parent) {
                $evidence[$parentType] = [
                    'expressedAllele' => $this->detectAlleleFromPhenotype($locusKey, $parent['phenotype'] ?? []),
                    'rulesOutExpression' => $this->phenotypeRulesOutExpression($locusKey, $parent['phenotype'] ?? []),
                    'knownGenotype' => $parent['genotype'][$locusKey]
                        ?? $this->genotypeFromPhenotype($locusKey, $parent['phenotype'] ?? []),
                    'tentativeGeno' => $parent['tentativeGeno'][$locusKey] ?? null,
                    'sex' => $parent['sex'] ?? ($parentType === 'sire' ? 'male' : 'female'),
                ];
            }
        }

        return $evidence;
    }

    /**
     * 祖父母からの証拠
     */
    private function getGrandparentEvidence(string $locusKey): array
    {
        $evidence = [];
        $gpPositions = [
            'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam',
            'sire_sire_sire', 'sire_sire_dam', 'sire_dam_sire', 'sire_dam_dam',
            'dam_sire_sire', 'dam_sire_dam', 'dam_dam_sire', 'dam_dam_dam'
        ];

        foreach ($gpPositions as $pos) {
            $gp = $this->familyMap[$pos] ?? null;
            if ($gp) {
                $evidence[$pos] = [
                    'expressedAllele' => $this->detectAlleleFromPhenotype($locusKey, $gp['phenotype'] ?? []),
                    'knownGenotype' => $gp['genotype'][$locusKey] ?? null,
                ];
            }
        }

        return $evidence;
    }

    /**
     * 指定位置の親を取得
     */
    private function getParentsOf(string $position): array
    {
        // 子供の親
        if (strpos($position, 'offspring') === 0) {
            return [
                'sire' => $this->familyMap['sire'] ?? null,
                'dam' => $this->familyMap['dam'] ?? null,
            ];
        }

        // sireの親
        if ($position === 'sire') {
            return [
                'sire' => $this->familyMap['sire_sire'] ?? null,
                'dam' => $this->familyMap['sire_dam'] ?? null,
            ];
        }

        // damの親
        if ($position === 'dam') {
            return [
                'sire' => $this->familyMap['dam_sire'] ?? null,
                'dam' => $this->familyMap['dam_dam'] ?? null,
            ];
        }

        // sire_sireの親
        if ($position === 'sire_sire') {
            return [
                'sire' => $this->familyMap['sire_sire_sire'] ?? null,
                'dam' => $this->familyMap['sire_sire_dam'] ?? null,
            ];
        }

        // sire_damの親
        if ($position === 'sire_dam') {
            return [
                'sire' => $this->familyMap['sire_dam_sire'] ?? null,
                'dam' => $this->familyMap['sire_dam_dam'] ?? null,
            ];
        }

        // dam_sireの親
        if ($position === 'dam_sire') {
            return [
                'sire' => $this->familyMap['dam_sire_sire'] ?? null,
                'dam' => $this->familyMap['dam_sire_dam'] ?? null,
            ];
        }

        // dam_damの親
        if ($position === 'dam_dam') {
            return [
                'sire' => $this->familyMap['dam_dam_sire'] ?? null,
                'dam' => $this->familyMap['dam_dam_dam'] ?? null,
            ];
        }

        return ['sire' => null, 'dam' => null];
    }

    /**
     * 子孫からの証拠（最強の拘束）
     */
    private function getOffspringEvidence(string $locusKey): array
    {
        $evidence = ['count' => 0, 'recessiveCount' => 0, 'recessiveAllele' => null, 'offspring' => []];

        // sire/damの場合のみ子孫証拠を収集
        if ($this->targetPosition !== 'sire' && $this->targetPosition !== 'dam') {
            return $evidence;
        }

        $offspring = $this->familyMap['offspring'] ?? [];
        $evidence['count'] = count($offspring);

        foreach ($offspring as $child) {
            $allele = $this->detectAlleleFromPhenotype($locusKey, $child['phenotype'] ?? []);
            $evidence['offspring'][] = [
                'sex' => $child['sex'] ?? 'unknown',
                'allele' => $allele,
                'genotype' => $child['genotype'][$locusKey]
                    ?? $this->genotypeFromPhenotype($locusKey, $child['phenotype'] ?? []),
            ];
            if ($allele) {
                $evidence['recessiveCount']++;
                $evidence['recessiveAllele'] = $allele;
            }
        }

        return $evidence;
    }

    /**
     * 兄弟からの証拠
     */
    private function getSiblingEvidence(string $locusKey): array
    {
        $evidence = ['count' => 0, 'recessiveCount' => 0, 'recessiveAllele' => null];

        // 子供の場合のみ兄弟証拠を収集
        if (strpos($this->targetPosition, 'offspring') !== 0) {
            return $evidence;
        }

        $targetIdx = (int)str_replace('offspring_', '', $this->targetPosition);
        $offspring = $this->familyMap['offspring'] ?? [];

        foreach ($offspring as $idx => $child) {
            if ($idx === $targetIdx) continue;

            $evidence['count']++;
            $allele = $this->detectAlleleFromPhenotype($locusKey, $child['phenotype'] ?? []);
            if ($allele) {
                $evidence['recessiveCount']++;
                $evidence['recessiveAllele'] = $allele;
            }
        }

        return $evidence;
    }

    /**
     * 表現型からアレルを検出
     * v6.8: COLOR_DEFINITIONSベースの検出
     */
    private function detectAlleleFromPhenotype(string $locusKey, array $phenotype): ?string
    {
        $baseColor = strtolower($phenotype['baseColor'] ?? '');
        $eyeColor = strtolower($phenotype['eyeColor'] ?? 'black');

        // COLOR_DEFINITIONSから遺伝子型を取得
        $colorDef = AgapornisLoci::COLOR_DEFINITIONS[$baseColor] ?? null;
        if ($colorDef && isset($colorDef['genotype'][$locusKey])) {
            $geno = $colorDef['genotype'][$locusKey];
            // ホモ接合の劣性アレルを返す
            if ($this->isHomozygousRecessive($locusKey, $geno)) {
                return $this->extractRecessiveAllele($locusKey, $geno);
            }
        }

        // フォールバック: 従来の文字列マッチング
        return $this->detectAlleleByPattern($locusKey, $baseColor, $eyeColor);
    }

    /**
     * ホモ接合劣性かどうか判定
     */
    private function isHomozygousRecessive(string $locusKey, string $genotype): bool
    {
        $recessivePatterns = [
            'parblue' => ['aqaq', 'tqtq', 'tqaq'],
            'ino' => ['inoino', 'inoW', 'pldpld', 'pldW'],
            'opaline' => ['opop', 'opW'],
            'cinnamon' => ['cincin', 'cinW'],
            'dark' => ['DD', 'Dd'], // 優性なので逆
            'violet' => ['VV', 'Vv'],
            'fallow_pale' => ['flpflp'],
            'fallow_bronze' => ['flbflb'],
            'pied_dom' => ['Pi+', 'PiPi'],
            'pied_rec' => ['pipi'],
            'dilute' => ['dildil'],
            'edged' => ['eded', 'EdEd'],
            'orangeface' => ['ofof'],
            'pale_headed' => ['phph'],
        ];

        return in_array($genotype, $recessivePatterns[$locusKey] ?? []);
    }

    /**
     * 劣性アレルを抽出
     */
    private function extractRecessiveAllele(string $locusKey, string $genotype): string
    {
        // 遺伝子型からアレルを抽出
        $alleleMap = [
            'aqaq' => 'aq', 'tqtq' => 'tq', 'tqaq' => 'tq/aq',
            'inoino' => 'ino', 'inoW' => 'ino', 'pldpld' => 'pld', 'pldW' => 'pld',
            'opop' => 'op', 'opW' => 'op',
            'cincin' => 'cin', 'cinW' => 'cin',
            'DD' => 'D', 'Dd' => 'D',
            'VV' => 'V', 'Vv' => 'V',
            'flpflp' => 'flp', 'flbflb' => 'flb',
            'Pi+' => 'Pi', 'PiPi' => 'Pi',
            'pipi' => 'pi',
            'dildil' => 'dil',
            'eded' => 'ed', 'EdEd' => 'Ed',
            'ofof' => 'of',
            'phph' => 'ph',
        ];

        return $alleleMap[$genotype] ?? $genotype;
    }

    /**
     * パターンマッチングによるアレル検出（フォールバック）
     */
    private function detectAlleleByPattern(string $locusKey, string $baseColor, string $eyeColor): ?string
    {
        switch ($locusKey) {
            case 'parblue':
                if (strpos($baseColor, 'aqua') !== false) return 'aq';
                if (strpos($baseColor, 'turquoise') !== false) return 'tq';
                if (strpos($baseColor, 'seagreen') !== false) return 'tq/aq';
                break;

            case 'ino':
                if (strpos($baseColor, 'lutino') !== false) return 'ino';
                if (strpos($baseColor, 'creamino') !== false) return 'ino';
                if (strpos($baseColor, 'pure_white') !== false) return 'ino';
                if (strpos($baseColor, 'pallid') !== false) return 'pld';
                if ($eyeColor === 'red') return 'ino';
                break;

            case 'opaline':
                if (strpos($baseColor, 'opaline') !== false) return 'op';
                break;

            case 'cinnamon':
                if (strpos($baseColor, 'cinnamon') !== false) return 'cin';
                break;

            case 'fallow_pale':
                if (strpos($baseColor, 'fallow_pale') !== false) return 'flp';
                break;

            case 'fallow_bronze':
                if (strpos($baseColor, 'fallow_bronze') !== false) return 'flb';
                break;

            case 'pied_dom':
                if (strpos($baseColor, 'pied_dom') !== false) return 'Pi';
                break;

            case 'pied_rec':
                if (strpos($baseColor, 'pied_rec') !== false) return 'pi';
                break;

            case 'dilute':
                if (strpos($baseColor, 'dilute') !== false) return 'dil';
                break;

            case 'edged':
                if (strpos($baseColor, 'edged') !== false) return 'ed';
                break;

            case 'orangeface':
                if (strpos($baseColor, 'orangeface') !== false || strpos($baseColor, 'yellowface') !== false) return 'of';
                break;

            case 'pale_headed':
                if (strpos($baseColor, 'paleheaded') !== false) return 'ph';
                break;
        }

        return null;
    }

    /**
     * 確率計算
     */
    private function calculateProbabilities(
        string $locusKey,
        array $config,
        string $sex,
        bool $isSexLinked,
        array $selfEvidence,
        array $parentEvidence,
        array $grandparentEvidence,
        array $offspringEvidence,
        array $siblingEvidence
    ): array {
        $candidates = [];
        $alleles = array_keys($config['alleles'] ?? ['+' => 0]);

        // 遺伝子型の全候補を生成
        if ($isSexLinked && $sex === 'female') {
            // メスはヘミ接合（ZW）
            foreach ($alleles as $a) {
                $geno = $a . 'W';
                $candidates[$geno] = ['genotype' => $geno, 'probability' => 100 / count($alleles)];
            }
        } else {
            // オスまたは常染色体（ホモ/ヘテロ）
            foreach ($alleles as $a1) {
                foreach ($alleles as $a2) {
                    $pair = [$a1, $a2];
                    sort($pair);
                    $geno = implode('', $pair);
                    if (!isset($candidates[$geno])) {
                        $candidates[$geno] = ['genotype' => $geno, 'probability' => 0];
                    }
                    $candidates[$geno]['probability'] += 100 / (count($alleles) * count($alleles));
                }
            }
        }

        // 1. 自己証拠による拘束
        if ($selfEvidence['knownGenotype']) {
            // 既知の遺伝子型があれば確定
            foreach ($candidates as $geno => &$data) {
                $data['probability'] = ($geno === $selfEvidence['knownGenotype']) ? 100 : 0;
            }
        } elseif ($selfEvidence['expressedAllele']) {
            // 表現型が出ている → その遺伝子型を含む候補のみ
            $allele = $selfEvidence['expressedAllele'];
            foreach ($candidates as $geno => &$data) {
                if (strpos($geno, $allele) === false && $geno !== $allele . 'W') {
                    $data['probability'] = 0;
                }
            }
        } elseif (!empty($selfEvidence['rulesOutExpression'])) {
            // 表現型に出ていない → その座位を表現する遺伝子型を除外
            foreach ($candidates as $geno => &$data) {
                $alleles = AgapornisLoci::splitGenotype($locusKey, (string)$geno);
                if ($alleles !== null && AgapornisLoci::isExpressedAlleles($locusKey, $alleles)) {
                    $data['probability'] = 0;
                }
            }
        }
        unset($data);

        // 2. 親証拠による拘束
        $candidates = $this->applyParentConstraint($candidates, $parentEvidence, $locusKey, $sex, $isSexLinked);

        // 3. 子孫証拠による拘束（最強）
        $candidates = $this->applyOffspringConstraint($candidates, $offspringEvidence, $locusKey, $sex, $isSexLinked);

        // 4. 兄弟証拠による拘束
        $candidates = $this->applySiblingConstraint($candidates, $siblingEvidence, $parentEvidence, $locusKey);

        // 正規化
        $total = array_sum(array_column($candidates, 'probability'));
        if ($total > 0) {
            foreach ($candidates as &$data) {
                $data['probability'] = round($data['probability'] / $total * 100, 1);
            }
        }

        // 確率順にソート
        uasort($candidates, fn($a, $b) => $b['probability'] <=> $a['probability']);

        return $candidates;
    }


    /**
     * 親拘束の適用（改善版）
     */
    private function applyParentConstraint(array $candidates, array $parentEvidence, string $locusKey, string $sex, bool $isSexLinked): array
    {
        $sire = $parentEvidence['sire'] ?? null;
        $dam = $parentEvidence['dam'] ?? null;

        if (!$sire && !$dam) return $candidates;

        // ===== 新規追加: 既知遺伝子型による完全拘束 =====
        $sireAlleles = $sire ? $this->extractAllelesFromGenotype($sire['knownGenotype'] ?? null, $locusKey) : null;
        $damAlleles = $dam ? $this->extractAllelesFromGenotype($dam['knownGenotype'] ?? null, $locusKey) : null;

        if ($sireAlleles !== null || $damAlleles !== null) {
            foreach ($candidates as $geno => &$data) {
                if (!$this->canInheritGenotype($geno, $sireAlleles, $damAlleles, $sex, $isSexLinked)) {
                    $data['probability'] = 0;
                }
            }
        }

        // ===== 既存ロジック: 伴性遺伝の特殊ケース =====
        if ($isSexLinked) {
            if ($sex === 'female' && $sire && $sire['expressedAllele']) {
                $fatherAllele = $sire['expressedAllele'];
                foreach ($candidates as $geno => &$data) {
                    if (strpos($geno, $fatherAllele) === false) {
                        $data['probability'] = 0;
                    }
                }
            }
            if ($sex === 'male' && $dam && $dam['expressedAllele']) {
                // メスは自分のZを息子全員に渡す → 母のアレルを持たない息子はありえない
                $motherAllele = $dam['expressedAllele'];
                foreach ($candidates as $geno => &$data) {
                    if (strpos($geno, $motherAllele) === false) {
                        $data['probability'] = 0;
                    }
                }
            }
        }
        unset($data);

        // ===== 両親とも表現なし、またはダーク（表現型から遺伝子型が決まる）: メンデル比で重み付け =====
        $bothUnexpressed = $sire && !$sire['expressedAllele'] && $dam && !$dam['expressedAllele'];
        if ($sire && $dam && ($bothUnexpressed || $locusKey === 'dark')) {
            $candidates = $this->applyMendelianWeights($candidates, $locusKey, $sex, $isSexLinked, $sire, $dam);
        }

        return $candidates;
    }

    /**
     * 表現型だけで遺伝子型が1つに決まる座位なら、その遺伝子型を返す
     * 現在はダーク因子のみ（不完全優性で dd / Dd / DD が見分けられる）。
     */
    private function genotypeFromPhenotype(string $locusKey, array $phenotype): ?string
    {
        if ($locusKey !== 'dark') return null;
        return AgapornisLoci::resolveDarkGenotype($phenotype);
    }

    /**
     * 表現型から「この座位を表現していない」と言い切れるか
     *
     * 色名が COLOR_DEFINITIONS にあり、その色がこの座位を表現しない場合に true。
     * ダーク・バイオレットは色名と別の入力（darkness）や系統色で見え方が変わるため対象外。
     * INO は他の座位の見た目を覆い隠すため、INO 個体では ino・parblue 以外を判定しない。
     */
    private function phenotypeRulesOutExpression(string $locusKey, array $phenotype): bool
    {
        if (in_array($locusKey, ['dark', 'violet'], true)) return false;
        $colorDef = AgapornisLoci::COLOR_DEFINITIONS[strtolower($phenotype['baseColor'] ?? '')] ?? null;
        if (!$colorDef) return false;
        $geno = $colorDef['genotype'] ?? [];
        if (!in_array($locusKey, ['ino', 'parblue'], true) && in_array($geno['ino'] ?? '', ['inoino', 'inoW'], true)) {
            return false;
        }
        if (isset($geno[$locusKey])) {
            $alleles = AgapornisLoci::splitGenotype($locusKey, $geno[$locusKey]);
            if ($alleles === null || AgapornisLoci::isExpressedAlleles($locusKey, $alleles)) return false;
        }
        return true;
    }

    /**
     * 親が子に渡すアレルの確率分布
     * 既知の遺伝子型があればそれを使い、なければ表現型と矛盾しない遺伝子型を一様に仮定する。
     */
    private function transmissionFromParent(array $parent, string $locusKey, bool $isSexLinked, bool $isDam): ?array
    {
        $alleleNames = array_map('strval', array_keys(AgapornisLoci::LOCI[$locusKey]['alleles'] ?? []));
        $hemizygous = $isSexLinked && $isDam;
        $known = $parent['knownGenotype'] ?? null;
        $knownAlleles = $known ? AgapornisLoci::splitGenotype($locusKey, (string)$known) : null;

        $genotypes = [];
        if ($knownAlleles !== null && count($knownAlleles) === ($hemizygous ? 1 : 2)) {
            $genotypes[] = $knownAlleles;
        } else {
            foreach ($alleleNames as $a) {
                if ($hemizygous) {
                    $genotypes[] = [$a];
                    continue;
                }
                foreach ($alleleNames as $b) {
                    $genotypes[] = [$a, $b];
                }
            }
            if (!empty($parent['rulesOutExpression'])) {
                $genotypes = array_values(array_filter(
                    $genotypes,
                    fn($g) => !AgapornisLoci::isExpressedAlleles($locusKey, $g)
                ));
            }
        }
        if (!$genotypes) return null;

        $dist = [];
        foreach ($genotypes as $g) {
            foreach ($g as $a) {
                $dist[$a] = ($dist[$a] ?? 0) + 1 / (count($genotypes) * count($g));
            }
        }
        return $dist;
    }

    /**
     * 両親から子の遺伝子型のメンデル比を求め、候補の確率に掛ける
     * 候補の初期値は一様分布なので、事前確率で割ってメンデル比に置き換える。
     * 入力が矛盾して全候補が 0 になる場合は、重み付け前の値を返す。
     */
    private function applyMendelianWeights(array $candidates, string $locusKey, string $sex, bool $isSexLinked, array $sire, array $dam): array
    {
        $n = count(AgapornisLoci::LOCI[$locusKey]['alleles'] ?? []);
        if ($n === 0) return $candidates;
        $isSexLinkedDaughter = $isSexLinked && $sex === 'female';

        $fromSire = $this->transmissionFromParent($sire, $locusKey, $isSexLinked, false);
        $fromDam = $isSexLinkedDaughter ? ['W' => 1.0] : $this->transmissionFromParent($dam, $locusKey, $isSexLinked, true);
        if ($fromSire === null || $fromDam === null) return $candidates;

        // 子の遺伝子型の分布（候補と同じ表記: ソートしたアレルを連結、伴性メスは aW）
        $expected = [];
        foreach ($fromSire as $a => $pa) {
            foreach ($fromDam as $b => $pb) {
                if ((string)$b === 'W') {
                    $key = $a . 'W';
                } else {
                    $pair = [(string)$a, (string)$b];
                    sort($pair);
                    $key = implode('', $pair);
                }
                $expected[$key] = ($expected[$key] ?? 0) + $pa * $pb;
            }
        }

        $weighted = $candidates;
        foreach ($weighted as $geno => &$data) {
            if ($isSexLinkedDaughter) {
                $prior = 1 / $n;
            } else {
                $alleles = AgapornisLoci::splitGenotype($locusKey, (string)$geno);
                if ($alleles === null || count($alleles) !== 2) continue;
                $prior = ($alleles[0] === $alleles[1] ? 1 : 2) / ($n * $n);
            }
            $data['probability'] *= ($expected[$geno] ?? 0) / $prior;
        }
        unset($data);

        $total = array_sum(array_column($weighted, 'probability'));
        return $total > 0 ? $weighted : $candidates;
    }

/**
 * 遺伝子型からアレルペアを抽出
 */
private function extractAllelesFromGenotype(?string $genotype, string $locusKey): ?array
{
    if (!$genotype) return null;
    
    // ヘミ接合（メス伴性）
    if (strpos($genotype, 'W') !== false) {
        $allele = str_replace('W', '', $genotype);
        return [$allele];
    }
    
    // ホモ接合パターン
    $homoPatterns = [
        '++' => ['+', '+'],
        'aqaq' => ['aq', 'aq'],
        'tqtq' => ['tq', 'tq'],
        'inoino' => ['ino', 'ino'],
        'pldpld' => ['pld', 'pld'],
        'opop' => ['op', 'op'],
        'cincin' => ['cin', 'cin'],
        'DD' => ['D', 'D'],
        'dd' => ['d', 'd'],
        'VV' => ['V', 'V'],
        'vv' => ['v', 'v'],
        'flpflp' => ['flp', 'flp'],
        'flbflb' => ['flb', 'flb'],
        'PiPi' => ['Pi', 'Pi'],
        'pipi' => ['pi', 'pi'],
        'dildil' => ['dil', 'dil'],
        'eded' => ['ed', 'ed'],
        'EdEd' => ['Ed', 'Ed'],
        'ofof' => ['of', 'of'],
        'phph' => ['ph', 'ph'],
    ];
    
    if (isset($homoPatterns[$genotype])) {
        return $homoPatterns[$genotype];
    }
    
    // ヘテロ接合パターン
    $heteroPatterns = [
        '+aq' => ['+', 'aq'],
        '+tq' => ['+', 'tq'],
        'tqaq' => ['tq', 'aq'],
        '+ino' => ['+', 'ino'],
        '+pld' => ['+', 'pld'],
        '+op' => ['+', 'op'],
        '+cin' => ['+', 'cin'],
        'Dd' => ['D', 'd'],
        'Vv' => ['V', 'v'],
        '+flp' => ['+', 'flp'],
        '+flb' => ['+', 'flb'],
        'Pi+' => ['Pi', '+'],
        '+pi' => ['+', 'pi'],
        '+dil' => ['+', 'dil'],
        '+ed' => ['+', 'ed'],
        'Ed+' => ['Ed', '+'],
        '+Ed' => ['+', 'Ed'],
        '+of' => ['+', 'of'],
        '+ph' => ['+', 'ph'],
    ];
    
    if (isset($heteroPatterns[$genotype])) {
        return $heteroPatterns[$genotype];
    }
    
    return null;
}

/**
 * 子の遺伝子型が親から遺伝可能か判定
 */
private function canInheritGenotype(string $childGeno, ?array $sireAlleles, ?array $damAlleles, string $sex, bool $isSexLinked): bool
{
    $childAlleles = $this->extractAllelesFromGenotype($childGeno, '');
    if (!$childAlleles) return true;
    
    if ($isSexLinked) {
        if ($sex === 'female') {
            if ($sireAlleles !== null) {
                $fromSire = false;
                foreach ($sireAlleles as $sa) {
                    if (in_array($sa, $childAlleles)) {
                        $fromSire = true;
                        break;
                    }
                }
                if (!$fromSire) return false;
            }
        } else {
            if ($sireAlleles !== null && $damAlleles !== null) {
                $possible = false;
                foreach ($sireAlleles as $sa) {
                    foreach ($damAlleles as $da) {
                        $pair = [$sa, $da];
                        sort($pair);
                        $testGeno = implode('', $pair);
                        if ($testGeno === $childGeno || $this->genotypeEquivalent($testGeno, $childGeno)) {
                            $possible = true;
                            break 2;
                        }
                    }
                }
                if (!$possible) return false;
            }
        }
    } else {
        if ($sireAlleles !== null && $damAlleles !== null) {
            $possible = false;
            foreach ($sireAlleles as $sa) {
                foreach ($damAlleles as $da) {
                    $pair = [$sa, $da];
                    sort($pair);
                    $testGeno = implode('', $pair);
                    if ($testGeno === $childGeno || $this->genotypeEquivalent($testGeno, $childGeno)) {
                        $possible = true;
                        break 2;
                    }
                }
            }
            if (!$possible) return false;
        } elseif ($sireAlleles !== null) {
            $hasFromSire = false;
            foreach ($sireAlleles as $sa) {
                if (strpos($childGeno, $sa) !== false) {
                    $hasFromSire = true;
                    break;
                }
            }
            if (!$hasFromSire) return false;
        } elseif ($damAlleles !== null) {
            $hasFromDam = false;
            foreach ($damAlleles as $da) {
                if (strpos($childGeno, $da) !== false) {
                    $hasFromDam = true;
                    break;
                }
            }
            if (!$hasFromDam) return false;
        }
    }
    
    return true;
}

/**
 * 遺伝子型の等価判定（表記揺れ対応）
 */
private function genotypeEquivalent(string $g1, string $g2): bool
{
    $normalize = function($g) {
        $g = str_replace(['++'], ['+/+'], $g);
        $parts = explode('/', $g);
        if (count($parts) === 1) {
            $chars = str_split($g);
            sort($chars);
            return implode('', $chars);
        }
        sort($parts);
        return implode('/', $parts);
    };
    
    return $normalize($g1) === $normalize($g2);
}


    /**
     * 子孫拘束の適用（最強の拘束）
     */
    private function applyOffspringConstraint(array $candidates, array $offspringEvidence, string $locusKey, string $sex, bool $isSexLinked): array
    {
        if ($offspringEvidence['count'] === 0) return $candidates;

        // 表現型から遺伝子型が決まる座位（ダーク）:
        // 配偶者のアレル（分かればその遺伝子型、なければ全アレル）と組んで、各子の遺伝子型を作れない候補を除く
        if ($locusKey === 'dark') {
            $allAlleles = array_map('strval', array_keys(AgapornisLoci::LOCI[$locusKey]['alleles'] ?? []));
            $mate = $this->familyMap[$this->targetPosition === 'sire' ? 'dam' : 'sire'] ?? null;
            $mateGeno = $mate ? ($mate['genotype'][$locusKey] ?? $this->genotypeFromPhenotype($locusKey, $mate['phenotype'] ?? [])) : null;
            $mateAlleles = ($mateGeno ? AgapornisLoci::splitGenotype($locusKey, (string)$mateGeno) : null) ?? $allAlleles;
            $before = $candidates;
            foreach ($offspringEvidence['offspring'] as $child) {
                $childAlleles = $child['genotype'] ? AgapornisLoci::splitGenotype($locusKey, (string)$child['genotype']) : null;
                if (!$childAlleles || count($childAlleles) !== 2) continue;
                sort($childAlleles);
                foreach ($candidates as $geno => &$data) {
                    $own = AgapornisLoci::splitGenotype($locusKey, (string)$geno) ?? [];
                    $possible = false;
                    foreach ($own as $x) {
                        foreach ($mateAlleles as $y) {
                            $pair = [$x, $y];
                            sort($pair);
                            if ($pair === $childAlleles) $possible = true;
                        }
                    }
                    if (!$possible) $data['probability'] = 0;
                }
                unset($data);
            }
            // 入力が矛盾して全候補が消える場合は、拘束前の値を残す
            return array_sum(array_column($candidates, 'probability')) > 0 ? $candidates : $before;
        }

        // 子に劣性形質が出た → 親は必ずその対立遺伝子を持つ
        if ($offspringEvidence['recessiveCount'] > 0 && $offspringEvidence['recessiveAllele']) {
            $allele = $offspringEvidence['recessiveAllele'];
            foreach ($candidates as $geno => &$data) {
                if (strpos($geno, $allele) === false) {
                    $data['probability'] = 0;
                }
            }
        }

        // 統計的拘束: N羽に劣性なし → スプリットでない確率が上がる
        if ($offspringEvidence['recessiveCount'] === 0 && $offspringEvidence['count'] >= 5) {
            $n = $offspringEvidence['count'];
            // 両親がスプリットなら25%で劣性が出る
            // N羽全員野生型の確率 = 0.75^N
            $probNoRecessive = pow(0.75, $n);
            foreach ($candidates as $geno => &$data) {
                // ヘテロ（スプリット）の確率を下げる
                if ($this->isHeterozygous($geno)) {
                    $data['probability'] *= $probNoRecessive;
                }
            }
        }

        return $candidates;
    }

    /**
     * 兄弟拘束の適用
     */
    private function applySiblingConstraint(array $candidates, array $siblingEvidence, array $parentEvidence, string $locusKey): array
    {
        if ($siblingEvidence['count'] === 0 || $locusKey === 'dark') return $candidates;

        // 兄弟に劣性形質 → 両親はスプリット確定 → 自分も2/3でスプリット
        if ($siblingEvidence['recessiveCount'] > 0 && $siblingEvidence['recessiveAllele']) {
            $allele = $siblingEvidence['recessiveAllele'];
            foreach ($candidates as $geno => &$data) {
                // 野生型ホモの確率は1/3、スプリットの確率は2/3
                if ($geno === '++' || $geno === '+W') {
                    $data['probability'] *= (1/3);
                } elseif (strpos($geno, $allele) !== false && strpos($geno, '+') !== false) {
                    $data['probability'] *= (2/3);
                }
            }
        }

        return $candidates;
    }

    /**
     * ヘテロ接合かどうか判定
     */
    private function isHeterozygous(string $genotype): bool
    {
        if (strpos($genotype, 'W') !== false) return false; // ヘミ接合
        if (strlen($genotype) < 2) return false;
        
        // +を含むヘテロ
        if (strpos($genotype, '+') !== false && $genotype !== '++') {
            return true;
        }
        
        // 異なるアレルのヘテロ
        $chars = str_split($genotype);
        return count(array_unique($chars)) > 1;
    }

    /**
     * 結果のフォーマット
     */
    private function formatLocusResult(string $locusKey, array $config, array $candidates): array
    {
        $candidateList = [];
        foreach ($candidates as $geno => $data) {
            if ($data['probability'] > 0) {
                $candidateList[] = [
                    'genotype' => $geno,
                    'probability' => $data['probability'],
                ];
            }
        }

        $topCandidate = reset($candidateList);
        $isConfirmed = $topCandidate && $topCandidate['probability'] >= 99;

        // 座位名を取得（v6.8対応: nameは配列）
        $locusName = $config['name'] ?? $locusKey;
        if (is_array($locusName)) {
            $locusName = $locusName['ja'] ?? $locusName['en'] ?? $locusKey;
        }

        return [
            'locusKey' => $locusKey,
            'locusName' => $locusName,
            'topGenotype' => $topCandidate['genotype'] ?? '?',
            'topProbability' => $topCandidate['probability'] ?? 0,
            'isConfirmed' => $isConfirmed,
            'candidates' => $candidateList,
            'note' => $this->generateNote($locusKey, $candidateList, $isConfirmed),
        ];
    }

    /**
     * 注釈を生成
     */
    private function generateNote(string $locusKey, array $candidates, bool $isConfirmed): string
    {
        if ($isConfirmed) {
            return '';
        }

        if (count($candidates) <= 1) {
            return '';
        }

        $top = $candidates[0] ?? null;
        if ($top && $top['probability'] < 50) {
            return "{$locusKey}: 複数の可能性あり（テスト交配推奨）";
        }

        return '';
    }

    /**
     * テスト交配提案を生成
     */
    private function generateTestBreedingProposals(array $results): array
    {
        $proposals = [];
        $sex = $this->targetBird['sex'];

        foreach ($results as $result) {
            if ($result['isConfirmed']) continue;
            if ($result['topProbability'] >= 90) continue;

            $locusKey = $result['locusKey'];
            $candidates = $result['candidates'];

            if (count($candidates) < 2) continue;

            // スプリット確認用のテスト交配
            $proposal = $this->proposeTestBreeding($locusKey, $candidates, $sex);
            if ($proposal) {
                $proposals[] = $proposal;
            }
        }

        return $proposals;
    }

    /**
     * 特定座位のテスト交配を提案
     */
    private function proposeTestBreeding(string $locusKey, array $candidates, string $sex): ?array
    {
        $isSexLinked = (AgapornisLoci::LOCI[$locusKey]['sex_linked'] ?? false);

        // 伴性遺伝座位の場合
        if ($isSexLinked) {
            if ($sex === 'male') {
                // オスのスプリット確認 → 発現メスと交配
                return [
                    'locus' => $locusKey,
                    'recommendation' => "発現メス（{$locusKey}）と交配",
                    'expectedResult' => '娘に発現 → スプリット確定',
                    'minOffspring' => 5,
                ];
            } else {
                // メスは表現型で確定するのでテスト不要
                return null;
            }
        }

        // 常染色体劣性の場合
        return [
            'locus' => $locusKey,
            'recommendation' => "発現個体（{$locusKey}ホモ）と交配",
            'expectedResult' => '子に発現 → スプリット確定',
            'minOffspring' => 8,
        ];
    }

    /**
     * 空の結果を生成
     */
    private function createEmptyResult(string $locusKey): array
    {
        return [
            'locusKey' => $locusKey,
            'locusName' => $locusKey,
            'topGenotype' => '?',
            'topProbability' => 0,
            'isConfirmed' => false,
            'candidates' => [],
            'note' => '座位情報が見つかりません',
        ];
    }

    // ========================================
    // v7.0: 連鎖遺伝の相（Phase）推論
    // ========================================

    /**
     * 連鎖座位群の相（Phase）を推論
     * @return array 連鎖群ごとの相情報
     */
    public function inferLinkagePhase(): array
    {
        $result = [
            'Z_linked' => $this->detectZLinkedPhase(),
            'autosomal_1' => $this->detectAutosomal1Phase(),
        ];

        return $result;
    }

    /**
     * Z染色体連鎖座位（cin, ino, opaline）の相を推論
     */
    private function detectZLinkedPhase(): array
    {
        $sex = $this->targetBird['sex'];
        $phenotype = $this->targetBird['phenotype'] ?? [];
        $baseColor = strtolower($phenotype['baseColor'] ?? '');

        // メスはヘミ接合（ZW）なので相の概念なし
        if ($sex === 'female') {
            $z1 = $this->detectZLinkedHaplotypeFromPhenotype($baseColor);
            return [
                'phase' => 'hemizygous',
                'Z1' => $z1,
                'Z2' => null,
                'confidence' => 100,
                'note' => 'メスはZ染色体1本のみ（相の概念なし）',
            ];
        }

        // オスの場合 - 表現型からの推論を試みる
        $expressed = $this->detectZLinkedExpressedLoci($baseColor);

        // 2つ以上の伴性形質が同時発現 → 各発現座位でホモ接合確定
        // cin/ino/op はいずれも劣性伴性形質のため、オスがこれらを「発現」するには
        // 該当座位でホモ接合（両Z染色体に同一の変異アレル）でなければならない。
        // 野生型アレルは「発現せず持っているだけ（スプリット）」の状態であり、
        // 発現とは両立しない。したがって Z2 も発現座位では Z1 と同じ変異アレルを持つ。
        if (count($expressed) >= 2) {
            $z1 = ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'];
            $z2 = ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'];
            foreach ($expressed as $locus => $allele) {
                $z1[$locus] = $allele;
                $z2[$locus] = $allele;
            }
            return [
                'phase' => 'homozygous',
                'Z1' => $z1,
                'Z2' => $z2,
                'confidence' => 100,
                'note' => '複数伴性形質同時発現 → 各発現座位でホモ接合確定（両Zが同一の変異アレルを保持）',
            ];
        }

        // 1つのみ発現 → ホモ接合または相不明
        if (count($expressed) === 1) {
            $locus = array_keys($expressed)[0];
            $allele = $expressed[$locus];

            // 子孫から相を推論
            $phaseFromOffspring = $this->inferZLinkedPhaseFromOffspring();
            if ($phaseFromOffspring['phase'] !== 'unknown') {
                return $phaseFromOffspring;
            }

            return [
                'phase' => 'unknown',
                'Z1' => [$locus => $allele] + ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
                'Z2' => [$locus => $allele] + ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
                'confidence' => 50,
                'note' => "ホモ{$locus}発現（スプリット座位不明）",
            ];
        }

        // 野生型表現 → スプリットの可能性は子孫から推論
        $phaseFromOffspring = $this->inferZLinkedPhaseFromOffspring();
        if ($phaseFromOffspring['phase'] !== 'unknown') {
            return $phaseFromOffspring;
        }

        // 親からの推論
        $phaseFromParents = $this->inferZLinkedPhaseFromParents();
        if ($phaseFromParents['phase'] !== 'unknown') {
            return $phaseFromParents;
        }

        return [
            'phase' => 'unknown',
            'Z1' => ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
            'Z2' => ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
            'confidence' => 0,
            'note' => '相情報なし（野生型表現）',
        ];
    }

    /**
     * 表現型からZ_linkedハプロタイプを検出
     */
    private function detectZLinkedHaplotypeFromPhenotype(string $baseColor): array
    {
        $haplotype = ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'];

        // Lacewing = cin + ino
        if (strpos($baseColor, 'lacewing') !== false) {
            $haplotype['cinnamon'] = 'cin';
            $haplotype['ino'] = 'ino';
        } elseif (strpos($baseColor, 'cinnamon') !== false) {
            $haplotype['cinnamon'] = 'cin';
        }

        // INO系
        if (strpos($baseColor, 'lutino') !== false ||
            strpos($baseColor, 'creamino') !== false ||
            strpos($baseColor, 'albino') !== false) {
            $haplotype['ino'] = 'ino';
        }
        if (strpos($baseColor, 'pallid') !== false) {
            $haplotype['ino'] = 'pld';
        }

        // Opaline
        if (strpos($baseColor, 'opaline') !== false) {
            $haplotype['opaline'] = 'op';
        }

        return $haplotype;
    }

    /**
     * 表現型から発現している伴性座位を検出
     */
    private function detectZLinkedExpressedLoci(string $baseColor): array
    {
        $expressed = [];

        if (strpos($baseColor, 'cinnamon') !== false || strpos($baseColor, 'lacewing') !== false) {
            $expressed['cinnamon'] = 'cin';
        }

        if (strpos($baseColor, 'lutino') !== false ||
            strpos($baseColor, 'creamino') !== false ||
            strpos($baseColor, 'albino') !== false ||
            strpos($baseColor, 'lacewing') !== false) {
            $expressed['ino'] = 'ino';
        }

        if (strpos($baseColor, 'pallid') !== false) {
            $expressed['ino'] = 'pld';
        }

        if (strpos($baseColor, 'opaline') !== false) {
            $expressed['opaline'] = 'op';
        }

        return $expressed;
    }

    /**
     * 子孫からZ_linked相を推論
     * Lacewing子が多い → 親はCis
     * cin子とino子が別々に出る → 親はTrans
     */
    private function inferZLinkedPhaseFromOffspring(): array
    {
        $offspring = $this->familyMap['offspring'] ?? [];
        if (empty($offspring)) {
            return ['phase' => 'unknown', 'confidence' => 0];
        }

        $stats = [
            'lacewing' => 0,       // cin + ino 同時発現
            'cin_only' => 0,       // cin のみ
            'ino_only' => 0,       // ino のみ
            'wild' => 0,           // 野生型
            'total' => 0,
        ];

        foreach ($offspring as $child) {
            $baseColor = strtolower($child['phenotype']['baseColor'] ?? '');
            $stats['total']++;

            if (strpos($baseColor, 'lacewing') !== false) {
                $stats['lacewing']++;
            } elseif (strpos($baseColor, 'cinnamon') !== false) {
                $stats['cin_only']++;
            } elseif (strpos($baseColor, 'lutino') !== false || strpos($baseColor, 'creamino') !== false) {
                $stats['ino_only']++;
            } else {
                $stats['wild']++;
            }
        }

        // 判定ロジック
        $total = $stats['total'];
        if ($total < 5) {
            return ['phase' => 'unknown', 'confidence' => 0, 'note' => '子数不足'];
        }

        $lacewingRatio = $stats['lacewing'] / $total;
        $separateRatio = ($stats['cin_only'] + $stats['ino_only']) / $total;

        // Lacewingが20%以上 → Cis可能性高い（期待値24.25%）
        if ($lacewingRatio >= 0.20 && $separateRatio < 0.05) {
            return [
                'phase' => 'cis',
                'Z1' => ['cinnamon' => 'cin', 'ino' => 'ino', 'opaline' => '+'],
                'Z2' => ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
                'confidence' => min(90, $lacewingRatio * 200),
                'note' => sprintf('Lacewing子%.0f%% → Cis推定', $lacewingRatio * 100),
            ];
        }

        // cin/inoが別々に出る → Trans可能性高い
        if ($separateRatio >= 0.30 && $stats['lacewing'] === 0) {
            return [
                'phase' => 'trans',
                'Z1' => ['cinnamon' => 'cin', 'ino' => '+', 'opaline' => '+'],
                'Z2' => ['cinnamon' => '+', 'ino' => 'ino', 'opaline' => '+'],
                'confidence' => min(90, $separateRatio * 150),
                'note' => sprintf('cin/ino別出現%.0f%% → Trans推定', $separateRatio * 100),
            ];
        }

        return ['phase' => 'unknown', 'confidence' => 0];
    }

    /**
     * 親からZ_linked相を推論
     */
    private function inferZLinkedPhaseFromParents(): array
    {
        $parents = $this->getParentsOf($this->targetPosition);
        $dam = $parents['dam'] ?? null;

        if (!$dam) {
            return ['phase' => 'unknown', 'confidence' => 0];
        }

        // 母親がLacewing → 息子はCis cin-ino/+ を母から継承
        $damColor = strtolower($dam['phenotype']['baseColor'] ?? '');
        if (strpos($damColor, 'lacewing') !== false) {
            return [
                'phase' => 'cis',
                'Z1' => ['cinnamon' => 'cin', 'ino' => 'ino', 'opaline' => '+'],
                'Z2' => ['cinnamon' => '+', 'ino' => '+', 'opaline' => '+'],
                'confidence' => 100,
                'note' => '母がLacewing → 息子のZ1はCis cin-ino',
            ];
        }

        return ['phase' => 'unknown', 'confidence' => 0];
    }

    /**
     * 常染色体連鎖座位（dark, parblue）の相を推論
     */
    private function detectAutosomal1Phase(): array
    {
        $phenotype = $this->targetBird['phenotype'] ?? [];
        $baseColor = strtolower($phenotype['baseColor'] ?? '');

        // 両座位のアレルを検出
        $darkAllele = $this->detectDarkAllele($baseColor);
        $parblueAllele = $this->detectParblueAllele($baseColor);

        // 両方とも野生型 → 相の概念なし
        if ($darkAllele === 'd' && $parblueAllele === '+') {
            return [
                'phase' => 'wild',
                'chr1' => ['dark' => 'd', 'parblue' => '+'],
                'chr2' => ['dark' => 'd', 'parblue' => '+'],
                'confidence' => 100,
                'note' => '両座位野生型',
            ];
        }

        // 子孫から相を推論
        $phaseFromOffspring = $this->inferAutosomal1PhaseFromOffspring();
        if ($phaseFromOffspring['phase'] !== 'unknown') {
            return $phaseFromOffspring;
        }

        // 相不明
        return [
            'phase' => 'unknown',
            'chr1' => ['dark' => $darkAllele, 'parblue' => $parblueAllele],
            'chr2' => ['dark' => 'd', 'parblue' => '+'],
            'confidence' => 0,
            'note' => '相情報不足',
        ];
    }

    /**
     * 表現型からdarkアレルを検出
     */
    private function detectDarkAllele(string $baseColor): string
    {
        if (strpos($baseColor, 'olive') !== false || strpos($baseColor, 'mauve') !== false) {
            return 'D'; // DF
        }
        if (strpos($baseColor, 'dark') !== false || strpos($baseColor, 'cobalt') !== false) {
            return 'D'; // SF (ヘテロ)
        }
        return 'd';
    }

    /**
     * 表現型からparblueアレルを検出
     */
    private function detectParblueAllele(string $baseColor): string
    {
        if (strpos($baseColor, 'aqua') !== false) return 'aq';
        if (strpos($baseColor, 'turquoise') !== false) return 'tq';
        if (strpos($baseColor, 'seagreen') !== false) return 'aq'; // tq/aq
        return '+';
    }

    /**
     * 子孫からautosomal_1相を推論
     */
    private function inferAutosomal1PhaseFromOffspring(): array
    {
        $offspring = $this->familyMap['offspring'] ?? [];
        if (count($offspring) < 5) {
            return ['phase' => 'unknown', 'confidence' => 0];
        }

        // dark+parblue同時発現 vs 別々発現をカウント
        $stats = ['combined' => 0, 'dark_only' => 0, 'parblue_only' => 0, 'total' => 0];

        foreach ($offspring as $child) {
            $baseColor = strtolower($child['phenotype']['baseColor'] ?? '');
            $stats['total']++;

            $hasDark = strpos($baseColor, 'dark') !== false || strpos($baseColor, 'olive') !== false;
            $hasParblue = strpos($baseColor, 'aqua') !== false || strpos($baseColor, 'turquoise') !== false;

            if ($hasDark && $hasParblue) {
                $stats['combined']++;
            } elseif ($hasDark) {
                $stats['dark_only']++;
            } elseif ($hasParblue) {
                $stats['parblue_only']++;
            }
        }

        // 7%組み換えなので、Cisなら93%が同時遺伝
        $total = $stats['total'];
        $combinedRatio = $stats['combined'] / $total;
        $separateRatio = ($stats['dark_only'] + $stats['parblue_only']) / $total;

        if ($combinedRatio >= 0.80) {
            return [
                'phase' => 'cis',
                'chr1' => ['dark' => 'D', 'parblue' => 'aq'],
                'chr2' => ['dark' => 'd', 'parblue' => '+'],
                'confidence' => min(90, $combinedRatio * 100),
                'note' => sprintf('Dark+Parblue同時遺伝%.0f%% → Cis推定', $combinedRatio * 100),
            ];
        }

        if ($separateRatio >= 0.80) {
            return [
                'phase' => 'trans',
                'chr1' => ['dark' => 'D', 'parblue' => '+'],
                'chr2' => ['dark' => 'd', 'parblue' => 'aq'],
                'confidence' => min(90, $separateRatio * 100),
                'note' => sprintf('Dark/Parblue別出現%.0f%% → Trans推定', $separateRatio * 100),
            ];
        }

        return ['phase' => 'unknown', 'confidence' => 0];
    }
}





class GeneticsCalculator
{
    /** 座位キー → calculateOffspring の入力欄名（f_〈欄〉 / m_〈欄〉） */
    public const LOCUS_FIELDS = [
        'parblue' => 'parblue', 'dark' => 'dark', 'violet' => 'vio',
        'fallow_pale' => 'flp', 'fallow_bronze' => 'flb', 'pied_dom' => 'pidom', 'pied_rec' => 'pirec',
        'dilute' => 'dil', 'edged' => 'ed', 'orangeface' => 'of', 'pale_headed' => 'ph',
        'ino' => 'ino', 'opaline' => 'opaline', 'cinnamon' => 'cinnamon',
    ];

    /**
     * 1座位だけを変えた交配の子の分布（性別ごとに合計1）。calculateOffspring の結果を座位ごとに集計する。
     * @return array ['male' => [正規の遺伝子型 => 確率], 'female' => [...]]
     */
    public function locusCross(string $locus, string $male, string $female): array
    {
        $field = self::LOCUS_FIELDS[$locus];
        $res = $this->calculateOffspring(['f_' . $field => $male, 'm_' . $field => $female, 'no_filter' => 1]);
        $out = ['male' => [], 'female' => []];
        foreach ($res['genotype'] as $r) {
            $g = $this->convertToGenotypeArrayV7($r['genotype'], $r['sex']);
            $v = AgapornisLoci::canonicalGenotype($locus, $g[$locus] ?? AgapornisLoci::wildGenotype($locus, $r['sex']));
            $out[$r['sex']][$v] = ($out[$r['sex']][$v] ?? 0) + $r['probability'];
        }
        foreach ($out as $sex => $dist) {
            $total = array_sum($dist);
            if ($total > 0) {
                foreach ($dist as $v => $p) $out[$sex][$v] = round($p / $total, 10);
            }
        }
        return $out;
    }

    /**
     * 連鎖する組（LINKAGE_GROUPS）ごとの交配表。親のハプロタイプの組から、組換え率を使って子の分布を出す
     * （calculateOffspring と同じ calcZLinkedV7 / calcAutosomal1V7）。index.php が LINKED_CROSS_TABLE として注入し、
     * JS は GenoData.linkedProbability で引く。子は座位ごとの正規の遺伝子型の組（loci の順に sep でつなぐ）で表す。
     * @return array [組] => ['loci', 'sep', 'pairSep', 'sexLinked', 'cross' => [父の鍵][母の鍵] => ['male' => [子の組 => 確率], 'female' => [...]]]
     */
    public static function linkedCrossTable(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $calc = new self();
        $sep = '|'; $pairSep = '/';
        $table = [];
        foreach (AgapornisLoci::LINKAGE_GROUPS as $group => $cfg) {
            $loci = $cfg['loci'];
            $sexLinked = (bool)(AgapornisLoci::LOCI[$loci[0]]['sex_linked'] ?? false);
            // すべてのハプロタイプ（座位ごとのアレルの組み合わせ）
            $haps = [[]];
            foreach ($loci as $l) {
                $next = [];
                foreach ($haps as $h) foreach (array_keys(AgapornisLoci::LOCI[$l]['alleles']) as $a) $next[] = $h + [$l => (string)$a];
                $haps = $next;
            }
            $key = fn(array $h) => implode($sep, array_map(fn($l) => $h[$l], $loci));
            $pairs = [];
            foreach ($haps as $i => $h1) {
                foreach ($haps as $j => $h2) {
                    if ($j < $i) continue;
                    $k = [$key($h1), $key($h2)]; usort($k, 'strcmp');
                    $pairs[implode($pairSep, $k)] = [$h1, $h2];
                }
            }
            $females = $sexLinked ? array_combine(array_map($key, $haps), array_map(fn($h) => [$h], $haps)) : $pairs;
            $tuple = function (string $genoKey, string $sex) use ($calc, $group, $loci, $sep) {
                $g = $calc->convertToGenotypeArrayV7([$group => $genoKey], $sex);
                return implode($sep, array_map(fn($l) => AgapornisLoci::canonicalGenotype($l, $g[$l]), $loci));
            };
            $cross = [];
            foreach ($pairs as $mk => [$m1, $m2]) {
                foreach ($females as $fk => $f) {
                    $out = ['male' => [], 'female' => []];
                    if ($sexLinked) {
                        $res = $calc->calcZLinkedV7($m1, $m2, $f[0]);
                        foreach (['male', 'female'] as $sex) {
                            foreach ($res[$sex] as $gk => $p) { $t = $tuple($gk, $sex); $out[$sex][$t] = ($out[$sex][$t] ?? 0) + $p; }
                        }
                    } else {
                        foreach ($calc->calcAutosomal1V7($m1, $m2, $f[0], $f[1]) as $gk => $p) {
                            foreach (['male', 'female'] as $sex) { $t = $tuple($gk, $sex); $out[$sex][$t] = ($out[$sex][$t] ?? 0) + $p; }
                        }
                    }
                    foreach ($out as $sex => $dist) {
                        $total = array_sum($dist);
                        foreach ($dist as $t => $p) $out[$sex][$t] = round($p / $total, 10);
                    }
                    $cross[$mk][$fk] = $out;
                }
            }
            $table[$group] = ['loci' => $loci, 'sep' => $sep, 'pairSep' => $pairSep, 'sexLinked' => $sexLinked, 'cross' => $cross];
        }
        return $cache = $table;
    }

    /**
     * 全座位・全組み合わせの交配表（index.php が CROSS_TABLE として注入。JS は GenoData.cross で引く）
     * @return array [座位][父の遺伝子型][母の遺伝子型] => ['male' => [...], 'female' => [...]]
     */
    public static function crossTable(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $calc = new self();
        $table = [];
        foreach (array_keys(self::LOCUS_FIELDS) as $locus) {
            $g = AgapornisLoci::canonicalGenotypes($locus);
            foreach ($g['male'] as $m) {
                foreach ($g['female'] as $f) {
                    $table[$locus][$m][$f] = $calc->locusCross($locus, $m, $f);
                }
            }
        }
        return $cache = $table;
    }

    /**
     * 常染色体遺伝計算
     */
    private function calcAutosomal(string $fVal, string $mVal, array $labels): array
    {
        $fAlleles = $this->parseAlleles($fVal);
        $mAlleles = $this->parseAlleles($mVal);
        $results = [];
        
        foreach ($fAlleles as $fa) {
            foreach ($mAlleles as $ma) {
                $pair = [$labels[$fa] ?? $fa, $labels[$ma] ?? $ma];
                sort($pair);
                $geno = implode('/', $pair);
                $prob = (1.0 / count($fAlleles)) * (1.0 / count($mAlleles));
                $results[$geno] = ($results[$geno] ?? 0) + $prob;
            }
        }
        return $results;
    }

    /**
     * 伴性遺伝計算（オス）
     */
    private function calcSLR_Male(string $fVal, string $mVal, array $labels): array
    {
        $fAlleles = $this->parseAlleles($fVal);
        $mAlleles = array_filter($this->parseAlleles($mVal), fn($a) => $a !== 'W');
        if (empty($mAlleles)) $mAlleles = ['+'];
        $results = [];
        
        foreach ($fAlleles as $fa) {
            foreach ($mAlleles as $ma) {
                $pair = [$labels[$fa] ?? $fa, $labels[$ma] ?? $ma];
                sort($pair);
                $geno = implode('/', $pair);
                $prob = (1.0 / count($fAlleles)) * (1.0 / count($mAlleles));
                $results[$geno] = ($results[$geno] ?? 0) + $prob;
            }
        }
        return $results;
    }

    /**
     * 伴性遺伝計算（メス）
     */
    private function calcSLR_Female(string $fVal, array $labels): array
    {
        $fAlleles = $this->parseAlleles($fVal);
        $results = [];
        
        foreach ($fAlleles as $fa) {
            $geno = ($labels[$fa] ?? $fa) . '/W';
            $prob = 1.0 / count($fAlleles);
            $results[$geno] = ($results[$geno] ?? 0) + $prob;
        }
        return $results;
    }

    // ========================================
    // v7.0: 連鎖遺伝計算メソッド
    // ========================================

    /**
     * Z染色体連鎖座位の計算（v7.0）
     *
     * GametesGenerator を使用して組み換え率を考慮した計算を行う。
     *
     * @param array $sireZ1 父のZ1ハプロタイプ
     * @param array $sireZ2 父のZ2ハプロタイプ
     * @param array $damZ1 母のZ1ハプロタイプ
     * @return array ['male' => [...], 'female' => [...]]
     */
    private function calcZLinkedV7(array $sireZ1, array $sireZ2, array $damZ1): array
    {
        // 父の配偶子生成（組み換えあり）
        $sireGametes = GametesGenerator::generateZLinkedMale($sireZ1, $sireZ2);

        // 母の配偶子生成（組み換えなし、Z:50% W:50%）
        $damGametes = GametesGenerator::generateZLinkedFemale($damZ1);

        // 子の遺伝型分布を計算
        $offspring = GametesGenerator::combineGametes($sireGametes, $damGametes);

        // オス（ZZ）とメス（ZW）に分離
        $maleResults = [];
        $femaleResults = [];

        foreach ($offspring as $genoKey => $prob) {
            if (strpos($genoKey, '/W') !== false) {
                // メス（Z/W）
                $femaleResults[$genoKey] = $prob;
            } else {
                // オス（Z/Z）
                $maleResults[$genoKey] = $prob;
            }
        }

        return [
            'male' => $maleResults,
            'female' => $femaleResults,
        ];
    }

    /**
     * 常染色体連鎖座位の計算（v7.0）
     *
     * dark-parblue の連鎖を考慮した計算。
     *
     * @param array $sireChr1 父の染色体1
     * @param array $sireChr2 父の染色体2
     * @param array $damChr1 母の染色体1
     * @param array $damChr2 母の染色体2
     * @return array 遺伝型 => 確率
     */
    private function calcAutosomal1V7(
        array $sireChr1, array $sireChr2,
        array $damChr1, array $damChr2
    ): array {
        $sireGametes = GametesGenerator::generateAutosomal1($sireChr1, $sireChr2);
        $damGametes = GametesGenerator::generateAutosomal1($damChr1, $damChr2);

        return GametesGenerator::combineGametes($sireGametes, $damGametes);
    }

    /**
     * 入力データからZ_linkedハプロタイプを抽出（v7.0）
     *
     * @param array $input 入力データ
     * @param string $prefix 'f_' or 'm_'
     * @param string $sex 'male' or 'female'
     * @return array ['Z1' => [...], 'Z2' => [...] or null]
     */
    private function extractZLinked(array $input, string $prefix, string $sex): array
    {
        // 新形式（Z_linked構造）がある場合
        $zLinkedKey = $prefix . 'Z_linked';
        if (isset($input[$zLinkedKey]) && is_array($input[$zLinkedKey])) {
            return $input[$zLinkedKey];
        }

        // 旧形式からの変換
        $inoVal = $input[$prefix . 'ino'] ?? ($sex === 'female' ? '+W' : '++');
        $opVal = $input[$prefix . 'op'] ?? $input[$prefix . 'opaline'] ?? ($sex === 'female' ? '+W' : '++');
        $cinVal = $input[$prefix . 'cin'] ?? $input[$prefix . 'cinnamon'] ?? ($sex === 'female' ? '+W' : '++');

        return $this->parseOldFormatToZLinked($inoVal, $opVal, $cinVal, $sex);
    }

    /**
     * 旧形式の遺伝型文字列からZ_linked構造に変換（v7.0）
     */
    private function parseOldFormatToZLinked(
        string $inoVal, string $opVal, string $cinVal, string $sex
    ): array {
        $parseAllele = function(string $val, string $locus) use ($sex) {
            // メス形式（末尾W）
            if (substr($val, -1) === 'W') {
                $allele = substr($val, 0, -1);
                return ['a1' => $allele ?: '+', 'a2' => null];
            }

            // オス形式
            $patterns = [
                '++' => ['+', '+'],
                'inoino' => ['ino', 'ino'],
                'pldpld' => ['pld', 'pld'],
                'pldino' => ['pld', 'ino'],
                '+ino' => ['ino', '+'],
                '+pld' => ['pld', '+'],
                'opop' => ['op', 'op'],
                '+op' => ['op', '+'],
                'cincin' => ['cin', 'cin'],
                '+cin' => ['cin', '+'],
            ];

            if (isset($patterns[$val])) {
                return ['a1' => $patterns[$val][0], 'a2' => $patterns[$val][1]];
            }

            return ['a1' => '+', 'a2' => $sex === 'male' ? '+' : null];
        };

        $ino = $parseAllele($inoVal, 'ino');
        $op = $parseAllele($opVal, 'opaline');
        $cin = $parseAllele($cinVal, 'cinnamon');

        if ($sex === 'male') {
            return [
                'Z1' => ['cinnamon' => $cin['a1'], 'ino' => $ino['a1'], 'opaline' => $op['a1']],
                'Z2' => ['cinnamon' => $cin['a2'], 'ino' => $ino['a2'], 'opaline' => $op['a2']],
            ];
        } else {
            return [
                'Z1' => ['cinnamon' => $cin['a1'], 'ino' => $ino['a1'], 'opaline' => $op['a1']],
                'Z2' => null,
            ];
        }
    }

    /**
     * 入力データからautosomal_1ハプロタイプを抽出（v7.0）
     */
    private function extractAutosomal1(array $input, string $prefix): array
    {
        // 新形式
        $autoKey = $prefix . 'autosomal_1';
        if (isset($input[$autoKey]) && is_array($input[$autoKey])) {
            return $input[$autoKey];
        }

        // 旧形式からの変換
        $darkVal = $input[$prefix . 'dark'] ?? 'dd';
        $parblueVal = $input[$prefix . 'parblue'] ?? '++';

        return $this->parseOldFormatToAutosomal1($darkVal, $parblueVal);
    }

    /**
     * 旧形式からautosomal_1構造に変換（v7.0）
     */
    private function parseOldFormatToAutosomal1(string $darkVal, string $parblueVal): array
    {
        $parseDark = function(string $val): array {
            switch ($val) {
                case 'DD': return ['D', 'D'];
                case 'Dd': return ['D', 'd'];
                case 'dd': return ['d', 'd'];
                default: return ['d', 'd'];
            }
        };

        $parseParblue = function(string $val): array {
            switch ($val) {
                case '++': return ['+', '+'];
                case '+aq': return ['aq', '+'];
                case '+tq': return ['tq', '+'];
                case 'aqaq': return ['aq', 'aq'];
                case 'tqtq': return ['tq', 'tq'];
                case 'tqaq': return ['tq', 'aq'];
                default: return ['+', '+'];
            }
        };

        $dark = $parseDark($darkVal);
        $parblue = $parseParblue($parblueVal);

        return [
            'chr1' => ['dark' => $dark[0], 'parblue' => $parblue[0]],
            'chr2' => ['dark' => $dark[1], 'parblue' => $parblue[1]],
        ];
    }

    /**
     * アレル解析（v6.8: 14座位対応）
     */
    private function parseAlleles(string $val): array
    {
        $knownAlleles = [
            'ino', 'pld', 'cin', 'tq', 'aq', 'op',
            'flp', 'flb', 'dil', 'pi', 'Pi', 'ed', 'Ed', 'of', 'ph',
            'D', 'd', 'V', 'v', 'W', '+'
        ];
        $result = [];
        $remaining = $val;
        
        while (strlen($remaining) > 0) {
            $matched = false;
            foreach ($knownAlleles as $allele) {
                if (strpos($remaining, $allele) === 0) {
                    $result[] = $allele;
                    $remaining = substr($remaining, strlen($allele));
                    $matched = true;
                    break;
                }
            }
            if (!$matched) $remaining = substr($remaining, 1);
        }
        
        if (count($result) === 0) return ['+', '+'];
        if (count($result) === 1) return [$result[0], $result[0]];
        return $result;
    }

    /**
     * 表現型の決定（v6.8: resolveColor使用）
     */
    private function getPhenotype(array $g, string $sex): string
    {
        // 内部形式を遺伝子型配列に変換
        $genotype = $this->convertToGenotypeArray($g, $sex);
        
        // AgapornisLoci::resolveColorで色名取得
        $colorInfo = AgapornisLoci::resolveColor($genotype);
        $phenotype = $colorInfo['ja'] ?? 'Unknown';
        
        // スプリット情報を追加
        $splits = $this->extractSplits($g, $sex);
        if (!empty($splits)) {
            $phenotype .= ' /' . implode(',', $splits);
        }
        
        return $phenotype;
    }

    /**
     * 計算結果を遺伝子型配列に変換
     */
    private function convertToGenotypeArray(array $g, string $sex): array
    {
        $genotype = [];
        
        // Parblue
        $pb = $g['parblue'] ?? 'B⁺/B⁺';
        if (strpos($pb, 'b^aq/b^aq') !== false || $pb === 'aqaq') {
            $genotype['parblue'] = 'aqaq';
        } elseif (strpos($pb, 'b^tq/b^tq') !== false || $pb === 'tqtq') {
            $genotype['parblue'] = 'tqtq';
        } elseif (strpos($pb, 'b^tq/b^aq') !== false || strpos($pb, 'b^aq/b^tq') !== false || $pb === 'tqaq') {
            $genotype['parblue'] = 'tqaq';
        } else {
            $genotype['parblue'] = '++';
        }
        
        // Dark
        $dk = $g['dark'] ?? 'd/d';
        if ($dk === 'D/D' || $dk === 'DD') {
            $genotype['dark'] = 'DD';
        } elseif (strpos($dk, 'D/d') !== false || strpos($dk, 'd/D') !== false || $dk === 'Dd') {
            $genotype['dark'] = 'Dd';
        } else {
            $genotype['dark'] = 'dd';
        }
        
        // INO
        $ino = $g['ino'] ?? ($sex === 'male' ? 'Z⁺/Z⁺' : 'Z⁺/W');
        if (strpos($ino, 'Z^ino/Z^ino') !== false || strpos($ino, 'Z^ino/W') !== false || $ino === 'inoino' || $ino === 'inoW') {
            $genotype['ino'] = ($sex === 'male') ? 'inoino' : 'inoW';
        } elseif (strpos($ino, 'Z^pld/Z^pld') !== false || strpos($ino, 'Z^pld/W') !== false || $ino === 'pldpld' || $ino === 'pldW') {
            $genotype['ino'] = ($sex === 'male') ? 'pldpld' : 'pldW';
        }
        
        // Opaline
        $op = $g['op'] ?? ($sex === 'male' ? 'Z⁺/Z⁺' : 'Z⁺/W');
        if (strpos($op, 'Z^op/Z^op') !== false || strpos($op, 'Z^op/W') !== false || $op === 'opop' || $op === 'opW') {
            $genotype['opaline'] = ($sex === 'male') ? 'opop' : 'opW';
        }
        
        // Cinnamon
        $cin = $g['cin'] ?? ($sex === 'male' ? 'Z⁺/Z⁺' : 'Z⁺/W');
        if (strpos($cin, 'Z^cin/Z^cin') !== false || strpos($cin, 'Z^cin/W') !== false || $cin === 'cincin' || $cin === 'cinW') {
            $genotype['cinnamon'] = ($sex === 'male') ? 'cincin' : 'cinW';
        }
        
        // Violet
        $vio = $g['vio'] ?? 'v/v';
        if ($vio === 'V/V' || $vio === 'VV') {
            $genotype['violet'] = 'VV';
        } elseif (strpos($vio, 'V/v') !== false || strpos($vio, 'v/V') !== false || $vio === 'Vv') {
            $genotype['violet'] = 'Vv';
        }
        
        // Pale Fallow
        $flp = $g['flp'] ?? '++';
        if (strpos($flp, 'flp/flp') !== false || $flp === 'flpflp') {
            $genotype['fallow_pale'] = 'flpflp';
        }
        
        // Bronze Fallow
        $flb = $g['flb'] ?? '++';
        if (strpos($flb, 'flb/flb') !== false || $flb === 'flbflb') {
            $genotype['fallow_bronze'] = 'flbflb';
        }
        
        // Dominant Pied
        $pidom = $g['pidom'] ?? '++';
        if (strpos($pidom, 'Pi') !== false) {
            $genotype['pied_dom'] = 'Pi+';
        }
        
        // Recessive Pied
        $pirec = $g['pirec'] ?? '++';
        if (strpos($pirec, 'pi/pi') !== false || $pirec === 'pipi') {
            $genotype['pied_rec'] = 'pipi';
        }
        
        // Dilute
        $dil = $g['dil'] ?? '++';
        if (strpos($dil, 'dil/dil') !== false || $dil === 'dildil') {
            $genotype['dilute'] = 'dildil';
        }
        
        // Edged
        $ed = $g['ed'] ?? '++';
        if (strpos($ed, 'ed/ed') !== false || $ed === 'eded' || strpos($ed, 'Ed/Ed') !== false || $ed === 'EdEd') {
            $genotype['edged'] = 'eded';
        }
        
        // Orangeface
        $of = $g['of'] ?? '++';
        if (strpos($of, 'of/of') !== false || $of === 'ofof') {
            $genotype['orangeface'] = 'ofof';
        }
        
        // Pale Headed
        $ph = $g['ph'] ?? '++';
        if (strpos($ph, 'ph/ph') !== false || $ph === 'phph') {
            $genotype['pale_headed'] = 'phph';
        }
        
        return $genotype;
    }

    /**
     * スプリット情報を抽出
     */
    private function extractSplits(array $g, string $sex): array
    {
        $splits = [];
        
        // Parblue スプリット
        $pb = $g['parblue'] ?? '';
        if (strpos($pb, 'B⁺/b^aq') !== false) $splits[] = 'Aq';
        if (strpos($pb, 'B⁺/b^tq') !== false) $splits[] = 'Tq';
        
        // 伴性スプリット（オスのみ）
        if ($sex === 'male') {
            $ino = $g['ino'] ?? '';
if (strpos($ino, 'Z⁺/Z^ino') !== false || strpos($ino, 'Z^ino/Z⁺') !== false) $splits[] = 'Ino';
if (strpos($ino, 'Z⁺/Z^pld') !== false || strpos($ino, 'Z^pld/Z⁺') !== false) $splits[] = 'Pld';
                        $op = $g['op'] ?? '';
            if (strpos($op, 'Z⁺/Z^op') !== false || strpos($op, 'Z^op/Z⁺') !== false) $splits[] = 'Op';
            
            $cin = $g['cin'] ?? '';
            if (strpos($cin, 'Z⁺/Z^cin') !== false || strpos($cin, 'Z^cin/Z⁺') !== false) $splits[] = 'Cin';
}                    
        
        // 常染色体スプリット
        $flp = $g['flp'] ?? '';
        if (strpos($flp, '+/flp') !== false || strpos($flp, 'flp/+') !== false) $splits[] = 'Flp';
        
        $flb = $g['flb'] ?? '';
        if (strpos($flb, '+/flb') !== false || strpos($flb, 'flb/+') !== false) $splits[] = 'Flb';
        
        $pirec = $g['pirec'] ?? '';
        if (strpos($pirec, '+/pi') !== false || strpos($pirec, 'pi/+') !== false) $splits[] = 'Pi';
        
        $dil = $g['dil'] ?? '';
        if (strpos($dil, '+/dil') !== false || strpos($dil, 'dil/+') !== false) $splits[] = 'Dil';
        
        $ed = $g['ed'] ?? '';
        if (strpos($ed, '+/ed') !== false || strpos($ed, 'ed/+') !== false) $splits[] = 'Ed';
        
        $of = $g['of'] ?? '';
        if (strpos($of, '+/of') !== false || strpos($of, 'of/+') !== false) $splits[] = 'Of';
        
        $ph = $g['ph'] ?? '';
        if (strpos($ph, '+/ph') !== false || strpos($ph, 'ph/+') !== false) $splits[] = 'Ph';

        
        return $splits;
    }  //

    /**
     * 表現型から遺伝子型を推定
     */
private function phenotypeToGenotype(array $input, string $prefix, string $sex): array
{
    // fromdbモード対応（空文字もフォールバック）
    $dbBase = $input[$prefix . '_db_baseColor'] ?? '';
    $baseColor = ($dbBase !== '') ? $dbBase : ($input[$prefix . '_baseColor'] ?? 'green');

    $dbEye = $input[$prefix . '_db_eyeColor'] ?? '';
    $eyeColor = ($dbEye !== '') ? $dbEye : ($input[$prefix . '_eyeColor'] ?? 'black');

    $dbDark = $input[$prefix . '_db_darkness'] ?? '';
    $darkness = ($dbDark !== '') ? $dbDark : ($input[$prefix . '_darkness'] ?? 'none');
        // COLOR_DEFINITIONSから遺伝子型を取得
        $colorDef = AgapornisLoci::COLOR_DEFINITIONS[$baseColor] ?? null;
        $geno = [];
        
        if ($colorDef && isset($colorDef['genotype'])) {
            $defGeno = $colorDef['genotype'];
            
            // Parblue
            $geno['parblue'] = $defGeno['parblue'] ?? '++';
            
            // Dark（濃さ欄 sf/df が優先、なければ色名の定義。INO 系で不明なら dd とみなす）
            $geno['dark'] = AgapornisLoci::resolveDarkGenotype(['baseColor' => $baseColor, 'darkness' => $darkness], 'dd');
            
            // INO
            if (isset($defGeno['ino'])) {
                $val = $defGeno['ino'];
                if ($val === 'inoino') {
                    $geno['ino'] = ($sex === 'male') ? 'inoino' : 'inoW';
                } elseif ($val === 'pldpld') {
                    $geno['ino'] = ($sex === 'male') ? 'pldpld' : 'pldW';
                }
            } else {
                $geno['ino'] = ($sex === 'male') ? '++' : '+W';
            }
            
            // Opaline
            if (isset($defGeno['opaline']) && $defGeno['opaline'] === 'opop') {
                $geno['op'] = ($sex === 'male') ? 'opop' : 'opW';
            } else {
                $geno['op'] = ($sex === 'male') ? '++' : '+W';
            }
            
            // Cinnamon
            if (isset($defGeno['cinnamon']) && $defGeno['cinnamon'] === 'cincin') {
                $geno['cin'] = ($sex === 'male') ? 'cincin' : 'cinW';
            } else {
                $geno['cin'] = ($sex === 'male') ? '++' : '+W';
            }
            
            // Violet
            $geno['vio'] = $defGeno['violet'] ?? 'vv';
            
            // Pale Fallow
            $geno['flp'] = $defGeno['fallow_pale'] ?? '++';
            
            // Bronze Fallow
            $geno['flb'] = $defGeno['fallow_bronze'] ?? '++';
            
            // Dominant Pied
            $geno['pidom'] = $defGeno['pied_dom'] ?? '++';
            
            // Recessive Pied
            $geno['pirec'] = $defGeno['pied_rec'] ?? '++';
            
            // Dilute
            $geno['dil'] = $defGeno['dilute'] ?? '++';
            
            // Edged
            $geno['ed'] = $defGeno['edged'] ?? '++';
            
            // Orangeface
            $geno['of'] = $defGeno['orangeface'] ?? '++';
            
            // Pale Headed
            $geno['ph'] = $defGeno['pale_headed'] ?? '++';
            
        } else {
            // フォールバック: 野生型
            $geno = [
                'parblue' => '++',
                'dark' => 'dd',
                'ino' => ($sex === 'male') ? '++' : '+W',
                'op' => ($sex === 'male') ? '++' : '+W',
                'cin' => ($sex === 'male') ? '++' : '+W',
                'vio' => 'vv',
                'flp' => '++',
                'flb' => '++',
                'pidom' => '++',
                'pirec' => '++',
                'dil' => '++',
                'ed' => '++',
                'of' => '++',
                'ph' => '++',
            ];
        }
        
        return $geno;
    }

    /**
     * 子孫計算（v7.0: 連鎖遺伝対応版）
     *
     * v7.0以降、連鎖座位の組み換え率を考慮した計算が常時有効。
     */
    public function calculateOffspring(array $input): array
    {
        // v7.0以降: 連鎖遺伝計算を常時使用
        return $this->calculateOffspringV7($input);
    }

    /**
     * v7.0 子孫計算 - 連鎖遺伝対応版
     *
     * GametesGenerator を使用して連鎖座位の組み換え率を考慮した計算を行う。
     */
    private function calculateOffspringV7(array $input): array
    {
        // 入力データ前処理（v6.8互換）
        $fMode = $input['f_mode'] ?? 'genotype';
        $mMode = $input['m_mode'] ?? 'genotype';

        if ($fMode === 'phenotype') {
            $fGeno = $this->phenotypeToGenotype($input, 'f', 'male');
            foreach ($fGeno as $k => $v) { $input['f_' . $k] = $v; }
        }
        if ($mMode === 'phenotype') {
            $mGeno = $this->phenotypeToGenotype($input, 'm', 'female');
            foreach ($mGeno as $k => $v) { $input['m_' . $k] = $v; }
        }

        if ($fMode === 'fromdb') {
            $genotypeSet = false;
            if (!empty($input['f_db_genotype'])) {
                $j = json_decode($input['f_db_genotype'], true);
                if ($j && !empty($j)) {
                    foreach ($j as $k => $v) { $input['f_' . $k] = $v; }
                    $keyMap = ['opaline'=>'op','cinnamon'=>'cin','violet'=>'vio','pied_rec'=>'pirec','pied_dom'=>'pidom','fallow_pale'=>'flp','fallow_bronze'=>'flb','dilute'=>'dil','edged'=>'ed','orangeface'=>'of','pale_headed'=>'ph'];
                    foreach ($keyMap as $long => $short) { if (isset($j[$long])) $input['f_'.$short] = $j[$long]; }
                    $genotypeSet = true;
                }
            }
            // genotype がない場合は phenotype からの推定にフォールバック
            if (!$genotypeSet && !empty($input['f_db_baseColor'])) {
                $input['f_baseColor'] = $input['f_db_baseColor'];
                $input['f_eyeColor'] = $input['f_db_eyeColor'] ?? 'black';
                $input['f_darkness'] = $input['f_db_darkness'] ?? 'none';
                $fGeno = $this->phenotypeToGenotype($input, 'f', 'male');
                foreach ($fGeno as $k => $v) { $input['f_' . $k] = $v; }
            }
        }
        if ($mMode === 'fromdb') {
            $genotypeSet = false;
            if (!empty($input['m_db_genotype'])) {
                $j = json_decode($input['m_db_genotype'], true);
                if ($j && !empty($j)) {
                    foreach ($j as $k => $v) { $input['m_' . $k] = $v; }
                    $keyMap = ['opaline'=>'op','cinnamon'=>'cin','violet'=>'vio','pied_rec'=>'pirec','pied_dom'=>'pidom','fallow_pale'=>'flp','fallow_bronze'=>'flb','dilute'=>'dil','edged'=>'ed','orangeface'=>'of','pale_headed'=>'ph'];
                    foreach ($keyMap as $long => $short) { if (isset($j[$long])) $input['m_'.$short] = $j[$long]; }
                    $genotypeSet = true;
                }
            }
            // genotype がない場合は phenotype からの推定にフォールバック
            if (!$genotypeSet && !empty($input['m_db_baseColor'])) {
                $input['m_baseColor'] = $input['m_db_baseColor'];
                $input['m_eyeColor'] = $input['m_db_eyeColor'] ?? 'black';
                $input['m_darkness'] = $input['m_db_darkness'] ?? 'none';
                $mGeno = $this->phenotypeToGenotype($input, 'm', 'female');
                foreach ($mGeno as $k => $v) { $input['m_' . $k] = $v; }
            }
        }

        // v7.3.15: 入力バリデーション
        $warnings = [];
        $input = $this->validateAndSanitizeInput($input, $warnings);

        // Z_linked ハプロタイプ抽出
        $sireZLinked = $this->extractZLinked($input, 'f_', 'male');
        $damZLinked = $this->extractZLinked($input, 'm_', 'female');

        // autosomal_1 ハプロタイプ抽出
        $sireAutosomal1 = $this->extractAutosomal1($input, 'f_');
        $damAutosomal1 = $this->extractAutosomal1($input, 'm_');

        // Z染色体連鎖座位の計算（組み換え率適用）
        $zLinkedResults = $this->calcZLinkedV7(
            $sireZLinked['Z1'],
            $sireZLinked['Z2'],
            $damZLinked['Z1']
        );

        // 常染色体連鎖座位の計算（組み換え率適用）
        $autosomal1Results = $this->calcAutosomal1V7(
            $sireAutosomal1['chr1'],
            $sireAutosomal1['chr2'],
            $damAutosomal1['chr1'],
            $damAutosomal1['chr2']
        );

        // 独立座位の計算（従来通り）
        $labels = [
            'vio' => ['V' => 'V', 'v' => 'v'],
            'flp' => ['+' => '+', 'flp' => 'flp'],
            'flb' => ['+' => '+', 'flb' => 'flb'],
            'pidom' => ['+' => '+', 'Pi' => 'Pi'],
            'pirec' => ['+' => '+', 'pi' => 'pi'],
            'dil' => ['+' => '+', 'dil' => 'dil'],
            'ed' => ['+' => '+', 'ed' => 'ed', 'Ed' => 'Ed'],
            'of' => ['+' => '+', 'of' => 'of'],
            'ph' => ['+' => '+', 'ph' => 'ph'],
        ];

        $independentLoci = [
            'vio' => $this->calcAutosomal($input['f_vio'] ?? 'vv', $input['m_vio'] ?? 'vv', $labels['vio']),
            'flp' => $this->calcAutosomal($input['f_flp'] ?? '++', $input['m_flp'] ?? '++', $labels['flp']),
            'flb' => $this->calcAutosomal($input['f_flb'] ?? '++', $input['m_flb'] ?? '++', $labels['flb']),
            'pidom' => $this->calcAutosomal($input['f_pidom'] ?? '++', $input['m_pidom'] ?? '++', $labels['pidom']),
            'pirec' => $this->calcAutosomal($input['f_pirec'] ?? '++', $input['m_pirec'] ?? '++', $labels['pirec']),
            'dil' => $this->calcAutosomal($input['f_dil'] ?? '++', $input['m_dil'] ?? '++', $labels['dil']),
            'ed' => $this->calcAutosomal($input['f_ed'] ?? '++', $input['m_ed'] ?? '++', $labels['ed']),
            'of' => $this->calcAutosomal($input['f_of'] ?? '++', $input['m_of'] ?? '++', $labels['of']),
            'ph' => $this->calcAutosomal($input['f_ph'] ?? '++', $input['m_ph'] ?? '++', $labels['ph']),
        ];

        // 結果を組み合わせる
        // v7.3.15: no_filterオプションで確率フィルタリングを無効化可能
        $noFilter = !empty($input['no_filter']);
        $PROB_THRESHOLD = $noFilter ? 0 : 0.0001;
        $maleResults = $this->enumerateCombinationsV7(
            $autosomal1Results,
            $zLinkedResults['male'],
            $independentLoci,
            'male',
            $PROB_THRESHOLD
        );
        $femaleResults = $this->enumerateCombinationsV7(
            $autosomal1Results,
            $zLinkedResults['female'],
            $independentLoci,
            'female',
            $PROB_THRESHOLD
        );

        $allResults = array_merge($maleResults, $femaleResults);

        // 結果を集約
        $phenotypeAggregated = [];
        $genotypeAggregated = [];

        foreach ($allResults as $r) {
            $prob = $r['prob'];
            $sex = $r['sex'];
            $geno = $r['geno'];

            $genotype = $this->convertToGenotypeArrayV7($geno, $sex);
            $colorInfo = AgapornisLoci::resolveColor($genotype);
            // v7.3.14: 両言語を保存し、表示層で選択できるようにする
            $colorNameJa = $colorInfo['ja'] ?? 'Unknown';
            $colorNameEn = $colorInfo['en'] ?? 'Unknown';
            $colorName = $colorNameJa; // 内部集約用キーは日本語維持

            $splits = $this->extractSplitsV7($geno, $sex);
            $splitStr = !empty($splits) ? ' /' . implode(',', $splits) : '';

            $phenoKey = $colorName . '|' . $sex;
            if (!isset($phenotypeAggregated[$phenoKey])) {
                $phenotypeAggregated[$phenoKey] = [
                    'phenotype' => $colorNameJa,
                    'phenotype_ja' => $colorNameJa,
                    'phenotype_en' => $colorNameEn,
                    'sex' => $sex,
                    'probability' => 0,
                    'splits' => [],
                    'colorKey' => $colorInfo['key'] ?? 'unknown',
                    'eyeColor' => $colorInfo['eye'] ?? 'black',
                ];
            }
            $phenotypeAggregated[$phenoKey]['probability'] += $prob;
            if (!empty($splits)) {
                foreach ($splits as $s) {
                    if (!in_array($s, $phenotypeAggregated[$phenoKey]['splits'])) {
                        $phenotypeAggregated[$phenoKey]['splits'][] = $s;
                    }
                }
            }

            $genoKey = json_encode($geno) . '|' . $sex;
            if (!isset($genotypeAggregated[$genoKey])) {
                $genotypeAggregated[$genoKey] = [
                    'genotype' => $geno,
                    'sex' => $sex,
                    'probability' => 0,
                    'phenotype' => $colorNameJa,
                    'phenotype_ja' => $colorNameJa,
                    'phenotype_en' => $colorNameEn,
                ];
            }
            $genotypeAggregated[$genoKey]['probability'] += $prob;
        }

        // 確率でソート
        uasort($phenotypeAggregated, fn($a, $b) => $b['probability'] <=> $a['probability']);
        uasort($genotypeAggregated, fn($a, $b) => $b['probability'] <=> $a['probability']);

        $phenotypeResults = array_values($phenotypeAggregated);
        $genotypeResults = array_values($genotypeAggregated);

        $result = [
            'phenotype' => $phenotypeResults,
            'genotype' => $genotypeResults,
            'results' => $genotypeResults,
            'linkage_mode' => true,
        ];
        // v7.3.15: Add warnings if present
        if (!empty($warnings)) {
            $result['warnings'] = $warnings;
        }
        return $result;
    }

    /**
     * v7.0: 連鎖座位を含む組み合わせ列挙
     */
    private function enumerateCombinationsV7(
        array $autosomal1Results,
        array $zLinkedResults,
        array $independentLoci,
        string $sex,
        float $threshold
    ): array {
        $results = [];

        // autosomal_1 と Z_linked の組み合わせを先に計算
        $linkedCombinations = [];
        foreach ($autosomal1Results as $autoKey => $autoProb) {
            foreach ($zLinkedResults as $zKey => $zProb) {
                $prob = $autoProb * $zProb;
                if ($prob < $threshold * 0.01) continue;
                $linkedCombinations[] = [
                    'autosomal_1' => $autoKey,
                    'Z_linked' => $zKey,
                    'prob' => $prob,
                ];
            }
        }

        // 独立座位との組み合わせ
        $allIndependent = [];
        foreach ($independentLoci as $key => $data) {
            $allIndependent[] = ['key' => $key, 'data' => $data];
        }

        foreach ($linkedCombinations as $linked) {
            $this->enumerateIndependentRecursive(
                $allIndependent,
                0,
                [
                    'autosomal_1' => $linked['autosomal_1'],
                    'Z_linked' => $linked['Z_linked'],
                ],
                $linked['prob'],
                $threshold,
                $sex,
                $results
            );
        }

        return $results;
    }

    /**
     * v7.0: 独立座位の再帰的列挙
     */
    private function enumerateIndependentRecursive(
        array $loci,
        int $index,
        array $currentGeno,
        float $currentProb,
        float $threshold,
        string $sex,
        array &$results
    ): void {
        if ($index >= count($loci)) {
            if ($currentProb >= $threshold) {
                $results[] = ['sex' => $sex, 'prob' => $currentProb, 'geno' => $currentGeno];
            }
            return;
        }

        $locus = $loci[$index];
        $key = $locus['key'];
        $data = $locus['data'];

        foreach ($data as $geno => $prob) {
            $newProb = $currentProb * $prob;
            if ($newProb < $threshold * 0.01) continue;
            $newGeno = $currentGeno;
            $newGeno[$key] = $geno;
            $this->enumerateIndependentRecursive($loci, $index + 1, $newGeno, $newProb, $threshold, $sex, $results);
        }
    }

    /**
     * v7.0: 遺伝型をresolveColor用の配列に変換
     */
    private function convertToGenotypeArrayV7(array $geno, string $sex): array
    {
        $result = [];

        // autosomal_1 をパース (dark|parblue/dark|parblue 形式)
        if (isset($geno['autosomal_1'])) {
            $parts = explode('/', $geno['autosomal_1']);
            $chr1 = GametesGenerator::keyToHaplotype($parts[0] ?? '');
            $chr2 = GametesGenerator::keyToHaplotype($parts[1] ?? $parts[0] ?? '');

            // dark
            $d1 = $chr1['dark'] ?? 'd';
            $d2 = $chr2['dark'] ?? 'd';
            $darkPair = [$d1, $d2];
            sort($darkPair);
            $result['dark'] = implode('', $darkPair);

            // parblue
            $p1 = $chr1['parblue'] ?? '+';
            $p2 = $chr2['parblue'] ?? '+';
            $parbluePair = [$p1, $p2];
            sort($parbluePair);
            $result['parblue'] = implode('', $parbluePair);
        }

        // Z_linked をパース
        if (isset($geno['Z_linked'])) {
            if (strpos($geno['Z_linked'], '/W') !== false) {
                // メス (Z/W)
                $zKey = str_replace('/W', '', $geno['Z_linked']);
                $z = GametesGenerator::keyToHaplotype($zKey);
                $result['cinnamon'] = ($z['cinnamon'] ?? '+') . 'W';
                $result['ino'] = ($z['ino'] ?? '+') . 'W';
                $result['opaline'] = ($z['opaline'] ?? '+') . 'W';
            } else {
                // オス (Z/Z)
                $parts = explode('/', $geno['Z_linked']);
                $z1 = GametesGenerator::keyToHaplotype($parts[0] ?? '');
                $z2 = GametesGenerator::keyToHaplotype($parts[1] ?? $parts[0] ?? '');

                $result['cinnamon'] = ($z1['cinnamon'] ?? '+') . ($z2['cinnamon'] ?? '+');
                $result['ino'] = ($z1['ino'] ?? '+') . ($z2['ino'] ?? '+');
                $result['opaline'] = ($z1['opaline'] ?? '+') . ($z2['opaline'] ?? '+');
            }
        }

        // 独立座位
        foreach (['vio', 'flp', 'flb', 'pidom', 'pirec', 'dil', 'ed', 'of', 'ph'] as $key) {
            if (isset($geno[$key])) {
                $keyMap = [
                    'vio' => 'violet',
                    'flp' => 'fallow_pale',
                    'flb' => 'fallow_bronze',
                    'pidom' => 'pied_dom',
                    'pirec' => 'pied_rec',
                    'dil' => 'dilute',
                    'ed' => 'edged',
                    'of' => 'orangeface',
                    'ph' => 'pale_headed',
                ];
                $mapKey = $keyMap[$key] ?? $key;
                $result[$mapKey] = str_replace('/', '', $geno[$key]);
            }
        }

        return $result;
    }

    /**
     * v7.0: スプリット情報の抽出
     */
    private function extractSplitsV7(array $geno, string $sex): array
    {
        $splits = [];

        // Z_linked からスプリット抽出
        if (isset($geno['Z_linked']) && $sex === 'male') {
            if (strpos($geno['Z_linked'], '/W') === false) {
                $parts = explode('/', $geno['Z_linked']);
                if (count($parts) === 2) {
                    $z1 = GametesGenerator::keyToHaplotype($parts[0]);
                    $z2 = GametesGenerator::keyToHaplotype($parts[1]);

                    foreach (['cinnamon' => 'cin', 'ino' => 'ino', 'opaline' => 'op'] as $locus => $short) {
                        $a1 = $z1[$locus] ?? '+';
                        $a2 = $z2[$locus] ?? '+';
                        if (($a1 !== '+' && $a2 === '+') || ($a1 === '+' && $a2 !== '+')) {
                            $mutant = $a1 !== '+' ? $a1 : $a2;
                            $splits[] = $mutant;
                        }
                    }
                }
            }
        }

        // 独立座位からスプリット抽出
        foreach ($geno as $key => $value) {
            if (in_array($key, ['autosomal_1', 'Z_linked'])) continue;
            if (strpos($value, '/') !== false) {
                $parts = explode('/', $value);
                if (count($parts) === 2 && $parts[0] !== $parts[1]) {
                    // ヘテロ接合
                    $mutant = $parts[0] !== '+' ? $parts[0] : $parts[1];
                    if ($mutant !== '+') {
                        $splits[] = $mutant;
                    }
                }
            }
        }

        return array_unique($splits);
    }

    private function enumerateCombinations(array $autosomalLoci, array $sexLinkedLoci, string $sex, float $threshold): array
    {
        $results = [];
        $allLoci = [];
        foreach ($autosomalLoci as $key => $data) { $allLoci[] = ['key' => $key, 'data' => $data]; }
        foreach ($sexLinkedLoci as $key => $data) { $allLoci[] = ['key' => $key, 'data' => $data]; }
        $this->enumerateRecursive($allLoci, 0, [], 1.0, $threshold, $sex, $results);
        return $results;
    }

    private function enumerateRecursive(array $loci, int $index, array $currentGeno, float $currentProb, float $threshold, string $sex, array &$results): void
    {
        if ($index >= count($loci)) {
            if ($currentProb >= $threshold) {
                $results[] = ['sex' => $sex, 'prob' => $currentProb, 'geno' => $currentGeno];
            }
            return;
        }
        $locus = $loci[$index];
        $key = $locus['key'];
        $data = $locus['data'];
        foreach ($data as $geno => $prob) {
            $newProb = $currentProb * $prob;
            if ($newProb < $threshold * 0.01) continue;
            $newGeno = $currentGeno;
            $newGeno[$key] = $geno;
            $this->enumerateRecursive($loci, $index + 1, $newGeno, $newProb, $threshold, $sex, $results);
        }
    }

    /**
     * v7.3.19: 子孫の遺伝子型が親から生じうるかをチェック（SSOT整合性チェック）
     *
     * @param array $sireGenotype 父の遺伝子型
     * @param array $damGenotype 母の遺伝子型
     * @param array $childGenotype 子の遺伝子型
     * @param string $childSex 子の性別 ('male' or 'female')
     * @return array ['consistent' => bool, 'probability' => float, 'message' => string|null]
     */
    public function checkOffspringConsistency(
        array $sireGenotype,
        array $damGenotype,
        array $childGenotype,
        string $childSex
    ): array {
        // 直接ルールベースで整合性をチェック
        $reason = $this->identifyInconsistencyReason($sireGenotype, $damGenotype, $childGenotype, $childSex);

        if ($reason === null) {
            return [
                'consistent' => true,
                'probability' => 100.0,
                'message' => null
            ];
        } else {
            return [
                'consistent' => false,
                'probability' => 0,
                'message' => $reason
            ];
        }
    }

    /**
     * 不整合の原因を特定（整合性がある場合はnullを返す）
     */
    private function identifyInconsistencyReason(
        array $sireGeno,
        array $damGeno,
        array $childGeno,
        string $childSex
    ): ?string {
        $sexLinkedLoci = [
            'ino' => ['ja' => 'INO/パリッド', 'en' => 'INO/Pallid'],
            'opaline' => ['ja' => 'オパーリン', 'en' => 'Opaline'],
            'cinnamon' => ['ja' => 'シナモン', 'en' => 'Cinnamon'],
        ];

        // 伴性遺伝チェック
        foreach ($sexLinkedLoci as $locus => $names) {
            $childVal = $childGeno[$locus] ?? '++';
            $sireVal = $sireGeno[$locus] ?? '++';
            $damVal = $damGeno[$locus] ?? '+W';

            // 子が発現している場合
            if ($childVal !== '++' && $childVal !== '+W') {
                $allele = $this->extractAlleleFromGenotype($childVal);

                if ($childSex === 'male') {
                    // オス発現 = 父がアレル持ち + 母が発現
                    $sireHas = str_contains($sireVal, $allele);
                    $damExpresses = str_contains($damVal, $allele) && str_ends_with($damVal, 'W');

                    if (!$sireHas || !$damExpresses) {
                        return "オスの{$names['ja']}発現には、父が{$allele}保有 + 母が{$allele}発現が必要です";
                    }
                } else {
                    // メス発現 = 父がアレル持ち
                    $sireHas = str_contains($sireVal, $allele);

                    if (!$sireHas) {
                        return "メスの{$names['ja']}発現には、父が{$allele}を保有している必要があります";
                    }
                }
            }
        }

        // 常染色体劣性チェック
        $autosomalRecessive = [
            'pied_rec' => ['allele' => 'pi', 'ja' => 'レセッシブパイド'],
            'dilute' => ['allele' => 'dil', 'ja' => 'ダイリュート'],
            'fallow_pale' => ['allele' => 'flp', 'ja' => 'ペールファロー'],
            'fallow_bronze' => ['allele' => 'flb', 'ja' => 'ブロンズファロー'],
            'edged' => ['allele' => 'ed', 'ja' => 'エッジド'],
            'pale_headed' => ['allele' => 'ph', 'ja' => 'ペールヘッド'],
            'orangeface' => ['allele' => 'of', 'ja' => 'オレンジフェイス'],
        ];

        foreach ($autosomalRecessive as $locus => $info) {
            $childVal = $childGeno[$locus] ?? '++';

            // ホモ接合（発現）の場合
            if ($childVal !== '++' && !str_contains($childVal, '+')) {
                $sireVal = $sireGeno[$locus] ?? '++';
                $damVal = $damGeno[$locus] ?? '++';

                $sireCarries = $sireVal !== '++';
                $damCarries = $damVal !== '++';

                if (!$sireCarries || !$damCarries) {
                    return "{$info['ja']}の発現には、両親がともに{$info['allele']}を保有している必要があります";
                }
            }
        }

        // parblue チェック
        $childParblue = $childGeno['parblue'] ?? '++';
        if ($childParblue !== '++') {
            $sireParblue = $sireGeno['parblue'] ?? '++';
            $damParblue = $damGeno['parblue'] ?? '++';

            // アレルを分解
            $getParblueAlleles = function($val) {
                if ($val === '++') return ['+', '+'];
                if ($val === '+aq') return ['+', 'aq'];
                if ($val === 'aqaq') return ['aq', 'aq'];
                if ($val === '+tq') return ['+', 'tq'];
                if ($val === 'tqtq') return ['tq', 'tq'];
                if ($val === 'tqaq') return ['tq', 'aq'];
                return ['+', '+'];
            };

            $sireAlleles = $getParblueAlleles($sireParblue);
            $damAlleles = $getParblueAlleles($damParblue);
            $childAlleles = $getParblueAlleles($childParblue);

            // 子のアレルが親から来れるかチェック
            $possibleFromSire = $sireAlleles;
            $possibleFromDam = $damAlleles;

            $valid = false;
            foreach ($possibleFromSire as $sA) {
                foreach ($possibleFromDam as $dA) {
                    $pair = [$sA, $dA];
                    sort($pair);
                    $resultAlleles = $pair;
                    $childAlleleSorted = $childAlleles;
                    sort($childAlleleSorted);
                    if ($resultAlleles === $childAlleleSorted) {
                        $valid = true;
                        break 2;
                    }
                }
            }

            if (!$valid) {
                $parblueNames = [
                    'aqaq' => 'アクア系',
                    'tqtq' => 'ターコイズ系',
                    'tqaq' => 'シーグリーン系',
                    '++' => 'グリーン系',
                ];
                $childName = $parblueNames[$childParblue] ?? $childParblue;
                return "前世代の構成からは{$childName}は生まれません";
            }
        }

        // 全チェックをパス = 整合性あり
        return null;
    }

    /**
     * 遺伝子型からアレルを抽出
     */
    private function extractAlleleFromGenotype(string $val): string {
        if (str_ends_with($val, 'W')) {
            return str_replace('W', '', $val);
        }
        // ホモ接合: inoino → ino, pldpld → pld
        $len = strlen($val);
        if ($len >= 4 && substr($val, 0, $len/2) === substr($val, $len/2)) {
            return substr($val, 0, $len/2);
        }
        // ヘテロ: +ino → ino
        if (str_starts_with($val, '+')) {
            return substr($val, 1);
        }
        return $val;
    }

    /**
     * v7.3.15: Input validation and sanitization
     * - Invalid allele values are replaced with defaults
     * - +W for males causes errors, so corrected to ++
     * @param array $input Input data
     * @param array &$warnings Array to store warning messages
     * @return array Sanitized input data
     */
    private function validateAndSanitizeInput(array $input, array &$warnings): array
    {
        // Valid allele values definition
        $validAlleles = [
            'parblue' => ['++', '+tq', '+aq', 'tq+', 'aq+', 'tqtq', 'aqaq', 'tqaq', 'aqtq'],
            'dark' => ['dd', 'Dd', 'dD', 'DD'],
            'vio' => ['vv', 'Vv', 'vV', 'VV'],
            'flp' => ['++', '+flp', 'flp+', 'flpflp'],
            'flb' => ['++', '+flb', 'flb+', 'flbflb'],
            'pidom' => ['++', 'Pi+', '+Pi', 'PiPi'],
            'pirec' => ['++', '+pi', 'pi+', 'pipi'],
            'dil' => ['++', '+dil', 'dil+', 'dildil'],
            'ed' => ['++', '+ed', 'ed+', 'eded', 'Ed+', '+Ed', 'EdEd'],
            'of' => ['++', '+of', 'of+', 'ofof'],
            'ph' => ['++', '+ph', 'ph+', 'phph'],
        ];

        // 伴性遺伝座位（オス/メスで異なる）
        $sexLinkedLoci = [
            'ino' => [
                'male' => ['++', '+ino', 'ino+', 'inoino', '+pld', 'pld+', 'pldpld', 'pldino', 'inopld'],
                'female' => ['+W', 'inoW', 'pldW'],
            ],
            'op' => [
                'male' => ['++', '+op', 'op+', 'opop'],
                'female' => ['+W', 'opW'],
            ],
            'opaline' => [
                'male' => ['++', '+op', 'op+', 'opop'],
                'female' => ['+W', 'opW'],
            ],
            'cin' => [
                'male' => ['++', '+cin', 'cin+', 'cincin'],
                'female' => ['+W', 'cinW'],
            ],
            'cinnamon' => [
                'male' => ['++', '+cin', 'cin+', 'cincin'],
                'female' => ['+W', 'cinW'],
            ],
        ];

        // デフォルト値
        $defaults = [
            'parblue' => '++', 'dark' => 'dd', 'vio' => 'vv',
            'flp' => '++', 'flb' => '++', 'pidom' => '++', 'pirec' => '++',
            'dil' => '++', 'ed' => '++', 'of' => '++', 'ph' => '++',
        ];
        $sexLinkedDefaults = [
            'male' => '++',
            'female' => '+W',
        ];

        // Male (f_) validation
        foreach ($validAlleles as $locus => $valid) {
            $key = 'f_' . $locus;
            if (isset($input[$key]) && !in_array($input[$key], $valid)) {
                $warnings[] = "Invalid allele value: {$key}=\"{$input[$key]}\" → corrected to default \"{$defaults[$locus]}\"";
                $input[$key] = $defaults[$locus];
            }
        }

        // Male sex-linked loci
        foreach ($sexLinkedLoci as $locus => $sexValid) {
            $key = 'f_' . $locus;
            if (isset($input[$key])) {
                $val = $input[$key];
                // If +W is specified for male
                if (str_ends_with($val, 'W')) {
                    $warnings[] = "Cannot specify +W for male: {$key}=\"{$val}\" → auto-corrected to \"++\"";
                    $input[$key] = '++';
                } elseif (!in_array($val, $sexValid['male'])) {
                    $warnings[] = "Invalid allele value: {$key}=\"{$val}\" → corrected to default \"++\"";
                    $input[$key] = '++';
                }
            }
        }

        // Female (m_) validation
        foreach ($validAlleles as $locus => $valid) {
            $key = 'm_' . $locus;
            if (isset($input[$key]) && !in_array($input[$key], $valid)) {
                $warnings[] = "Invalid allele value: {$key}=\"{$input[$key]}\" → corrected to default \"{$defaults[$locus]}\"";
                $input[$key] = $defaults[$locus];
            }
        }

        // Female sex-linked loci
        foreach ($sexLinkedLoci as $locus => $sexValid) {
            $key = 'm_' . $locus;
            if (isset($input[$key])) {
                $val = $input[$key];
                if (!in_array($val, $sexValid['female']) && !in_array($val, $sexValid['male'])) {
                    // Male genotype specified for female is acceptable (carrier possibility)
                    // However, completely invalid values default to +W
                    $warnings[] = "Invalid allele value: {$key}=\"{$val}\" → corrected to default \"+W\"";
                    $input[$key] = '+W';
                }
            }
        }

        return $input;
    }
}

/**
 * FamilyEstimatorV3 - 一族マップからの遺伝子型推論エンジン
 * @version 6.8
 */
class PathFinder
{
    public function findPath(string $targetKey): array
    {
        $colorDef = AgapornisLoci::COLOR_DEFINITIONS[$targetKey] ?? null;

        if (!$colorDef) {
            return ['error' => 'pf_unsupported_target', 'errorParam' => $targetKey];
        }

        $genotype = $colorDef['genotype'] ?? [];
        $steps = $this->generateSteps($genotype);
        $warnings = $this->generateWarnings($genotype, $colorDef);

        // v7.0: 連鎖遺伝分析
        $linkageAnalysis = $this->analyzeLinkageRequirements($genotype);

        // v7.2: 人間が読める交配シナリオを生成
        $scenario = $this->generateBreedingScenario($genotype, $steps, $targetKey);

        return [
            'targetKey' => $targetKey,
            'genotype' => $genotype,
            'steps' => $steps,
            'warnings' => $warnings,
            'minGenerations' => $this->calculateMinGenerations($genotype),
            // v7.0: 連鎖遺伝情報
            'linkage' => $linkageAnalysis,
            // v7.2: 交配シナリオ
            'scenario' => $scenario,
        ];
    }
    
    private function generateSteps(array $genotype): array
    {
        $steps = [];
        $priority = ['parblue', 'dark', 'violet', 'pied_dom', 'pied_rec', 'dilute', 'edged', 'orangeface', 'pale_headed', 'fallow_pale', 'fallow_bronze', 'opaline', 'cinnamon', 'ino'];
        
        foreach ($priority as $locus) {
            if (!isset($genotype[$locus])) continue;
            $targetAllele = $genotype[$locus];
            if ($targetAllele === '++' || $targetAllele === 'dd' || $targetAllele === '+W' || $targetAllele === 'vv') continue;
            
            $step = $this->generateStepForLocus($locus, $targetAllele);
            if ($step) {
                $steps[] = $step;
            }
        }
        
        if (empty($steps)) {
            $steps[] = [
                'title_key' => 'pf_wild_type',
                'male_key' => 'green',
                'female_key' => 'green',
                'result_key' => 'pf_wild_type_result',
            ];
        }
        
        return $steps;
    }
    
    /**
     * 最短世代数（このプランの手順に沿った値）
     *
     * 各座位の表現個体を野生型と交配する系統を並行して進める（1世代目）。
     * 系統が n 本あれば、2本ずつ合流させて全座位を1羽に集めるのに ceil(log2 n) 世代かかる。
     * 目標の遺伝子型が野生型アレルを含まない座位（劣性ホモ、伴性メスのヘミ、DD など）が
     * 1つでもあれば、ヘテロ同士を交配してそろえる世代が1つ増える。
     * 変異座位がない（野生型）なら 0。
     */
    private function calculateMinGenerations(array $genotype): int
    {
        $lociCount = 0;
        $needsFixation = false;
        foreach ($genotype as $locus => $value) {
            if (!isset(AgapornisLoci::LOCI[$locus])) continue;
            if (in_array($value, ['++', 'dd', '+W', 'vv'], true)) continue;
            $lociCount++;
            $alleles = AgapornisLoci::splitGenotype($locus, $value);
            if ($alleles === null || !in_array(AgapornisLoci::wildAllele($locus), $alleles, true)) {
                $needsFixation = true;
            }
        }
        if ($lociCount === 0) return 0;

        $mergeGenerations = 0;
        while ((1 << $mergeGenerations) < $lociCount) {
            $mergeGenerations++;
        }
        return 1 + $mergeGenerations + ($needsFixation ? 1 : 0);
    }

    private function generateStepForLocus(string $locus, string $targetAllele): ?array
    {
        $locusInfo = AgapornisLoci::LOCI[$locus] ?? null;
        if (!$locusInfo) return null;
        
        $isSexLinked = $locusInfo['sex_linked'] ?? false;
        
        if ($isSexLinked) {
            return $this->generateSexLinkedStep($locus, $targetAllele);
        } else {
            return $this->generateAutosomalStep($locus, $targetAllele);
        }
    }
    
    private function generateSexLinkedStep(string $locus, string $targetAllele): array
    {
        $expressedColorKey = $this->getAlleleColorKey($locus, $targetAllele);
        
        return [
            'title_key' => 'pf_introduce_slr',
            'title_params' => ['locus' => $locus],
            'male_key' => 'green',
            'male_suffix_key' => 'pf_wild_type',
            'female_key' => $expressedColorKey,
            'female_suffix_key' => 'pf_expressed_female',
            'result_key' => 'pf_slr_result',
        ];
    }
    
    private function generateAutosomalStep(string $locus, string $targetAllele): array
    {
        $expressedColorKey = $this->getAlleleColorKey($locus, $targetAllele);
        $type = AgapornisLoci::LOCI[$locus]['type'] ?? 'AR';
        
        if ($type === 'AD' || $type === 'AID') {
            return [
                'title_key' => 'pf_introduce_dominant',
                'title_params' => ['locus' => $locus],
                'male_key' => $expressedColorKey,
                'female_key' => 'green',
                'female_suffix_key' => 'pf_wild_type',
                'result_key' => 'pf_dominant_result',
                'result_params' => ['locus' => $locus],
            ];
        } else {
            return [
                'title_key' => 'pf_introduce_ar',
                'title_params' => ['locus' => $locus, 'gen' => 1],
                'male_key' => $expressedColorKey,
                'female_key' => 'green',
                'female_suffix_key' => 'pf_wild_type',
                'result_key' => 'pf_ar_result',
            ];
        }
    }
    
    private function getAlleleColorKey(string $locus, string $allele): string
    {
        $colorKeys = [
            'parblue' => ['aqaq' => 'aqua', 'tqtq' => 'turquoise', 'tqaq' => 'seagreen'],
            'ino' => ['inoino' => 'lutino', 'inoW' => 'lutino', 'pldpld' => 'pallid_green', 'pldW' => 'pallid_green'],
            'opaline' => ['opop' => 'opaline_green', 'opW' => 'opaline_green'],
            'cinnamon' => ['cincin' => 'cinnamon_green', 'cinW' => 'cinnamon_green'],
            'dark' => ['Dd' => 'darkgreen', 'DD' => 'olive'],
            'violet' => ['Vv' => 'violet_aqua', 'VV' => 'violet_aqua'],
            'pied_dom' => ['Pi+' => 'pied_dom_green', 'PiPi' => 'pied_dom_green'],
            'pied_rec' => ['pipi' => 'pied_rec_green'],
            'dilute' => ['dildil' => 'dilute_green'],
            'edged' => ['eded' => 'edged_green', 'EdEd' => 'edged_green'],
            'orangeface' => ['ofof' => 'orangefaced_green'],
            'pale_headed' => ['phph' => 'paleheaded_green'],
            'fallow_pale' => ['flpflp' => 'fallow_pale_green'],
            'fallow_bronze' => ['flbflb' => 'fallow_bronze_green'],
        ];
        
        return $colorKeys[$locus][$allele] ?? 'green';
    }
    
    private function generateWarnings(array $genotype, array $colorDef): array
    {
        $warnings = [];
        
        if (isset($genotype['ino']) && in_array($genotype['ino'], ['inoino', 'inoW', 'pldpld', 'pldW'])) {
            $warnings[] = ['key' => 'pf_warning_ino'];
        }
        
        if (isset($genotype['fallow_pale']) || isset($genotype['fallow_bronze'])) {
            $warnings[] = ['key' => 'pf_warning_fallow'];
        }
        
        $tier = $colorDef['tier'] ?? 1;
        if ($tier >= 2) {
            $warnings[] = ['key' => 'pf_warning_complex', 'params' => ['tier' => $tier]];
        }

        return $warnings;
    }

    // ========================================
    // v7.2: 人間が読める交配シナリオ生成
    // ========================================

    /**
     * 目標を作出するための具体的な交配シナリオを生成
     * 「結局どうすればいいのか」を明示する
     */
    private function generateBreedingScenario(array $genotype, array $steps, string $targetKey): array
    {
        $requiredGenes = $this->categorizeRequiredGenes($genotype);
        $totalGenes = count($requiredGenes['autosomal']) + count($requiredGenes['slr']);

        // 単純なケース: 遺伝子が1つだけ
        if ($totalGenes === 0) {
            return $this->generateWildTypeScenario();
        }

        if ($totalGenes === 1) {
            return $this->generateSingleGeneScenario($requiredGenes, $targetKey);
        }

        // 複数遺伝子: 世代ごとの交配計画を生成
        return $this->generateMultiGeneScenario($requiredGenes, $targetKey);
    }

    /**
     * 必要な遺伝子を常染色体/伴性に分類
     */
    private function categorizeRequiredGenes(array $genotype): array
    {
        $autosomal = [];
        $slr = [];

        foreach ($genotype as $locus => $allele) {
            // 野生型は除外
            if (in_array($allele, ['++', 'dd', 'vv', '+W'])) continue;

            $locusInfo = AgapornisLoci::LOCI[$locus] ?? null;
            if (!$locusInfo) continue;

            $isSexLinked = $locusInfo['sex_linked'] ?? false;
            $type = $locusInfo['type'] ?? 'AR';

            $geneInfo = [
                'locus' => $locus,
                'allele' => $allele,
                'type' => $type,
                'colorKey' => $this->getAlleleColorKey($locus, $allele),
            ];

            if ($isSexLinked) {
                $slr[] = $geneInfo;
            } else {
                $autosomal[] = $geneInfo;
            }
        }

        return ['autosomal' => $autosomal, 'slr' => $slr];
    }

    /**
     * 野生型（グリーン）のシナリオ
     */
    private function generateWildTypeScenario(): array
    {
        return [
            'totalGenerations' => 1,
            'phases' => [
                [
                    'phase' => 1,
                    'title_key' => 'pf_scenario_wildtype',
                    'description_key' => 'pf_scenario_wildtype_desc',
                    'pairings' => [
                        ['male_key' => 'green', 'female_key' => 'green', 'result_key' => 'pf_scenario_green_result']
                    ],
                ]
            ],
            'summary_key' => 'pf_scenario_wildtype_summary',
        ];
    }

    /**
     * 単一遺伝子のシナリオ
     */
    private function generateSingleGeneScenario(array $requiredGenes, string $targetKey): array
    {
        $gene = !empty($requiredGenes['autosomal'])
            ? $requiredGenes['autosomal'][0]
            : $requiredGenes['slr'][0];

        $isSLR = !empty($requiredGenes['slr']);
        $type = $gene['type'];

        if ($isSLR) {
            return $this->generateSLRScenario($gene, $targetKey);
        } elseif ($type === 'AD' || $type === 'AID') {
            return $this->generateDominantScenario($gene, $targetKey);
        } else {
            return $this->generateRecessiveScenario($gene, $targetKey);
        }
    }

    /**
     * 伴性劣性遺伝シナリオ
     */
    private function generateSLRScenario(array $gene, string $targetKey): array
    {
        return [
            'totalGenerations' => 2,
            'phases' => [
                [
                    'phase' => 1,
                    'title_key' => 'pf_scenario_slr_phase1',
                    'title_params' => ['locus' => strtoupper($gene['locus'])],
                    'pairings' => [
                        [
                            'male_key' => 'green',
                            'male_note_key' => 'pf_any_male',
                            'female_key' => $gene['colorKey'],
                            'female_note_key' => 'pf_expressing_female',
                            'result_key' => 'pf_scenario_slr_result1',
                            'result_params' => ['locus' => strtoupper($gene['locus'])],
                        ]
                    ],
                ],
                [
                    'phase' => 2,
                    'title_key' => 'pf_scenario_slr_phase2',
                    'title_params' => ['locus' => strtoupper($gene['locus'])],
                    'pairings' => [
                        [
                            'male_key' => 'split_male',
                            'male_note_key' => 'pf_split_male_from_g1',
                            'female_key' => 'any_female',
                            'female_note_key' => 'pf_any_female',
                            'result_key' => 'pf_scenario_slr_result2',
                            'result_params' => ['target' => $targetKey],
                        ]
                    ],
                ],
            ],
            'summary_key' => 'pf_scenario_slr_summary',
            'summary_params' => ['target' => $targetKey],
        ];
    }

    /**
     * 優性遺伝シナリオ（Dark, Violet, Pied Dom）
     */
    private function generateDominantScenario(array $gene, string $targetKey): array
    {
        $isIncomplete = ($gene['type'] === 'AID');
        $needsHomozygous = in_array($gene['allele'], ['DD', 'VV', 'PiPi']);

        if ($needsHomozygous) {
            // DD/VVが必要な場合: 2世代
            return [
                'totalGenerations' => 2,
                'phases' => [
                    [
                        'phase' => 1,
                        'title_key' => 'pf_scenario_dom_phase1',
                        'title_params' => ['locus' => strtoupper($gene['locus'])],
                        'pairings' => [
                            [
                                'male_key' => $gene['colorKey'],
                                'male_note_key' => $isIncomplete ? 'pf_sf_individual' : 'pf_expressing',
                                'female_key' => $gene['colorKey'],
                                'female_note_key' => $isIncomplete ? 'pf_sf_individual' : 'pf_expressing',
                                'result_key' => 'pf_scenario_dom_result1',
                                'result_params' => ['locus' => strtoupper($gene['locus'])],
                            ]
                        ],
                    ],
                    [
                        'phase' => 2,
                        'title_key' => 'pf_scenario_dom_phase2',
                        'pairings' => [
                            [
                                'male_key' => 'df_individual',
                                'male_note_key' => 'pf_df_from_g1',
                                'female_key' => 'any_female',
                                'result_key' => 'pf_scenario_dom_result2',
                                'result_params' => ['target' => $targetKey],
                            ]
                        ],
                    ],
                ],
                'summary_key' => 'pf_scenario_dom_summary',
                'summary_params' => ['target' => $targetKey],
            ];
        } else {
            // Dd/Vvでよい場合: 1世代
            return [
                'totalGenerations' => 1,
                'phases' => [
                    [
                        'phase' => 1,
                        'title_key' => 'pf_scenario_simple',
                        'pairings' => [
                            [
                                'male_key' => $gene['colorKey'],
                                'female_key' => 'green',
                                'result_key' => 'pf_scenario_simple_result',
                                'result_params' => ['target' => $targetKey, 'prob' => '50'],
                            ]
                        ],
                    ],
                ],
                'summary_key' => 'pf_scenario_simple_summary',
                'summary_params' => ['target' => $targetKey],
            ];
        }
    }

    /**
     * 常染色体劣性遺伝シナリオ
     */
    private function generateRecessiveScenario(array $gene, string $targetKey): array
    {
        return [
            'totalGenerations' => 2,
            'phases' => [
                [
                    'phase' => 1,
                    'title_key' => 'pf_scenario_ar_phase1',
                    'title_params' => ['locus' => strtoupper($gene['locus'])],
                    'pairings' => [
                        [
                            'male_key' => $gene['colorKey'],
                            'male_note_key' => 'pf_expressing',
                            'female_key' => 'green',
                            'female_note_key' => 'pf_wildtype',
                            'result_key' => 'pf_scenario_ar_result1',
                            'result_params' => ['locus' => strtoupper($gene['locus'])],
                        ]
                    ],
                ],
                [
                    'phase' => 2,
                    'title_key' => 'pf_scenario_ar_phase2',
                    'pairings' => [
                        [
                            'male_key' => 'split_from_g1',
                            'female_key' => 'split_from_g1',
                            'result_key' => 'pf_scenario_ar_result2',
                            'result_params' => ['target' => $targetKey],
                        ]
                    ],
                ],
            ],
            'summary_key' => 'pf_scenario_ar_summary',
            'summary_params' => ['target' => $targetKey],
        ];
    }

    /**
     * 遺伝子/カラーキーの市場入手性を取得
     *
     * @param string $locus 座位名
     * @param string $colorKey カラーキー
     * @return string 'easy', 'normal', 'difficult' のいずれか
     */
    private function getGeneAvailability(string $locus, string $colorKey): string
    {
        $avail = AgapornisLoci::MARKET_AVAILABILITY;

        // 座位別の特殊処理
        switch ($locus) {
            case 'parblue':
                // アクア、ターコイズ、シーグリーンを区別
                if (strpos($colorKey, 'seagreen') !== false) {
                    return $avail['parblue_sg'] ?? 'normal';
                } elseif (strpos($colorKey, 'turquoise') !== false) {
                    return $avail['parblue_tq'] ?? 'easy';
                } else {
                    return $avail['parblue_aq'] ?? 'easy';
                }

            case 'dark':
                // SF(Dd)とDF(DD)を区別
                $genotype = AgapornisLoci::COLOR_DEFINITIONS[$colorKey]['genotype'] ?? [];
                $darkVal = $genotype['dark'] ?? 'dd';
                if ($darkVal === 'DD') {
                    return $avail['dark_df'] ?? 'normal';
                } elseif ($darkVal === 'Dd') {
                    return $avail['dark_sf'] ?? 'easy';
                }
                return 'easy';

            case 'ino':
                // INO座位: pallid(pld)と lutino(ino)を区別
                $genotype = AgapornisLoci::COLOR_DEFINITIONS[$colorKey]['genotype'] ?? [];
                $inoVal = $genotype['ino'] ?? '++';
                if (strpos($inoVal, 'pld') !== false) {
                    return $avail['pallid'] ?? 'normal';
                }
                return $avail['ino'] ?? 'easy';

            default:
                // 直接マッピング
                return $avail[$locus] ?? 'easy';
        }
    }

    /**
     * 複数遺伝子シナリオ（メイン）
     */
    private function generateMultiGeneScenario(array $requiredGenes, string $targetKey): array
    {
        $autosomal = $requiredGenes['autosomal'];
        $slr = $requiredGenes['slr'];
        $allGenes = array_merge($autosomal, $slr);
        $totalGenes = count($allGenes);

        $geneList = [];
        $colorKeys = [];
        $availabilities = [];
        foreach ($allGenes as $g) {
            $geneList[] = strtoupper($g['locus']);
            $colorKeys[] = $g['colorKey'];
            $availabilities[] = $this->getGeneAvailability($g['locus'], $g['colorKey']);
        }

        $phases = [];
        $currentPhase = 1;

        // ============================================
        // 現実的なシナリオ: 市場から発現個体を入手して交配
        // グリーンから始めるのではなく、発現個体同士を直接交配
        // ============================================

        // フェーズ1: 最初の2つの遺伝子を持つ発現個体同士を交配
        $pairing1 = [
            'purpose_key' => 'pf_cross_expressing',
            'male_key' => $colorKeys[0],
            'male_note_key' => 'pf_expressing_trait',
            'male_note_params' => ['gene' => $geneList[0]],
            'male_availability' => $availabilities[0],
            'female_key' => $colorKeys[1],
            'female_note_key' => 'pf_expressing_trait',
            'female_note_params' => ['gene' => $geneList[1]],
            'female_availability' => $availabilities[1],
            'result_key' => 'pf_offspring_carry_both',
            'result_params' => ['gene1' => $geneList[0], 'gene2' => $geneList[1]],
        ];
        $phases[] = [
            'phase' => $currentPhase,
            'title_key' => 'pf_phase_n_combine_direct',
            'title_params' => ['n' => $currentPhase],
            'description_key' => 'pf_acquire_and_cross_desc',
            'description_params' => ['gene1' => $geneList[0], 'gene2' => $geneList[1]],
            'pairings' => [$pairing1],
        ];
        $currentPhase++;

        // フェーズ2〜: 追加の遺伝子を順次組み合わせ
        for ($i = 2; $i < $totalGenes; $i++) {
            $previousGenes = array_slice($geneList, 0, $i);
            $nextGene = $geneList[$i];
            $nextColorKey = $colorKeys[$i];
            $nextAvailability = $availabilities[$i];

            $pairingN = [
                'purpose_key' => 'pf_add_gene_to_line',
                'purpose_params' => ['gene' => $nextGene],
                'male_key' => 'prev_gen_carrier',
                'male_note_key' => 'pf_carrying_genes',
                'male_note_params' => ['genes' => implode('+', $previousGenes)],
                'female_key' => $nextColorKey,
                'female_note_key' => 'pf_expressing_trait',
                'female_note_params' => ['gene' => $nextGene],
                'female_availability' => $nextAvailability,
                'result_key' => 'pf_some_carry_genes',
                'result_params' => ['genes' => implode('+', array_slice($geneList, 0, $i + 1))],
            ];
            $phases[] = [
                'phase' => $currentPhase,
                'title_key' => 'pf_phase_n_add_gene',
                'title_params' => ['n' => $currentPhase, 'gene' => $nextGene],
                'description_key' => 'pf_add_expressing_desc',
                'description_params' => ['gene' => $nextGene],
                'pairings' => [$pairingN],
            ];
            $currentPhase++;
        }

        // 最終フェーズ: ホモ化・作出
        $phases[] = [
            'phase' => $currentPhase,
            'title_key' => 'pf_phase_n_final',
            'title_params' => ['n' => $currentPhase],
            'description_key' => 'pf_phase_final_desc',
            'description_params' => ['target' => $targetKey],
            'pairings' => [
                [
                    'purpose_key' => 'pf_final_cross',
                    'male_key' => 'multi_carrier',
                    'male_note_key' => 'pf_all_required_genes',
                    'female_key' => 'multi_carrier_or_expressing',
                    'result_key' => 'pf_final_result',
                    'result_params' => ['target' => $targetKey],
                ]
            ],
            'final_note_key' => 'pf_final_note',
            'final_note_params' => ['target' => $targetKey],
        ];

        // Generations = number of phases
        $totalGenerations = count($phases);

        // Collect warnings for difficult-to-obtain foundation birds
        $difficultBirds = [];
        $normalBirds = [];
        for ($i = 0; $i < count($colorKeys); $i++) {
            if ($availabilities[$i] === 'difficult') {
                $difficultBirds[] = ['colorKey' => $colorKeys[$i], 'gene' => $geneList[$i]];
            } elseif ($availabilities[$i] === 'normal') {
                $normalBirds[] = ['colorKey' => $colorKeys[$i], 'gene' => $geneList[$i]];
            }
        }

        return [
            'totalGenerations' => $totalGenerations,
            'requiredGenes' => $geneList,
            'phases' => $phases,
            'availability' => [
                'difficult' => $difficultBirds,
                'normal' => $normalBirds,
            ],
            'summary_key' => 'pf_scenario_multi_summary',
            'summary_params' => [
                'target' => $targetKey,
                'gens' => $totalGenerations,
                'genes' => implode(', ', $geneList),
            ],
            'lineage_advice_key' => 'pf_lineage_advice',
        ];
    }

    // ========================================
    // v7.0: 連鎖遺伝分析
    // ========================================

    /**
     * 目標表現型の連鎖座位要件を分析
     */
    private function analyzeLinkageRequirements(array $genotype): array
    {
        $zLinked = $this->analyzeZLinkedRequirements($genotype);
        $autosomal1 = $this->analyzeAutosomal1Requirements($genotype);

        return [
            'Z_linked' => $zLinked,
            'autosomal_1' => $autosomal1,
        ];
    }

    /**
     * Z染色体連鎖座位の要件分析
     */
    private function analyzeZLinkedRequirements(array $genotype): array
    {
        $required = [];

        // cin (cinnamon)
        $cinVal = $genotype['cinnamon'] ?? '++';
        if (in_array($cinVal, ['cincin', 'cinW', '+cin'])) {
            $required['cinnamon'] = 'cin';
        }

        // ino (ino/pld)
        $inoVal = $genotype['ino'] ?? '++';
        if (in_array($inoVal, ['inoino', 'inoW', '+ino'])) {
            $required['ino'] = 'ino';
        } elseif (in_array($inoVal, ['pldpld', 'pldW', '+pld'])) {
            $required['ino'] = 'pld';
        }

        // opaline
        $opVal = $genotype['opaline'] ?? '++';
        if (in_array($opVal, ['opop', 'opW', '+op'])) {
            $required['opaline'] = 'op';
        }

        // 連鎖の必要性を判定
        $linkedCount = count($required);

        if ($linkedCount < 2) {
            return [
                'requiredLoci' => $required,
                'needsLinkage' => false,
                'note' => '連鎖考慮不要（単一座位のみ）',
            ];
        }

        // 組み換え率を取得
        $recombInfo = $this->getZLinkedRecombinationInfo($required);

        return [
            'requiredLoci' => $required,
            'needsLinkage' => true,
            'recombination' => $recombInfo,
            'cisAdvantage' => $this->calculateCisAdvantage($required),
            'note' => 'Cis配置個体を優先使用すると効率的',
        ];
    }

    /**
     * Z連鎖の組み換え情報を取得
     */
    private function getZLinkedRecombinationInfo(array $required): array
    {
        $info = [];
        $rates = AgapornisLoci::RECOMBINATION_RATES;

        $loci = array_keys($required);

        if (in_array('cinnamon', $loci) && in_array('ino', $loci)) {
            $info['cinnamon-ino'] = [
                'rate' => $rates['cinnamon-ino'] ?? 0.03,
                'parentalProb' => 1 - ($rates['cinnamon-ino'] ?? 0.03),
                'note' => '3%組み換え（強い連鎖）',
            ];
        }

        if (in_array('ino', $loci) && in_array('opaline', $loci)) {
            $info['ino-opaline'] = [
                'rate' => $rates['ino-opaline'] ?? 0.30,
                'parentalProb' => 1 - ($rates['ino-opaline'] ?? 0.30),
                'note' => '30%組み換え（弱い連鎖）',
            ];
        }

        if (in_array('cinnamon', $loci) && in_array('opaline', $loci)) {
            $rate = $rates['cinnamon-opaline'] ?? AgapornisLoci::RECOMBINATION_RATES['cinnamon-opaline'];
            $info['cinnamon-opaline'] = [
                'rate' => $rate,
                'parentalProb' => 1 - $rate,
                'note' => sprintf('%.1f%%組み換え（ほぼ独立分離）', $rate * 100),
            ];
        }

        return $info;
    }

    /**
     * Cis配置の効率優位性を計算
     *
     * @return array Cis vs Trans の期待羽数比較
     */
    private function calculateCisAdvantage(array $required): array
    {
        $loci = array_keys($required);

        // Lacewing (cin + ino) の場合
        if (in_array('cinnamon', $loci) && in_array('ino', $loci) && !in_array('opaline', $loci)) {
            $rate = AgapornisLoci::RECOMBINATION_RATES['cinnamon-ino'] ?? 0.03;
            $cisProb = (1 - $rate) / 2 * 100;  // 片方のメス子 48.5%
            $transProb = $rate / 2 * 100;       // 組み換え必要 1.5%

            return [
                'cisExpectedRatio' => $cisProb,
                'transExpectedRatio' => $transProb,
                'cisExpectedOffspring' => round(100 / $cisProb, 1),
                'transExpectedOffspring' => round(100 / $transProb, 1),
                'efficiencyRatio' => round((100 / $transProb) / (100 / $cisProb), 1),
                'note' => sprintf(
                    'Cis: %.1f%%確率（期待%.1f羽）, Trans: %.1f%%確率（期待%.0f羽）',
                    $cisProb, 100 / $cisProb, $transProb, 100 / $transProb
                ),
            ];
        }

        // その他の組み合わせ（汎用計算）
        // 最も強い連鎖ペアを使用
        if (in_array('cinnamon', $loci) && in_array('ino', $loci)) {
            $rate = AgapornisLoci::RECOMBINATION_RATES['cinnamon-ino'];
        } elseif (in_array('ino', $loci) && in_array('opaline', $loci)) {
            $rate = AgapornisLoci::RECOMBINATION_RATES['ino-opaline'];
        } elseif (in_array('cinnamon', $loci) && in_array('opaline', $loci)) {
            $rate = AgapornisLoci::RECOMBINATION_RATES['cinnamon-opaline'];
        } else {
            $rate = 0.50; // 独立分離
        }

        $cisProb = (1 - $rate) * 50;
        $transProb = $rate * 50;

        return [
            'cisExpectedRatio' => $cisProb,
            'transExpectedRatio' => $transProb,
            'note' => 'Cis配置が効率的',
        ];
    }

    /**
     * 常染色体連鎖座位の要件分析
     */
    private function analyzeAutosomal1Requirements(array $genotype): array
    {
        $required = [];

        // dark
        $darkVal = $genotype['dark'] ?? 'dd';
        if (in_array($darkVal, ['Dd', 'DD'])) {
            $required['dark'] = 'D';
        }

        // parblue
        $pbVal = $genotype['parblue'] ?? '++';
        if (in_array($pbVal, ['aqaq', '+aq'])) {
            $required['parblue'] = 'aq';
        } elseif (in_array($pbVal, ['tqtq', '+tq'])) {
            $required['parblue'] = 'tq';
        } elseif ($pbVal === 'tqaq') {
            $required['parblue'] = 'tq/aq';
        }

        $linkedCount = count($required);

        if ($linkedCount < 2) {
            return [
                'requiredLoci' => $required,
                'needsLinkage' => false,
                'note' => '連鎖考慮不要',
            ];
        }

        $rate = AgapornisLoci::RECOMBINATION_RATES['dark-parblue'] ?? 0.07;

        return [
            'requiredLoci' => $required,
            'needsLinkage' => true,
            'recombinationRate' => $rate,
            'parentalProb' => 1 - $rate,
            'note' => sprintf('dark-parblue: %.0f%%組み換え', $rate * 100),
        ];
    }
}


/**
 * GenotypeEstimator - 表現型から遺伝子型を推定
 * v6.8: SSOT準拠
 */
class GenotypeEstimator
{
    /**
     * 学術表記（血統書の遺伝子型欄）。正本はここ。JS（pedigree.js）は PEDIGREE_DATA 経由で引く。
     * 伴性メスのヘミ接合（+W, inoW など）は pedigreeData() で LOCI のアレルから生成する。
     */
    public const ACADEMIC_NOTATION = [
        // Parblue
        '++' => '+/+', '+aq' => '+/b^aq', 'aqaq' => 'b^aq/b^aq',
        '+tq' => '+/b^tq', 'tqtq' => 'b^tq/b^tq', 'tqaq' => 'b^tq/b^aq',
        // Dark
        'dd' => 'd/d', 'Dd' => 'D/d', 'DD' => 'D/D',
        // Violet
        'vv' => 'v/v', 'Vv' => 'V/v', 'VV' => 'V/V',
        // INO（オス）
        '+ino' => '+/Z^ino', 'inoino' => 'Z^ino/Z^ino',
        '+pld' => '+/Z^pld', 'pldpld' => 'Z^pld/Z^pld', 'pldino' => 'Z^pld/Z^ino',
        // Opaline（オス）
        '+op' => '+/Z^op', 'opop' => 'Z^op/Z^op',
        // Cinnamon（オス）
        '+cin' => '+/Z^cin', 'cincin' => 'Z^cin/Z^cin',
        // 常染色体
        '+flp' => '+/flp', 'flpflp' => 'flp/flp',
        '+flb' => '+/flb', 'flbflb' => 'flb/flb',
        'Pi+' => 'Pi/+', 'PiPi' => 'Pi/Pi',
        '+pi' => '+/pi', 'pipi' => 'pi/pi',
        '+dil' => '+/dil', 'dildil' => 'dil/dil',
        // Edged（正規表記 Ed。旧表記 ed も同じ表示にそろえる）
        'Ed+' => 'Ed/+', '+Ed' => 'Ed/+', 'EdEd' => 'Ed/Ed', '+ed' => 'Ed/+', 'ed+' => 'Ed/+', 'eded' => 'Ed/Ed',
        '+of' => '+/of', 'ofof' => 'of/of',
        '+ph' => '+/ph', 'phph' => 'ph/ph',
    ];

    /** 濃さ欄の値（PHP の resolveDarkGenotype が区別する値）。表にない値は 'none' と同じ扱い。 */
    private const DARKNESS_VALUES = ['none', 'sf', 'df', 'unknown'];

    /**
     * 血統書（pedigree.js）が使う遺伝の情報を、すべてここで計算して1つにまとめる。
     * JS は判定を一切行わず、この表を引くだけにする（SSOT。index.php が PEDIGREE_DATA として注入）。
     *
     * - loci:       座位の順序（confidence の配列の並び）
     * - sexLinked:  伴性座位
     * - wild:       座位ごとの野生型 {male, female}
     * - academic:   遺伝子型 → 学術表記（伴性メスのヘミ接合を含む）
     * - confidence: 色 → {male: [...], female: [...], darkness: {座位: {濃さ欄の値: 確信度}}}
     *               確信度は phenotypeConfidence() の結果（100 / 50 / 0）
     */
    public static function pedigreeData(): array
    {
        $loci = array_keys(AgapornisLoci::LOCI);
        $sexLinked = [];
        $wild = [];
        $academic = self::ACADEMIC_NOTATION;
        foreach (AgapornisLoci::LOCI as $key => $config) {
            $w = AgapornisLoci::wildAllele($key) ?? '+';
            $isSL = (bool)($config['sex_linked'] ?? false);
            if ($isSL) {
                $sexLinked[] = $key;
                foreach (array_keys($config['alleles'] ?? []) as $a) {
                    $a = (string)$a;
                    $academic[$a . 'W'] = ($a === $w) ? $a . '/W' : 'Z^' . $a . '/W';
                }
            }
            $wild[$key] = ['male' => $w . $w, 'female' => $isSL ? $w . 'W' : $w . $w];
        }

        $estimator = new self();
        $confidence = [];
        foreach (AgapornisLoci::COLOR_DEFINITIONS as $color => $def) {
            $eye = $def['eye'] ?? 'black';
            $row = ['darkness' => []];
            foreach (['male', 'female'] as $sex) {
                $byLocus = [];
                foreach ($estimator->estimate($sex, (string)$color, $eye, 'none')['loci'] ?? [] as $l) {
                    $byLocus[$l['locusKey']] = $l['confidence'];
                }
                $row[$sex] = array_map(fn($k) => $byLocus[$k] ?? 0, $loci);
            }
            // 濃さ欄で結果が変わる座位（ダーク）は、濃さ欄の値ごとに持つ
            foreach (self::DARKNESS_VALUES as $dk) {
                foreach ($estimator->estimate('male', (string)$color, $eye, $dk)['loci'] ?? [] as $l) {
                    $row['darkness'][$l['locusKey']][$dk] = $l['confidence'];
                }
            }
            foreach ($row['darkness'] as $locus => $vals) {
                if (count(array_unique($vals)) === 1) unset($row['darkness'][$locus]);   // 濃さ欄で変わらない座位は省く
            }
            $row['darkness'] = (object)$row['darkness'];
            $confidence[$color] = $row;
        }

        return [
            'loci' => $loci,
            'sexLinked' => $sexLinked,
            'wild' => $wild,
            'academic' => $academic,
            'confidence' => $confidence,
        ];
    }

    /**
     * 表現型から遺伝子型を推定
     */
public function estimate(string $sex, string $baseColor, string $eyeColor, string $darkFactor, bool $isJa = true): array
    {
        // COLOR_DEFINITIONSから色定義を取得
        $colorDef = AgapornisLoci::COLOR_DEFINITIONS[$baseColor] ?? null;
        
        if (!$colorDef) {
            return ['error' => 'Unsupported color: ' . $baseColor];
        }
        
        $genotype = $colorDef['genotype'] ?? [];
        $isFemale = ($sex === 'female');
        
        // ダークファクター適用（画面からは小文字 sf/df、旧呼び出しは大文字 SF/DF）
        // 判断できない（INO 系で濃さ未入力）ときは dark を未設定のままにし、確信度 0 にする
        $dark = AgapornisLoci::resolveDarkGenotype(['baseColor' => $baseColor, 'darkness' => $darkFactor]);
        if ($dark !== null) {
            $genotype['dark'] = $dark;
        } else {
            unset($genotype['dark']);
        }
        
        // 伴性遺伝子のヘミ接合変換（メスの場合）
        if ($isFemale) {
            $sexLinkedLoci = ['ino', 'opaline', 'cinnamon'];
            foreach ($sexLinkedLoci as $locus) {
                if (isset($genotype[$locus])) {
                    $val = $genotype[$locus];
                    // ホモ → ヘミ変換
                    if ($val === 'inoino') $genotype[$locus] = 'inoW';
                    elseif ($val === 'pldpld') $genotype[$locus] = 'pldW';
                    elseif ($val === 'opop') $genotype[$locus] = 'opW';
                    elseif ($val === 'cincin') $genotype[$locus] = 'cinW';
                }
            }
        }
        
        // 結果をフォーマット
        $results = [];
        foreach (AgapornisLoci::LOCI as $locusKey => $config) {
            $locusName = $isJa ? ($config['name']['ja'] ?? $locusKey) : ($config['name']['en'] ?? $locusKey);
            $isSexLinked = $config['sex_linked'] ?? false;
            
            $genoValue = $genotype[$locusKey] ?? null;
            
            if ($genoValue === null) {
                // 野生型（ダーク dd・バイオレット vv も LOCI の野生型アレルから作る）
                $wild = AgapornisLoci::wildAllele($locusKey) ?? '+';
                $genoValue = ($isSexLinked && $isFemale) ? $wild . 'W' : $wild . $wild;
            }
            [$confidence, $isConfirmed, $alternatives] = $this->phenotypeConfidence($locusKey, $config, $genoValue, $isSexLinked, $isFemale, $genotype);

            $results[] = [
                'locusKey' => $locusKey,
                'locusName' => $locusName,
                'genotype' => $genoValue,
                'isConfirmed' => $isConfirmed,
                'confidence' => $confidence,
                'alternatives' => $alternatives,
                'isSexLinked' => $isSexLinked,
            ];
        }        
        return [
            'success' => true,
            'sex' => $sex,
            'baseColor' => $baseColor,
            'colorName' => $isJa ? ($colorDef['ja'] ?? $baseColor) : ($colorDef['en'] ?? $colorDef['albs'] ?? $baseColor),
            'eyeColor' => $colorDef['eye'] ?? $eyeColor,
            'loci' => $results,
            'splitPossibilities' => $this->calculateSplitPossibilities($genotype, $isFemale, $isJa),
        ];
    }
    
    /**
     * 表現型だけから遺伝子型をどこまで確定できるか
     *
     * 100: 確定
     *  50: 見た目が同じ2通りがある（ドミナントパイドの Pi+ と PiPi、パリッドのオスの pldpld と pldino）
     *   0: 表現型からは判別できない（劣性の隠れスプリット、INO に隠された座位）
     *
     * @return array{0:int,1:bool,2:array} [確信度, 確定か, 見た目が同じ遺伝子型の候補（50 のときだけ）]
     */
    private function phenotypeConfidence(string $locusKey, array $config, string $genoValue, bool $isSexLinked, bool $isFemale, array $genotype): array
    {
        // ダーク因子が判断できない（INO 系で濃さ未入力）
        if ($locusKey === 'dark' && !isset($genotype['dark'])) return [0, false, []];

        $alleles = AgapornisLoci::splitGenotype($locusKey, $genoValue);
        if ($alleles === null) return [0, false, []];
        $type = $config['type'] ?? 'AR';

        if (AgapornisLoci::isExpressedAlleles($locusKey, $alleles)) {
            // 見た目（phenoForm）が同じ遺伝子型が複数あれば 50（優性の Pi+ / PiPi、パリッドのオスの pldpld / pldino）
            $table = AgapornisLoci::genotypeData()['loci'][$locusKey]['genotypes'];
            $look = $table[AgapornisLoci::canonicalGenotype($locusKey, $genoValue)]['phenoForm'] ?? null;
            $same = array_values(array_filter(AgapornisLoci::canonicalGenotypes($locusKey)[$isFemale ? 'female' : 'male'],
                fn($g) => ($table[$g]['phenoForm'] ?? null) === $look));
            return count($same) > 1 ? [50, false, $same] : [100, true, []];
        }

        // INO は他の座位の見た目を覆い隠す（parblue は色名で、dark は入力で決まるので除外）
        $inoMasked = in_array($genotype['ino'] ?? '', ['inoino', 'inoW'], true)
            && !in_array($locusKey, ['ino', 'parblue', 'dark'], true);
        if ($inoMasked) return [0, false, []];

        if ($type === 'AD' || $type === 'AID') return [100, true, []];   // 優性が出ていない → 野生型で確定
        if ($isSexLinked && $isFemale) return [100, true, []];             // メスはヘミ接合なので隠れキャリアがいない
        return [0, false, []];                                             // 劣性のスプリットは表現型から判別できない
    }

    /**
     * スプリットの可能性を計算
     */
    private function calculateSplitPossibilities(array $genotype, bool $isFemale, bool $isJa = true): array
    {
        $possibilities = [];

        // 表現型に現れない劣性因子の可能性
        $recessiveLoci = [
            'parblue' => ['aq', 'tq'],
            'pied_rec' => ['pi'],
            'dilute' => ['dil'],
            'edged' => ['ed'],
            'orangeface' => ['of'],
            'pale_headed' => ['ph'],
            'fallow_pale' => ['flp'],
            'fallow_bronze' => ['flb'],
        ];

        // 伴性（オスのみスプリット可能）
        if (!$isFemale) {
            $sexLinkedLoci = [
                'ino' => ['ino', 'pld'],
                'opaline' => ['op'],
                'cinnamon' => ['cin'],
            ];
            $recessiveLoci = array_merge($recessiveLoci, $sexLinkedLoci);
        }

        $noteText = $isJa ? '親の情報があれば確定可能' : 'Can be confirmed with parental info';

        foreach ($recessiveLoci as $locus => $alleles) {
            $current = $genotype[$locus] ?? '++';
            // 既にホモ発現していなければスプリットの可能性あり
            foreach ($alleles as $allele) {
                if (strpos($current, $allele) === false) {
                    $locusConfig = AgapornisLoci::LOCI[$locus] ?? null;
                    $locusName = $locusConfig
                        ? ($isJa ? ($locusConfig['name']['ja'] ?? $locus) : ($locusConfig['name']['en'] ?? $locus))
                        : $locus;
                    $possibilities[] = [
                        'locus' => $locusName,
                        'allele' => $allele,
                        'note' => $noteText,
                    ];
                }
            }
        }

        return $possibilities;
    }
}


