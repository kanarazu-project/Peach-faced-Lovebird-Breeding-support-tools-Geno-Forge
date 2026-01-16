# Budgerigar Genetics Source Data
## For BudgerigarLoci class in genetics.php

---

## 1. LOCI DEFINITIONS

### Sex-Linked Recessive (Z chromosome)
```php
'ino' => [
    'name' => 'INO',
    'type' => 'sex-linked',
    'alleles' => ['ino'],  // Lutino (green series) / Albino (blue series)
    'notes' => 'Removes all melanin, red eyes'
],
'opaline' => [
    'name' => 'Opaline',
    'type' => 'sex-linked',
    'alleles' => ['op'],
    'notes' => 'Body color bleeds into wings, reversed striping'
],
'cinnamon' => [
    'name' => 'Cinnamon',
    'type' => 'sex-linked',
    'alleles' => ['cin'],
    'notes' => 'Brown melanin instead of black'
],
'clearbody' => [
    'name' => 'Texas Clearbody',
    'type' => 'sex-linked',
    'alleles' => ['cb'],
    'notes' => 'Reduced body melanin, normal wings'
],
'slate' => [
    'name' => 'Slate',
    'type' => 'sex-linked',
    'alleles' => ['sl'],
    'notes' => 'Very rare, grayish-blue tint'
],
```

### Autosomal Incomplete Dominant
```php
'dark' => [
    'name' => 'Dark Factor',
    'type' => 'autosomal-incomplete-dominant',
    'alleles' => ['D'],
    'effects' => [
        'SF' => 'Dark Green / Cobalt',
        'DF' => 'Olive / Mauve'
    ]
],
'violet' => [
    'name' => 'Violet Factor',
    'type' => 'autosomal-incomplete-dominant',
    'alleles' => ['V'],
    'effects' => [
        'SF' => 'Violet tint (best visible on Cobalt)',
        'DF' => 'Deeper violet'
    ]
],
'spangle' => [
    'name' => 'Spangle',
    'type' => 'autosomal-incomplete-dominant',
    'alleles' => ['Sp'],
    'effects' => [
        'SF' => 'Reversed wing markings (yellow edge, black center)',
        'DF' => 'Pure yellow (green) / Pure white (blue), no melanin'
    ]
],
'grey' => [
    'name' => 'Grey Factor',
    'type' => 'autosomal-dominant',
    'alleles' => ['G'],
    'notes' => 'Grey (blue series) / Grey-green (green series)'
],
```

### Autosomal Recessive
```php
'blue' => [
    'name' => 'Blue',
    'type' => 'autosomal-recessive',
    'alleles' => ['bl'],
    'notes' => 'Removes yellow pigment (psittacofulvin)'
],
'dilute' => [
    'name' => 'Dilute',
    'type' => 'autosomal-recessive',
    'alleles' => ['dil'],
    'notes' => 'Reduces melanin ~50%, pastel appearance'
],
'greywing' => [
    'name' => 'Greywing',
    'type' => 'autosomal-recessive',
    'alleles' => ['gw'],
    'notes' => 'Grey wing markings, diluted body'
],
'clearwing' => [
    'name' => 'Clearwing',
    'type' => 'autosomal-recessive',
    'alleles' => ['cw'],
    'notes' => 'Very pale wing markings'
],
'fallow' => [
    'name' => 'Fallow',
    'type' => 'autosomal-recessive',
    'alleles' => ['f'],
    'variants' => ['German Fallow', 'English Fallow', 'Scottish Fallow'],
    'notes' => 'Brown melanin, red eyes'
],
'recessive_pied' => [
    'name' => 'Recessive Pied (Danish Pied)',
    'type' => 'autosomal-recessive',
    'alleles' => ['pi'],
    'notes' => 'Random patches of clear feathers, dark eyes no iris ring'
],
'saddleback' => [
    'name' => 'Saddleback',
    'type' => 'autosomal-recessive',
    'alleles' => ['sb'],
    'notes' => 'V-shaped marking on back'
],
```

### Autosomal Dominant
```php
'dominant_pied' => [
    'name' => 'Dominant Pied (Australian Pied)',
    'type' => 'autosomal-dominant',
    'alleles' => ['Pi'],
    'notes' => 'Clear patch on back of head/neck'
],
'clearflight' => [
    'name' => 'Clearflight Pied',
    'type' => 'autosomal-dominant',
    'alleles' => ['Cf'],
    'notes' => 'Clear primary flights and tail'
],
'dominant_clearbody' => [
    'name' => 'Dominant Clearbody (Easley)',
    'type' => 'autosomal-dominant',
    'alleles' => ['Cb'],
    'notes' => 'Different from Texas Clearbody'
],
```

### Yellowface Alleles (Complex - same locus as Blue)
```php
'yellowface' => [
    'name' => 'Yellowface',
    'type' => 'autosomal-multiple-alleles',
    'alleles' => [
        'yf1' => 'Yellowface Type 1 (mask only, DF appears whiteface)',
        'yf2' => 'Yellowface Type 2 (yellow floods body after moult)',
        'gf'  => 'Goldenface (deeper yellow, floods body)'
    ],
    'dominance' => 'Green > Goldenface > YF2 > YF1 > Blue'
],
```

---

## 2. BASE COLORS (Dark Factor × Blue interaction)

### Green Series (Yellow base - bl+/bl+ or bl+/bl)
| Genotype | WBO Name | Japanese | Pantone |
|----------|----------|----------|---------|
| D0 (++) | Light Green | ライトグリーン | 375 |
| D1 (+D) | Dark Green | ダークグリーン | 369 |
| D2 (DD) | Olive | オリーブ | 371 |

### Blue Series (White base - bl/bl)
| Genotype | WBO Name | Japanese | Pantone |
|----------|----------|----------|---------|
| D0 (++) | Sky Blue | スカイブルー | 310 |
| D1 (+D) | Cobalt | コバルト | 2915 |
| D2 (DD) | Mauve | モーブ | 535 |

### Grey-Green Series (Green + Grey)
| Genotype | WBO Name | Japanese |
|----------|----------|----------|
| D0 + G | Light Grey-Green | ライトグレイグリーン |
| D1 + G | Dark Grey-Green | ダークグレイグリーン |
| D2 + G | Olive Grey-Green | オリーブグレイグリーン |

### Grey Series (Blue + Grey)
| Genotype | WBO Name | Japanese |
|----------|----------|----------|
| D0 + G | Light Grey | ライトグレイ |
| D1 + G | Medium Grey | ミディアムグレイ |
| D2 + G | Dark Grey | ダークグレイ |

### Violet Series (Blue + Violet, best on Cobalt)
| Genotype | WBO Name | Japanese |
|----------|----------|----------|
| D1 + V(SF) | Visual Violet | バイオレット |
| D1 + V(DF) | Deep Violet | ディープバイオレット |

---

## 3. COMBINED PHENOTYPES

### INO Series
| Base | Phenotype | Japanese | Eye |
|------|-----------|----------|-----|
| Green series | Lutino | ルチノー | Red |
| Blue series | Albino | アルビノ | Red |
| Grey-green + INO | Lutino | ルチノー | Red |
| Grey + INO | Albino | アルビノ | Red |

### Cinnamon Series
| Base | Phenotype | Japanese |
|------|-----------|----------|
| Light Green | Cinnamon Light Green | シナモン ライトグリーン |
| Dark Green | Cinnamon Dark Green | シナモン ダークグリーン |
| Olive | Cinnamon Olive | シナモン オリーブ |
| Sky Blue | Cinnamon Sky Blue | シナモン スカイブルー |
| Cobalt | Cinnamon Cobalt | シナモン コバルト |
| Mauve | Cinnamon Mauve | シナモン モーブ |

