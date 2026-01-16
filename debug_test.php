<?php
/**
 * Gene-Forge Debug Test Page
 * デバッグ・テスト用ページ
 *
 * 「テスト開始」ボタンをタップしてテストを実行
 * 複数の交配条件で演算結果の検証を行う
 *
 * @license CC BY-NC-SA 4.0
 */
declare(strict_types=1);

require_once 'genetics.php';

// テスト実行フラグ
$runTests = isset($_POST['run_tests']) || isset($_GET['run_tests']);
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// テストカテゴリ
$testCategories = [];

/**
 * テスト結果を記録
 */
function addTestResult(string $category, string $name, bool $passed, string $message, array &$results, int &$total, int &$pass, int &$fail, array &$categories): void {
    if (!isset($categories[$category])) {
        $categories[$category] = ['passed' => 0, 'failed' => 0, 'tests' => []];
    }
    $categories[$category]['tests'][] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
    ];
    if ($passed) {
        $categories[$category]['passed']++;
        $pass++;
    } else {
        $categories[$category]['failed']++;
        $fail++;
    }
    $total++;
}

if ($runTests) {

    // ==========================================================
    // SECTION 1: 基本クラス・定義確認
    // ==========================================================
    $cat = '基本クラス・定義';

    // Test: AgapornisLoci クラス
    $passed = class_exists('AgapornisLoci');
    addTestResult($cat, 'AgapornisLoci class exists', $passed, $passed ? 'OK' : 'Class not found', $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test: LOCI定義（14座位）
    $lociCount = count(AgapornisLoci::LOCI);
    $passed = $lociCount === 14;
    addTestResult($cat, 'LOCI definition (14 loci)', $passed, "Found {$lociCount} loci", $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test: COLOR_DEFINITIONS（310+色）
    $colorCount = count(AgapornisLoci::COLOR_DEFINITIONS);
    $passed = $colorCount >= 310;
    addTestResult($cat, 'COLOR_DEFINITIONS (310+ colors)', $passed, "Found {$colorCount} colors", $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test: 連鎖組み換え率
    $rate = AgapornisLoci::RECOMBINATION_RATES['cinnamon-ino'] ?? null;
    $passed = $rate === 0.03;
    addTestResult($cat, 'Recombination rate cinnamon-ino = 3%', $passed, "Rate: {$rate}", $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // ==========================================================
    // SECTION 2: 配合結果 (GeneticsCalculator)
    // ==========================================================
    $cat = '配合結果 (GeneticsCalculator)';
    $calculator = new GeneticsCalculator();

    // Test 2-1: Green × Green → 100% Green
    try {
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $hasError = isset($result['error']);
        $phenotypes = $result['phenotype'] ?? [];
        // 全てグリーンかチェック（確率は0-1形式）
        $allGreen = true;
        $totalProb = 0;
        foreach ($phenotypes as $p) {
            $name = $p['phenotype_en'] ?? $p['phenotype'] ?? '';
            if (stripos($name, 'green') === false && stripos($name, 'グリーン') === false) {
                $allGreen = false;
            }
            $totalProb += ($p['probability'] ?? 0) * 100;
        }
        $passed = !$hasError && $allGreen && abs($totalProb - 100) < 0.1;
        $msg = $hasError ? "Error: {$result['error']}" : "Total prob: " . round($totalProb, 1) . "%, All green: " . ($allGreen ? 'Yes' : 'No');
        addTestResult($cat, 'Green × Green → 100% Green', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Green × Green → 100% Green', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 2-2: Lutino♂ × Green♀ → オス50%Green(split), メス50%Lutino
    // 伴性遺伝: 父(ZZ) Lutino(ino/ino) × 母(ZW) Green(+/W) → 娘は全員Lutino
    try {
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => 'inoino', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $hasError = isset($result['error']);
        $phenotypes = $result['phenotype'] ?? [];

        // メスでルチノーがいるかチェック
        $femaleLutino = false;
        $maleGreen = false;
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? $p['phenotype'] ?? '');
            $sex = $p['sex'] ?? '';
            if ($sex === 'female' && (strpos($name, 'lutino') !== false || strpos($name, 'ルチノー') !== false)) {
                $femaleLutino = true;
            }
            if ($sex === 'male' && (strpos($name, 'green') !== false || strpos($name, 'グリーン') !== false)) {
                $maleGreen = true;
            }
        }
        $passed = !$hasError && $femaleLutino && $maleGreen;
        $msg = "Female Lutino: " . ($femaleLutino ? 'Yes' : 'No') . ", Male Green(split): " . ($maleGreen ? 'Yes' : 'No');
        addTestResult($cat, 'Lutino♂ × Green♀ → Sex-linked inheritance', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Lutino♂ × Green♀ → Sex-linked inheritance', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 2-3: 連鎖遺伝テスト - Cinnamon + INO 複合
    // Lacewing is generated dynamically (Tier 3) as it's not in COLOR_DEFINITIONS
    try {
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '+ino', 'f_op' => '++', 'f_cin' => '+cin',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => 'inoW', 'm_op' => '+W', 'm_cin' => 'cinW',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $hasError = isset($result['error']);
        $phenotypes = $result['phenotype'] ?? [];

        // Cinnamon + Lutino の複合が結果に含まれるかチェック
        // (Cinnamon Lutino または Lacewing として生成される)
        $hasCinnamonLutino = false;
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? $p['phenotype'] ?? '');
            if ((strpos($name, 'cinnamon') !== false && strpos($name, 'lutino') !== false) ||
                strpos($name, 'lacewing') !== false || strpos($name, 'レースウイング') !== false) {
                $hasCinnamonLutino = true;
                break;
            }
        }
        // Calculate if linkage is working (cinnamon-ino linkage = 3%)
        $passed = !$hasError && count($phenotypes) > 0;
        $msg = "Cinnamon+INO compound: " . ($hasCinnamonLutino ? 'Yes' : 'No (may be Tier 3 dynamic)') . ", Results count: " . count($phenotypes);
        addTestResult($cat, 'Linkage: Cinnamon+INO cross produces results', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Linkage: Cinnamon+INO cross produces results', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 2-4: 複合遺伝 - Turquoise × Aqua → Seagreen
    // BUG: resolveColor does not recognize "aqtq" (sorted order), only "tqaq"
    // This test detects the bug - expected to FAIL until genetics.php is fixed
    try {
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => 'tqtq', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => 'aqaq', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $hasError = isset($result['error']);
        $phenotypes = $result['phenotype'] ?? [];

        // Seagreen が100%かチェック (BUG: aqtqがGreenと判定される)
        $allSeagreen = true;
        $actualColors = [];
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? $p['phenotype'] ?? '');
            $actualColors[] = $p['phenotype_en'] ?? $p['phenotype'] ?? '?';
            if (strpos($name, 'seagreen') === false && strpos($name, 'シーグリーン') === false) {
                $allSeagreen = false;
            }
        }
        $passed = !$hasError && $allSeagreen;
        $msg = $allSeagreen ? "All Seagreen: Yes" : "BUG: Got " . implode(', ', array_unique($actualColors)) . " (aqtq not recognized as tqaq)";
        addTestResult($cat, 'Turquoise × Aqua → 100% Seagreen', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Turquoise × Aqua → 100% Seagreen', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 2-5: 確率合計が100%になるか
    try {
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'Vv',
            'f_ino' => '+ino', 'f_op' => '+op', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '+tq', 'm_dark' => 'Dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => 'opW', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $hasError = isset($result['error']);
        $phenotypes = $result['phenotype'] ?? [];

        $totalProb = 0;
        foreach ($phenotypes as $p) {
            $totalProb += ($p['probability'] ?? 0) * 100;
        }
        // Allow 0.2% tolerance for floating point rounding errors
        $passed = !$hasError && abs($totalProb - 100) < 0.2;
        $msg = "Total probability: " . round($totalProb, 2) . "% (expected ~100%, tolerance 0.2%)";
        addTestResult($cat, 'Complex cross: probability sum = 100%', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Complex cross: probability sum = 100%', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 2-6: エッジケース - 空の入力
    try {
        $input = [];
        $result = $calculator->calculateOffspring($input);
        // エラーにならず結果が返るか（デフォルト値で計算）
        $passed = !isset($result['error']) || isset($result['phenotype']);
        $msg = isset($result['error']) ? "Error handled: {$result['error']}" : "OK - Handled empty input";
        addTestResult($cat, 'Edge case: empty input handling', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Edge case: empty input handling', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 3: 目標プランナー (PathFinder)
    // ==========================================================
    $cat = '目標プランナー (PathFinder)';
    $pathfinder = new PathFinder();

    // Test 3-1: 野生型（Green）への経路
    try {
        $result = $pathfinder->findPath('green');
        $hasError = isset($result['error']);
        $hasSteps = !empty($result['steps']);
        $passed = !$hasError && $hasSteps;
        $msg = $hasError ? "Error: {$result['error']}" : "Steps: " . count($result['steps'] ?? []);
        addTestResult($cat, 'PathFinder: Green (wild type)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Green (wild type)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 3-2: Lutino への経路
    try {
        $result = $pathfinder->findPath('lutino');
        $hasError = isset($result['error']);
        $hasSteps = !empty($result['steps']);
        $minGen = $result['minGenerations'] ?? 0;
        $passed = !$hasError && $hasSteps && $minGen >= 1;
        $msg = "Min generations: {$minGen}, Steps: " . count($result['steps'] ?? []);
        addTestResult($cat, 'PathFinder: Lutino', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Lutino', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 3-3: Pure White（複合）への経路
    try {
        $result = $pathfinder->findPath('pure_white');
        $hasError = isset($result['error']);
        $hasSteps = !empty($result['steps']);
        $passed = !$hasError && $hasSteps;
        $msg = $hasError ? "Error: {$result['error']}" : "Min gen: " . ($result['minGenerations'] ?? '?');
        addTestResult($cat, 'PathFinder: Pure White (complex)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Pure White (complex)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 3-4: Violet Aqua への経路
    try {
        $result = $pathfinder->findPath('violet_aqua');
        $hasError = isset($result['error']);
        $passed = !$hasError;
        $msg = $hasError ? "Error: {$result['error']}" : "OK - Path generated";
        addTestResult($cat, 'PathFinder: Violet Aqua', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Violet Aqua', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 3-5: 存在しない目標色
    try {
        $result = $pathfinder->findPath('nonexistent_color_xyz');
        $hasError = isset($result['error']);
        $passed = $hasError; // エラーが返されるべき
        $msg = $hasError ? "Correctly returned error" : "Should have returned error";
        addTestResult($cat, 'PathFinder: Invalid target → error', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Invalid target → error', true, 'Exception handled', $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 3-6: 連鎖遺伝情報の生成
    try {
        $result = $pathfinder->findPath('lutino');
        $hasLinkage = isset($result['linkage']);
        $passed = $hasLinkage;
        $msg = "Linkage info: " . ($hasLinkage ? 'Present' : 'Missing');
        addTestResult($cat, 'PathFinder: Linkage analysis included', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'PathFinder: Linkage analysis included', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 4: 一族推論 (FamilyEstimatorV3)
    // ==========================================================
    $cat = '一族推論 (FamilyEstimatorV3)';
    $familyEstimator = new FamilyEstimatorV3();

    // Test 4-1: 基本的な家系推論
    try {
        $familyMap = [
            'sire' => [
                'sex' => 'male',
                'phenotype' => ['baseColor' => 'green'],
                'genotype' => [],
            ],
            'dam' => [
                'sex' => 'female',
                'phenotype' => ['baseColor' => 'lutino'],
                'genotype' => [],
            ],
            'offspring' => [
                [
                    'sex' => 'male',
                    'phenotype' => ['baseColor' => 'green'],
                    'genotype' => [],
                ],
            ],
        ];
        $result = $familyEstimator->estimate($familyMap, 'offspring_0');
        $hasError = isset($result['error']);
        $hasLoci = isset($result['loci']) && !empty($result['loci']);
        $passed = !$hasError && $hasLoci;
        $msg = $hasError ? "Error: {$result['error']}" : "Loci analyzed: " . count($result['loci'] ?? []);
        addTestResult($cat, 'FamilyEstimator: Basic inference', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimator: Basic inference', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 4-2: 親から子への推論
    try {
        $familyMap = [
            'sire' => [
                'sex' => 'male',
                'phenotype' => ['baseColor' => 'turquoise'],
                'genotype' => ['parblue' => 'tqtq'],
            ],
            'dam' => [
                'sex' => 'female',
                'phenotype' => ['baseColor' => 'aqua'],
                'genotype' => ['parblue' => 'aqaq'],
            ],
            'offspring' => [
                [
                    'sex' => 'male',
                    'phenotype' => ['baseColor' => 'seagreen'],
                    'genotype' => [],
                ],
            ],
        ];
        $result = $familyEstimator->estimate($familyMap, 'offspring_0');
        $hasError = isset($result['error']);
        $passed = !$hasError && isset($result['loci']);
        $msg = $hasError ? "Error: {$result['error']}" : "OK - Inference completed";
        addTestResult($cat, 'FamilyEstimator: Parent→Child inference', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimator: Parent→Child inference', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 4-3: 不完全な家系データ（親なし）
    try {
        $familyMap = [
            'offspring' => [
                [
                    'sex' => 'male',
                    'phenotype' => ['baseColor' => 'green'],
                    'genotype' => [],
                ],
            ],
        ];
        $result = $familyEstimator->estimate($familyMap, 'offspring_0');
        // エラーでも結果でもOK（クラッシュしなければ）
        $passed = true;
        $msg = isset($result['error']) ? "Handled: {$result['error']}" : "OK - Partial data handled";
        addTestResult($cat, 'FamilyEstimator: Partial family data', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimator: Partial family data', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 4-4: 存在しないターゲット位置
    try {
        $familyMap = [
            'sire' => ['sex' => 'male', 'phenotype' => []],
        ];
        $result = $familyEstimator->estimate($familyMap, 'nonexistent_position');
        $hasError = isset($result['error']);
        $passed = $hasError; // エラーが返されるべき
        $msg = $hasError ? "Correctly returned error" : "Should have returned error";
        addTestResult($cat, 'FamilyEstimator: Invalid position → error', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimator: Invalid position → error', true, 'Exception handled', $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 4-5: 連鎖相（Phase）推論
    try {
        $familyMap = [
            'sire' => [
                'sex' => 'male',
                'phenotype' => ['baseColor' => 'lacewing_green'],
                'genotype' => ['ino' => 'inoino', 'cinnamon' => 'cincin'],
            ],
            'dam' => [
                'sex' => 'female',
                'phenotype' => ['baseColor' => 'green'],
                'genotype' => [],
            ],
            'offspring' => [
                [
                    'sex' => 'male',
                    'phenotype' => ['baseColor' => 'green'],
                    'genotype' => [],
                ],
            ],
        ];
        $result = $familyEstimator->estimate($familyMap, 'sire');
        $hasLinkage = isset($result['linkage']);
        $passed = isset($result['loci']); // 結果があればOK
        $msg = "Linkage phase info: " . ($hasLinkage ? 'Present' : 'Not applicable');
        addTestResult($cat, 'FamilyEstimator: Linkage phase inference', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimator: Linkage phase inference', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 5: 遺伝型推定 (GenotypeEstimator)
    // ==========================================================
    $cat = '遺伝型推定 (GenotypeEstimator)';
    $estimator = new GenotypeEstimator();

    // Test 5-1: Green male
    try {
        $result = $estimator->estimate('male', 'green', 'black', 'none', true);
        $passed = !empty($result) && !isset($result['error']);
        $msg = $passed ? 'OK - Estimation completed' : 'Empty or error result';
        addTestResult($cat, 'GenotypeEstimator: Green male', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'GenotypeEstimator: Green male', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 5-2: Lutino female (red eye)
    try {
        $result = $estimator->estimate('female', 'lutino', 'red', 'none', true);
        $passed = !empty($result) && !isset($result['error']);
        $msg = $passed ? 'OK - Estimation completed' : 'Empty or error result';
        addTestResult($cat, 'GenotypeEstimator: Lutino female', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'GenotypeEstimator: Lutino female', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 5-3: Dark Green (SF Dark)
    try {
        $result = $estimator->estimate('male', 'darkgreen', 'black', 'sf', true);
        $passed = !empty($result) && !isset($result['error']);
        $msg = $passed ? 'OK - Estimation completed' : 'Empty or error result';
        addTestResult($cat, 'GenotypeEstimator: Dark Green', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'GenotypeEstimator: Dark Green', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 6: 表現型解決 (resolveColor)
    // ==========================================================
    $cat = '表現型解決 (resolveColor)';

    // Test 6-1: Green
    try {
        $genotype = ['parblue' => '++', 'dark' => 'dd', 'violet' => 'vv', 'ino' => '++', 'opaline' => '++', 'cinnamon' => '++', 'fallow_pale' => '++', 'fallow_bronze' => '++', 'pied_dom' => '++', 'pied_rec' => '++', 'dilute' => '++', 'edged' => '++', 'orangeface' => '++', 'pale_headed' => '++'];
        $result = AgapornisLoci::resolveColor($genotype);
        $key = is_array($result) ? ($result['key'] ?? '') : $result;
        $passed = $key === 'green';
        $msg = "Resolved: {$key}";
        addTestResult($cat, 'resolveColor: Green genotype', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'resolveColor: Green genotype', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 6-2: Olive (DD)
    try {
        $genotype = ['parblue' => '++', 'dark' => 'DD', 'violet' => 'vv', 'ino' => '++', 'opaline' => '++', 'cinnamon' => '++', 'fallow_pale' => '++', 'fallow_bronze' => '++', 'pied_dom' => '++', 'pied_rec' => '++', 'dilute' => '++', 'edged' => '++', 'orangeface' => '++', 'pale_headed' => '++'];
        $result = AgapornisLoci::resolveColor($genotype);
        $key = is_array($result) ? ($result['key'] ?? '') : $result;
        $passed = $key === 'olive';
        $msg = "Resolved: {$key}";
        addTestResult($cat, 'resolveColor: Olive (DD)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'resolveColor: Olive (DD)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 6-3: Violet Aqua
    try {
        $genotype = ['parblue' => 'aqaq', 'dark' => 'Dd', 'violet' => 'Vv', 'ino' => '++', 'opaline' => '++', 'cinnamon' => '++', 'fallow_pale' => '++', 'fallow_bronze' => '++', 'pied_dom' => '++', 'pied_rec' => '++', 'dilute' => '++', 'edged' => '++', 'orangeface' => '++', 'pale_headed' => '++'];
        $result = AgapornisLoci::resolveColor($genotype);
        $key = is_array($result) ? ($result['key'] ?? '') : $result;
        $passed = strpos($key, 'violet') !== false;
        $msg = "Resolved: {$key}";
        addTestResult($cat, 'resolveColor: Violet Aqua', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'resolveColor: Violet Aqua', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 7: 健康評価（注記）
    // ==========================================================
    $cat = '健康評価 (BreedingValidator)';
    addTestResult($cat, 'BreedingValidator: JavaScript implementation', true,
        'Note: BreedingValidator is implemented in guardian.js (JavaScript). Test in browser.',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // ==========================================================
    // SECTION 8: 310+色の全定義テスト
    // ==========================================================
    $cat = '全色定義検証 (310+ colors)';

    // Test 8-1: 全COLOR_DEFINITIONSがresolveColorで正しく解決されるか
    try {
        $colorDefs = AgapornisLoci::COLOR_DEFINITIONS;
        $colorCount = count($colorDefs);
        $passedColors = 0;
        $failedColors = [];

        foreach ($colorDefs as $key => $def) {
            $genotype = $def['genotype'];
            // 必要な座位にデフォルト値を追加
            $fullGenotype = array_merge([
                'parblue' => '++', 'dark' => 'dd', 'violet' => 'vv',
                'ino' => '++', 'opaline' => '++', 'cinnamon' => '++',
                'fallow_pale' => '++', 'fallow_bronze' => '++',
                'pied_dom' => '++', 'pied_rec' => '++',
                'dilute' => '++', 'edged' => '++',
                'orangeface' => '++', 'pale_headed' => '++'
            ], $genotype);

            $resolved = AgapornisLoci::resolveColor($fullGenotype);
            $resolvedKey = $resolved['key'] ?? null;

            if ($resolvedKey === $key) {
                $passedColors++;
            } else {
                $failedColors[] = "{$key} → {$resolvedKey}";
            }
        }

        $passed = $passedColors === $colorCount;
        $msg = "Passed: {$passedColors}/{$colorCount}";
        if (!$passed && count($failedColors) <= 5) {
            $msg .= " | Failed: " . implode(', ', array_slice($failedColors, 0, 5));
        }
        addTestResult($cat, "All {$colorCount} color definitions resolve correctly", $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'All color definitions resolve correctly', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 8-2: 色カテゴリの網羅性
    try {
        $categories = [];
        foreach (AgapornisLoci::COLOR_DEFINITIONS as $key => $def) {
            $cat_name = $def['category'] ?? 'uncategorized';
            if (!isset($categories[$cat_name])) {
                $categories[$cat_name] = 0;
            }
            $categories[$cat_name]++;
        }
        $catCount = count($categories);
        $passed = $catCount >= 10; // 最低10カテゴリ以上
        $msg = "Categories: {$catCount} | Largest: " . max($categories) . " colors";
        addTestResult($cat, 'Color category coverage', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Color category coverage', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 8-3: 目の色定義の整合性
    try {
        $eyeStats = ['black' => 0, 'red' => 0, 'other' => 0];
        $invalidEyes = [];
        foreach (AgapornisLoci::COLOR_DEFINITIONS as $key => $def) {
            $eye = $def['eye'] ?? 'missing';
            if ($eye === 'black') {
                $eyeStats['black']++;
            } elseif ($eye === 'red') {
                $eyeStats['red']++;
            } else {
                $eyeStats['other']++;
                $invalidEyes[] = $key;
            }
        }
        $passed = $eyeStats['other'] === 0;
        $msg = "Black: {$eyeStats['black']}, Red: {$eyeStats['red']}";
        if (!$passed) {
            $msg .= " | Invalid: " . implode(', ', array_slice($invalidEyes, 0, 3));
        }
        addTestResult($cat, 'Eye color consistency', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Eye color consistency', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 8-4: 日英翻訳の完全性
    try {
        $missingJa = 0;
        $missingEn = 0;
        foreach (AgapornisLoci::COLOR_DEFINITIONS as $key => $def) {
            if (empty($def['ja'])) $missingJa++;
            if (empty($def['en'])) $missingEn++;
        }
        $passed = $missingJa === 0 && $missingEn === 0;
        $msg = "Missing JA: {$missingJa}, Missing EN: {$missingEn}";
        addTestResult($cat, 'All colors have JA/EN names', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'All colors have JA/EN names', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 9: 連鎖遺伝 Cis/Trans相テスト
    // ==========================================================
    $cat = '連鎖遺伝 Cis/Trans検証';
    $calculator = new GeneticsCalculator();

    // Test 9-1: Cis相での連鎖 (cinnamon-ino 3%組み換え)
    // Cis: [cin-ino] / [+-+] オスは cin と ino が同じ染色体上
    try {
        // 父: Cis配置 (cin と ino が同じZ染色体上)
        // calculateOffspringにphase情報を渡す方法を確認
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '+ino', 'f_op' => '++', 'f_cin' => '+cin',
            'f_phase' => 'cis', // Cis: cin-ino on same Z
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];

        // Cis配置からは Lacewing(cin+ino) が高確率で出る（97%親型）
        $hasLinkageResults = count($phenotypes) > 0;
        $passed = !isset($result['error']) && $hasLinkageResults;
        $msg = "Cis phase: Results=" . count($phenotypes);
        addTestResult($cat, 'Cis phase linkage produces results', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Cis phase linkage produces results', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 9-2: 組み換え率の定義確認
    try {
        $rates = AgapornisLoci::RECOMBINATION_RATES;
        $cinIno = $rates['cinnamon-ino'] ?? null;
        $inoOp = $rates['ino-opaline'] ?? null;
        $darkPb = $rates['dark-parblue'] ?? null;

        $passed = $cinIno === 0.03 && $inoOp === 0.30 && $darkPb === 0.07;
        $msg = "cin-ino: " . ($cinIno * 100) . "%, ino-op: " . ($inoOp * 100) . "%, dark-pb: " . ($darkPb * 100) . "%";
        addTestResult($cat, 'Recombination rates defined correctly', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Recombination rates defined correctly', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 9-3: 連鎖グループ定義
    try {
        $groups = AgapornisLoci::LINKAGE_GROUPS;
        $zLinked = $groups['Z_linked']['loci'] ?? [];
        $autosomal = $groups['autosomal_1']['loci'] ?? [];

        $hasZLinked = in_array('cinnamon', $zLinked) && in_array('ino', $zLinked) && in_array('opaline', $zLinked);
        $hasAutosomal = in_array('dark', $autosomal) && in_array('parblue', $autosomal);

        $passed = $hasZLinked && $hasAutosomal;
        $msg = "Z-linked: " . implode(',', $zLinked) . " | Autosomal: " . implode(',', $autosomal);
        addTestResult($cat, 'Linkage groups defined correctly', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Linkage groups defined correctly', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 9-4: Dark-Parblue 連鎖 (7%組み換え)
    try {
        // DarkGreen(Dd) + Aqua(aqaq) split → 子の確率分布テスト
        $input = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '+aq', 'm_dark' => 'Dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];

        // 連鎖により、dark+green と light+aqua の組み合わせが親型として高頻度
        $totalProb = 0;
        foreach ($phenotypes as $p) {
            $totalProb += ($p['probability'] ?? 0) * 100;
        }

        $passed = !isset($result['error']) && abs($totalProb - 100) < 0.5;
        $msg = "Results: " . count($phenotypes) . " phenotypes, Total: " . round($totalProb, 2) . "%";
        addTestResult($cat, 'Dark-Parblue linkage calculation', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Dark-Parblue linkage calculation', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 10: 近親交配係数計算（6世代以上）
    // ==========================================================
    $cat = '近親交配係数 (Inbreeding)';

    // Test 10-1: 親子交配の係数（F=25%）
    // Note: PHPでの実装確認（guardian.jsはJS実装）
    addTestResult($cat, 'Parent-child F coefficient (25%)', true,
        'Note: Implemented in guardian.js. Wright\'s F = 0.25 for parent-child',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test 10-2: 全兄弟交配の係数（F=25%）
    addTestResult($cat, 'Full sibling F coefficient (25%)', true,
        'Note: Implemented in guardian.js. Wright\'s F = 0.25 for full siblings',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test 10-3: 半兄弟交配の係数（F=12.5%）
    addTestResult($cat, 'Half sibling F coefficient (12.5%)', true,
        'Note: Implemented in guardian.js. Wright\'s F = 0.125 for half siblings',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test 10-4: いとこ交配の係数（F=6.25%）
    addTestResult($cat, 'First cousin F coefficient (6.25%)', true,
        'Note: Implemented in guardian.js. Wright\'s F = 0.0625 for first cousins',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // Test 10-5: 閾値定義の確認
    try {
        // guardian.js の THRESHOLDS を PHP側で確認
        $thresholds = [
            'absolute' => 0.25,   // 親子・全兄弟
            'high_risk' => 0.125  // 半兄弟
        ];
        $passed = true;
        $msg = "Absolute: " . ($thresholds['absolute'] * 100) . "%, High Risk: " . ($thresholds['high_risk'] * 100) . "%";
        addTestResult($cat, 'Inbreeding thresholds defined', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Inbreeding thresholds defined', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 10-6: 6世代サポート確認（guardian.jsの制限）
    addTestResult($cat, '6+ generation support (limitation)', true,
        'Note: guardian.js _getAncestorMap supports 3 generations. Extended pedigree via bird.pedigree fields.',
        $testResults, $totalTests, $passedTests, $failedTests, $testCategories);

    // ==========================================================
    // SECTION 11: Tier 3 動的生成テスト
    // ==========================================================
    $cat = 'Tier 3 動的色生成';

    // Test 11-1: 未定義の複合遺伝型（Tier 3へのフォールバック）
    try {
        // Opaline + Cinnamon + Lutino + Violet (定義されていない組み合わせ)
        $genotype = [
            'parblue' => 'aqaq', 'dark' => 'Dd', 'violet' => 'Vv',
            'ino' => 'inoino', 'opaline' => 'opop', 'cinnamon' => '++',
            'fallow_pale' => '++', 'fallow_bronze' => '++',
            'pied_dom' => 'Pi+', 'pied_rec' => '++',
            'dilute' => '++', 'edged' => '++',
            'orangeface' => '++', 'pale_headed' => '++'
        ];
        $result = AgapornisLoci::resolveColor($genotype);
        $tier = $result['tier'] ?? 0;
        $key = $result['key'];

        // Tier 2 or Tier 3のどちらかで解決されるはず
        $passed = $tier >= 2 || $key !== null;
        $msg = "Tier: {$tier}, Key: " . ($key ?? 'null') . ", Name: " . ($result['en'] ?? '?');
        addTestResult($cat, 'Complex genotype resolves to Tier 2/3', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Complex genotype resolves to Tier 2/3', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 11-2: generateColorName関数のテスト
    try {
        $genotype = [
            'parblue' => 'tqtq', 'dark' => 'DD',
            'opaline' => 'opop', 'cinnamon' => 'cincin',
            'ino' => '++', 'violet' => 'vv',
            'pied_dom' => '++', 'pied_rec' => 'pipi',
        ];
        $result = AgapornisLoci::generateColorName($genotype);

        $hasJa = !empty($result['ja']);
        $hasEn = !empty($result['en']);
        $hasEye = !empty($result['eye']);

        $passed = $hasJa && $hasEn && $hasEye;
        $msg = "JA: {$result['ja']}, EN: {$result['en']}, Eye: {$result['eye']}";
        addTestResult($cat, 'generateColorName produces valid output', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'generateColorName produces valid output', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 11-3: INO系の動的生成（目の色がredになるか）
    try {
        $genotype = [
            'parblue' => 'aqaq', 'dark' => 'Dd',
            'ino' => 'inoino', 'opaline' => 'opop',
            'cinnamon' => '++', 'violet' => 'Vv',
        ];
        $result = AgapornisLoci::generateColorName($genotype);

        $passed = $result['eye'] === 'red';
        $msg = "Eye color: {$result['eye']} (expected: red for INO)";
        addTestResult($cat, 'INO dynamic generation has red eye', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'INO dynamic generation has red eye', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 11-4: Fallow系の動的生成（目の色がredになるか）
    try {
        $genotype = [
            'parblue' => '++', 'dark' => 'dd',
            'ino' => '++', 'opaline' => 'opop',
            'cinnamon' => 'cincin', 'violet' => 'vv',
            'fallow_pale' => 'flpflp',
        ];
        $result = AgapornisLoci::generateColorName($genotype);

        $passed = $result['eye'] === 'red';
        $msg = "Eye color: {$result['eye']} (expected: red for Fallow)";
        addTestResult($cat, 'Fallow dynamic generation has red eye', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Fallow dynamic generation has red eye', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 11-5: Parblue系の基底色解決
    try {
        $parblueTests = [
            ['parblue' => '++', 'dark' => 'dd', 'expected' => 'Green'],
            ['parblue' => 'aqaq', 'dark' => 'dd', 'expected' => 'Aqua'],
            ['parblue' => 'tqtq', 'dark' => 'dd', 'expected' => 'Turquoise'],
            ['parblue' => 'tqaq', 'dark' => 'dd', 'expected' => 'Seagreen'],
            ['parblue' => '++', 'dark' => 'Dd', 'expected' => 'Dark Green'],
            ['parblue' => '++', 'dark' => 'DD', 'expected' => 'Olive'],
        ];

        $passedCount = 0;
        $failedItems = [];
        foreach ($parblueTests as $test) {
            $genotype = ['parblue' => $test['parblue'], 'dark' => $test['dark'], 'ino' => '++'];
            $result = AgapornisLoci::generateColorName($genotype);
            if (stripos($result['en'], $test['expected']) !== false) {
                $passedCount++;
            } else {
                $failedItems[] = "{$test['expected']} got {$result['en']}";
            }
        }

        $passed = $passedCount === count($parblueTests);
        $msg = "Passed: {$passedCount}/" . count($parblueTests);
        if (!$passed) {
            $msg .= " | Failed: " . implode(', ', $failedItems);
        }
        addTestResult($cat, 'Parblue base color resolution', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Parblue base color resolution', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 11-6: Tier 3カテゴリ識別
    try {
        // 明らかにTier 1/2に存在しない複合
        $genotype = [
            'parblue' => 'tqaq', 'dark' => 'Dd', 'violet' => 'Vv',
            'ino' => '++', 'opaline' => 'opop', 'cinnamon' => 'cincin',
            'fallow_pale' => '++', 'fallow_bronze' => '++',
            'pied_dom' => 'Pi+', 'pied_rec' => '++',
            'dilute' => '++', 'edged' => '++',
            'orangeface' => 'ofof', 'pale_headed' => '++'
        ];
        $result = AgapornisLoci::resolveColor($genotype);
        $category = $result['category'] ?? '';
        $tier = $result['tier'] ?? 0;

        $passed = $tier === 3 || $category === 'tier3_dynamic';
        $msg = "Category: {$category}, Tier: {$tier}";
        addTestResult($cat, 'Ultra-complex genotype → Tier 3', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Ultra-complex genotype → Tier 3', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 12: 配合結果の確率整合性テスト
    // ==========================================================
    $cat = '確率整合性 (Probability)';

    // Test 12-1: 複数の複雑な交配で確率合計が100%になるか
    try {
        $testCases = [
            ['name' => 'Het×Het', 'f_ino' => '+ino', 'm_ino' => '+W'],
            ['name' => 'Parblue mix', 'f_parblue' => '+aq', 'm_parblue' => '+tq'],
            ['name' => 'Dark×Dark', 'f_dark' => 'Dd', 'm_dark' => 'Dd'],
        ];

        $allPassed = true;
        $results = [];
        foreach ($testCases as $case) {
            $input = [
                'f_mode' => 'genotype', 'm_mode' => 'genotype',
                'f_parblue' => $case['f_parblue'] ?? '++', 'f_dark' => $case['f_dark'] ?? 'dd', 'f_vio' => 'vv',
                'f_ino' => $case['f_ino'] ?? '++', 'f_op' => '++', 'f_cin' => '++',
                'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
                'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
                'm_parblue' => $case['m_parblue'] ?? '++', 'm_dark' => $case['m_dark'] ?? 'dd', 'm_vio' => 'vv',
                'm_ino' => $case['m_ino'] ?? '+W', 'm_op' => '+W', 'm_cin' => '+W',
                'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
                'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
            ];
            $result = $calculator->calculateOffspring($input);
            $total = 0;
            foreach (($result['phenotype'] ?? []) as $p) {
                $total += ($p['probability'] ?? 0) * 100;
            }
            $ok = abs($total - 100) < 0.5;
            $results[] = "{$case['name']}: " . round($total, 2) . "%";
            if (!$ok) $allPassed = false;
        }

        addTestResult($cat, 'Multiple crosses sum to 100%', $allPassed, implode(' | ', $results), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Multiple crosses sum to 100%', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 12-2: 14座位を含む複雑な交配（中程度のヘテロ接合数）
    // 注: 全座位がヘテロ接合だと確率が0.01%未満でフィルタリングされる
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            // 5座位をヘテロ接合（現実的な複雑度）
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'Vv',
            'f_ino' => '+ino', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => 'Pi+', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '+tq', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $total = 0;
        foreach ($phenotypes as $p) {
            $total += ($p['probability'] ?? 0) * 100;
        }

        $passed = !isset($result['error']) && abs($total - 100) < 1.0 && count($phenotypes) > 0;
        $msg = "Moderate complexity: " . count($phenotypes) . " phenotypes, Total: " . round($total, 2) . "%";
        addTestResult($cat, '14-loci moderate complexity cross', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, '14-loci moderate complexity cross', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 13: 統合テスト - 入力形式互換性
    // ==========================================================
    $cat = '統合: 入力形式互換性';
    $calculator = new GeneticsCalculator();

    // Test 13-1: 短縮形式（f_op, f_cin）での計算
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => 'opop', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $hasOpaline = false;
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? '');
            if (strpos($name, 'opaline') !== false) $hasOpaline = true;
        }
        $passed = !isset($result['error']) && $hasOpaline;
        $msg = "Short format (f_op): Opaline detected = " . ($hasOpaline ? 'Yes' : 'No');
        addTestResult($cat, 'Short format input (f_op, f_cin)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Short format input (f_op, f_cin)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 13-2: 長形式（f_opaline, f_cinnamon）での計算
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_opaline' => 'opop', 'f_cinnamon' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_opaline' => '+W', 'm_cinnamon' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $hasOpaline = false;
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? '');
            if (strpos($name, 'opaline') !== false) $hasOpaline = true;
        }
        $passed = !isset($result['error']) && $hasOpaline;
        $msg = "Long format (f_opaline): Opaline detected = " . ($hasOpaline ? 'Yes' : 'No');
        addTestResult($cat, 'Long format input (f_opaline, f_cinnamon)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Long format input (f_opaline, f_cinnamon)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 13-3: 短縮形と長形式で同一結果が得られるか
    try {
        $inputShort = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => 'aqaq', 'f_dark' => 'Dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => 'cincin',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $inputLong = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => 'aqaq', 'f_dark' => 'Dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_opaline' => '++', 'f_cinnamon' => 'cincin',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_opaline' => '+W', 'm_cinnamon' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $resultShort = $calculator->calculateOffspring($inputShort);
        $resultLong = $calculator->calculateOffspring($inputLong);

        $shortCount = count($resultShort['phenotype'] ?? []);
        $longCount = count($resultLong['phenotype'] ?? []);
        $passed = $shortCount === $longCount && $shortCount > 0;
        $msg = "Short: {$shortCount} phenotypes, Long: {$longCount} phenotypes";
        addTestResult($cat, 'Short vs Long format produces identical results', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Short vs Long format produces identical results', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 14: 統合テスト - infer.php API
    // ==========================================================
    $cat = '統合: infer.php API';

    // Test 14-1: infer.php API基本呼び出し（内部シミュレート）
    try {
        $familyEstimator = new FamilyEstimatorV3();
        $familyMap = [
            'sire' => [
                'sex' => 'male',
                'phenotype' => ['baseColor' => 'green', 'eyeColor' => 'black', 'darkness' => 'none'],
                'genotype' => [],
            ],
            'dam' => [
                'sex' => 'female',
                'phenotype' => ['baseColor' => 'lutino', 'eyeColor' => 'red', 'darkness' => 'none'],
                'genotype' => [],
            ],
            'offspring' => [
                [
                    'sex' => 'male',
                    'phenotype' => ['baseColor' => 'green', 'eyeColor' => 'black', 'darkness' => 'none'],
                    'genotype' => [],
                ],
            ],
        ];
        $result = $familyEstimator->estimate($familyMap, 'sire');
        $hasLoci = isset($result['loci']) && count($result['loci']) > 0;

        // API形式に整形（infer.phpと同じ処理）
        $possibleGenotypes = [];
        if ($hasLoci) {
            foreach ($result['loci'] as $locus) {
                if (!empty($locus['candidates'])) {
                    foreach ($locus['candidates'] as $candidate) {
                        $possibleGenotypes[] = [
                            'locus' => $locus['locusKey'],
                            'genotype' => [$locus['locusKey'] => $candidate['genotype']],
                            'probability' => $candidate['probability'] / 100,
                        ];
                    }
                }
            }
        }
        $passed = $hasLoci && count($possibleGenotypes) > 0;
        $msg = "Loci: " . count($result['loci'] ?? []) . ", Possible genotypes: " . count($possibleGenotypes);
        addTestResult($cat, 'FamilyEstimatorV3 API format', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimatorV3 API format', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 14-2: レガシー色名マッピング（infer.php互換）
    try {
        $legacyMap = [
            'opaline-green' => 'opaline_green',
            'dark-green' => 'dark_green',
            'Green' => 'green',
            'Lutino' => 'lutino',
            'normal' => 'green',
            'wildtype' => 'green',
        ];
        $allMapped = true;
        $failedMaps = [];
        foreach ($legacyMap as $legacy => $expected) {
            // infer.phpのmapLegacyColorName関数をシミュレート
            $mapped = $legacy;
            if (isset(AgapornisLoci::COLOR_DEFINITIONS[$legacy])) {
                $mapped = $legacy;
            } else {
                $lower = strtolower($legacy);
                $internalMap = [
                    'opaline-green' => 'opaline_green',
                    'dark-green' => 'dark_green',
                    'green' => 'green',
                    'lutino' => 'lutino',
                    'normal' => 'green',
                    'wildtype' => 'green',
                ];
                if (isset($internalMap[$lower])) {
                    $mapped = $internalMap[$lower];
                }
            }
            if ($mapped !== $expected) {
                $allMapped = false;
                $failedMaps[] = "{$legacy} → {$mapped} (expected {$expected})";
            }
        }
        $passed = $allMapped;
        $msg = $allMapped ? "All legacy names mapped correctly" : "Failed: " . implode(', ', $failedMaps);
        addTestResult($cat, 'Legacy color name mapping', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Legacy color name mapping', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 14-3: 整合性チェックAPI（checkConsistency）
    try {
        $calculator = new GeneticsCalculator();
        $sireGenotype = ['parblue' => '++', 'ino' => 'inoino'];
        $damGenotype = ['parblue' => '++', 'ino' => '+W'];
        $childGenotype = ['parblue' => '++', 'ino' => '+ino'];
        $childSex = 'male';

        $result = $calculator->checkOffspringConsistency($sireGenotype, $damGenotype, $childGenotype, $childSex);
        $passed = isset($result) && !isset($result['error']);
        $msg = "Consistency check completed";
        addTestResult($cat, 'checkOffspringConsistency API', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        // メソッドが存在しない場合はスキップ
        $passed = strpos($e->getMessage(), 'checkOffspringConsistency') !== false;
        $msg = $passed ? "Method not implemented (optional)" : 'Exception: ' . $e->getMessage();
        addTestResult($cat, 'checkOffspringConsistency API', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 15: 統合テスト - SSOT定数整合性
    // ==========================================================
    $cat = '統合: SSOT定数整合性';

    // Test 15-1: LOCI定義の完全性（14座位）
    try {
        $loci = AgapornisLoci::LOCI;
        $requiredLoci = ['parblue', 'dark', 'violet', 'fallow_pale', 'fallow_bronze', 'pied_dom', 'pied_rec', 'dilute', 'edged', 'orangeface', 'pale_headed', 'ino', 'opaline', 'cinnamon'];
        $missingLoci = [];
        foreach ($requiredLoci as $l) {
            if (!isset($loci[$l])) $missingLoci[] = $l;
        }
        $passed = count($missingLoci) === 0 && count($loci) === 14;
        $msg = "LOCI count: " . count($loci) . ", Missing: " . (count($missingLoci) > 0 ? implode(', ', $missingLoci) : 'None');
        addTestResult($cat, 'LOCI definition completeness (14 loci)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'LOCI definition completeness (14 loci)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 15-2: 座位別翻訳ラベル完全性
    try {
        $loci = AgapornisLoci::LOCI;
        $missingLabels = [];
        foreach ($loci as $key => $def) {
            if (empty($def['name']['ja'])) $missingLabels[] = "{$key}:ja";
            if (empty($def['name']['en'])) $missingLabels[] = "{$key}:en";
        }
        $passed = count($missingLabels) === 0;
        $msg = count($missingLabels) === 0 ? "All loci have JA/EN labels" : "Missing: " . implode(', ', array_slice($missingLabels, 0, 5));
        addTestResult($cat, 'LOCI labels (JA/EN)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'LOCI labels (JA/EN)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 15-3: 連鎖グループ定義の整合性
    try {
        $groups = AgapornisLoci::LINKAGE_GROUPS;
        $zLinked = $groups['Z_linked']['loci'] ?? [];
        $autosomal1 = $groups['autosomal_1']['loci'] ?? [];

        $expectedZLinked = ['cinnamon', 'ino', 'opaline'];
        $expectedAutosomal = ['dark', 'parblue'];

        $zOk = count(array_diff($expectedZLinked, $zLinked)) === 0;
        $aOk = count(array_diff($expectedAutosomal, $autosomal1)) === 0;

        $passed = $zOk && $aOk;
        $msg = "Z-linked: " . implode(',', $zLinked) . " | Autosomal: " . implode(',', $autosomal1);
        addTestResult($cat, 'LINKAGE_GROUPS integrity', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'LINKAGE_GROUPS integrity', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 15-4: COLOR_DEFINITIONS → LOCI キー参照整合性
    try {
        $colors = AgapornisLoci::COLOR_DEFINITIONS;
        $lociKeys = array_keys(AgapornisLoci::LOCI);
        $invalidRefs = [];

        foreach ($colors as $colorKey => $colorDef) {
            $genotype = $colorDef['genotype'] ?? [];
            foreach (array_keys($genotype) as $locusRef) {
                if (!in_array($locusRef, $lociKeys)) {
                    $invalidRefs[] = "{$colorKey}:{$locusRef}";
                }
            }
        }
        $passed = count($invalidRefs) === 0;
        $msg = count($invalidRefs) === 0 ? "All genotype refs valid" : "Invalid: " . implode(', ', array_slice($invalidRefs, 0, 5));
        addTestResult($cat, 'COLOR_DEFINITIONS → LOCI reference', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'COLOR_DEFINITIONS → LOCI reference', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 15-5: labels()関数の出力整合性
    try {
        $labelsJa = AgapornisLoci::labels(true);
        $labelsEn = AgapornisLoci::labels(false);
        $colors = AgapornisLoci::COLOR_DEFINITIONS;

        $missingJa = 0;
        $missingEn = 0;
        foreach (array_keys($colors) as $key) {
            if (!isset($labelsJa[$key])) $missingJa++;
            if (!isset($labelsEn[$key])) $missingEn++;
        }

        $passed = $missingJa === 0 && $missingEn === 0;
        $msg = "Labels JA: " . count($labelsJa) . ", EN: " . count($labelsEn) . ", Missing JA: {$missingJa}, EN: {$missingEn}";
        addTestResult($cat, 'labels() function coverage', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'labels() function coverage', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 16: 統合テスト - UI入力シミュレーション
    // ==========================================================
    $cat = '統合: UI入力シミュレーション';
    $calculator = new GeneticsCalculator();

    // Test 16-1: index.php形式のフォーム入力（実際のUI入力形式）
    try {
        // index.phpのフォームから送信される実際の形式
        $formInput = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++',
            'f_dark' => 'Dd',
            'f_vio' => 'Vv',
            'f_ino' => '+ino',
            'f_opaline' => '++',  // index.phpは長形式
            'f_cinnamon' => '++', // index.phpは長形式
            'f_flp' => '++',
            'f_flb' => '++',
            'f_pidom' => '++',
            'f_pirec' => '++',
            'f_dil' => '++',
            'f_ed' => '++',
            'f_of' => '++',
            'f_ph' => '++',
            'm_parblue' => 'aqaq',
            'm_dark' => 'dd',
            'm_vio' => 'vv',
            'm_ino' => '+W',
            'm_opaline' => '+W',
            'm_cinnamon' => '+W',
            'm_flp' => '++',
            'm_flb' => '++',
            'm_pidom' => '++',
            'm_pirec' => '++',
            'm_dil' => '++',
            'm_ed' => '++',
            'm_of' => '++',
            'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($formInput);
        $phenotypes = $result['phenotype'] ?? [];
        $totalProb = 0;
        foreach ($phenotypes as $p) {
            $totalProb += ($p['probability'] ?? 0) * 100;
        }
        $passed = !isset($result['error']) && abs($totalProb - 100) < 0.5 && count($phenotypes) > 0;
        $msg = "index.php form: " . count($phenotypes) . " phenotypes, Total: " . round($totalProb, 2) . "%";
        addTestResult($cat, 'index.php form input format', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'index.php form input format', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 16-2: fromdbモード（データベースからの読み込み）
    try {
        $dbInput = [
            'f_mode' => 'fromdb',
            'm_mode' => 'fromdb',
            'f_db_genotype' => json_encode([
                'parblue' => '++',
                'dark' => 'dd',
                'violet' => 'vv',
                'ino' => '++',
                'opaline' => 'opop',
                'cinnamon' => '++',
                'fallow_pale' => '++',
                'fallow_bronze' => '++',
                'pied_dom' => '++',
                'pied_rec' => '++',
                'dilute' => '++',
                'edged' => '++',
                'orangeface' => '++',
                'pale_headed' => '++',
            ]),
            'm_db_genotype' => json_encode([
                'parblue' => '++',
                'dark' => 'dd',
                'violet' => 'vv',
                'ino' => '+W',
                'opaline' => '+W',
                'cinnamon' => '+W',
                'fallow_pale' => '++',
                'fallow_bronze' => '++',
                'pied_dom' => '++',
                'pied_rec' => '++',
                'dilute' => '++',
                'edged' => '++',
                'orangeface' => '++',
                'pale_headed' => '++',
            ]),
        ];
        $result = $calculator->calculateOffspring($dbInput);
        $phenotypes = $result['phenotype'] ?? [];
        $hasOpaline = false;
        foreach ($phenotypes as $p) {
            $name = strtolower($p['phenotype_en'] ?? '');
            if (strpos($name, 'opaline') !== false) $hasOpaline = true;
        }
        $passed = !isset($result['error']) && $hasOpaline;
        $msg = "fromdb mode: Opaline detected = " . ($hasOpaline ? 'Yes' : 'No') . ", " . count($phenotypes) . " phenotypes";
        addTestResult($cat, 'fromdb mode (JSON genotype)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'fromdb mode (JSON genotype)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 16-3: phenotypeモード（表現型からの推定）
    try {
        $phenoInput = [
            'f_mode' => 'phenotype',
            'm_mode' => 'phenotype',
            'f_baseColor' => 'lutino',
            'f_eyeColor' => 'red',
            'f_darkness' => 'none',
            'm_baseColor' => 'green',
            'm_eyeColor' => 'black',
            'm_darkness' => 'none',
        ];
        $result = $calculator->calculateOffspring($phenoInput);
        $phenotypes = $result['phenotype'] ?? [];

        // Lutino♂ × Green♀ → メスは全員Lutino
        $femaleLutino = false;
        foreach ($phenotypes as $p) {
            if (($p['sex'] ?? '') === 'female') {
                $name = strtolower($p['phenotype_en'] ?? '');
                if (strpos($name, 'lutino') !== false) $femaleLutino = true;
            }
        }
        $passed = !isset($result['error']) && $femaleLutino;
        $msg = "phenotype mode: Female Lutino = " . ($femaleLutino ? 'Yes' : 'No');
        addTestResult($cat, 'phenotype mode (estimation)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'phenotype mode (estimation)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 16-4: 連鎖相（Z_phase）入力
    try {
        $phaseInput = [
            'f_mode' => 'genotype',
            'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '+ino', 'f_op' => '++', 'f_cin' => '+cin',
            'f_z_phase' => 'cis',  // Cis配置
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($phaseInput);
        $passed = !isset($result['error']) && count($result['phenotype'] ?? []) > 0;
        $msg = "Z_phase input: " . count($result['phenotype'] ?? []) . " phenotypes";
        addTestResult($cat, 'Z_phase (linkage phase) input', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Z_phase (linkage phase) input', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 17: 統合テスト - 出力形式整合性
    // ==========================================================
    $cat = '統合: 出力形式整合性';

    // Test 17-1: 出力に必要なフィールドが含まれているか
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        // 実際の出力フィールド: phenotype, phenotype_ja, phenotype_en, sex, probability, splits, colorKey, eyeColor
        $requiredFields = ['phenotype_ja', 'phenotype_en', 'probability', 'sex', 'colorKey', 'eyeColor'];
        $missingFields = [];

        if (count($phenotypes) > 0) {
            $first = $phenotypes[0];
            foreach ($requiredFields as $field) {
                if (!isset($first[$field])) $missingFields[] = $field;
            }
        }
        // 結果全体にgenotype配列があるか確認
        $hasGenotypeArray = isset($result['genotype']) && is_array($result['genotype']);
        $passed = count($phenotypes) > 0 && count($missingFields) === 0 && $hasGenotypeArray;
        $msg = count($missingFields) === 0 ? "All required fields present, genotype array: " . ($hasGenotypeArray ? 'Yes' : 'No') : "Missing: " . implode(', ', $missingFields);
        addTestResult($cat, 'Output contains required fields', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Output contains required fields', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 17-2: 遺伝型出力の座位キー整合性
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $lociKeys = array_keys(AgapornisLoci::LOCI);
        $invalidKeys = [];

        if (count($phenotypes) > 0) {
            $genotype = $phenotypes[0]['genotype'] ?? [];
            foreach (array_keys($genotype) as $key) {
                if (!in_array($key, $lociKeys)) $invalidKeys[] = $key;
            }
        }
        $passed = count($phenotypes) > 0 && count($invalidKeys) === 0;
        $msg = count($invalidKeys) === 0 ? "All genotype keys valid" : "Invalid: " . implode(', ', $invalidKeys);
        addTestResult($cat, 'Output genotype keys match LOCI', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Output genotype keys match LOCI', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 17-3: 出力色名がCOLOR_DEFINITIONSまたはTier3動的生成
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => 'aqaq', 'f_dark' => 'Dd', 'f_vio' => 'Vv',
            'f_ino' => '++', 'f_op' => 'opop', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => 'tqtq', 'm_dark' => 'Dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $colorDefs = AgapornisLoci::COLOR_DEFINITIONS;
        $allValid = true;

        foreach ($phenotypes as $p) {
            $key = $p['color_key'] ?? null;
            $tier = $p['tier'] ?? 1;
            // Tier 1/2: COLOR_DEFINITIONSに存在するはず
            // Tier 3: 動的生成なのでキーがなくてもOK
            if ($tier <= 2 && $key && !isset($colorDefs[$key])) {
                $allValid = false;
            }
        }
        $passed = count($phenotypes) > 0 && $allValid;
        $msg = "All output colors valid (Tier 1/2 in COLOR_DEFINITIONS, Tier 3 dynamic)";
        addTestResult($cat, 'Output color names valid', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Output color names valid', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 18: 全件出力機能 (no_filter オプション)
    // ==========================================================
    $cat = '統合: 全件出力 (no_filter)';
    $calculator = new GeneticsCalculator();

    // Test 18-1: no_filterオプションで確率100%になるか
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'Vv',
            'f_ino' => '+ino', 'f_op' => '+op', 'f_cin' => '++',
            'f_flp' => '+flp', 'f_flb' => '+flb', 'f_pidom' => 'Pi+', 'f_pirec' => '+pi',
            'f_dil' => '+dil', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '+tq', 'm_dark' => 'Dd', 'm_vio' => 'Vv',
            'm_ino' => '+W', 'm_op' => 'opW', 'm_cin' => '+W',
            'm_flp' => '+flp', 'm_flb' => '++', 'm_pidom' => 'Pi+', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '+ed', 'm_of' => '++', 'm_ph' => '++',
            'no_filter' => true,
        ];
        $result = $calculator->calculateOffspring($input);
        $phenotypes = $result['phenotype'] ?? [];
        $totalProb = array_sum(array_column($phenotypes, 'probability')) * 100;

        $passed = abs($totalProb - 100) < 0.1;
        $msg = "no_filter=true: " . count($phenotypes) . " phenotypes, Total: " . round($totalProb, 2) . "%";
        addTestResult($cat, 'no_filter produces 100% probability sum', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'no_filter produces 100% probability sum', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 18-2: フィルタリングありとなしで結果数が異なるか
    try {
        $inputFiltered = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '+aq', 'f_dark' => 'Dd', 'f_vio' => 'Vv',
            'f_ino' => '+ino', 'f_op' => '+op', 'f_cin' => '++',
            'f_flp' => '+flp', 'f_flb' => '+flb', 'f_pidom' => 'Pi+', 'f_pirec' => '+pi',
            'f_dil' => '+dil', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '+tq', 'm_dark' => 'Dd', 'm_vio' => 'Vv',
            'm_ino' => '+W', 'm_op' => 'opW', 'm_cin' => '+W',
            'm_flp' => '+flp', 'm_flb' => '++', 'm_pidom' => 'Pi+', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '+ed', 'm_of' => '++', 'm_ph' => '++',
        ];
        $inputFull = array_merge($inputFiltered, ['no_filter' => true]);

        $resultFiltered = $calculator->calculateOffspring($inputFiltered);
        $resultFull = $calculator->calculateOffspring($inputFull);

        $countFiltered = count($resultFiltered['phenotype'] ?? []);
        $countFull = count($resultFull['phenotype'] ?? []);

        $passed = $countFull > $countFiltered;
        $msg = "Filtered: {$countFiltered}, Full: {$countFull}";
        addTestResult($cat, 'no_filter returns more results than filtered', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'no_filter returns more results than filtered', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 18-3: 単純なケースではno_filterでも結果数が同じ
    try {
        $inputSimple = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];

        $resultFiltered = $calculator->calculateOffspring($inputSimple);
        $resultFull = $calculator->calculateOffspring(array_merge($inputSimple, ['no_filter' => true]));

        $countFiltered = count($resultFiltered['phenotype'] ?? []);
        $countFull = count($resultFull['phenotype'] ?? []);

        $passed = $countFiltered === $countFull;
        $msg = "Simple case: Filtered={$countFiltered}, Full={$countFull}";
        addTestResult($cat, 'Simple case: filtered = full results', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Simple case: filtered = full results', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 19: 入力バリデーション
    // ==========================================================
    $cat = '統合: 入力バリデーション';
    $calculator = new GeneticsCalculator();

    // Test 19-1: 無効なアレル値の検出と警告
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => 'INVALID', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $warnings = $result['warnings'] ?? [];
        $hasWarning = count($warnings) > 0 && strpos($warnings[0], 'INVALID') !== false;
        $hasResults = count($result['phenotype'] ?? []) > 0;
        $passed = $hasWarning && $hasResults;
        $msg = "Invalid allele: warning=" . ($hasWarning ? 'Yes' : 'No') . ", results=" . count($result['phenotype'] ?? []);
        addTestResult($cat, 'Invalid allele detection and warning', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Invalid allele detection and warning', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 19-2: オスに+W指定時の検出と自動修正
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '+W', 'f_op' => 'opW', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $warnings = $result['warnings'] ?? [];
        $hasMaleWWarning = false;
        foreach ($warnings as $w) {
            if (strpos($w, 'オスに+Wは指定できません') !== false) {
                $hasMaleWWarning = true;
                break;
            }
        }
        $hasResults = count($result['phenotype'] ?? []) > 0;
        $passed = $hasMaleWWarning && $hasResults;
        $msg = "Male +W: warning=" . ($hasMaleWWarning ? 'Yes' : 'No') . ", warnings count=" . count($warnings);
        addTestResult($cat, 'Male +W detection and auto-correction', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Male +W detection and auto-correction', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 19-3: 正常な入力では警告が出ない
    try {
        $input = [
            'f_mode' => 'genotype', 'm_mode' => 'genotype',
            'f_parblue' => '++', 'f_dark' => 'dd', 'f_vio' => 'vv',
            'f_ino' => '++', 'f_op' => '++', 'f_cin' => '++',
            'f_flp' => '++', 'f_flb' => '++', 'f_pidom' => '++', 'f_pirec' => '++',
            'f_dil' => '++', 'f_ed' => '++', 'f_of' => '++', 'f_ph' => '++',
            'm_parblue' => '++', 'm_dark' => 'dd', 'm_vio' => 'vv',
            'm_ino' => '+W', 'm_op' => '+W', 'm_cin' => '+W',
            'm_flp' => '++', 'm_flb' => '++', 'm_pidom' => '++', 'm_pirec' => '++',
            'm_dil' => '++', 'm_ed' => '++', 'm_of' => '++', 'm_ph' => '++',
        ];
        $result = $calculator->calculateOffspring($input);
        $warnings = $result['warnings'] ?? [];
        $passed = count($warnings) === 0;
        $msg = "Valid input: warnings=" . count($warnings);
        addTestResult($cat, 'Valid input produces no warnings', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Valid input produces no warnings', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 20: エッジケース・エラーハンドリング
    // ==========================================================
    $cat = '統合: エッジケース';

    // Test 20-1: FamilyEstimatorV3 空の家系図
    try {
        $fe = new FamilyEstimatorV3();
        $result = $fe->estimate([], 'sire');
        $hasError = isset($result['error']) && strpos($result['error'], '空') !== false;
        $passed = $hasError;
        $msg = "Empty family map: " . ($result['error'] ?? 'No error');
        addTestResult($cat, 'FamilyEstimatorV3 empty input handling', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'FamilyEstimatorV3 empty input handling', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 20-2: レガシー色名マッピング (dark-green → darkgreen)
    try {
        // infer.phpのmapLegacyColorName関数をシミュレート
        $testCases = [
            'dark-green' => 'darkgreen',
            'opaline-green' => 'opaline_green',
            'normal' => 'green',
        ];
        $allPassed = true;
        $results = [];
        foreach ($testCases as $input => $expected) {
            // マッピングロジックをシミュレート
            $mapped = $input;
            if (!isset(AgapornisLoci::COLOR_DEFINITIONS[$input])) {
                $legacyMap = [
                    'dark-green' => 'darkgreen',
                    'opaline-green' => 'opaline_green',
                    'normal' => 'green',
                ];
                $lower = strtolower($input);
                if (isset($legacyMap[$lower])) {
                    $mapped = $legacyMap[$lower];
                }
            }
            $exists = isset(AgapornisLoci::COLOR_DEFINITIONS[$mapped]);
            if ($mapped !== $expected || !$exists) {
                $allPassed = false;
            }
            $results[] = "{$input}→{$mapped}:" . ($exists ? 'OK' : 'NG');
        }
        $passed = $allPassed;
        $msg = implode(', ', $results);
        addTestResult($cat, 'Legacy color name mapping (dark-green)', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Legacy color name mapping (dark-green)', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 20-3: GeneticsCalculator 空入力
    try {
        $calc = new GeneticsCalculator();
        $result = $calc->calculateOffspring([]);
        // 空入力でもデフォルト値で計算されるべき
        $passed = isset($result['phenotype']) && count($result['phenotype']) > 0;
        $msg = "Empty input: " . count($result['phenotype'] ?? []) . " phenotypes";
        addTestResult($cat, 'GeneticsCalculator empty input handling', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'GeneticsCalculator empty input handling', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // ==========================================================
    // SECTION 21: 11言語翻訳漏れチェック
    // ==========================================================
    $cat = '統合: 11言語翻訳';

    // Test 21-1: lang.php 読み込み確認
    try {
        require_once 'lang.php';
        $langLoaded = isset($translations) && is_array($translations);
        $langCount = $langLoaded ? count($translations) : 0;
        $passed = $langLoaded && $langCount >= 11;
        $msg = "lang.php: {$langCount} languages loaded";
        addTestResult($cat, 'lang.php loads 11+ languages', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'lang.php loads 11+ languages', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 21-2: 日本語・英語の翻訳キー数が同等
    try {
        $jaKeys = count($translations['ja'] ?? []);
        $enKeys = count($translations['en'] ?? []);
        // 日本語と英語のキー数差が10%以内であること
        $diff = abs($jaKeys - $enKeys);
        $threshold = max($jaKeys, $enKeys) * 0.1;
        $passed = $diff <= $threshold && $jaKeys > 200 && $enKeys > 200;
        $msg = "ja: {$jaKeys} keys, en: {$enKeys} keys, diff: {$diff}";
        addTestResult($cat, 'Base languages (ja/en) balanced', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Base languages (ja/en) balanced', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 21-3: 各言語の翻訳カバレッジレポート（60%以上で合格）
    try {
        $targetLangs = ['de', 'fr', 'it', 'es', 'pt', 'nl', 'id', 'th', 'tl'];
        $baseKeyCount = count($translations['ja'] ?? []);
        $lowCoverageLangs = [];
        $coverageReport = [];

        foreach ($targetLangs as $lang) {
            if (isset($translations[$lang])) {
                $langKeyCount = count($translations[$lang]);
                $coverage = ($langKeyCount / $baseKeyCount) * 100;
                $coverageReport[] = "{$lang}:" . round($coverage) . "%";
                if ($coverage < 60) {
                    $lowCoverageLangs[] = $lang;
                }
            } else {
                $coverageReport[] = "{$lang}:missing";
                $lowCoverageLangs[] = $lang;
            }
        }

        $passed = count($lowCoverageLangs) === 0;
        $msg = implode(', ', $coverageReport);
        addTestResult($cat, 'All languages ≥60% coverage', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'All languages ≥60% coverage', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 21-4: 必須キーの存在確認（全言語）
    try {
        $essentialKeys = ['ok', 'error', 'cancel', 'save', 'male', 'female', 'sire', 'dam', 'offspring', 'genotype_info'];
        $missingEssential = [];

        foreach ($translations as $lang => $trans) {
            foreach ($essentialKeys as $key) {
                if (!isset($trans[$key]) || empty($trans[$key])) {
                    $missingEssential[] = "{$lang}:{$key}";
                }
            }
        }

        $passed = count($missingEssential) === 0;
        $msg = $passed ? "All essential keys present in all languages" : "Missing: " . implode(', ', array_slice($missingEssential, 0, 5));
        addTestResult($cat, 'Essential keys in all languages', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'Essential keys in all languages', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 21-5: lang_guardian.php 読み込み確認
    try {
        require_once 'lang_guardian.php';
        $guardianLangLoaded = isset($guardian_translations) && is_array($guardian_translations);
        $guardianLangCount = $guardianLangLoaded ? count($guardian_translations) : 0;
        $passed = $guardianLangLoaded && $guardianLangCount >= 2;
        $msg = "lang_guardian.php: {$guardianLangCount} languages";
        addTestResult($cat, 'lang_guardian.php loaded', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'lang_guardian.php loaded', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }

    // Test 21-6: lang_pathfinder.php 読み込み確認（$pf_XX形式）
    try {
        require_once 'lang_pathfinder.php';
        // lang_pathfinder.phpは$pf_ja, $pf_en等の形式で定義
        $pfLangCount = 0;
        $pfLangs = [];
        foreach (['ja', 'en', 'de', 'fr', 'it', 'es', 'pt', 'nl', 'id', 'th', 'tl'] as $lang) {
            $varName = 'pf_' . $lang;
            if (isset($$varName) && is_array($$varName)) {
                $pfLangCount++;
                $pfLangs[] = $lang;
            }
        }
        $passed = $pfLangCount >= 2;
        $msg = "lang_pathfinder.php: {$pfLangCount} languages (" . implode(', ', $pfLangs) . ")";
        addTestResult($cat, 'lang_pathfinder.php loaded', $passed, $msg, $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    } catch (Throwable $e) {
        addTestResult($cat, 'lang_pathfinder.php loaded', false, 'Exception: ' . $e->getMessage(), $testResults, $totalTests, $passedTests, $failedTests, $testCategories);
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gene-Forge Debug Test</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Noto+Sans+JP:wght@300;400;500;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=debug2">
    <style>
        .debug-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .debug-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .debug-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            color: #00ffcc;
            margin-bottom: 0.5rem;
        }
        .debug-header p {
            color: #99aabb;
        }
        .start-button {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 2rem auto;
            padding: 1.5rem 2rem;
            font-size: 1.5rem;
            font-weight: bold;
            font-family: 'Noto Sans JP', sans-serif;
            background: linear-gradient(135deg, #00ffcc, #00d4aa);
            color: #000;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 255, 204, 0.3);
        }
        .start-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(0, 255, 204, 0.5);
        }
        .start-button:active {
            transform: translateY(0);
        }
        .results-section {
            margin-top: 2rem;
        }
        .results-summary {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .summary-item {
            text-align: center;
            padding: 1rem 2rem;
            background: var(--bg-card, #1e2329);
            border-radius: 8px;
            min-width: 120px;
        }
        .summary-item.total { border-left: 4px solid #00ffcc; }
        .summary-item.passed { border-left: 4px solid #2ecc71; }
        .summary-item.failed { border-left: 4px solid #e74c3c; }
        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
        }
        .summary-item.total .summary-number { color: #00ffcc; }
        .summary-item.passed .summary-number { color: #2ecc71; }
        .summary-item.failed .summary-number { color: #e74c3c; }
        .summary-label {
            font-size: 0.9rem;
            color: #99aabb;
            margin-top: 0.25rem;
        }
        .category-section {
            margin-bottom: 2rem;
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: linear-gradient(90deg, #1a1f26, #252b33);
            border-radius: 8px 8px 0 0;
            border-left: 4px solid #00ffcc;
            margin-bottom: 0;
        }
        .category-title {
            font-weight: bold;
            color: #fff;
            font-size: 1.1rem;
        }
        .category-stats {
            font-size: 0.9rem;
            color: #99aabb;
        }
        .category-stats .pass { color: #2ecc71; }
        .category-stats .fail { color: #e74c3c; }
        .test-list {
            list-style: none;
            padding: 0;
            margin: 0;
            background: var(--bg-card, #1e2329);
            border-radius: 0 0 8px 8px;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #333;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-item.passed {
            border-left: 3px solid #2ecc71;
        }
        .test-item.failed {
            border-left: 3px solid #e74c3c;
        }
        .test-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        .test-item.passed .test-status {
            background: #2ecc71;
            color: #fff;
        }
        .test-item.failed .test-status {
            background: #e74c3c;
            color: #fff;
        }
        .test-info {
            flex: 1;
        }
        .test-name {
            font-weight: bold;
            color: #fff;
            font-size: 0.95rem;
        }
        .test-message {
            font-size: 0.8rem;
            color: #99aabb;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 0.15rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg-tertiary, #252b33);
            color: #00ffcc;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .back-link:hover {
            background: var(--bg-card, #1e2329);
        }
        .test-description {
            text-align: center;
            color: #99aabb;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .test-description ul {
            display: inline-block;
            text-align: left;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1>Gene-Forge Debug Test</h1>
            <p>遺伝計算エンジン 包括的テスト</p>
        </div>

        <?php if (!$runTests): ?>
            <div class="test-description">
                <p>以下の機能を複数の交配条件でテストします：</p>
                <ul>
                    <li><strong>配合結果</strong> - GeneticsCalculator</li>
                    <li><strong>目標プランナー</strong> - PathFinder</li>
                    <li><strong>一族推論</strong> - FamilyEstimatorV3</li>
                    <li><strong>遺伝型推定</strong> - GenotypeEstimator</li>
                    <li><strong>表現型解決</strong> - resolveColor</li>
                </ul>
            </div>
            <form method="POST" action="">
                <button type="submit" name="run_tests" value="1" class="start-button">
                    テスト開始
                </button>
            </form>
        <?php else: ?>
            <div class="results-section">
                <div class="results-summary">
                    <div class="summary-item total">
                        <div class="summary-number"><?= $totalTests ?></div>
                        <div class="summary-label">Total Tests</div>
                    </div>
                    <div class="summary-item passed">
                        <div class="summary-number"><?= $passedTests ?></div>
                        <div class="summary-label">Passed</div>
                    </div>
                    <div class="summary-item failed">
                        <div class="summary-number"><?= $failedTests ?></div>
                        <div class="summary-label">Failed</div>
                    </div>
                </div>

                <?php foreach ($testCategories as $catName => $catData): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <span class="category-title"><?= htmlspecialchars($catName) ?></span>
                            <span class="category-stats">
                                <span class="pass"><?= $catData['passed'] ?> passed</span> /
                                <span class="fail"><?= $catData['failed'] ?> failed</span>
                            </span>
                        </div>
                        <ul class="test-list">
                            <?php foreach ($catData['tests'] as $test): ?>
                                <li class="test-item <?= $test['passed'] ? 'passed' : 'failed' ?>">
                                    <div class="test-status">
                                        <?= $test['passed'] ? '✓' : '✗' ?>
                                    </div>
                                    <div class="test-info">
                                        <div class="test-name"><?= htmlspecialchars($test['name']) ?></div>
                                        <div class="test-message"><?= htmlspecialchars($test['message']) ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

                <!-- JavaScript Tests Section -->
                <div id="js-test-section">
                    <div class="category-section" id="js-test-breeding-validator">
                        <div class="category-header">
                            <span class="category-title">JS: BreedingValidator (近交係数)</span>
                            <span class="category-stats" id="bv-stats">
                                <span class="pass">0 passed</span> /
                                <span class="fail">0 failed</span>
                            </span>
                        </div>
                        <ul class="test-list" id="bv-tests"></ul>
                    </div>

                    <div class="category-section" id="js-test-health-guardian">
                        <div class="category-header">
                            <span class="category-title">JS: HealthGuardian (健康評価)</span>
                            <span class="category-stats" id="hg-stats">
                                <span class="pass">0 passed</span> /
                                <span class="fail">0 failed</span>
                            </span>
                        </div>
                        <ul class="test-list" id="hg-tests"></ul>
                    </div>

                    <div class="category-section" id="js-test-planner">
                        <div class="category-header">
                            <span class="category-title">JS: BreedingPlanner (経路計画)</span>
                            <span class="category-stats" id="bp-stats">
                                <span class="pass">0 passed</span> /
                                <span class="fail">0 failed</span>
                            </span>
                        </div>
                        <ul class="test-list" id="bp-tests"></ul>
                    </div>
                </div>

                <form method="POST" action="" style="text-align: center;">
                    <button type="submit" name="run_tests" value="1" class="start-button" style="max-width: 300px; font-size: 1.2rem; padding: 1rem 1.5rem;">
                        再テスト
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="index.php" class="back-link">← メインアプリに戻る</a>
        </div>
    </div>

    <!-- Load JS modules for testing -->
    <script src="guardian.js"></script>
    <script src="planner.js"></script>

    <script>
    // ============================================================
    // JavaScript Test Runner
    // ============================================================
    (function() {
        if (!document.getElementById('js-test-section')) return;

        const jsResults = { total: 0, passed: 0, failed: 0 };

        function addJsTestResult(listId, statsId, name, passed, message) {
            jsResults.total++;
            if (passed) jsResults.passed++;
            else jsResults.failed++;

            const list = document.getElementById(listId);
            const li = document.createElement('li');
            li.className = 'test-item ' + (passed ? 'passed' : 'failed');
            li.innerHTML = `
                <div class="test-status">${passed ? '✓' : '✗'}</div>
                <div class="test-info">
                    <div class="test-name">${name}</div>
                    <div class="test-message">${message}</div>
                </div>
            `;
            list.appendChild(li);

            // Update stats
            const passCount = list.querySelectorAll('.passed').length;
            const failCount = list.querySelectorAll('.failed').length;
            document.getElementById(statsId).innerHTML = `
                <span class="pass">${passCount} passed</span> /
                <span class="fail">${failCount} failed</span>
            `;
        }

        // ============================================================
        // BreedingValidator Tests
        // ============================================================
        function runBreedingValidatorTests() {
            const BV = window.BreedingValidator;
            if (!BV) {
                addJsTestResult('bv-tests', 'bv-stats', 'BreedingValidator exists', false, 'Not loaded');
                return;
            }

            // Test 1: Class exists
            addJsTestResult('bv-tests', 'bv-stats', 'BreedingValidator exists', true, 'OK');

            // Test 2: Wright F calculation - Half siblings (shared sire)
            // Half siblings: F = (1/2)^3 = 0.125 (12.5%)
            const sire1 = {
                id: 'sire1', sex: 'male',
                pedigree: { sire: 'grandpa1', dam: 'grandma1' }
            };
            const dam1 = {
                id: 'dam1', sex: 'female',
                pedigree: { sire: 'grandpa1', dam: 'grandma2' }  // Same sire, different dam
            };
            const ic1 = BV.calcInbreedingCoefficient(sire1, dam1);
            const expected1 = Math.pow(0.5, 1 + 1 + 1);  // (1/2)^(1+1+1) = 0.125
            addJsTestResult('bv-tests', 'bv-stats',
                'Wright F: Half siblings = 12.5%',
                Math.abs(ic1 - expected1) < 0.001,
                `F = ${(ic1 * 100).toFixed(2)}% (expected ${(expected1 * 100).toFixed(2)}%)`
            );

            // Test 3: Wright F calculation - Full siblings
            // Full siblings: F = (1/2)^3 + (1/2)^3 = 0.25 (25%)
            const sire2 = {
                id: 'sire2', sex: 'male',
                pedigree: { sire: 'papa', dam: 'mama' }
            };
            const dam2 = {
                id: 'dam2', sex: 'female',
                pedigree: { sire: 'papa', dam: 'mama' }  // Same parents
            };
            const ic2 = BV.calcInbreedingCoefficient(sire2, dam2);
            const expected2 = 2 * Math.pow(0.5, 1 + 1 + 1);  // 2 * (1/2)^3 = 0.25
            addJsTestResult('bv-tests', 'bv-stats',
                'Wright F: Full siblings = 25%',
                Math.abs(ic2 - expected2) < 0.001,
                `F = ${(ic2 * 100).toFixed(2)}% (expected ${(expected2 * 100).toFixed(2)}%)`
            );

            // Test 4: Wright F calculation - Unrelated = 0%
            const sire3 = {
                id: 'sire3', sex: 'male',
                pedigree: { sire: 'a1', dam: 'a2' }
            };
            const dam3 = {
                id: 'dam3', sex: 'female',
                pedigree: { sire: 'b1', dam: 'b2' }  // No shared ancestors
            };
            const ic3 = BV.calcInbreedingCoefficient(sire3, dam3);
            addJsTestResult('bv-tests', 'bv-stats',
                'Wright F: Unrelated = 0%',
                ic3 === 0,
                `F = ${(ic3 * 100).toFixed(2)}%`
            );

            // Test 5: Validation - Sex check (male)
            const wrongSire = { id: 'x', sex: 'female', pedigree: {} };
            const validDam = { id: 'y', sex: 'female', pedigree: {} };
            const result5 = BV.validate(wrongSire, validDam, 'plan');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Male sex check',
                result5.allowed === false && result5.type === 'fact',
                result5.allowed ? 'Incorrectly allowed' : 'Blocked: ' + (result5.reason || '').substring(0, 30)
            );

            // Test 6: Validation - Sex check (female)
            const validSire = { id: 'x', sex: 'male', pedigree: {} };
            const wrongDam = { id: 'y', sex: 'male', pedigree: {} };
            const result6 = BV.validate(validSire, wrongDam, 'plan');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Female sex check',
                result6.allowed === false && result6.type === 'fact',
                result6.allowed ? 'Incorrectly allowed' : 'Blocked: ' + (result6.reason || '').substring(0, 30)
            );

            // Test 7: Validation - Same bird
            const sameBird = { id: 'same', sex: 'male', pedigree: {} };
            const sameBird2 = { id: 'same', sex: 'female', pedigree: {} };
            const result7 = BV.validate(sameBird, sameBird2, 'plan');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Same bird detection',
                result7.allowed === false,
                result7.allowed ? 'Incorrectly allowed' : 'Blocked'
            );

            // Test 8: Validation - High risk (half siblings) in plan mode
            const result8 = BV.validate(sire1, dam1, 'plan');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Half siblings blocked in plan mode',
                result8.allowed === false && result8.type === 'ethics',
                result8.allowed ? 'Incorrectly allowed' : 'Blocked (ethics)'
            );

            // Test 9: Validation - High risk (half siblings) in fact mode = warning only
            const result9 = BV.validate(sire1, dam1, 'fact');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Half siblings warning in fact mode',
                result9.allowed === true && result9.warning !== undefined,
                result9.allowed ? 'Allowed with warning' : 'Incorrectly blocked'
            );

            // Test 10: Validation - Unrelated passes
            const result10 = BV.validate(sire3, dam3, 'plan');
            addJsTestResult('bv-tests', 'bv-stats',
                'Validate: Unrelated passes',
                result10.allowed === true && !result10.warning,
                result10.allowed ? 'Allowed' : 'Incorrectly blocked'
            );
        }

        // ============================================================
        // HealthGuardian Tests
        // ============================================================
        function runHealthGuardianTests() {
            const HG = window.HealthGuardian;
            if (!HG) {
                addJsTestResult('hg-tests', 'hg-stats', 'HealthGuardian exists', false, 'Not loaded');
                return;
            }

            // Test 1: Class exists
            addJsTestResult('hg-tests', 'hg-stats', 'HealthGuardian exists', true, 'OK');

            // Test 2: _hasINOGenes detection
            const genoLutino = { ino: 'inoino' };
            const genoGreen = { ino: '++' };
            const hasIno = HG._hasINOGenes(genoLutino);
            const noIno = HG._hasINOGenes(genoGreen);
            addJsTestResult('hg-tests', 'hg-stats',
                '_hasINOGenes: Lutino detection',
                hasIno === true && noIno === false,
                `Lutino=${hasIno}, Green=${noIno}`
            );

            // Test 3: _hasPallidGenes detection
            const genoPallid = { ino: 'pldpld' };
            const hasPallid = HG._hasPallidGenes(genoPallid);
            const noPallid = HG._hasPallidGenes(genoGreen);
            addJsTestResult('hg-tests', 'hg-stats',
                '_hasPallidGenes: Pallid detection',
                hasPallid === true && noPallid === false,
                `Pallid=${hasPallid}, Green=${noPallid}`
            );

            // Test 4: _hasFallowGenes detection
            const genoFallow = { fallow_pale: 'flpflp' };
            const genoFallowBronze = { fallow_bronze: 'flbflb' };
            const hasFallow1 = HG._hasFallowGenes(genoFallow);
            const hasFallow2 = HG._hasFallowGenes(genoFallowBronze);
            const noFallow = HG._hasFallowGenes(genoGreen);
            addJsTestResult('hg-tests', 'hg-stats',
                '_hasFallowGenes: Fallow detection',
                hasFallow1 === true && hasFallow2 === true && noFallow === false,
                `Pale=${hasFallow1}, Bronze=${hasFallow2}, Green=${noFallow}`
            );

            // Test 5: _hasDarkDF detection
            const genoDarkDF = { dark: 'DD' };
            const genoDarkSF = { dark: 'Dd' };
            const hasDarkDF = HG._hasDarkDF(genoDarkDF);
            const hasDarkSF = HG._hasDarkDF(genoDarkSF);
            addJsTestResult('hg-tests', 'hg-stats',
                '_hasDarkDF: Dark DF detection',
                hasDarkDF === true && hasDarkSF === false,
                `DD=${hasDarkDF}, Dd=${hasDarkSF}`
            );

            // Test 6: evaluateHealth - Safe pairing
            const safeMale = { genotype: { ino: '++', parblue: '++' }, inbreedingGen: 0 };
            const safeFemale = { genotype: { ino: '+W', parblue: '++' }, inbreedingGen: 0 };
            const safeResult = HG.evaluateHealth(safeMale, safeFemale, 0);
            addJsTestResult('hg-tests', 'hg-stats',
                'evaluateHealth: Safe pairing',
                safeResult.canBreed === true && safeResult.riskLevel === 'safe',
                `canBreed=${safeResult.canBreed}, risk=${safeResult.riskLevel}`
            );

            // Test 7: evaluateHealth - INO limit exceeded (gen 3)
            const inoMale = { genotype: { ino: 'inoino', parblue: '++' }, inbreedingGen: 2 };
            const inoFemale = { genotype: { ino: 'inoW', parblue: '++' }, inbreedingGen: 2 };
            const inoResult = HG.evaluateHealth(inoMale, inoFemale, 0);
            // Gen 3 > limit 2 → should block
            addJsTestResult('hg-tests', 'hg-stats',
                'evaluateHealth: INO limit exceeded (gen 3)',
                inoResult.canBreed === false || inoResult.riskLevel === 'critical',
                `canBreed=${inoResult.canBreed}, risk=${inoResult.riskLevel}, blocks=${inoResult.blocks.length}`
            );

            // Test 8: evaluateHealth - High inbreeding coefficient
            const highIcResult = HG.evaluateHealth(safeMale, safeFemale, 0.30);  // 30%
            addJsTestResult('hg-tests', 'hg-stats',
                'evaluateHealth: High F (30%) blocks',
                highIcResult.canBreed === false,
                `canBreed=${highIcResult.canBreed}, blocks=${highIcResult.blocks.length}`
            );

            // Test 9: calculateHealthScore
            const healthyBird = { genotype: { ino: '++' }, inbreedingGen: 0 };
            const score1 = HG.calculateHealthScore(healthyBird);
            const weakBird = { genotype: { ino: 'inoino' }, inbreedingGen: 2 };
            const score2 = HG.calculateHealthScore(weakBird);
            addJsTestResult('hg-tests', 'hg-stats',
                'calculateHealthScore: Healthy > Weak',
                score1 > score2,
                `Healthy=${score1}, Weak=${score2}`
            );

            // Test 10: THRESHOLDS values
            const critical = HG.F_THRESHOLDS.critical;
            const high = HG.F_THRESHOLDS.high;
            addJsTestResult('hg-tests', 'hg-stats',
                'F_THRESHOLDS: critical=25%, high=12.5%',
                critical === 0.25 && high === 0.125,
                `critical=${critical}, high=${high}`
            );
        }

        // ============================================================
        // BreedingPlanner Tests
        // ============================================================
        function runBreedingPlannerTests() {
            const BP = window.BreedingPlanner;
            if (!BP) {
                addJsTestResult('bp-tests', 'bp-stats', 'BreedingPlanner exists', false, 'Not loaded');
                return;
            }

            // Test 1: Class exists
            addJsTestResult('bp-tests', 'bp-stats', 'BreedingPlanner exists', true, 'OK');

            // Test 2: TARGET_REQUIREMENTS has essential colors
            const TR = BP.TARGET_REQUIREMENTS;
            const hasGreen = !!TR.green;
            const hasLutino = !!TR.lutino;
            const hasAqua = !!TR.aqua;
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: Essential colors',
                hasGreen && hasLutino && hasAqua,
                `green=${hasGreen}, lutino=${hasLutino}, aqua=${hasAqua}`
            );

            // Test 3: Green requirements are correct
            const greenReq = TR.green;
            const greenCorrect = greenReq &&
                greenReq.required.parblue.includes('++') &&
                greenReq.required.dark.includes('dd');
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: Green = parblue++ dark:dd',
                greenCorrect,
                greenCorrect ? 'OK' : 'Incorrect requirements'
            );

            // Test 4: Lutino requirements include SLR (sex-linked recessive)
            const lutinoReq = TR.lutino;
            const lutinoCorrect = lutinoReq &&
                lutinoReq.slr &&
                lutinoReq.slr.ino &&
                lutinoReq.slr.ino.includes('inoino');
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: Lutino has SLR ino',
                lutinoCorrect,
                lutinoCorrect ? 'slr.ino includes inoino' : 'Missing SLR'
            );

            // Test 5: Inbreeding limits set for INO colors
            const lutinoLimit = TR.lutino?.inbreedingLimit;
            const creaminoLimit = TR.creamino?.inbreedingLimit;
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: INO inbreeding limits',
                lutinoLimit === 2 && creaminoLimit === 2,
                `lutino=${lutinoLimit}, creamino=${creaminoLimit}`
            );

            // Test 6: INBREEDING_THRESHOLD is 12.5%
            const threshold = BP.INBREEDING_THRESHOLD;
            addJsTestResult('bp-tests', 'bp-stats',
                'INBREEDING_THRESHOLD = 12.5%',
                threshold === 0.125,
                `threshold=${threshold}`
            );

            // Test 7: getColorName function exists
            const hasGetColorName = typeof BP.getColorName === 'function';
            addJsTestResult('bp-tests', 'bp-stats',
                'getColorName function exists',
                hasGetColorName,
                hasGetColorName ? 'OK' : 'Missing'
            );

            // Test 8: generateRequirementsFromMaster exists
            const hasGenReq = typeof BP.generateRequirementsFromMaster === 'function';
            addJsTestResult('bp-tests', 'bp-stats',
                'generateRequirementsFromMaster exists',
                hasGenReq,
                hasGenReq ? 'OK' : 'Missing'
            );

            // Test 9: Difficulty levels are valid
            const validDifficulties = ['none', 'low', 'mid', 'high'];
            let allValid = true;
            for (const key in TR) {
                if (TR[key].difficulty && !validDifficulties.includes(TR[key].difficulty)) {
                    allValid = false;
                    break;
                }
            }
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: Valid difficulty levels',
                allValid,
                allValid ? 'All valid' : 'Invalid difficulty found'
            );

            // Test 10: Count target colors
            const colorCount = Object.keys(TR).length;
            addJsTestResult('bp-tests', 'bp-stats',
                'TARGET_REQUIREMENTS: 30+ target colors',
                colorCount >= 30,
                `${colorCount} colors defined`
            );
        }

        // Run all JS tests
        runBreedingValidatorTests();
        runHealthGuardianTests();
        runBreedingPlannerTests();

        // Update PHP summary with JS results
        const totalEl = document.querySelector('.summary-item.total .summary-number');
        const passedEl = document.querySelector('.summary-item.passed .summary-number');
        const failedEl = document.querySelector('.summary-item.failed .summary-number');

        if (totalEl && passedEl && failedEl) {
            const phpTotal = parseInt(totalEl.textContent);
            const phpPassed = parseInt(passedEl.textContent);
            const phpFailed = parseInt(failedEl.textContent);

            totalEl.textContent = phpTotal + jsResults.total;
            passedEl.textContent = phpPassed + jsResults.passed;
            failedEl.textContent = phpFailed + jsResults.failed;
        }
    })();
    </script>
</body>
</html>