### Opaline Series
| Base | Phenotype | Japanese |
|------|-----------|----------|
| Light Green | Opaline Light Green | オパーリン ライトグリーン |
| Dark Green | Opaline Dark Green | オパーリン ダークグリーン |
| Olive | Opaline Olive | オパーリン オリーブ |
| Sky Blue | Opaline Sky Blue | オパーリン スカイブルー |
| Cobalt | Opaline Cobalt | オパーリン コバルト |
| Mauve | Opaline Mauve | オパーリン モーブ |

### Spangle Series
| Factor | Green Series | Blue Series |
|--------|--------------|-------------|
| SF | Spangle (reversed markings) | Spangle (reversed markings) |
| DF | Pure Yellow (DF Spangle) | Pure White (DF Spangle) |

### Dilute Series
| Base | Phenotype | Japanese |
|------|-----------|----------|
| Light Green | Dilute Light Green (Suffused Yellow) | ディルート ライトグリーン |
| Sky Blue | Dilute Sky Blue (Suffused White) | ディルート スカイブルー |

### Greywing Series
| Base | Phenotype | Japanese |
|------|-----------|----------|
| Light Green | Greywing Light Green | グレイウイング ライトグリーン |
| Sky Blue | Greywing Sky Blue | グレイウイング スカイブルー |

### Clearwing Series
| Base | Phenotype | Japanese |
|------|-----------|----------|
| Light Green | Clearwing Light Green (Yellow-wing) | クリアウイング ライトグリーン |
| Sky Blue | Clearwing Sky Blue (Whitewing) | クリアウイング スカイブルー |

### Yellowface Combinations (Blue series only)
| YF Type | Base | Phenotype | Japanese |
|---------|------|-----------|----------|
| YF1 SF | Sky Blue | Yellowface Type 1 Sky Blue | イエローフェイス1 スカイブルー |
| YF2 SF | Sky Blue | Yellowface Type 2 Sky Blue (Seafoam) | イエローフェイス2 スカイブルー |
| GF SF | Cobalt | Goldenface Cobalt | ゴールデンフェイス コバルト |

### Pied Combinations
| Type | Base | Phenotype |
|------|------|-----------|
| Dominant Pied | Light Green | Dominant Pied Light Green |
| Recessive Pied | Light Green | Recessive Pied Light Green |
| Clearflight | Light Green | Clearflight Light Green |

### Lacewing (Cinnamon + INO crossover)
| Base | Phenotype | Japanese | Eye |
|------|-----------|----------|-----|
| Green | Lacewing (Yellow) | レースウイング | Red |
| Blue | Lacewing (White) | レースウイング | Red |

---

## 4. LINKAGE GROUPS (Z Chromosome)

### Sex-Linked Loci on Z Chromosome
```
Z chromosome: ino --- opaline --- cinnamon --- slate --- clearbody
```

### Known Crossover Combinations
| Combination | Result | Japanese |
|-------------|--------|----------|
| cinnamon + ino | Lacewing | レースウイング |
| opaline + ino | Lutino/Albino Opaline | オパーリン ルチノー |
| opaline + cinnamon | Opaline Cinnamon | オパーリン シナモン |
| opaline + cinnamon + ino | Opaline Lacewing | オパーリン レースウイング |

---

## 5. RECOMBINATION RATES (Estimated)

| Loci Pair | Rate | Notes |
|-----------|------|-------|
| cinnamon-ino | ~2-4% | Lacewing is rare |
| ino-opaline | ~15-20% | Moderate crossover |
| cinnamon-opaline | ~20-25% | Moderate crossover |

*Note: Exact rates vary by source, less documented than Lovebirds*

---

## 6. HEALTH CONCERNS BY MUTATION

### High Risk
| Mutation | Risk | Reason |
|----------|------|--------|
| INO (Lutino/Albino) | Immune deficiency | Melanin absence |
| Fallow | Vision issues | Red eyes, light sensitivity |
| DF Spangle | Feather issues | Some lines have weak feathers |

### Moderate Risk
| Mutation | Risk | Reason |
|----------|------|--------|
| Recessive Pied | None specific | Generally healthy |
| Dark Factor DF | Size issues | Some lines smaller |

### Low/None
| Mutation | Notes |
|----------|-------|
| Opaline | No health issues |
| Cinnamon | No health issues |
| Spangle SF | No health issues |
| Violet | No health issues |
| Grey | No health issues |
| Yellowface | No health issues |

---

## 7. EYE COLORS

| Type | Eye Color | Iris Ring |
|------|-----------|-----------|
| Normal | Black | Yes (adult) |
| INO (Lutino/Albino) | Red/Pink | No |
| Fallow | Red/Plum | No |
| Recessive Pied | Black | No (dark-eyed clear) |
| Lacewing | Red | No |
| DF Spangle | Black | Yes |

---

## 8. MARKET AVAILABILITY (Japan)

### Easy (容易)
- Light Green, Sky Blue
- Opaline, Cinnamon
- Lutino, Albino
- Dominant Pied

### Normal (普通)
- Dark Green, Cobalt, Olive, Mauve
- Yellowface (all types)
- Violet (SF)
- Spangle (SF)
- Grey, Grey-green

### Difficult (困難)
- Recessive Pied
- Clearwing
- Dilute
- Fallow
- Saddleback
- Lacewing
- DF Spangle
- Anthracite
- Blackface

---

## 9. WBO STANDARD COLORS (Official)

Reference: https://www.world-budgerigar.org/colourstds.htm

### Primary Colors with Pantone Codes
| Color | Pantone | Hex (approx) |
|-------|---------|--------------|
| Light Green | 375 | #8CC63F |
| Dark Green | 369 | #5A9A38 |
| Olive | 371 | #5E7E3D |
| Sky Blue | 310 | #72D0EB |
| Cobalt | 2915 | #62B5E5 |
| Mauve | 535 | #9BA3B7 |
| Violet | 2725 | #5E5AB5 |
| Grey | 423 | #898B8E |
| Yellow (Lutino) | 107 | #FFD205 |
| White (Albino) | - | #FFFFFF |

---

## 10. GENOTYPE OPTIONS FOR UI

### Autosomal Loci
```php
'blue' => [
    'options' => [
        ['++', 'Green (B⁺/B⁺)'],
        ['+bl', 'Green/blue (B⁺/bl)'],
        ['blbl', 'Blue (bl/bl)'],
    ]
],
'dark' => [
    'options' => [
        ['++', 'No Dark (d/d)'],
        ['+D', 'SF Dark (D/d)'],
        ['DD', 'DF Dark (D/D)'],
    ]
],
'violet' => [
    'options' => [
        ['++', 'No Violet'],
        ['+V', 'SF Violet'],
        ['VV', 'DF Violet'],
    ]
],
'grey' => [
    'options' => [
        ['++', 'No Grey'],
        ['+G', 'Grey (G/+)'],
        ['GG', 'Grey (G/G)'],
    ]
],
'spangle' => [
    'options' => [
        ['++', 'Normal'],
        ['+Sp', 'SF Spangle'],
        ['SpSp', 'DF Spangle'],
    ]
],
'dilute' => [
    'options' => [
        ['++', 'Normal'],
        ['+dil', 'Normal/dilute'],
        ['dildil', 'Dilute'],
    ]
],
'greywing' => [
    'options' => [
        ['++', 'Normal'],
        ['+gw', 'Normal/greywing'],
        ['gwgw', 'Greywing'],
    ]
],
'clearwing' => [
    'options' => [
        ['++', 'Normal'],
        ['+cw', 'Normal/clearwing'],
        ['cwcw', 'Clearwing'],
    ]
],
'dominant_pied' => [
    'options' => [
        ['++', 'Normal'],
        ['+Pi', 'Dominant Pied'],
        ['PiPi', 'Dominant Pied (homo)'],
    ]
],
'recessive_pied' => [
    'options' => [
        ['++', 'Normal'],
        ['+pi', 'Normal/pied'],
        ['pipi', 'Recessive Pied'],
    ]
],
'fallow' => [
    'options' => [
        ['++', 'Normal'],
        ['+f', 'Normal/fallow'],
        ['ff', 'Fallow'],
    ]
],
'yellowface' => [
    'options' => [
        ['++', 'Whiteface/Green'],
        ['+yf1', 'YF Type 1 (het)'],
        ['yf1yf1', 'YF Type 1 (homo - appears whiteface)'],
        ['+yf2', 'YF Type 2 SF'],
        ['yf2yf2', 'YF Type 2 DF'],
        ['+gf', 'Goldenface SF'],
        ['gfgf', 'Goldenface DF'],
    ]
],
```

### Sex-Linked Loci (Male)
```php
'ino_male' => [
    'options' => [
        ['++', 'Normal (ino⁺/ino⁺)'],
        ['+ino', 'Normal/ino (ino⁺/ino)'],
        ['inoino', 'INO (ino/ino)'],
    ]
],
'opaline_male' => [
    'options' => [
        ['++', 'Normal (op⁺/op⁺)'],
        ['+op', 'Normal/opaline (op⁺/op)'],
        ['opop', 'Opaline (op/op)'],
    ]
],
'cinnamon_male' => [
    'options' => [
        ['++', 'Normal (cin⁺/cin⁺)'],
        ['+cin', 'Normal/cinnamon (cin⁺/cin)'],
        ['cincin', 'Cinnamon (cin/cin)'],
    ]
],
```

### Sex-Linked Loci (Female - no split possible)
```php
'ino_female' => [
    'options' => [
        ['+W', 'Normal (ino⁺/W)'],
        ['inoW', 'INO (ino/W)'],
    ]
],
'opaline_female' => [
    'options' => [
        ['+W', 'Normal (op⁺/W)'],
        ['opW', 'Opaline (op/W)'],
    ]
],
'cinnamon_female' => [
    'options' => [
        ['+W', 'Normal (cin⁺/W)'],
        ['cinW', 'Cinnamon (cin/W)'],
    ]
],
```

---

## 11. DILUTION MULTIPLE ALLELES (重要追加)

Greywing / Clearwing / Dilute は同一座位の複対立遺伝子系。

### Dominance Hierarchy
```
dil+ (wild) > dil^cw (Clearwing) > dil^gw (Greywing) > dil (Dilute)
```

### Genotype Options (Updated)
```php
'dilution' => [
    'name' => 'Dilution Locus',
    'type' => 'autosomal-multiple-alleles',
    'alleles' => ['+', 'cw', 'gw', 'dil'],
    'options' => [
        ['++', 'Normal'],
        ['+cw', 'Normal/clearwing'],
        ['+gw', 'Normal/greywing'],
        ['+dil', 'Normal/dilute'],
        ['cwcw', 'Clearwing'],
        ['cwgw', 'Full-Body Greywing'],  // 共優性！
        ['cwdil', 'Clearwing'],
        ['gwgw', 'Greywing'],
        ['gwdil', 'Greywing'],
        ['dildil', 'Dilute'],
    ]
],
```

### Full-Body Greywing (FBG)
- 遺伝型: dil^cw / dil^gw（共優性）
- 外見: Greywingの翼 + Clearwingの体色
- 繁殖: FBG × FBG → 25% Clearwing, 50% FBG, 25% Greywing

---

## 12. PRECISE RECOMBINATION RATES (実測値)

Warner & Daniels による実測データ:

| 座位ペア | 組換え率 | サンプル数 | 出典 |
|---------|---------|-----------|------|
| cinnamon-ino | **2.8%** | 1/36 | Warner & Daniels |
| cinnamon-opaline | **31.7%** | 26/82 | Warner & Daniels |
| opaline-ino | **~30%** | 3/10 | 推定（cin-opと同等） |

### Z染色体上の座位順序（推定）
```
centromere --- slate --- cinnamon --- ino --- opaline
              (近)                              (遠)
```

---

## 13. RARE MUTATIONS (稀少変異)

### Anthracite
```php
'anthracite' => [
    'name' => 'Anthracite',
    'type' => 'autosomal-dominant',
    'alleles' => ['An'],
    'notes' => 'スプリット不可、常に発現、超稀少'
],
```

### Blackface
```php
'blackface' => [
    'name' => 'Blackface',
    'type' => 'autosomal-recessive',
    'alleles' => ['bf'],
    'notes' => '1992年発見、最も稀少な変異'
],
```

### Saddleback
```php
'saddleback' => [
    'name' => 'Saddleback',
    'type' => 'autosomal-recessive',
    'alleles' => ['sb'],
    'notes' => '遺伝機構は研究中'
],
```

### Crested (多因子遺伝)
```php
'crested' => [
    'name' => 'Crested',
    'type' => 'polygenic',
    'theory' => 'PE Theory (Penetrance & Expressivity)',
    'notes' => 'SF浸透率15-20%, DF浸透率100%',
    'types' => ['Tufted', 'Half-Circular', 'Full-Circular']
],
```

---

## 14. COMPOSITE VARIETIES (合成品種)

### Dark-Eyed Clear (DEC)
```php
'dark_eyed_clear' => [
    'required' => ['recessive_pied' => 'pipi', 'clearflight_pied' => '+Cf or CfCf'],
    'phenotype' => '完全クリア（黄/白）、黒目、虹彩輪なし',
    'eye' => 'solid_black'
],
```

### Lacewing (Z連鎖交差産物)
```php
'lacewing' => [
    'required' => 'cinnamon + ino on same Z chromosome',
    'crossover_rate' => '2.8%',
    'phenotype' => 'INOの体色 + シナモンの翼模様',
    'eye' => 'red'
],
```

### Rainbow
```php
'rainbow' => [
    'required' => [
        'opaline' => true,
        'clearwing' => true,
        'yellowface_2' => 'SF',
        'blue' => true
    ],
    'phenotype' => 'ターコイズ～シーグリーンの体色、クリア翼'
],
```

---

## 15. VIOLET VISUAL EXPRESSION

Violetの視覚発現は Dark Factor との組み合わせに依存:

| 遺伝型 | Violet因子 | Dark因子 | 視覚的外見 |
|--------|-----------|---------|-----------|
| Vv dd | SF | なし | 淡いコバルト風（非Visual） |
| **VV dd** | DF | なし | **Visual Violet** |
| **Vv Dd** | SF | SF | **Visual Violet**（最も鮮やか）|
| **VV Dd** | DF | SF | **Visual Violet** |
| Vv DD | SF | DF | モーブに近い |
| VV DD | DF | DF | モーブに近い |

---

## 16. HEALTH CONCERNS (UPDATED)

### Lethal/Semi-Lethal
| 状態 | 遺伝様式 | 平均寿命 | 備考 |
|------|---------|---------|------|
| **Feather Duster** | 劣性 | 2-12ヶ月 | 致死変異 |

### NOT Lethal (誤解されやすい)
| 状態 | 実際 |
|------|------|
| DF Spangle | **健康** - 致死ではない |
| Crested × Crested | **可能** - 致死因子なし |

### Viral (非遺伝)
| 状態 | 原因 |
|------|------|
| French Moult | ウイルス |

---

## 17. FALLOW ALLELIC SERIES

3種のFallowは**異なる座位**（同一座位の対立遺伝子ではない）:

| 種類 | 座位記号 | 眼の特徴 | 虹彩輪 |
|------|---------|---------|--------|
| German (Bronze) | fg | 赤 | **あり** |
| English | fe | 赤（単色） | ほぼなし |
| Scottish | fs | 赤 | ピンク |

証明: 異種Fallow間の交配 → 黒目ノーマルのみ産出

---

## 18. INO ALLELIC SERIES (重要)

INO座位は複対立遺伝子系で、Sex-linked Clearbodyと対立関係:

### Dominance Hierarchy at ino locus
```
ino+ (wild) > cb (Texas Clearbody) > ino (Lutino/Albino)
```

### Allelic Relationships
```php
'ino_locus' => [
    'name' => 'INO Locus',
    'type' => 'sex-linked-multiple-alleles',
    'alleles' => ['+', 'cb', 'ino'],
    'notes' => 'Clearbody is dominant to Ino but recessive to wild-type'
],
```

### 特殊な遺伝パターン
- Normal/ino × Normal/cb → Normal + Clearbody + Ino が出現可能
- **Clearbody/ino オスは Clearbody として発現**
- Normalは同時にino と cb の両方にスプリットになれない

---

## 19. TWO CLEARBODY MUTATIONS (重要！混同注意)

| 名称 | 遺伝様式 | 座位 | 翼色 | チークパッチ |
|------|---------|------|------|------------|
| **Texas Clearbody** | 伴性劣性（ino対立） | Z染色体 ino座位 | 銀灰色 | やや薄い青 |
| **Easley Clearbody** | 常染色体優性 | 常染色体 | **漆黒** | 灰色 |

```php
'texas_clearbody' => [
    'name' => 'Texas Clearbody (SL)',
    'type' => 'sex-linked',
    'locus' => 'ino',
    'allele' => 'cb',
    'dominant_to' => 'ino',
    'recessive_to' => 'wild-type'
],
'easley_clearbody' => [
    'name' => 'Easley Clearbody (Dom)',
    'type' => 'autosomal-dominant',
    'allele' => 'Cb',
    'sf_df_difference' => 'DFはより薄い体色'
],
```

---

## 20. PIED MUTATIONS CLARIFICATION

### 3つの独立したPied座位
| 名称 | 遺伝様式 | 座位記号 | 備考 |
|------|---------|---------|------|
| **Recessive Pied** (Danish) | 常染色体劣性 | pi | 虹彩輪なし |
| **Dominant Pied** (Australian) | 常染色体優性 | Pi | 頭部後方にクリアパッチ |
| **Clearflight Pied** | 常染色体優性 | Pc | Continental/Dutchと同一 |

### Continental = Dutch = Clearflight
```
Continental Clearflight = Dutch Pied = Clearflight Pied
（同一変異、選抜による表現型差異のみ）
```

### 3つのPiedは独立座位
```php
// 1羽が3種すべてのPied変異を持つことが可能
'pied_combination' => [
    'recessive_pied' => 'pipi',
    'dominant_pied' => '+Pi',
    'clearflight_pied' => '+Pc'
],
```

---

## 21. GREY FACTOR DETAILS

### SF vs DF の違い
| 項目 | SF Grey | DF Grey |
|------|---------|---------|
| 体色 | 同じ | 同じ |
| 羽軸（shaft） | 白 | **黒** |
| 綿羽（afterfeather） | 白 | **濃灰** |

### 歴史的注記
- Australian Grey（優性）: 現存
- English Grey（劣性）: **絶滅の可能性**

```php
'grey' => [
    'name' => 'Grey Factor (Australian)',
    'type' => 'autosomal-dominant',
    'allele' => 'G',
    'sf_df_visual' => '羽軸と綿羽の色で判別可能'
],
```

---

## 22. YELLOWFACE DETAILED GENETICS

### 優性関係（Blue座位の複対立遺伝子）
```
Green (+) > Goldenface (gf) > YF Type 2 (yf2) > YF Type 1 (yf1) > Blue (bl)
```

### SF vs DF の表現型差異（重要！）
| 変異 | SF | DF |
|------|----|----|
| **YF Type 1** | 黄色マスク、体は青 | **白マスクに見える**（blue同様）|
| **YF Type 2** | 黄色が体全体に浸透（ターコイズ） | 浸透が弱い |
| **Goldenface** | 濃い黄色が体に浸透 | 浸透が弱い |

### 遺伝子型オプション（更新版）
```php
'yellowface' => [
    'options' => [
        ['++', 'Green'],
        ['+bl', 'Green/blue'],
        ['blbl', 'Blue (Whiteface)'],
        ['+yf1', 'Green/yf1'],
        ['yf1bl', 'YF1 SF (yellow mask)'],
        ['yf1yf1', 'YF1 DF (appears whiteface!)'],
        ['+yf2', 'Green/yf2'],
        ['yf2bl', 'YF2 SF (seafoam/turquoise)'],
        ['yf2yf2', 'YF2 DF (yellow restricted)'],
        ['+gf', 'Green/gf'],
        ['gfbl', 'Goldenface SF'],
        ['gfgf', 'Goldenface DF'],
        ['yf2yf1', 'YF2/YF1 (appears YF2)'],
        ['gfyf2', 'GF/YF2 (appears GF)'],
    ]
],
```

---

## 23. HALF-SIDER (非遺伝性)

### 定義
Half-siderは**キメラ（Tetragametic Chimera）**であり、遺伝変異ではない。

### 発生機構
- 2つの受精卵（異なる遺伝型）が初期発生段階で融合
- 2細胞期〜64細胞期の間に発生
- 体の左右で異なる遺伝型が発現

### 繁殖
- **再現不可能** - 生殖細胞は片方の遺伝型のみ
- 繁殖しても通常の子孫のみ産出

### Gynandromorph（雌雄モザイク）
- 稀に左右で性別が異なる個体も存在
- 蝋膜（cere）が半分青/半分茶色

```php
// genetics.phpには実装しない（遺伝しないため）
// 参考情報として記載のみ
```

---

## 24. SUFFUSION GENETICS (浸透遺伝学)

### 体色浸透に影響する因子
| 因子 | 効果 |
|------|------|
| Dark Factor | メラニン構造変化 → 体色濃化 |
| Violet Factor | メラニン量増加 → 体色濃化 |
| Dilute | メラニン95%減少 → 淡色化 |
| YF2/GF | 黄色色素が体全体に浸透 |
| Opaline | 体色が翼に浸透（オパール効果）|

### Suffused Yellow/White
- Dilute + 若干の浸透 = Suffused
- 浸透強度は個体差あり（修飾遺伝子による）

---

## 25. HISTORICAL MUTATIONS (絶滅変異)

| 変異 | 発見 | 状態 | 備考 |
|------|------|------|------|
| NSL Lutino | 1870-75年 | **絶滅** | 非伴性劣性だった |
| English Grey | 1934年頃 | **絶滅の可能性** | 劣性Grey |
| 初期Suffused | 1870年代 | 現存 | 最古の変異 |

---

## 26. MOLECULAR GENETICS (分子遺伝学)

### MuPKS Gene (Blue Locus)
セキセイインコで初めて同定されたメンデル形質の遺伝子。

```php
'MuPKS' => [
    'gene_name' => 'Multidomain Polyketide Synthase',
    'chromosome' => 1,  // 最大の染色体
    'position' => '21,019,187 - 21,445,705 bp',
    'mutation' => 'R644W',  // Arg → Trp置換
    'effect' => 'psittacofulvin（黄色色素）合成停止',
    'inheritance' => 'autosomal-recessive',
    'domain' => 'MAT (malonyl-CoA:ACP transacylase)',
],
```

### 分子メカニズム
- MuPKSは**ポリケチド合成酵素**をコード
- 脂肪酸ユニットからpsittacofulvin前駆体を反復合成
- R644W変異はMAT活性部位の塩橋を破壊
- 野生型MuPKSを酵母で発現 → 黄色色素産生
- blue変異型を酵母で発現 → 色素産生なし

### SLC45A2 Gene (INO関連候補)
Z染色体上の3つの候補遺伝子:

| 遺伝子 | 機能 | 役割 |
|--------|------|------|
| **SLC45A2** | イオン輸送体 | メラニン合成のメラノソーム輸送 |
| TYRP1 | チロシナーゼ関連蛋白質1 | ユーメラニン産生 |
| AGRP | アグーチ関連ニューロペプチド | メラノコルチン受容体拮抗 |

### Psittacofulvin（プシタコフルビン）
- インコ科特有の赤/橙/黄色ポリエン色素
- カロテノイドとは異なり**内因性合成**（食事由来ではない）
- 羽毛濾胞細胞で合成
- 緑色 = psittacofulvin（黄）+ 構造色（青）
- Blue変異 = psittacofulvin欠損 → 青色構造のみ

---

## 27. MOTTLED MUTATION (進行性斑)

### 特徴
```php
'mottled' => [
    'name' => 'Mottled',
    'type' => 'uncertain', // 変異か障害か未確定
    'inheritance' => 'possibly_autosomal',
    'progression' => true,  // 換羽ごとに進行
],
```

### 進行パターン
1. **孵化時**: 通常の羽色で誕生
2. **初回換羽**: わずかにパイド様の白斑出現
3. **換羽ごと**: 白斑面積が拡大
4. **最終的**: 全身がクリア（模様・色素なし）

### Recessive Piedとの違い
| 特徴 | Mottled | Recessive Pied |
|------|---------|----------------|
| 虹彩輪 | **あり** | なし |
| 進行性 | **毎換羽で増加** | 変化なし |
| 遺伝 | 不確定 | 常染色体劣性 |

### Progressive Greying（進行性灰化）
- メラニン産生細胞の**進行的消失**
- 他の動物種（犬、猫、マウス）でも類似現象
- 遺伝的変異か後天的障害か議論中

---

## 28. FADED & BROWNWING MUTATIONS (希少変異)

### Faded Mutation
```php
'faded' => [
    'name' => 'Faded',
    'type' => 'autosomal-dominant',
    'effect' => 'メラニン分布減少（特に頭部・上半身）',
    'visual' => 'グラデーション状に淡色化',
    'status' => 'very_rare',
],
```

### Brownwing Mutation
```php
'brownwing' => [
    'name' => 'Brownwing',
    'type' => 'autosomal-recessive',
    'phenotype' => 'Cinnamonと類似（茶色翼模様）',
    'eye' => 'plum_colored',  // プラム色
    'status' => 'extremely_rare_or_extinct',
    'history' => [
        'British race' => '真の変異、戦後消失',
        'Continental race' => '選抜育種による色相、絶滅'
    ]
],
```

### 歴史的背景
- British Brownwing: 明確な変異、第二次世界大戦で消失
- Raymaeker Brownwings: 選抜育種による系統、戦中消失
- **再出現の可能性**: 認識されていないだけかも

---

## 29. FEATHER DUSTER SYNDROME (詳細)

### 遺伝学
```php
'feather_duster' => [
    'name' => 'Feather Duster / Chrysanthemum',
    'type' => 'autosomal-recessive',
    'first_recorded' => 1966,
    'origin' => 'English Budgerigar（展示用系統）',
    'cause' => [
        'primary' => 'recessive_gene',
        'possible' => 'herpesvirus_cofactor'
    ],
],
```

### 羽毛異常の詳細
| 特徴 | 正常 | Feather Duster |
|------|------|----------------|
| 成長 | 一定長で停止 | **成長し続ける** |
| 羽軸 | 直線 | **湾曲** |
| 羽枝・小羽枝 | 連結 | **連結せず** |
| 外見 | 整然 | ふわふわ/もじゃもじゃ |

### 推定される分子機構
- 羽毛成長停止の**制御遺伝子**の機能喪失
- 羽毛サイクルの終了シグナル欠損

### 予後
- 平均寿命: 2-12ヶ月
- 飛行不可
- 他の奇形を併発することあり（小眼球症など）
- **治療法なし**

---

## 30. HAGOROMO / HELICOPTER BUDGIE (羽衣)

### 概要
```php
'hagoromo' => [
    'name' => 'Hagoromo / Helicopter / Japanese Budgie',
    'origin' => 'Japan, 1960s',
    'derived_from' => 'Crested mutation',
    'type' => 'polygenic_modified',
],
```

### 名称の由来
- 「羽衣」= 天人の着物（仏教）
- 背中の羽が天女の衣を連想させる

### 表現型の詳細
| 部位 | 特徴 |
|------|------|
| 頭部 | クレスト（冠羽） |
| 背中 | **花状の羽毛**（約18枚が放射状） |
| 翼付根 | フリル状の羽毛 |

### WBO基準（2018年3月）
> 「各翼の付根に、10枚以下の羽毛で構成される対称的で均一な花状のクレストを有すること」

### 系統の発展
| 系統 | 成立年代 | 起源 |
|------|---------|------|
| European/Continental | 1940年代 | ヨーロッパ |
| American | 1950年代 | カナダ |
| **Japanese (Hagoromo)** | **1980年代** | **日本** |

### 繁殖
- 高品質Hagoromo × 高品質Hagoromo が理想
- またはHagoromo × DF Crested
- 価格帯: $200-$400（希少変異でさらに高額）

---

## 31. MELANISTIC SPANGLE (メラニスティック スパングル)

### 概要
```php
'melanistic_spangle' => [
    'name' => 'Melanistic Spangle / Danish Dominant / Cleartail',
    'origin' => 'Brisbane, Australia, May 1991',
    'breeder' => 'Garry Heuval',
    'derived_from' => 'conventional Spangle stock',
],
```

### 通常Spangleとの違い
| 特徴 | 通常SF Spangle | Melanistic Spangle |
|------|----------------|-------------------|
| 巣羽（nest feather） | Spangle模様 | **Normal様** |
| 成鳥換羽後 | Spangle維持 | **Spangle化** |
| メラニン量 | 減少 | **増加** |
| 体色 | やや薄い | **濃い・豊か** |
| 頬パッチ | 中断あり | **完全なバイオレット/グレー** |
| 喉スポット | 通常6個 | **完全な黒6個** |

### Double Factor Melanistic Spangle
- **Clearwingに類似**した外見
- 体色強度はやや低下
- 完全な白/黄色にはならない（通常DF Spangleと異なる）

---

## 32. VIOLET × DARK FACTOR INTERACTIONS (詳細)

### Visual Violetの条件
「Visual Violet」として展示基準を満たす組み合わせ:

| 遺伝型 | Violet | Dark | 外見 | Visual? |
|--------|--------|------|------|---------|
| Vv dd | SF | 0 | 淡いCobalt風 | **No** |
| **VV dd** | DF | 0 | 鮮やかなViolet | **Yes** |
| **Vv Dd** | SF | SF | **最も鮮やか** | **Yes** |
| VV Dd | DF | SF | 深いViolet | **Yes** |
| Vv DD | SF | DF | Mauve風 | No |
| VV DD | DF | DF | Mauve風 | No |

### 作用機序の違い
| 因子 | 作用 |
|------|------|
| **Dark Factor** | 羽枝の**構造**を変化 |
| **Violet Factor** | **メラニン量**を増加 |

### 判別ポイント（SF Violet Cobalt vs Cobalt）
| 部位 | Cobalt | SF Violet Cobalt |
|------|--------|------------------|
| 尾羽 | 全体が紺 | **根元がターコイズ** |
| 風切羽 | 濃紺 | **光沢あるターコイズ調** |

### Dark Factorなしでも Visual Violet可能
- DF Violet Skyblue (VV dd) = Visual Violet
- Dark Factorは必須ではない（従来の誤解）

---

## 33. IRIS RING DEVELOPMENT (虹彩輪の発達)

### 年齢による発達タイムライン
| 月齢 | 虹彩の状態 |
|------|-----------|
| 0-4ヶ月 | **真っ黒**（虹彩見えず） |
| 4-6ヶ月 | **うっすら**輪が見え始める |
| 6-8ヶ月 | **明確な**灰/白の輪 |
| 8ヶ月以上 | **はっきりした白い輪** |

### 虹彩輪が発達しない変異
```php
'no_iris_ring_mutations' => [
    'recessive_pied' => true,   // 生涯黒目
    'dark_eyed_clear' => true,  // 生涯黒目
    'ino' => 'red/pink eyes',   // 別機構
    'fallow' => 'red eyes',     // 別機構
    'lacewing' => 'red eyes',   // 別機構
],
```

### 環境因子
- アビアリー飼育の個体は**成熟が早い**傾向
- 単独飼育より集団飼育で虹彩発達が早い

---

## 34. CERE COLOR GENETICS (蝋膜の遺伝学)

### 性決定との関係
| 性別 | 成鳥の蝋膜色 | ホルモン |
|------|-------------|---------|
| オス | **青** | エストロゲン欠乏 |
| メス | **茶/ベージュ** | エストロゲン存在 |

### ホルモン機構
- 青色 = エストロゲン**不在**の結果（構造色）
- 茶色 = エストロゲンによるメラニン沈着
- テストステロン投与メスでも完全な青にはならない

### 年齢による変化
| 年齢 | オス | メス |
|------|------|------|
| 若鳥 | ピンク〜バイオレット | 薄い青（鼻孔周囲に白輪） |
| 成鳥 | **濃い青** | **茶/ベージュ** |
| 発情期メス | - | **濃い茶、痂皮状** |

### 変異による例外
| 変異 | オスの蝋膜 | メスの蝋膜 |
|------|-----------|-----------|
| INO (Lutino/Albino) | **ピンク維持** | 白〜ベージュ |
| Recessive Pied | やや薄いピンク〜青 | 白〜薄茶 |

### 健康上の注意
- **オスの蝋膜が茶色化** = 深刻な健康問題
  - セルトリ細胞腫瘍（精巣癌）の可能性
  - エストロゲン分泌腫瘍
  - 栄養欠乏

---

## 35. BREEDING & FERTILITY GENETICS (繁殖遺伝学)

### 産卵数
- 平均クラッチサイズ: **5-6個**（4-8個の範囲）
- 年間2回以上のクラッチ可能

### 繁殖力の遺伝性
```
オスの繁殖力: 高度に遺伝する（父→息子）
メスの繁殖力: 遺伝性は低い
```

### 不妊の原因
| 原因 | 詳細 |
|------|------|
| 遺伝的 | 近親交配による劣性致死因子の蓄積 |
| 羽毛 | 肛門周囲の**厚い羽毛**が交尾を妨害 |
| 栄養 | エネルギー不足（育雛中10倍必要） |
| 季節 | 不適切な時期の繁殖開始 |
| 疾病 | French Moult, Megabacteria等 |

### 胚致死
- **Feather Duster遺伝子**: ホモ接合で発現、致死
- 一般的な孵化失敗原因は**環境的**（湿度、温度等）

### 推奨事項
- 近親交配を避ける
- 肛門周囲の羽毛をトリミング
- 繁殖期のエネルギー補給
- 実績のある無関係のオスを導入（不妊系統対策）

---

## 36. SLATE MUTATION DETAILS (スレート変異詳細)

### 発見
- **1935年5月**: T.S. Bowman（カーライル、イギリス）
- 最初の個体: Skyblue SlateとCobalt Slateのメス

### 遺伝学
```php
'slate' => [
    'name' => 'Slate',
    'type' => 'sex-linked-recessive',
    'chromosome' => 'Z',
    'locus_position' => 'close to cinnamon',
    'first_bred' => '1935',
    'status' => 'very_rare',
],
```

### Z染色体上の連鎖
```
centromere --- slate --- cinnamon --- ino --- opaline
              (最も動原体に近い)
```

### Cinnamon-Slateの困難さ
- slateとcinnamonは**非常に近い**位置
- 交差頻度が極めて低い
- Cinnamon Slateを作出するには長期間の繁殖が必要

### 現状
- アメリカでは**非常に稀**
- ヨーロッパではやや入手可能
- 日本では**ほぼ見かけない**

---

## 37. CROSSING-OVER DETAILS (交差詳細)

### Z染色体の構造
```
       短腕 (short arm)
          │
    ╔═════╧═════╗
    ║ centromere ║  ← セントロメア（動原体）
    ╚═════╤═════╝
          │
       長腕 (long arm)  ← 1.5倍長い
```

短腕:長腕 = 1:1.5

### 正確な交差率（Crossover Values）
Warner & Daniels + その他の研究者による実測値:

| 遺伝子座ペア | 交差率 | サンプル | 連鎖の強さ |
|-------------|--------|---------|-----------|
| **Cinnamon-Ino** | **≥4±3%** | 1/36 + 1/18 | **非常に強い** |
| **Cinnamon-Slate** | **~5%** | 調査中 | **非常に強い** |
| Cinnamon-Opaline | 32-36% | 41/113 | 中程度 |
| Opaline-Ino | ~30% | 3/10 | 中程度 |
| Opaline-Slate | **40-41%** | 22/54 | **ほぼなし** |

### Type I vs Type II スプリット

#### Type I（カップリング/Coupling）
```
Z染色体1: [cin]-[op]  ← 同じ染色体上に両変異
Z染色体2: [+  ]-[+ ]
```
- Cinnamon-Opaline × Normal から産出
- 子孫: 主にCinnamon-Opaline + Normal
- 交差でまれにCinnamon単独 or Opaline単独

#### Type II（リパルジョン/Repulsion）
```
Z染色体1: [cin]-[+ ]  ← 変異が別々の染色体に
Z染色体2: [+  ]-[op]
```
- Cinnamon × Opaline から産出
- 子孫: 主にCinnamon + Opaline（別々）
- 交差でまれにCinnamon-Opaline or Normal

### Lacewing産出の確率
```
Type II cin/ino オス × Normal メス
↓
交差確率 ~3-4%
↓
Lacewing（cin-ino）メス産出
```

---

## 38. BLACKWING MUTATION (ブラックウイング)

### 発見
```php
'blackwing' => [
    'name' => 'Blackwing',
    'discovered' => 2002,
    'location' => 'Venezuela',
    'discoverer' => 'Alejandro Alvarez',
    'type' => 'autosomal-recessive',
],
```

### 外見
| 特徴 | 詳細 |
|------|------|
| 翼 | **メラニン増加**で黒く見える |
| 翼模様 | 拡大・暗化、翼全体を覆う |
| ぼかし | 黒が**にじんだ**ように見える |
| 喉スポット | 原型にはなし（選抜で出現） |

### 遺伝
- **常染色体劣性**
- Cinnamon系と組み合わせると茶色の模様に変化
- Fallow, Dilute, Greywing等との組み合わせも可能

### WBO未認定
- 2024年時点でまだ公式カラー標準に未登録
- Blackfaceも同様

---

## 39. ANTHRACITE DETAILS (アンスラサイト詳細)

### 発見と歴史
```php
'anthracite' => [
    'name' => 'Anthracite',
    'discovered' => 1998,
    'location' => 'Germany',
    'discoverer' => 'Hans-Jürgen H. Lenk',
    'type' => 'autosomal-incomplete-dominant',
    'possibly' => 'English Grey re-emergence',
],
```

### SF vs DF の違い
| 因子 | Green系 | Blue系 |
|------|---------|--------|
| **SF** | Dark Greenより深い | Cobaltより深い灰青 |
| **DF** | 深いオリーブ色 | **ほぼ黒**に近い灰色 |

### DFの特徴
- 体色: 極めて濃い灰色
- 翼模様: **漆黒**
- 頬パッチ: 体色と同じ濃灰色
- 視覚的に「黒いセキセイ」に最も近い

### English Greyとの関係
> "Anthraciteの記述と遺伝的挙動はEnglish Greyと同一である限り、AnthraciteはEnglish Greyの再出現である可能性が高い"

### 国際的普及（2008年末時点）
🇩🇪ドイツ → 🇺🇸アメリカ, 🇧🇪ベルギー, 🇨🇦カナダ, 🇬🇧イギリス, 🇫🇮フィンランド, 🇳🇱オランダ, 🇮🇹イタリア, 🇳🇴ノルウェー, 🇸🇪スウェーデン, 🇨🇭スイス, 🇵🇰パキスタン

---

## 40. BLACKFACE DETAILS (ブラックフェイス詳細)

### 発見
```php
'blackface' => [
    'name' => 'Blackface',
    'discovered' => 1982,
    'location' => 'Netherlands',
    'type' => 'autosomal-recessive',
    'status' => 'extremely_rare',
],
```

### 外見
- 顔（マスク部分）が**黒い縞模様**で覆われる
- 通常の白/黄色マスクの代わりにゼブラ様の模様
- 体は通常の色

### 繁殖の困難さ
- **近交弱勢**: Blackface × Blackface を続けると
  - 体サイズ縮小
  - 繁殖力低下
  - 健康問題

### 推奨繁殖法
```
Blackface × Normal/Blackface（スプリット）
↓
健康な子孫を維持しつつBlackface産出
```

---

## 41. FALLOW VARIANTS (ファロー変異体詳細)

### 4種類のFallow（独立座位）

| 名称 | 国名 | 座位 | 眼 | 虹彩輪 | 発見 |
|------|------|------|-----|--------|------|
| **German/Continental** | ドイツ・大陸 | fg | 赤 | **明瞭** | 1931頃 |
| **English** | イギリス | fe | 赤 | 微小 | 1937 |
| **Scottish (Moffat)** | スコットランド | fs | 赤 | **ほぼなし** | 1930 |
| **Australian (Bronze)** | オーストラリア | fa | 赤 | あり | 1930年代 |

### 各Fallowの表現型の違い
| 種類 | 体色 | 翼模様 | 備考 |
|------|------|--------|------|
| German | やや明るい | 茶色 | 最も一般的 |
| English | 淡い | 淡茶 | 稀少 |
| Scottish | 淡い | 灰茶 | 非常に稀少 |

### 独立座位の証明
```
German Fallow × English Fallow
↓
子孫: 全てNormal（黒目）
↓
結論: 異なる座位の変異
```

---

## 42. GERMAN TERMINOLOGY (ドイツ語用語対照表)

| German | English | 日本語 |
|--------|---------|--------|
| Wellensittich | Budgerigar | セキセイインコ |
| Grünreihe | Green series | グリーン系 |
| Blaureihe | Blue series | ブルー系 |
| Zimt | Cinnamon | シナモン |
| Opalin | Opaline | オパーリン |
| Falbe | Fallow | ファロー |
| Schiefer | Slate | スレート |
| Schwarzgesicht | Blackface | ブラックフェイス |
| Schwarzflügel | Blackwing | ブラックウイング |
| Haube | Crested | クレスト |
| Rezessiv Schecke | Recessive Pied | レセシブパイド |
| Hellflügel | Clearwing | クリアウイング |
| Grauflügel | Greywing | グレイウイング |
| Aufgehellt | Dilute | ディルート |
| Spalt | Split | スプリット |
| Kreuzung | Crossing-over | 交差 |

---

## 43. DUTCH TERMINOLOGY (オランダ語用語対照表)

| Dutch | English | 日本語 |
|-------|---------|--------|
| Grasparkiet | Budgerigar | セキセイインコ |
| Kaneel | Cinnamon | シナモン |
| Vererving | Inheritance | 遺伝 |
| Kruising | Crossover | 交差 |
| Overerving | Heredity | 遺伝性 |
| Fokken | Breeding | 繁殖 |
| Mutatie | Mutation | 変異 |
| Pastel | Dilute | ディルート |
| Overgoten | Suffused | サフューズド |

---

## 44. HISTORICAL TIMELINE (変異発見年表)

| 年 | 変異 | 発見地 |
|----|------|--------|
| 1870-75 | NSL Lutino（絶滅）| 英国/大陸欧州 |
| 1870s | Suffused（Dilute）| 英国 |
| 1910 | Blue | ベルギー |
| 1916 | Dark Factor (Olive) | フランス |
| 1921 | Mauve | フランス |
| 1928 | Opaline | オーストラリア |
| 1930 | Scottish Fallow | スコットランド |
| 1931 | German Fallow | ドイツ |
| 1931 | SL Lutino（現存）| 英国/大陸欧州 |
| 1934 | English Grey（絶滅?）| 英国 |
| 1935 | Slate | 英国（T.S. Bowman）|
| 1937 | English Fallow | 英国 |
| 1948 | Dominant Grey | オーストラリア |
| 1971 | Spangle | オーストラリア |
| 1982 | Blackface | オランダ |
| 1991 | Melanistic Spangle | オーストラリア |
| 1998 | Anthracite | ドイツ（H.J. Lenk）|
| 2002 | Blackwing | ベネズエラ |

---

## Sources

- [WBO Colour Standards](https://www.world-budgerigar.org/colourstds.htm)
- [WBO Budgerigar Colour Guide](https://www.world-budgerigar.org/budgerigarcolourguide.htm)
- [Budgerigar colour genetics - Wikipedia](https://en.wikipedia.org/wiki/Budgerigar_colour_genetics)
- [Budgie Mutations 101](https://www.backyardchickens.com/threads/budgie-mutations-101.1494504/)
- [Yellowface Genetics - MUTAVI](https://www.mutavi.info/index.php?art=yellowface)
- [Spangle Budgerigar - BCSA](https://bcsa.com.au/varieties/spangle-budgerigar/)
- [Crossing-over in Sex-chromosome - MUTAVI](https://www.mutavi.info/index.php?art=sexchrom)
- [Clearwing mutation - Wikipedia](https://en.wikipedia.org/wiki/Clearwing_budgerigar_mutation)
- [Dark Eyed Clear - BCSA](https://bcsa.com.au/varieties/dark-eyed-clear-budgerigar/)
- [Violet mutation - Wikipedia](https://en.wikipedia.org/wiki/Violet_budgerigar_mutation)
- [German Fallow - Wikipedia](https://en.wikipedia.org/wiki/German_Fallow_budgerigar_mutation)
- [Crested Budgerigars Genetics - BCSA](https://bcsa.com.au/crested-budgerigars-genetics/)
- [Anthracite mutation - Wikipedia](https://en.wikipedia.org/wiki/Anthracite_budgerigar_mutation)
- [Half-sider budgerigar - Wikipedia](https://en.wikipedia.org/wiki/Half-sider_budgerigar)
- [Clearflight Pied - Wikipedia](https://en.wikipedia.org/wiki/Clearflight_Pied_budgerigar_mutation)
- [Sex-linked Clearbody - Wikipedia](https://en.wikipedia.org/wiki/Sex-linked_Clearbody_budgerigar_mutation)
- [Dominant Clearbody - Wikipedia](https://en.wikipedia.org/wiki/Dominant_Clearbody_budgerigar_mutation)
- [Dominant Grey - Wikipedia](https://en.wikipedia.org/wiki/Dominant_Grey_budgerigar_mutation)
- [Yellowface I - Wikipedia](https://en.wikipedia.org/wiki/Yellowface_I_budgerigar_mutation)
- [Ino mutation - Wikipedia](https://en.wikipedia.org/wiki/Ino_budgerigar_mutation)
- [Halfsiders and Mosaics - OG VZW](https://www.ogvzw.org/halfsiders-gynandromorphism-and-mosaics/)
- [Dutch Pied History - Euronet](https://www.euronet.nl/users/hnl/dutchpie.htm)
- [Dilute mutation - Wikipedia](https://en.wikipedia.org/wiki/Dilute_budgerigar_mutation)
- [MuPKS Gene - Cell Journal](https://www.cell.com/cell/fulltext/S0092-8674(17)30941-8)
- [Genetic Mapping of Yellow Feather Pigmentation - PMC](https://pmc.ncbi.nlm.nih.gov/articles/PMC5951300/)
- [SLC45A2 mutations in parrot feathers - G3 Journal](https://academic.oup.com/g3journal/article/14/2/jkad254/7379049)
- [The Colour Gene MuPKS - North East Budgerigar Society](https://northeastbudgerigarsociety.com/the-colour-gene-mupks-in-budgerigars/)
- [Mottled mutation - Petiska](https://www.petiska.com/budgie-mutations-colors-varieties/)
- [Brownwing and Faded Budgerigar - Ornitho-Mutations](https://www.ornitho-mutations.com/didier/fadedbudgerigar.htm)
- [Feather Duster Budgerigar - Wikipedia](https://en.wikipedia.org/wiki/Feather_duster_budgerigar)
- [Feather Duster Budgies - Lafeber](https://lafeber.com/pet-birds/feather-duster-budgies/)
- [Hagoromo Budgie - Budgiefly](https://budgiefly.com/hagoromo-budgie/)
- [Hagoromo mutation - Petiska](https://www.petiska.com/hagoromo-helicopter-japones-budgies-breeding-mutation/)
- [Melanistic Spangle - United Budgerigar Society](https://unitedbudgies.org.au/beginner-information/about-breeding-genetics/melanistic-spangle/)
- [The Melanistic Spangle - Best of Breeds](http://www.bestofbreeds.com/spanglebudgerigars.co.uk/articles/attwood.htm)
- [Dark Factor - Keith Leedham](http://www.absbudgieclub.org.au/wp-content/uploads/2016/03/THE-DARK-FACTOR.pdf)
- [Violet mutation - Wikipedia](https://en.wikipedia.org/wiki/Violet_budgerigar_mutation)
- [Slate mutation - Wikipedia](https://en.wikipedia.org/wiki/Slate_budgerigar_mutation)
- [Slate Budgerigar Review - Euronet](https://www.euronet.nl/users/hnl/slate.htm)
- [Budgie Cere Guide - Petiska](https://www.petiska.com/budgie-cere-guide-color-color-transition-age-health-breeding-conditions-shape-mutations/)
- [Cere Color and Testosterone - PMC](https://pmc.ncbi.nlm.nih.gov/articles/PMC3901734/)
- [Breeding Budgerigars - BCSA](https://bcsa.com.au/breeding/genetics/)
- [Egg Problems - Rob Marshall/WBO](https://www.world-budgerigar.org/article2.htm)
- [Ino mutation - Budgie World](https://www.budgieworld.org/wiki/ino-gene-in-budgies/)
- [Parblue mutations - OG VZW](https://www.ogvzw.org/parblue-mutations/)
- [Colour aberrations nomenclature - British Ornithologists' Club](https://bioone.org/journals/bulletin-of-the-british-ornithologists-club/volume-141/issue-3/bboc.v141i3.2021.a5/)

### German Sources (ドイツ語文献)
- [Welli.net Genetik](https://www.welli.net/genetik.html)
- [Welli.net Farbvererbung](https://www.welli.net/vererbungslehre-bei-wellensittichen.html)
- [ORNIS Diepholz - Crossing Over](https://ornis-diepholz.hpage.com/genetik/das-crossing-over.html)
- [Sittiche.de Farbschläge](https://www.sittiche.de/farbschlaege/farbschlaege.htm)
- [Sittiche.de Blackface/Blackwing](https://www.sittiche.de/farbschlaege/blackface.htm)
- [Anthrazitwellensittich-Zucht](http://www.anthrazitwellensittich-zucht.de/Anthrazit%20Wellensittiche.html)
- [Rainbowzucht Vererbungslehre](http://www.rainbowzucht.de/ZBV_rainbowzucht_vererbungslehre.htm)
- [Wellensittich.de Forum](https://www.wellensittich.de/)
- [DSV-EV Blackwing](https://dsv-ev.de/aktuell?b=1003298&c=NL,ND1000107)
- [Vogelbund Standard Farbenwellensittiche](http://vogelbund.de/wp-content/uploads/2022/03/Standard-Farbenwellensittiche.pdf)

### Dutch Sources (オランダ語文献)
- [Grasparkiet Genetische Rekenmachine](http://www.gencalc.com/gen/dutch_genc.php?sp=0Budg)
- [Vogelkwekerij Van Kollenburg - Mutaties](https://www.vogelkwekerij-vankollenburg.nl/info-algemeen/mutaties-vererving)
- [Vogelproblemen Erfelijkheidsleer](https://www.vogelproblemen.nl/Erfelijkheidsleer%20-%20Deel%2011.htm)
- [Euronet - Crossing over Sex-chromosome](http://www.euronet.nl/users/hnl/sexchrom.htm)
- [Euronet - Lacewing](https://www.euronet.nl/users/hnl/lacewing.htm)
- [Euronet - Mutant Gene Symbols](http://www.euronet.nl/users/hnl/symbols.htm)

### Anthracite Specific Sources
- [AWEBSA Anthracite Genetics PDF](https://awebsa.com/wp-content/uploads/2016/12/Genetics_Anthracitesm.pdf)
- [Best of Breeds - Anthracite by Gerd Bleicher](http://www.bestofbreeds.com/rarebudgerigars.co.uk/anthracite1.htm)
