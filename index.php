<?php
// DEBUG: エラー表示ON
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * @license CC BY-NC-SA 4.0
 * Commercial use strictly prohibited.
 * NPO/Educational use welcome.
 * 
 * 「制度は責任を放棄した。制度外がそれを果たす。」
 * 制度外文明・かならづプロジェクト
 *
 * Agapornis Gene-Forge v7.0
 * 連鎖遺伝（Linkage Genetics）対応版
 * FamilyEstimator V3 搭載
 * ALBS Peachfaced部門準拠版
 * 
 * v6.7.3 → v6.7.4 変更点:
 * - 全ハードコードをt()関数呼び出しに統一
 * - 多言語対応基盤整備
 */
require_once 'genetics.php';
require_once 'lang.php';

/**
 * v7.3.2: プレースホルダー置換ヘルパー（opcacheバイパス用）
 * t_pf()の結果に対して追加の置換を行う
 */
function t_pf_fix(string $text, array $params = []): string {
    foreach ($params as $key => $value) {
        $text = str_replace('{' . $key . '}', (string)$value, $text);
    }
    return $text;
}

$lang = getLang();
// セキュリティ: langパラメータをホワイトリストで検証
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en', 'de', 'fr', 'it', 'es'], true)) {
    setcookie('lang', $_GET['lang'], [
        'expires' => time() + 86400 * 365,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * 共通：表現型選択肢を生成
 * 全画面で統一された選択肢を使用
 * v6.7.4: 全ラベルをt()関数経由に変更
 */
function renderPhenotypeSelect(string $prefix, string $field, bool $isJa = true, string $selected = ''): string {
    if ($field === 'baseColor') {
        $grouped = AgapornisLoci::groupedByCategory();
        $html = '<select name="' . $prefix . '_' . $field . '" class="form-select">';
        foreach ($grouped as $category => $colors) {
            $html .= '<optgroup label="' . htmlspecialchars(AgapornisLoci::categoryLabel($category, $isJa)) . '">';
            foreach ($colors as $key => $data) {
                $label = $isJa ? $data['ja'] : $data['en'];
                $sel = ($selected === $key) ? ' selected' : '';
                $html .= '<option value="' . $key . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';
        return $html;
    }    
    switch ($field) {
        case 'eyeColor':
            $sel = function($v) use ($selected) { return $selected === $v ? ' selected' : ''; };
            return '<select name="' . $prefix . '_eyeColor" class="form-select">
                <option value="black"' . $sel('black') . '>' . t('eye_black') . '</option>
                <option value="red"' . $sel('red') . '>' . t('eye_red') . '</option>
                <option value="plum"' . $sel('plum') . '>' . t('eye_plum') . '</option>
            </select>';
            
        case 'darkness':
            $sel = function($v) use ($selected) { return $selected === $v ? ' selected' : ''; };
            return '<select name="' . $prefix . '_darkness" class="form-select">
                <option value="none"' . $sel('none') . '>' . t('dark_none') . '</option>
                <option value="sf"' . $sel('sf') . '>' . t('dark_sf') . '</option>
                <option value="df"' . $sel('df') . '>' . t('dark_df') . '</option>
                <option value="unknown"' . $sel('unknown') . '>' . t('unknown') . '</option>
            </select>';
    }
    return '';
}

/**
 * v7.3.20: 近親交配制限セクションのHTML生成
 * 全ての結果セクションで共通使用
 */
function renderInbreedingLimitSection(): string {
    return '
    <div class="card inbreeding-limit-section" style="margin-top: 1.5rem;">
        <h3 class="card-title">' . t('inbreeding_limit') . '</h3>
        <div class="health-recommendations">
            <ul>
                <li class="urgency-critical"><strong>INO (' . t('lutino') . '/' . t('creamino') . '/' . t('pure_white') . '): 2 ' . t('generation') . '</strong><span class="rec-detail">' . t('static_ino_limit_desc') . '</span></li>
                <li class="urgency-critical"><strong>' . t('pallid') . ': 2 ' . t('generation') . '</strong><span class="rec-detail">' . t('static_pallid_limit_desc') . '</span></li>
                <li class="urgency-high"><strong>' . t('fallow') . ': 2 ' . t('generation') . '</strong><span class="rec-detail">' . t('static_fallow_limit_desc') . '</span></li>
                <li class="urgency-moderate"><strong>Dark DF: 3 ' . t('generation') . '</strong><span class="rec-detail">' . t('static_dark_df_desc') . '</span></li>
                <li><strong>' . t('static_general_limit') . '</strong></li>
            </ul>
        </div>
    </div>';
}

$result = null;
$familyResult = null;
$action = $_REQUEST['action'] ?? '';
$activeTab = 'birddb';

if ($action === 'calculate') {
    try {
        // DEBUG: 入力パラメータ出力
        error_log('DEBUG calculate: ' . json_encode($_REQUEST));

        $calculator = new GeneticsCalculator();
        $result = $calculator->calculateOffspring($_REQUEST);

        // DEBUG: 結果確認
        error_log('DEBUG result: ' . json_encode(array_keys($result ?? [])));

        $activeTab = 'feasibility';
    } catch (Throwable $e) {
        // DEBUG: 例外キャッチ
        echo '<pre style="background:red;color:white;padding:1rem;">PHP Error: ' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        error_log('DEBUG exception: ' . $e->getMessage());
    }
} elseif ($action === 'calculate_csv') {
    // v7.3.15: 全件CSV出力（フィルタリングなし）
    $calculator = new GeneticsCalculator();
    $_REQUEST['no_filter'] = true;
    $result = $calculator->calculateOffspring($_REQUEST);

    // CSVヘッダー出力
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="breeding_results_full.csv"');

    $output = fopen('php://output', 'w');
    // BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // ヘッダー行
    fputcsv($output, ['Sex', 'Phenotype (JA)', 'Phenotype (EN)', 'Probability (%)', 'Eye Color', 'Color Key']);

    // データ行
    $offspring = $result['phenotype'] ?? [];
    foreach ($offspring as $o) {
        $prob = ($o['probability'] ?? 0) * 100;
        fputcsv($output, [
            $o['sex'] === 'male' ? 'Male' : 'Female',
            $o['phenotype_ja'] ?? $o['phenotype'] ?? '',
            $o['phenotype_en'] ?? '',
            number_format($prob, 4),
            $o['eyeColor'] ?? '',
            $o['colorKey'] ?? ''
        ]);
    }
    fclose($output);
    exit;
} elseif ($action === 'pathfind') {
    $pathfinder = new PathFinder();
    $result = $pathfinder->findPath($_REQUEST['target'] ?? '');
    $activeTab = 'pathfinder';
} elseif ($action === 'estimate') {
    $estimator = new GenotypeEstimator();
    $result = $estimator->estimate(
        $_REQUEST['sex'] ?? 'male',
        $_REQUEST['est_baseColor'] ?? 'green',
        $_REQUEST['est_eyeColor'] ?? 'black',
        $_REQUEST['est_darkness'] ?? 'none',
        $lang === 'ja'
    );
    $activeTab = 'estimator';
} elseif ($action === 'family_infer') {
    $familyEstimator = new FamilyEstimatorV3();
    $rawJson = $_REQUEST['familyData'] ?? '{}';
    $familyData = json_decode($rawJson, true);
    $targetPosition = $_REQUEST['targetPosition'] ?? '';
    // JSON検証: デコード失敗または不正な構造をチェック
    if (json_last_error() !== JSON_ERROR_NONE) {
        $familyResult = ['error' => 'Invalid JSON data'];
    } elseif (!is_array($familyData)) {
        $familyResult = ['error' => 'Invalid data structure'];
    } elseif ($familyData && $targetPosition) {
        $familyResult = $familyEstimator->estimate($familyData, $targetPosition);
    } else {
        $familyResult = ['error' => t('select_target')];
    }
    $activeTab = 'family';
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <!-- DEBUG: JSエラーキャッチ -->
    <script>
    window.onerror = function(msg, url, line, col, error) {
        alert('JS Error: ' + msg + '\nLine: ' + line + '\nFile: ' + url);
        console.error('JS Error:', msg, url, line, col, error);
        return false;
    };
    window.addEventListener('unhandledrejection', function(e) {
        alert('Promise Error: ' + e.reason);
        console.error('Promise Error:', e.reason);
    });
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>🦜 Gene-Forge v7.0</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Noto+Sans+JP:wght@300;400;500;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=674">
    <style>
        /* ロービジ対策：明るい文字色 */
        .guide-item p{color:#b8c0cc !important}
        .guide-item strong{color:#fff !important}
        .section-label{color:#b8c0cc !important}
        .slot-label{color:#99aabb !important}
        .bird-details{color:#aabbcc !important}
        .empty-hint{color:#99aabb !important}
        .target-display{color:#b8c0cc !important}
        .connector-row{color:#8899aa !important}
        
        /* グローバルモード切替（アプリ全体のヘッダー直下） */
        .global-mode-switch{display:flex;align-items:center;justify-content:center;gap:1rem;padding:1rem 1.5rem;background:#1a1f26;border-radius:10px;margin:0 auto 1.5rem;max-width:400px;border:2px solid #444}
        .global-mode-switch .mode-label{font-size:1.1rem;color:#fff;font-weight:bold}
        .global-mode-switch .mode-btn{padding:.7rem 1.5rem;border-radius:8px;font-size:1rem;font-weight:bold;cursor:pointer;border:2px solid #666;transition:all .2s;background:#2d333b;color:#fff}
        .global-mode-switch .mode-btn:hover{background:#3d444d;border-color:#888}
        .global-mode-switch .mode-btn.active{background:linear-gradient(135deg,#00ffcc,#00d4aa);color:#000;border-color:#00ffcc;box-shadow:0 0 12px rgba(0,255,204,.5);font-weight:900}
        
        /* モード切替バー（最上段） - 明るく見やすく */
        .mode-switch-bar{display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;background:#1a1f26;border-radius:10px;margin-bottom:1.5rem;border:2px solid #444}
        .mode-switch-bar .mode-label{font-size:1.1rem;color:#fff;font-weight:bold}
        .mode-switch-bar .mode-btn{padding:.7rem 1.5rem;border-radius:8px;font-size:1rem;font-weight:bold;cursor:pointer;border:2px solid #666;transition:all .2s;background:#2d333b;color:#fff}
        .mode-switch-bar .mode-btn:hover{background:#3d444d;border-color:#888}
        .mode-switch-bar .mode-btn.active{background:linear-gradient(135deg,#00ffcc,#00d4aa);color:#000;border-color:#00ffcc;box-shadow:0 0 12px rgba(0,255,204,.5);font-weight:900}
        
        .family-map-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem}
        .family-map-header h2{margin:0}
        .family-map-actions-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.4rem;max-width:220px}
        .family-section{padding:1rem;background:var(--bg-tertiary);border-radius:8px;margin-bottom:1rem}
        .family-section.paternal{border-top:3px solid #4a90d9}
        .family-section.maternal{border-bottom:3px solid #d94a8c}
        .family-section.offspring{border-left:3px solid var(--accent-turquoise)}
        .section-label{font-weight:bold;font-size:.85rem;margin-bottom:.5rem}
        .generation{display:flex;flex-wrap:wrap;justify-content:center;gap:.5rem;margin:.5rem 0}
        .connector-row{text-align:center;font-size:.9rem}
        .bird-slot{background:var(--bg-card);border:1px solid var(--border-color);border-radius:6px;padding:.5rem;min-width:100px;max-width:140px;cursor:pointer;transition:all .2s;position:relative}
        .bird-slot:hover{border-color:var(--accent-turquoise)}
        .bird-slot.is-target{border-color:gold;box-shadow:0 0 8px rgba(255,215,0,.5)}
        .bird-slot.empty{border-style:dashed;opacity:.7}
        .slot-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem}
        .slot-label{font-size:.7rem}
        .btn-clear{background:none;border:none;color:#e74c3c;cursor:pointer;font-size:.8rem;padding:0}
        .slot-empty-content{text-align:center;padding:.5rem 0}
        .slot-empty-content select{font-size:.75rem;padding:.2rem}
        .empty-hint{display:block;font-size:.65rem;margin-top:.25rem}
        .slot-filled-content{text-align:center}
        .bird-sex{font-size:1.2rem;color:#fff}
        .bird-pheno{font-size:.8rem;font-weight:bold;color:#e8e8e8}
        .bird-details{font-size:.65rem}
        .bird-name{font-size:.7rem;color:var(--accent-turquoise);margin-top:.2rem}
        .slot-actions{display:flex;justify-content:center;gap:.25rem;margin-top:.3rem}
        .btn-mini{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:4px;padding:.15rem .3rem;font-size:.7rem;cursor:pointer;color:#ccc}
        .btn-mini:hover{background:var(--bg-tertiary)}
        .offspring-grid{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center}
        .family-map-footer{margin-top:1rem;text-align:center}
        .target-display{margin-bottom:.5rem}
        .target-display strong{color:gold}
        .result-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.75rem}
        .locus-card{background:var(--bg-tertiary);border-radius:8px;padding:.75rem}
        .locus-card.confirmed{border-left:3px solid var(--success)}
        .locus-card.uncertain{border-left:3px solid var(--warning)}
        .locus-name{font-weight:bold;color:var(--accent-blue);font-size:.85rem}
        .candidate-item{display:flex;justify-content:space-between;font-size:.8rem;padding:.2rem 0;border-bottom:1px solid var(--border-color);color:#e0e0e0}
        .candidate-item:last-child{border-bottom:none}
        .candidate-geno{font-family:'JetBrains Mono',monospace;color:#fff}
        .candidate-prob{color:#aaa}
        .candidate-prob.high{color:var(--success);font-weight:bold}
        .candidate-item.low-prob{opacity:.6;font-size:.75rem}
        .test-item{background:var(--bg-tertiary);border-radius:6px;padding:.75rem;margin-bottom:.5rem;color:#e0e0e0}
        .test-locus{font-weight:bold;color:var(--accent-turquoise)}
        .modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.8);z-index:1000;justify-content:center;align-items:center}
        .modal.active{display:flex}
        .modal-content{background:var(--bg-card);border-radius:12px;padding:1.5rem;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;color:#e0e0e0}
        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
        .modal-close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#aaa}
        .modal-content .form-group{margin-bottom:1rem}
        .modal-content .form-group label{display:block;margin-bottom:.3rem;color:#e0e0e0;font-size:.9rem}
        .modal-content .form-group select,.modal-content .form-group input{width:100%;padding:.6rem;background:#1a2535;border:1px solid #3a4555;border-radius:6px;color:#fff;font-size:1rem}
        .modal-content .form-group select:focus,.modal-content .form-group input:focus{border-color:var(--accent-turquoise);outline:none}
        .modal-content .btn-group{display:flex;gap:.5rem;margin-top:1.5rem}
        .modal-content .btn{padding:.6rem 1.2rem;border-radius:6px;cursor:pointer;font-size:.9rem}
        .modal-content .btn-primary{background:var(--accent-turquoise);color:#000;border:none}
        .modal-content .btn-outline{background:transparent;color:#aaa;border:1px solid #555}
        .saved-map-item{display:flex;justify-content:space-between;align-items:center;padding:.5rem;border-bottom:1px solid var(--border-color);color:#e0e0e0}
        
        /* 親型配合結果推論用 */
        .parent-block{margin-bottom:1.5rem;padding:1rem;background:rgba(255,255,255,.03);border-radius:8px}
        .parent-block h3{margin:0 0 .75rem 0;color:#fff}
        .input-mode-toggle{display:flex;gap:1.5rem;margin-bottom:1rem}
        .input-mode-toggle label{display:flex;align-items:center;gap:.4rem;color:#ccc;cursor:pointer;font-size:.9rem}
        .input-mode-toggle input[type="radio"]{accent-color:var(--accent-turquoise)}
        .input-panel{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem}
        .input-panel .form-group{margin:0}
        .input-panel .form-group label{display:block;font-size:.8rem;color:#aaa;margin-bottom:.25rem}
        .input-panel .form-group select{width:100%;padding:.5rem;background:#1a2535;border:1px solid #3a4555;border-radius:4px;color:#fff;font-size:.85rem}
        
        /* フッター */
        .footer-credits{margin-top:2rem;padding:1rem;text-align:center}
        .footer-credits summary{cursor:pointer;color:#888;font-size:.9rem}
        .footer-credits summary:hover{color:#4ecdc4}
        .credits-content{margin-top:1rem;padding:1rem;background:var(--bg-card);border-radius:8px;text-align:left}
        .credits-content p{margin:.3rem 0;color:#ccc;font-size:.85rem}
        
        /* 健康評価 */
        .health-recommendations ul{list-style:none;padding:0}
        .health-recommendations li{padding:.75rem;margin:.5rem 0;background:var(--bg-tertiary);border-radius:6px;border-left:3px solid #666}
        .health-recommendations li.urgency-critical{border-left-color:#ef4444}
        .health-recommendations li.urgency-high{border-left-color:#f59e0b}
        .health-recommendations li.urgency-moderate{border-left-color:#eab308}
        .health-recommendations strong{color:#fff}
        .rec-detail{display:block;font-size:.85rem;color:#888;margin-top:.25rem}
        
        /* 個体管理 */
        .header-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin:1rem 0}
        .db-stats{margin:1rem 0;padding:.75rem;background:var(--bg-tertiary);border-radius:6px}
        .search-bar{display:flex;gap:.5rem;flex-wrap:wrap;margin:1rem 0}
        .search-bar input,.search-bar select{padding:.5rem;background:#1a2535;border:1px solid #3a4555;border-radius:4px;color:#fff}
        .bird-list{display:grid;gap:.5rem}
        .dropdown{position:relative;display:inline-block}
        .dropdown-menu{display:none;position:absolute;background:var(--bg-card);border:1px solid var(--border-color);border-radius:4px;z-index:100}
        .dropdown:hover .dropdown-menu{display:block}
        .dropdown-menu button{display:block;width:100%;padding:.5rem 1rem;background:none;border:none;color:#fff;text-align:left;cursor:pointer}
        .dropdown-menu button:hover{background:var(--bg-tertiary)}
        .section-title{color:#4ecdc4;margin:1rem 0 .5rem;font-size:.9rem}
        .genotype-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.5rem}
        
        /* 言語切り替え - 左上固定 */
        .lang-switch{position:absolute;top:1rem;left:1rem;display:flex;gap:1px;background:var(--bg-surface);border-radius:8px;padding:3px;border:1px solid var(--border-subtle);z-index:10}
        .lang-switch a{color:#888;text-decoration:none;padding:.3rem .4rem;border-radius:4px;font-size:.7rem}
        .lang-switch a:hover{color:#4ecdc4;background:rgba(78,205,196,.1)}
        .lang-switch a.active{color:#000;background:linear-gradient(135deg,#00e5ff,#00ffc8)}
        header{position:relative;padding-top:4.5rem}
        
        /* カスタム確認ダイアログ */
        .custom-confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;justify-content:center;align-items:center;z-index:10000}
        .custom-confirm-modal{background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:12px;padding:1.5rem;max-width:90%;width:320px;box-shadow:0 8px 32px rgba(0,0,0,.5)}
        .custom-confirm-message{color:var(--text-primary);margin-bottom:1.5rem;line-height:1.5;white-space:pre-line}
        .custom-confirm-buttons{display:flex;gap:.75rem;justify-content:flex-end}
        .custom-confirm-buttons button{padding:.6rem 1.2rem;border-radius:8px;font-size:.9rem;cursor:pointer;border:none;transition:all .2s}
        .btn-confirm-ok{background:linear-gradient(135deg,#00e5ff,#00ffc8);color:#000}
        .btn-confirm-ok:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,229,255,.4)}
        .btn-confirm-cancel{background:var(--bg-elevated);color:var(--text-secondary);border:1px solid var(--border-subtle)}
        .btn-confirm-cancel:hover{background:var(--bg-surface)}
        .custom-prompt-input{width:100%;padding:.75rem;border-radius:8px;border:1px solid var(--border-subtle);background:var(--bg-elevated);color:var(--text-primary);font-size:1rem;margin-bottom:1rem}
        .custom-prompt-input:focus{outline:none;border-color:#00e5ff}
        .custom-select-modal{width:360px}
        .custom-select-options{max-height:300px;overflow-y:auto;margin-bottom:1rem}
        .custom-select-option{padding:.75rem 1rem;border-radius:8px;cursor:pointer;transition:all .2s;color:var(--text-primary)}
        .custom-select-option:hover{background:rgba(0,229,255,.15);color:#00e5ff}
        .version-tag{position:absolute;top:1rem;right:1rem;font-size:.75rem;color:#888;font-family:'JetBrains Mono',monospace}
    </style>
    <script>
    // フォーム送信制御フラグ
    window._allowSubmit = false;
    
    /**
     * グローバル確認ダイアログ（ブラウザのconfirm()を置き換え）
     * 「ダイアログを表示しない」オプションを排除
     * v6.7.4: ボタンラベルをT辞書から取得
     */
    function customConfirm(message) {
        return new Promise((resolve) => {
            const existing = document.getElementById('customConfirmOverlay');
            if (existing) existing.remove();
            
            const overlay = document.createElement('div');
            overlay.id = 'customConfirmOverlay';
            overlay.className = 'custom-confirm-overlay';
            overlay.innerHTML = `
                <div class="custom-confirm-modal">
                    <div class="custom-confirm-message">${message}</div>
                    <div class="custom-confirm-buttons">
                        <button type="button" class="btn-confirm-cancel">${T.cancel}</button>
                        <button type="button" class="btn-confirm-ok">${T.ok}</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            const okBtn = overlay.querySelector('.btn-confirm-ok');
            const cancelBtn = overlay.querySelector('.btn-confirm-cancel');
            
            const cleanup = (result) => {
                overlay.remove();
                resolve(result);
            };
            
            okBtn.addEventListener('click', () => cleanup(true));
            cancelBtn.addEventListener('click', () => cleanup(false));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) cleanup(false);
            });
            
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escHandler);
                    cleanup(false);
                }
            };
            document.addEventListener('keydown', escHandler);
            
            okBtn.focus();
        });
    }
    
    /**
     * グローバル入力ダイアログ（ブラウザのprompt()を置き換え）
     * v6.7.4: ボタンラベルをT辞書から取得
     */
    function customPrompt(message, defaultValue = '') {
        return new Promise((resolve) => {
            const existing = document.getElementById('customPromptOverlay');
            if (existing) existing.remove();
            
            const overlay = document.createElement('div');
            overlay.id = 'customPromptOverlay';
            overlay.className = 'custom-confirm-overlay';
            overlay.innerHTML = `
                <div class="custom-confirm-modal custom-prompt-modal">
                    <div class="custom-confirm-message">${message}</div>
                    <input type="text" class="custom-prompt-input" value="${defaultValue.replace(/"/g, '&quot;')}">
                    <div class="custom-confirm-buttons">
                        <button type="button" class="btn-confirm-cancel">${T.cancel}</button>
                        <button type="button" class="btn-confirm-ok">${T.ok}</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            const input = overlay.querySelector('.custom-prompt-input');
            const okBtn = overlay.querySelector('.btn-confirm-ok');
            const cancelBtn = overlay.querySelector('.btn-confirm-cancel');
            
            const cleanup = (result) => {
                overlay.remove();
                resolve(result);
            };
            
            okBtn.addEventListener('click', () => cleanup(input.value));
            cancelBtn.addEventListener('click', () => cleanup(null));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) cleanup(null);
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') cleanup(input.value);
                if (e.key === 'Escape') cleanup(null);
            });
            
            input.focus();
            input.select();
        });
    }
    
    /**
     * グローバル選択ダイアログ（リストから選択）
     * v6.7.4: ボタンラベルをT辞書から取得
     */
    function customSelect(message, options) {
        return new Promise((resolve) => {
            const existing = document.getElementById('customSelectOverlay');
            if (existing) existing.remove();
            
            const overlay = document.createElement('div');
            overlay.id = 'customSelectOverlay';
            overlay.className = 'custom-confirm-overlay';
            
            const optionsHtml = options.map((opt, i) => 
                `<div class="custom-select-option" data-index="${i}">${i + 1}. ${opt.label || opt}</div>`
            ).join('');
            
            overlay.innerHTML = `
                <div class="custom-confirm-modal custom-select-modal">
                    <div class="custom-confirm-message">${message}</div>
                    <div class="custom-select-options">${optionsHtml}</div>
                    <div class="custom-confirm-buttons">
                        <button type="button" class="btn-confirm-cancel">${T.cancel}</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            const cancelBtn = overlay.querySelector('.btn-confirm-cancel');
            const optionEls = overlay.querySelectorAll('.custom-select-option');
            
            const cleanup = (result) => {
                overlay.remove();
                resolve(result);
            };
            
            optionEls.forEach(el => {
                el.addEventListener('click', () => {
                    cleanup(parseInt(el.dataset.index));
                });
            });
            
            cancelBtn.addEventListener('click', () => cleanup(null));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) cleanup(null);
            });
            
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escHandler);
                    cleanup(null);
                }
            };
            document.addEventListener('keydown', escHandler);
        });
    }
    </script>
    <script>
    // v6.8修正: T辞書を先に定義（customConfirm等で使用）
    const LANG = '<?= $lang ?>';
    const T = <?= json_encode(getLangDict()) ?>;
    // v7.0: guardian.js等からwindow.Tでアクセス可能にする
    window.T = T;

    // XSS対策: HTMLエスケープ関数
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
    }

    // SSOT: genetics.php から注入
    const COLOR_LABELS = <?= json_encode(AgapornisLoci::labels($lang === 'ja')) ?>;
    const COLOR_MASTER = <?= json_encode(AgapornisLoci::COLOR_DEFINITIONS) ?>;
    const LABEL_TO_KEY = <?= json_encode(AgapornisLoci::labelToKey($lang === 'ja')) ?>;
const COLOR_GROUPED = <?= json_encode(AgapornisLoci::groupedKeys()) ?>;
const CATEGORY_LABELS = <?= json_encode(AgapornisLoci::categoryLabels($lang === 'ja')) ?>;
const LOCI_MASTER = <?= json_encode(AgapornisLoci::LOCI) ?>;
    // v7.3.11: SSOT - 色キー部品翻訳（genetics.phpから注入）
    const COLOR_PART_LABELS = <?= json_encode(AgapornisLoci::COLOR_PART_LABELS) ?>;

    /**
     * 任意のカラーキーをローカライズされたラベルに変換
     * COLOR_LABELSに存在しないキーも動的に変換する
     * v7.3.12: SSOT - COLOR_MASTER優先、キャッシュ対策強化
     */
    function keyToLabel(key) {
        if (!key) return '';
        // v7.3.14: 6言語対応 - COLOR_MASTERはja/enのみなので、非日本語はenをフォールバック
        const langKey = (LANG === 'ja') ? 'ja' : 'en';

        // 1. COLOR_MASTERに存在すれば正しい言語で取得（最も信頼性が高い）
        if (typeof COLOR_MASTER !== 'undefined' && COLOR_MASTER[key]) {
            return COLOR_MASTER[key][langKey] || COLOR_MASTER[key].en || COLOR_MASTER[key].ja || key;
        }

        // 2. COLOR_LABELSに存在すればそれを返す（キャッシュ対策: 言語が一致するか確認）
        if (typeof COLOR_LABELS !== 'undefined' && COLOR_LABELS[key]) {
            return COLOR_LABELS[key];
        }

        // 3. キーを分解してパーツごとに変換（SSOT: COLOR_PART_LABELSを使用）
        if (typeof COLOR_PART_LABELS === 'undefined') {
            return key; // フォールバック
        }
        const parts = key.split('_');
        const result = parts.map(part => {
            const p = part.toLowerCase();
            if (COLOR_PART_LABELS[p]) return COLOR_PART_LABELS[p][langKey] || COLOR_PART_LABELS[p].en || p;
            return part.charAt(0).toUpperCase() + part.slice(1);
        });

        return (LANG === 'ja') ? result.join('') : result.join(' ');
    }
const GENOTYPE_OPTIONS = <?= json_encode(AgapornisLoci::GENOTYPE_OPTIONS) ?>;
const UI_GENOTYPE_LOCI = <?= json_encode(AgapornisLoci::UI_GENOTYPE_LOCI) ?>;
// v7.0: 連鎖遺伝用定数
const LINKAGE_GROUPS = <?= json_encode(AgapornisLoci::LINKAGE_GROUPS) ?>;
const RECOMBINATION_RATES = <?= json_encode(AgapornisLoci::RECOMBINATION_RATES) ?>;
const INDEPENDENT_LOCI = <?= json_encode(AgapornisLoci::INDEPENDENT_LOCI) ?>;
</script>
</head>

<body>

    <div class="bg-grid"></div>
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>
    <div id="app-container">
        <header>
<div class="lang-switch">
    <a href="?lang=ja" class="<?= $lang === 'ja' ? 'active' : '' ?>">JA</a>
    <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
    <a href="?lang=de" class="<?= $lang === 'de' ? 'active' : '' ?>">DE</a>
    <a href="?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>
    <a href="?lang=it" class="<?= $lang === 'it' ? 'active' : '' ?>">IT</a>
    <a href="?lang=es" class="<?= $lang === 'es' ? 'active' : '' ?>">ES</a>
    <a href="?lang=pt" class="<?= $lang === 'pt' ? 'active' : '' ?>">PT</a>
    <a href="?lang=nl" class="<?= $lang === 'nl' ? 'active' : '' ?>">NL</a>
    <a href="?lang=id" class="<?= $lang === 'id' ? 'active' : '' ?>">ID</a>
    <a href="?lang=th" class="<?= $lang === 'th' ? 'active' : '' ?>">TH</a>
    <a href="?lang=tl" class="<?= $lang === 'tl' ? 'active' : '' ?>">TL</a>
</div>
                        <span class="version-tag"><a href="https://github.com/kanarazu-project/gene-forge" target="_blank" style="color:#4fc3f7;font-size:.75rem;text-decoration:none;">Github</a><br><a href="https://kanarazu-project.com/gene-forge/Rosy-faced-Lovebird/readme.php?lang=<?= $lang ?>" target="_blank" style="color:#666;font-size:.65rem;text-decoration:none;">README</a></span>
            <h1 class="logo">🦜 GENE-FORGE</h1>
<p class="app-subtitle"><?= t('subtitle') ?></p>
<span class="version-badge"><?= t('coming_soon') ?> | ALBS<?= t('compliant') ?></span>
        </header>
        <!-- アプリ全体のモード切替（アカウント相当） -->
        <div id="globalModeSwitch" class="global-mode-switch">
            <span class="mode-label"><?= t('mode') ?>:</span>
            <button id="modeBtnDemo" class="mode-btn" onclick="BirdDB.setMode('demo')">🎮 <?= t('demo_mode') ?></button>
            <button id="modeBtnUser" class="mode-btn active" onclick="BirdDB.setMode('user')">👤 <?= t('user_mode') ?></button>
        </div>
        
        <div class="card guide-card">
            <div class="guide-grid">
                <div class="guide-item clickable" onclick="showTab('birddb')"><strong>📁 <?= t('tab0') ?></strong><p><?= t('tab0_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('health')"><strong>🛡️ <?= t('tab6') ?></strong><p><?= t('tab6_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('planner')"><strong>🎯 <?= t('tab5') ?></strong><p><?= t('tab5_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('pathfinder')"><strong>🧭 <?= t('tab1') ?></strong><p><?= t('tab1_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('feasibility')"><strong>🧬 <?= t('tab2') ?></strong><p><?= t('tab2_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('estimator')"><strong>🔬 <?= t('tab3') ?></strong><p><?= t('tab3_func') ?></p></div>
                <div class="guide-item clickable" onclick="showTab('family')"><strong>👨‍👩‍👧‍👦 <?= t('tab7') ?></strong><p><?= t('tab7_func') ?></p></div>
            </div>
        </div>
        <main>
            <section id="family" class="tab-content<?= $activeTab === 'family' ? ' active' : '' ?>">
                <div class="card"><div id="familyMapContainer"></div></div>
                <div id="inbreedingWarning"></div>
                <div id="family-result">
                <?php if ($familyResult && !isset($familyResult['error'])): ?>
                <div class="output-panel" style="margin-top:1rem;">
                    <div class="output-header"><span class="output-title">🧬 <?= t('result_title') ?></span></div>
                    <div style="text-align:center;padding:1rem;"><div><?= t('overall_confidence') ?></div><div style="font-size:2rem;font-family:Orbitron;color:var(--accent-turquoise);"><?= $familyResult['overallConfidence'] ?>%</div></div>
                    <div class="result-grid">
                        <?php foreach ($familyResult['loci'] as $locus): ?>
                        <div class="locus-card <?= $locus['isConfirmed']?'confirmed':'uncertain' ?>">
                            <div class="locus-name"><?= htmlspecialchars($locus['locusName']) ?></div>
                        <?php foreach ($locus['candidates'] as $c): ?>
                            <div class="candidate-item <?= $c['probability']<5?'low-prob':'' ?>">
                                <span class="candidate-geno"><?= htmlspecialchars($c['genotype']) ?></span>
                                <span class="candidate-prob <?= $c['probability']>=90?'high':'' ?>"><?= $c['probability'] ?>%<?= !empty($c['expectedRatio']) ? ' ('.$c['expectedRatio'].')' : '' ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($familyResult['testBreedings'])): ?>
                    <h4 style="margin-top:1.5rem;color:#fff;">🧪 <?= t('test_breeding_proposal') ?></h4>
                    <?php foreach ($familyResult['testBreedings'] as $tb): ?>
                    <div class="test-item">
                        <div class="test-locus"><?= htmlspecialchars(t($tb['locus']) ?: ucfirst($tb['locus'])) ?></div>
                        <div style="margin:.5rem 0;">
                            <strong style="color:#4ecdc4;"><?= t('recommendation') ?>:</strong>
                            <span style="color:#ddd;"><?= htmlspecialchars($tb['recommendation'] ?? '') ?></span>
                        </div>
                        <div style="margin-top:.75rem;padding:.5rem;background:rgba(0,0,0,.2);border-radius:4px;">
                            <div style="font-size:.85rem;color:#aaa;margin-bottom:.3rem;"><?= t('determination_by_offspring') ?>:</div>
                            <div style="font-size:.85rem;color:#ddd;padding:.2rem 0;"><?= htmlspecialchars($tb['expectedResult'] ?? '') ?></div>
                            <?php if (!empty($tb['minOffspring'])): ?>
                            <div style="font-size:.8rem;color:#888;margin-top:.3rem;">
                                (<?= $lang === 'ja' ? '推奨子数: ' . $tb['minOffspring'] . '羽以上' : 'Recommended offspring: ' . $tb['minOffspring'] . '+' ?>)
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php elseif ($familyResult && isset($familyResult['error'])): ?>
                <div class="output-panel"><div class="warning-box"><?= htmlspecialchars($familyResult['error']) ?></div></div>
                <?php endif; ?>
                </div>
            </section>

            <!-- Tab 0: 個体DB (v5.15.1完全復元) -->
            <section id="birddb" class="tab-content<?= $activeTab === 'birddb' ? ' active' : '' ?>">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🗂️</div>
                        <div>
                            <h2 class="card-title"><?= t('bird_management') ?></h2>
                            <p class="card-subtitle"><?= t('bird_management_desc') ?></p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn btn-small btn-primary" onclick="openBirdForm()">➕ <?= t('add_bird') ?></button>
                    </div>
                    <div class="import-export-section">
                        <div class="import-export-group">
                            <span class="group-label">📤 <?= t('export') ?></span>
                            <div class="btn-row">
                                <button type="button" class="btn btn-small btn-outline" onclick="exportBirdDB()">📁 JSON</button>
                                <button type="button" class="btn btn-small btn-outline" onclick="exportBirdsCSV()">📊 CSV</button>
                            </div>
                        </div>
                        <div class="import-export-group">
                            <span class="group-label">📥 <?= t('import') ?></span>
                            <div class="btn-row">
                                <button type="button" class="btn btn-small btn-outline" onclick="document.getElementById('importFile').click()">📂 <?= t('load') ?></button>
                                <input type="file" id="importFile" accept=".json,.csv" style="display:none" onchange="importBirdDB(this)">
                            </div>
                            <div class="template-download">
                                <span class="template-label"><?= t('template') ?? 'Template' ?>:</span>
                                <button type="button" class="btn btn-tiny btn-ghost" onclick="downloadImportTemplate('json')">JSON</button>
                                <button type="button" class="btn btn-tiny btn-ghost" onclick="downloadImportTemplate('csv')">CSV</button>
                            </div>
                        </div>
                    </div>
                    <div class="db-stats" id="dbStats"></div>
                    <div class="search-bar">
                        <input type="text" id="birdSearch" placeholder="<?= t('search_placeholder') ?>" oninput="filterBirds()">
                        <select id="birdFilterSex" onchange="filterBirds()">
                            <option value=""><?= t('all_sex') ?></option>
                            <option value="male">♂ <?= t('male') ?></option>
                            <option value="female">♀ <?= t('female') ?></option>
                        </select>
                        <select id="birdFilterLineage" onchange="filterBirds()">
                            <option value=""><?= t('all_lineage') ?></option>
                        </select>
                    </div>
                    <div class="bird-list" id="birdList"></div>
                </div>

                <!-- 個体モーダル -->
                <div id="birdModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="birdModalTitle"><?= t('add_bird') ?></h3>
                            <button type="button" class="modal-close" onclick="closeBirdForm()">×</button>
                        </div>
                        <form id="birdForm" onsubmit="saveBird(event)">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label"><?= t('name') ?> *</label>
                                    <input type="text" id="birdName" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('code') ?></label>
                                    <input type="text" id="birdCode" placeholder="<?= t('auto_generate') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('sex') ?> *</label>
                                    <select id="birdSex" required onchange="updateGenotypeOptions()">
                                        <option value="male">♂ <?= t('male') ?></option>
                                        <option value="female">♀ <?= t('female') ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('birth_date') ?></label>
                                    <input type="date" id="birdBirthDate">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('lineage') ?></label>
                                    <input type="text" id="birdLineage" list="lineageList">
                                    <datalist id="lineageList"></datalist>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">🛡️ <?= t('inbreeding_gen') ?></label>
                                    <input type="number" id="birdInbreedingGen" min="0" max="10" value="0">
                                </div>
                            </div>
                            
                            <h4 class="section-title">👁️ <?= t('observed_info') ?></h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label"><?= t('base_color_observed') ?></label>
                                    <?= renderPhenotypeSelect('bird', 'baseColor', $lang === 'ja') ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('eye_color') ?></label>
                                    <?= renderPhenotypeSelect('bird', 'eyeColor', $lang === 'ja') ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('dark_factor') ?></label>
                                    <?= renderPhenotypeSelect('bird', 'darkness', $lang === 'ja') ?>
                                </div>
                            </div>
                            
                            <h4 class="section-title"><?= t('parent_info') ?></h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label"><?= t('sire') ?></label>
                                    <select id="birdSire"><option value=""><?= t('unknown_or_external') ?></option></select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= t('dam') ?></label>
                                    <select id="birdDam"><option value=""><?= t('unknown_or_external') ?></option></select>
                                </div>
                            </div>

                            <!-- v7.3.13: ユーザーモード用拡張血統編集 -->
                            <details id="extendedPedigreeSection" class="pedigree-extended" style="margin-top:1rem;">
                                <summary style="cursor:pointer;color:var(--accent-turquoise);">
                                    📋 <?= $lang === 'ja' ? '詳細血統情報を編集（祖父母・曾祖父母）' : 'Edit Extended Pedigree (Grandparents/Great-grandparents)' ?>
                                </summary>
                                <div style="margin-top:0.5rem;padding:0.5rem;background:rgba(0,0,0,0.2);border-radius:6px;">
                                    <p style="font-size:0.8rem;color:#aaa;margin-bottom:0.5rem;">
                                        <?= $lang === 'ja' ? '個体IDを直接入力できます。空欄は不明として扱われます。' : 'Enter bird IDs directly. Empty fields are treated as unknown.' ?>
                                    </p>

                                    <!-- 祖父母 -->
                                    <h5 style="font-size:0.85rem;color:#ccc;margin:0.8rem 0 0.4rem;"><?= t('grandparents') ?></h5>
                                    <div class="form-grid" style="grid-template-columns:repeat(4,1fr);gap:0.4rem;">
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.75rem;"><?= t('paternal_gf') ?></label>
                                            <input type="text" id="pedigree_sire_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.75rem;"><?= t('paternal_gm') ?></label>
                                            <input type="text" id="pedigree_sire_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.75rem;"><?= t('maternal_gf') ?></label>
                                            <input type="text" id="pedigree_dam_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.75rem;"><?= t('maternal_gm') ?></label>
                                            <input type="text" id="pedigree_dam_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                    </div>

                                    <!-- 曾祖父母 -->
                                    <h5 style="font-size:0.85rem;color:#ccc;margin:0.8rem 0 0.4rem;"><?= t('great_grandparents') ?></h5>
                                    <div class="form-grid" style="grid-template-columns:repeat(4,1fr);gap:0.4rem;">
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gf_father') ?> (<?= t('sire') ?>)</label>
                                            <input type="text" id="pedigree_sire_sire_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gf_mother') ?> (<?= t('sire') ?>)</label>
                                            <input type="text" id="pedigree_sire_sire_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gm_father') ?> (<?= t('sire') ?>)</label>
                                            <input type="text" id="pedigree_sire_dam_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gm_mother') ?> (<?= t('sire') ?>)</label>
                                            <input type="text" id="pedigree_sire_dam_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                    </div>
                                    <div class="form-grid" style="grid-template-columns:repeat(4,1fr);gap:0.4rem;margin-top:0.4rem;">
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gf_father') ?> (<?= t('dam') ?>)</label>
                                            <input type="text" id="pedigree_dam_sire_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gf_mother') ?> (<?= t('dam') ?>)</label>
                                            <input type="text" id="pedigree_dam_sire_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gm_father') ?> (<?= t('dam') ?>)</label>
                                            <input type="text" id="pedigree_dam_dam_sire" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom:0.3rem;">
                                            <label class="form-label" style="font-size:0.7rem;"><?= t('gm_mother') ?> (<?= t('dam') ?>)</label>
                                            <input type="text" id="pedigree_dam_dam_dam" placeholder="ID" style="padding:0.4rem;font-size:0.8rem;">
                                        </div>
                                    </div>
                                </div>
                            </details>
                            <h4 class="section-title"><?= t('genotype_info') ?></h4>
                            <div class="form-grid genotype-grid" id="genotypeFields"></div>
                            <div class="form-group">
                                <label class="form-label"><?= t('phase') ?></label>
                                <select id="birdPhase">
                                    <option value="independent"><?= t('phase_independent') ?></option>
                                    <option value="cis_pld_cin">Z^pld,cin (cis)</option>
                                    <option value="trans_pld_cin">Z^pld / Z^cin (trans)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?= t('notes') ?></label>
                                <textarea id="birdNotes" rows="2"></textarea>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary"><?= t('save') ?></button>
                                <button type="button" class="btn btn-outline" onclick="closeBirdForm()"><?= t('cancel') ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 血統書モーダル -->
                <div id="pedigreeModal" class="modal">
                    <div class="modal-content modal-large">
                        <div class="modal-header">
                            <h3><?= t('pedigree_preview') ?></h3>
                            <button type="button" class="modal-close" onclick="closePedigreeModal()">×</button>
                        </div>
                        <div class="pedigree-options">
                            <label><input type="radio" name="pedigreeGen" value="3" checked> 3<?= t('generation') ?></label>
                            <label><input type="radio" name="pedigreeGen" value="5"> 5<?= t('generation') ?></label>
                        </div>
                        <div id="pedigreePreview"></div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" onclick="printPedigree()">🖨️ <?= t('print') ?></button>
                            <button type="button" class="btn btn-secondary" onclick="downloadPedigreeHTML()">📄 HTML</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tab: 健康評価 (v5.15.1完全復元) -->
            <section id="health" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🛡️</div>
                        <div>
                            <h2 class="card-title">Health Guardian v7.0</h2>
                            <p class="card-subtitle"><?= t('health_guardian_desc') ?></p>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><?= t('sire') ?> (DB)</label>
                            <select id="healthSire"><option value=""><?= t('select_placeholder') ?></option></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('dam') ?> (DB)</label>
                            <select id="healthDam"><option value=""><?= t('select_placeholder') ?></option></select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="checkPairingHealth()"><?= t('btn_health_check') ?></button>
                    
                    <div id="healthCheckResult" style="margin-top:1.5rem"></div>
                    <div id="healthEvalResult"></div>
                </div>

                <?= renderInbreedingLimitSection() ?>
            </section>

            <!-- Tab: Target Planner (v6.7.3: 32色対応) -->
            <section id="planner" class="tab-content<?= $activeTab === 'planner' ? ' active' : '' ?>">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">🎯</div>
                        <div>
                            <h2 class="card-title"><?= t('tab5') ?></h2>
                            <p class="card-subtitle"><?= t('tab5_desc') ?></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('target_trait') ?></label>
                    <select id="plannerTarget">
                        <option value=""><?= t('select_placeholder') ?></option>
                        <?php foreach (AgapornisLoci::groupedByCategory() as $cat => $colors): ?>
                        <optgroup label="<?= htmlspecialchars(AgapornisLoci::categoryLabel($cat, $lang === 'ja')) ?>">
                            <?php foreach ($colors as $key => $def): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($lang === 'ja' ? $def['ja'] : $def['en']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary" onclick="runPlanner()">🔍 <?= t('btn_plan') ?></button>
                                    </div>
                </div>
                <div id="plannerResult" class="output-panel" style="display:none;"></div>
                <div id="plannerEmpty" class="output-panel">
                    <div class="empty-state"><div class="empty-icon">🗺️</div><p><?= t('empty_planner') ?></p></div>
                </div>
            </section>

            <section id="pathfinder" class="tab-content<?= $activeTab === 'pathfinder' ? ' active' : '' ?>">
                <?php $pathTarget = $_REQUEST['target'] ?? ''; ?>
                <form method="GET" action="#pathfinder-result" class="card"><input type="hidden" name="action" value="pathfind">
                    <h3>🧭 <?= t('target_trait') ?></h3>
                    <select name="target" required style="width:100%;padding:.5rem;margin-bottom:1rem;">
                        <option value=""><?= t('select_placeholder') ?></option>
                        <?php foreach (AgapornisLoci::groupedByCategory() as $cat => $colors): ?>
                        <optgroup label="<?= htmlspecialchars(AgapornisLoci::categoryLabel($cat, $lang === 'ja')) ?>">
                            <?php foreach ($colors as $key => $def): ?>
                            <option value="<?= $key ?>"<?= ($pathTarget ?? '') === $key ? ' selected' : '' ?>><?= htmlspecialchars($lang === 'ja' ? $def['ja'] : $def['en']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary"><?= t('btn_pathfind') ?></button>
                </form>
                <div id="pathfinder-result">
                <?php if ($action === 'pathfind' && $result && !isset($result['error'])): ?>
                <?php
                // 目標色名を取得
                $targetKey = $result['targetKey'] ?? '';
                $targetColorDef = AgapornisLoci::COLOR_DEFINITIONS[$targetKey] ?? null;
                $targetName = $targetColorDef ? ($lang === 'ja' ? $targetColorDef['ja'] : $targetColorDef['en']) : $targetKey;

                // ヘルパー関数: 色キーから翻訳済み名称を取得
                $getColorName = function($colorKey) use ($lang) {
                    $colorDef = AgapornisLoci::COLOR_DEFINITIONS[$colorKey] ?? null;
                    if ($colorDef) {
                        return $lang === 'ja' ? $colorDef['ja'] : $colorDef['en'];
                    }
                    return t($colorKey) ?: ucfirst($colorKey);
                };
                ?>
                <div class="output-panel" style="margin-top:1rem;">
                    <h4>🎯 <?= htmlspecialchars($targetName) ?></h4>

                    <?php // 警告表示 ?>
                    <?php if(!empty($result['warnings'])): ?>
                    <?php foreach($result['warnings'] as $warn): ?>
                    <?php
                    $warnKey = is_array($warn) ? ($warn['key'] ?? '') : $warn;
                    $warnParams = is_array($warn) ? ($warn['params'] ?? []) : [];
                    ?>
                    <div class="warning-box" style="margin-bottom:.5rem;"><?= htmlspecialchars(t_pf_fix(t_pf($warnKey, $warnParams), $warnParams)) ?></div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <?php // v7.2: 交配シナリオ表示 ?>
                    <?php if(!empty($result['scenario'])): ?>
                    <?php $scenario = $result['scenario']; ?>

                    <div style="background:#2a3441;color:#fff;padding:.75rem 1rem;border-radius:8px 8px 0 0;margin-top:1rem;">
                        <strong>📋 <?= t_pf('pf_breeding_scenario') ?></strong>
                        <span style="float:right;"><?= t_pf('pf_estimated_gen') ?>: <?= $scenario['totalGenerations'] ?? count($result['steps']) ?></span>
                    </div>

                    <div style="border:2px solid #3a4451;border-top:none;border-radius:0 0 8px 8px;padding:1rem;background:#1a2332;color:#e0e0e0;">

                        <?php // 必要遺伝子リスト ?>
                        <?php if(!empty($scenario['requiredGenes'])): ?>
                        <div style="margin-bottom:1rem;padding:.5rem;background:#151c28;border-radius:4px;color:rgba(255,255,255,0.6);">
                            <strong style="color:#fff;">🧬 <?= t_pf('pf_required_genes') ?>:</strong>
                            <?= htmlspecialchars(implode(', ', $scenario['requiredGenes'])) ?>
                        </div>
                        <?php endif; ?>

                        <?php // v7.3.8: 入手性警告 ?>
                        <?php if(!empty($scenario['availability'])): ?>
                        <?php $avail = $scenario['availability']; ?>

                        <?php // 入手困難な祖 ?>
                        <?php if(!empty($avail['difficult'])): ?>
                        <div style="margin-bottom:1rem;padding:.75rem;background:#1f252d;border-radius:4px;border-left:4px solid rgba(255,255,255,0.3);color:rgba(255,255,255,0.6);">
                            <strong style="color:#fff;"><?= t_pf('pf_avail_warning_difficult') ?></strong>
                            <p style="margin:.5rem 0 .25rem;font-size:.85em;color:rgba(255,255,255,0.6);"><?= t_pf('pf_avail_note_difficult') ?></p>
                            <ul style="margin:0;padding-left:1.5rem;color:#fff;">
                                <?php foreach($avail['difficult'] as $bird): ?>
                                <li><?= htmlspecialchars($getColorName($bird['colorKey'])) ?> (<?= htmlspecialchars($bird['gene']) ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php // やや希少な祖 ?>
                        <?php if(!empty($avail['normal'])): ?>
                        <div style="margin-bottom:1rem;padding:.5rem .75rem;background:#1f252d;border-radius:4px;border-left:4px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.6);">
                            <strong style="color:#fff;"><?= t_pf('pf_avail_warning_normal') ?></strong>
                            <span style="font-size:.85em;color:rgba(255,255,255,0.6);margin-left:.5rem;">
                                <?php foreach($avail['normal'] as $i => $bird): ?>
                                <?= $i > 0 ? ', ' : '' ?><?= htmlspecialchars($getColorName($bird['colorKey'])) ?>
                                <?php endforeach; ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php endif; ?>

                        <?php // フェーズごとの表示 ?>
                        <?php foreach($scenario['phases'] ?? [] as $phase): ?>
                        <div style="margin:1rem 0;padding:1rem;background:#151c28;border-radius:8px;border-left:4px solid rgba(255,255,255,0.3);color:#e0e0e0;">

                            <?php // フェーズタイトル ?>
                            <div style="font-weight:bold;font-size:1.1em;margin-bottom:.75rem;color:#fff;">
                                <?= t_pf_fix(t_pf('pf_phase_label', ['n' => $phase['phase']]), ['n' => $phase['phase']]) ?>:
                                <?= htmlspecialchars(t_pf_fix(t_pf($phase['title_key'] ?? '', $phase['title_params'] ?? []), $phase['title_params'] ?? [])) ?>
                            </div>

                            <?php // フェーズ説明 ?>
                            <?php if(!empty($phase['description_key'])): ?>
                            <p style="color:rgba(255,255,255,0.6);margin-bottom:.75rem;font-size:.9em;">
                                <?= htmlspecialchars(t_pf_fix(t_pf($phase['description_key'], $phase['description_params'] ?? []), $phase['description_params'] ?? [])) ?>
                            </p>
                            <?php endif; ?>

                            <?php // ペアリング ?>
                            <?php foreach($phase['pairings'] ?? [] as $pairing): ?>
                            <div style="background:#0f1520;padding:.75rem;border-radius:4px;margin:.5rem 0;color:#e0e0e0;">

                                <?php // 目的 ?>
                                <?php if(!empty($pairing['purpose_key'])): ?>
                                <div style="font-size:.85em;color:rgba(255,255,255,0.6);margin-bottom:.5rem;">
                                    📌 <?= htmlspecialchars(t_pf_fix(t_pf($pairing['purpose_key'], $pairing['purpose_params'] ?? []), $pairing['purpose_params'] ?? [])) ?>
                                </div>
                                <?php endif; ?>

                                <?php // ♂親 ?>
                                <?php
                                $maleKey = $pairing['male_key'] ?? 'green';
                                $maleName = $getColorName($maleKey);
                                // v7.3.17: 色名でなければpf_プレフィックス付きで翻訳キーを検索
                                if (strtolower($maleName) === strtolower($maleKey)) {
                                    $translated = t_pf('pf_' . $maleKey);
                                    if ($translated !== 'pf_' . $maleKey) $maleName = $translated;
                                }
                                $maleNote = !empty($pairing['male_note_key'])
                                    ? t_pf_fix(t_pf($pairing['male_note_key'], $pairing['male_note_params'] ?? []), $pairing['male_note_params'] ?? [])
                                    : '';
                                $maleAvail = $pairing['male_availability'] ?? null;
                                ?>
                                <div style="margin:.25rem 0;">
                                    <span style="color:#fff;">♂</span>
                                    <strong style="color:#fff;"><?= htmlspecialchars($maleName) ?></strong>
                                    <?php if($maleAvail === 'difficult'): ?>
                                    <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:.7em;padding:1px 4px;border-radius:3px;margin-left:4px;"><?= t_pf('pf_avail_badge_difficult') ?></span>
                                    <?php elseif($maleAvail === 'normal'): ?>
                                    <span style="background:rgba(255,255,255,0.15);color:#fff;font-size:.7em;padding:1px 4px;border-radius:3px;margin-left:4px;"><?= t_pf('pf_avail_badge_normal') ?></span>
                                    <?php endif; ?>
                                    <?php if($maleNote): ?>
                                    <span style="color:rgba(255,255,255,0.6);font-size:.9em;"> — <?= htmlspecialchars($maleNote) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php // ♀親 ?>
                                <?php
                                $femaleKey = $pairing['female_key'] ?? 'green';
                                $femaleName = $getColorName($femaleKey);
                                // v7.3.17: 色名でなければpf_プレフィックス付きで翻訳キーを検索
                                if (strtolower($femaleName) === strtolower($femaleKey)) {
                                    $translated = t_pf('pf_' . $femaleKey);
                                    if ($translated !== 'pf_' . $femaleKey) $femaleName = $translated;
                                }
                                $femaleNote = !empty($pairing['female_note_key'])
                                    ? t_pf_fix(t_pf($pairing['female_note_key'], $pairing['female_note_params'] ?? []), $pairing['female_note_params'] ?? [])
                                    : '';
                                $femaleAvail = $pairing['female_availability'] ?? null;
                                ?>
                                <div style="margin:.25rem 0;">
                                    <span style="color:rgba(255,255,255,0.6);">♀</span>
                                    <strong style="color:#fff;"><?= htmlspecialchars($femaleName) ?></strong>
                                    <?php if($femaleAvail === 'difficult'): ?>
                                    <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:.7em;padding:1px 4px;border-radius:3px;margin-left:4px;"><?= t_pf('pf_avail_badge_difficult') ?></span>
                                    <?php elseif($femaleAvail === 'normal'): ?>
                                    <span style="background:rgba(255,255,255,0.15);color:#fff;font-size:.7em;padding:1px 4px;border-radius:3px;margin-left:4px;"><?= t_pf('pf_avail_badge_normal') ?></span>
                                    <?php endif; ?>
                                    <?php if($femaleNote): ?>
                                    <span style="color:rgba(255,255,255,0.6);font-size:.9em;"> — <?= htmlspecialchars($femaleNote) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php // 結果 ?>
                                <?php if(!empty($pairing['result_key'])): ?>
                                <div style="margin-top:.5rem;padding-top:.5rem;border-top:1px dashed #444;color:rgba(255,255,255,0.6);">
                                    → <?= htmlspecialchars(t_pf_fix(t_pf($pairing['result_key'], $pairing['result_params'] ?? []), $pairing['result_params'] ?? [])) ?>
                                </div>
                                <?php endif; ?>

                            </div>
                            <?php endforeach; ?>

                            <?php // フェーズ最終ノート ?>
                            <?php if(!empty($phase['final_note_key'])): ?>
                            <div style="margin-top:.75rem;padding:.5rem;background:rgba(255,255,255,0.1);color:#fff;border-radius:4px;font-size:.9em;">
                                💡 <?= htmlspecialchars(t_pf_fix(t_pf($phase['final_note_key'], $phase['final_note_params'] ?? []), $phase['final_note_params'] ?? [])) ?>
                            </div>
                            <?php endif; ?>

                        </div>
                        <?php endforeach; ?>

                        <?php // サマリー ?>
                        <?php if(!empty($scenario['summary_key'])): ?>
                        <div style="margin-top:1rem;padding:1rem;background:rgba(255,255,255,0.1);color:#fff;border-radius:8px;">
                            <strong>✅ <?= t_pf('pf_summary') ?>:</strong><br>
                            <?= htmlspecialchars(t_pf_fix(t_pf($scenario['summary_key'], $scenario['summary_params'] ?? []), $scenario['summary_params'] ?? [])) ?>
                        </div>
                        <?php endif; ?>

                        <?php // v7.3.17: 近親交配回避のアドバイス ?>
                        <?php // v7.3.19: 2世代以上の計画で表示（兄弟婚防止の重要性） ?>
                        <?php if(!empty($scenario['lineage_advice_key']) && ($scenario['totalGenerations'] ?? 0) >= 2): ?>
                        <div style="margin-top:1rem;padding:1rem;background:rgba(30,136,229,0.15);border-radius:8px;border:1px solid rgba(30,136,229,0.4);color:#90caf9;">
                            <?= htmlspecialchars(t_pf($scenario['lineage_advice_key'])) ?>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endif; ?>

                    <?php // v7.3.14 連鎖遺伝の相（Phase）推論結果を表示 (i18n compliant) ?>
                    <?php if(!empty($result['linkage'])): ?>
                    <?php
                        $zLinked = $result['linkage']['Z_linked'] ?? [];
                        $auto1 = $result['linkage']['autosomal_1'] ?? [];
                        $hasZPhase = !empty($zLinked['phase']) && $zLinked['phase'] !== 'unknown' && $zLinked['phase'] !== 'hemizygous' && $zLinked['phase'] !== 'wild';
                        $hasAutoPhase = !empty($auto1['phase']) && $auto1['phase'] !== 'unknown' && $auto1['phase'] !== 'wild';
                    ?>
                    <?php if($hasZPhase || $hasAutoPhase): ?>
                    <div style="margin-top:1rem;padding:1rem;background:#1a2332;border-radius:8px;border:1px solid rgba(255,255,255,0.2);color:#e0e0e0;">
                        <h4 style="margin:0 0 .75rem 0;color:#fff;font-size:1.1em;">🔗 <?= t('phase_inference_title') ?></h4>

                        <?php if($hasZPhase): ?>
                        <div style="background:rgba(255,255,255,0.05);padding:.75rem;border-radius:6px;margin-bottom:.75rem;">
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
                                <span style="font-weight:bold;color:#fff;">🧬 <?= t('phase_z_linked') ?></span>
                            </div>
                            <div style="display:grid;grid-template-columns:auto 1fr;gap:.25rem .75rem;font-size:.95em;">
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_result') ?></span>
                                <span style="font-weight:bold;color:#fff;">
                                    <?php if($zLinked['phase'] === 'cis'): ?>
                                        <?= t('phase_z_cis') ?>
                                    <?php elseif($zLinked['phase'] === 'trans'): ?>
                                        <?= t('phase_z_trans') ?>
                                    <?php elseif($zLinked['phase'] === 'homozygous'): ?>
                                        <?= t('phase_z_homozygous') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($zLinked['phase']) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if(!empty($zLinked['confidence'])): ?>
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_confidence') ?></span>
                                <span>
                                    <span style="display:inline-block;width:100px;height:8px;background:rgba(255,255,255,0.1);border-radius:4px;overflow:hidden;">
                                        <span style="display:block;height:100%;width:<?= min(100, $zLinked['confidence']) ?>%;background:rgba(255,255,255,0.6);"></span>
                                    </span>
                                    <span style="margin-left:.5rem;color:rgba(255,255,255,0.6);"><?= number_format($zLinked['confidence'], 0) ?>%</span>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($zLinked['note'])): ?>
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_evidence') ?></span>
                                <span style="color:rgba(255,255,255,0.6);"><?= htmlspecialchars($zLinked['note']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if($zLinked['phase'] === 'cis'): ?>
                            <div style="margin-top:.75rem;padding:.5rem;background:rgba(255,255,255,0.1);border-radius:4px;font-size:.85em;color:#fff;">
                                💡 <?= t('phase_z_cis_tip') ?>
                            </div>
                            <?php elseif($zLinked['phase'] === 'trans'): ?>
                            <div style="margin-top:.75rem;padding:.5rem;background:rgba(255,255,255,0.1);border-radius:4px;font-size:.85em;color:#fff;">
                                ⚠️ <?= t('phase_z_trans_tip') ?>
                            </div>
                            <?php elseif($zLinked['phase'] === 'homozygous'): ?>
                            <div style="margin-top:.75rem;padding:.5rem;background:rgba(255,255,255,0.1);border-radius:4px;font-size:.85em;color:#fff;">
                                💡 <?= t('phase_z_homozygous_tip') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($hasAutoPhase): ?>
                        <div style="background:rgba(255,255,255,0.05);padding:.75rem;border-radius:6px;">
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
                                <span style="font-weight:bold;color:#fff;">🧬 <?= t('phase_auto_linked') ?></span>
                            </div>
                            <div style="display:grid;grid-template-columns:auto 1fr;gap:.25rem .75rem;font-size:.95em;">
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_result') ?></span>
                                <span style="font-weight:bold;color:#fff;">
                                    <?php if($auto1['phase'] === 'cis'): ?>
                                        <?= t('phase_auto_cis') ?>
                                    <?php elseif($auto1['phase'] === 'trans'): ?>
                                        <?= t('phase_auto_trans') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($auto1['phase']) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if(!empty($auto1['confidence'])): ?>
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_confidence') ?></span>
                                <span>
                                    <span style="display:inline-block;width:100px;height:8px;background:rgba(255,255,255,0.1);border-radius:4px;overflow:hidden;">
                                        <span style="display:block;height:100%;width:<?= min(100, $auto1['confidence']) ?>%;background:rgba(255,255,255,0.6);"></span>
                                    </span>
                                    <span style="margin-left:.5rem;color:rgba(255,255,255,0.6);"><?= number_format($auto1['confidence'], 0) ?>%</span>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($auto1['note'])): ?>
                                <span style="color:rgba(255,255,255,0.6);"><?= t('phase_evidence') ?></span>
                                <span style="color:rgba(255,255,255,0.6);"><?= htmlspecialchars($auto1['note']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php elseif ($action === 'pathfind' && isset($result['error'])): ?>
                <div class="warning-box" style="margin-top:1rem;">
                    <?= htmlspecialchars(t_pf_fix(t_pf($result['error'], ['target' => $result['errorParam'] ?? '']), ['target' => $result['errorParam'] ?? ''])) ?>
                </div>
                <?php endif; ?>
                </div>

                <?= renderInbreedingLimitSection() ?>
            </section>

            <section id="feasibility" class="tab-content<?= $activeTab === 'feasibility' ? ' active' : '' ?>">
                <?php
                // フォーム値を保持
                $fMode = $_REQUEST['f_mode'] ?? 'phenotype';
                $mMode = $_REQUEST['m_mode'] ?? 'phenotype';
                $fBaseColor = $_REQUEST['f_baseColor'] ?? 'green';
                $fEyeColor = $_REQUEST['f_eyeColor'] ?? 'black';
                $fDarkness = $_REQUEST['f_darkness'] ?? 'none';
                $mBaseColor = $_REQUEST['m_baseColor'] ?? 'green';
                $mEyeColor = $_REQUEST['m_eyeColor'] ?? 'black';
                $mDarkness = $_REQUEST['m_darkness'] ?? 'none';
                // 遺伝子型直接入力の値（v6.7.3: aq対応）
                $fDbId = $_REQUEST['f_db_id'] ?? '';
                $mDbId = $_REQUEST['m_db_id'] ?? '';
                $fParblue = $_REQUEST['f_parblue'] ?? '++';
                $fIno = $_REQUEST['f_ino'] ?? '++';
                $fDark = $_REQUEST['f_dark'] ?? 'dd';
                $fOpaline = $_REQUEST['f_opaline'] ?? '++';
                $fCinnamon = $_REQUEST['f_cinnamon'] ?? '++';
                $fPied = $_REQUEST['f_pirec'] ?? '++';
                $mParblue = $_REQUEST['m_parblue'] ?? '++';
                $mIno = $_REQUEST['m_ino'] ?? '+W';
                $mDark = $_REQUEST['m_dark'] ?? 'dd';
                $mOpaline = $_REQUEST['m_opaline'] ?? '+W';
                $mCinnamon = $_REQUEST['m_cinnamon'] ?? '+W';
                $mPied = $_REQUEST['m_pirec'] ?? '++';
                // v6.8追加: 14座位対応
$fVio = $_REQUEST['f_vio'] ?? 'vv';
$fPidom = $_REQUEST['f_pidom'] ?? '++';
$fFlp = $_REQUEST['f_flp'] ?? '++';
$fFlb = $_REQUEST['f_flb'] ?? '++';
$fDil = $_REQUEST['f_dil'] ?? '++';
$fEd = $_REQUEST['f_ed'] ?? '++';
$fOf = $_REQUEST['f_of'] ?? '++';
$fPh = $_REQUEST['f_ph'] ?? '++';
$mVio = $_REQUEST['m_vio'] ?? 'vv';
$mPidom = $_REQUEST['m_pidom'] ?? '++';
$mFlp = $_REQUEST['m_flp'] ?? '++';
$mFlb = $_REQUEST['m_flb'] ?? '++';
$mDil = $_REQUEST['m_dil'] ?? '++';
$mEd = $_REQUEST['m_ed'] ?? '++';
$mOf = $_REQUEST['m_of'] ?? '++';
$mPh = $_REQUEST['m_ph'] ?? '++';
                ?>
                <form method="GET" action="#feasibility-result" class="card" id="feasibilityForm" onsubmit="return window._allowSubmit;"><input type="hidden" name="action" value="calculate">

                    
                    <!-- ♂ Father -->
                    <div class="parent-block">
                        <h3>♂ <?= t('father') ?></h3>
                        <div class="input-mode-toggle">
                            <label><input type="radio" name="f_mode" value="phenotype"<?= $fMode === 'phenotype' ? ' checked' : '' ?> onchange="toggleInputMode('f')"> <?= t('from_phenotype') ?></label>
                            <label><input type="radio" name="f_mode" value="genotype"<?= $fMode === 'genotype' ? ' checked' : '' ?> onchange="toggleInputMode('f')"> <?= t('direct_genotype') ?></label>
                            <label><input type="radio" name="f_mode" value="fromdb"<?= $fMode === 'fromdb' ? ' checked' : '' ?> onchange="toggleInputMode('f')"> 📁 <?= t('from_db') ?></label>
                        </div>
                        
                        <!-- DB選択 -->
                        <div id="f_db_inputs" class="input-panel"<?= $fMode !== 'fromdb' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group">
                                <label><?= t('registered_males') ?></label>
                                <select id="f_db_select" name="f_db_id">

                                    <option value=""><?= t('select_placeholder') ?></option>
                                </select>
                            </div>
                            <!-- fromdbモード用隠しフィールド -->
                            <input type="hidden" name="f_db_baseColor" id="f_db_baseColor" value="<?= htmlspecialchars($_REQUEST['f_db_baseColor'] ?? '') ?>">
                            <input type="hidden" name="f_db_eyeColor" id="f_db_eyeColor" value="<?= htmlspecialchars($_REQUEST['f_db_eyeColor'] ?? '') ?>">
                            <input type="hidden" name="f_db_darkness" id="f_db_darkness" value="<?= htmlspecialchars($_REQUEST['f_db_darkness'] ?? '') ?>">
                            <input type="hidden" name="f_db_genotype" id="f_db_genotype" value="<?= htmlspecialchars($_REQUEST['f_db_genotype'] ?? '') ?>">

                        </div>
                        
                        <!-- Phenotype inputs (共通) -->
                        <div id="f_phenotype_inputs" class="input-panel"<?= $fMode === 'genotype' || $fMode === 'fromdb' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group"><label><?= t('base_color_observed') ?></label><?= renderPhenotypeSelect('f', 'baseColor', $lang === 'ja', $fBaseColor) ?></div>
                            <div class="form-group"><label><?= t('eye_color') ?></label><?= renderPhenotypeSelect('f', 'eyeColor', $lang === 'ja', $fEyeColor) ?></div>
                            <div class="form-group"><label><?= t('dark_factor') ?></label><?= renderPhenotypeSelect('f', 'darkness', $lang === 'ja', $fDarkness) ?></div>
                        </div>
                        
                        <!-- Genotype inputs (v6.7.3: aq対応) -->
                        <div id="f_genotype_inputs" class="input-panel"<?= $fMode !== 'genotype' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group"><label>Parblue</label><select name="f_parblue"><?php foreach(['++=B⁺/B⁺','+aq=B⁺/b^aq','aqaq=b^aq/b^aq','+tq=B⁺/b^tq','tqtq=b^tq/b^tq','tqaq=b^tq/b^aq'] as $o){$p=explode('=',$o);echo '<option value="'.$p[0].'"'.($fParblue===$p[0]?' selected':'').'>'.$p[1].'</option>';}?></select></div>
                            <div class="form-group"><label>INO</label><select name="f_ino"><?php foreach(['++'=>'Z⁺/Z⁺','+ino'=>'Z⁺/Z^ino','inoino'=>'Z^ino/Z^ino','+pld'=>'Z⁺/Z^pld','pldpld'=>'Z^pld/Z^pld','pldino'=>'Z^pld/Z^ino'] as $v=>$l){echo '<option value="'.$v.'"'.($fIno===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Dark</label><select name="f_dark"><?php foreach(['dd'=>'d/d','Dd'=>'D/d','DD'=>'D/D'] as $v=>$l){echo '<option value="'.$v.'"'.($fDark===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Opaline</label><select name="f_opaline"><?php foreach(['++'=>'Z⁺/Z⁺','+op'=>'Z⁺/Z^op','opop'=>'Z^op/Z^op'] as $v=>$l){echo '<option value="'.$v.'"'.($fOpaline===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Cinnamon</label><select name="f_cinnamon"><?php foreach(['++'=>'Z⁺/Z⁺','+cin'=>'Z⁺/Z^cin','cincin'=>'Z^cin/Z^cin'] as $v=>$l){echo '<option value="'.$v.'"'.($fCinnamon===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Pied</label><select name="f_pirec"><?php foreach(['++'=>'+/+','+pi'=>'+/pi','pipi'=>'pi/pi'] as $v=>$l){echo '<option value="'.$v.'"'.($fPied===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                        <div class="form-group"><label>Violet</label><select name="f_vio"><?php foreach(['vv'=>'v/v','Vv'=>'V/v','VV'=>'V/V'] as $v=>$l){echo '<option value="'.$v.'"'.(($fVio??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Dom Pied</label><select name="f_pidom"><?php foreach(['++'=>'+/+','Pi+'=>'Pi/+','PiPi'=>'Pi/Pi'] as $v=>$l){echo '<option value="'.$v.'"'.(($fPidom??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Pale Fallow</label><select name="f_flp"><?php foreach(['++'=>'+/+','+flp'=>'+/flp','flpflp'=>'flp/flp'] as $v=>$l){echo '<option value="'.$v.'"'.(($fFlp??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Bronze Fallow</label><select name="f_flb"><?php foreach(['++'=>'+/+','+flb'=>'+/flb','flbflb'=>'flb/flb'] as $v=>$l){echo '<option value="'.$v.'"'.(($fFlb??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Dilute</label><select name="f_dil"><?php foreach(['++'=>'+/+','+dil'=>'+/dil','dildil'=>'dil/dil'] as $v=>$l){echo '<option value="'.$v.'"'.(($fDil??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Edged</label><select name="f_ed"><?php foreach(['++'=>'+/+','+ed'=>'+/ed','eded'=>'ed/ed'] as $v=>$l){echo '<option value="'.$v.'"'.(($fEd??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Orangeface</label><select name="f_of"><?php foreach(['++'=>'+/+','+of'=>'+/of','ofof'=>'of/of'] as $v=>$l){echo '<option value="'.$v.'"'.(($fOf??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Pale Headed</label><select name="f_ph"><?php foreach(['++'=>'+/+','+ph'=>'+/ph','phph'=>'ph/ph'] as $v=>$l){echo '<option value="'.$v.'"'.(($fPh??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                    </div>                  
                    <!-- ♀ Mother -->
                    <div class="parent-block">
                        <h3>♀ <?= t('mother') ?></h3>
                        <div class="input-mode-toggle">
                            <label><input type="radio" name="m_mode" value="phenotype"<?= $mMode === 'phenotype' ? ' checked' : '' ?> onchange="toggleInputMode('m')"> <?= t('from_phenotype') ?></label>
                            <label><input type="radio" name="m_mode" value="genotype"<?= $mMode === 'genotype' ? ' checked' : '' ?> onchange="toggleInputMode('m')"> <?= t('direct_genotype') ?></label>
                            <label><input type="radio" name="m_mode" value="fromdb"<?= $mMode === 'fromdb' ? ' checked' : '' ?> onchange="toggleInputMode('m')"> 📁 <?= t('from_db') ?></label>
                        </div>
                        
                        <!-- DB選択 -->
                        <div id="m_db_inputs" class="input-panel"<?= $mMode !== 'fromdb' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group">
                                <label><?= t('registered_females') ?></label>
                                <select id="m_db_select" name="m_db_id">
                                    <option value=""><?= t('select_placeholder') ?></option>
                                </select>
                            </div>
                            <!-- fromdbモード用隠しフィールド -->
                            <input type="hidden" name="m_db_baseColor" id="m_db_baseColor" value="<?= htmlspecialchars($_REQUEST['m_db_baseColor'] ?? '') ?>">
                            <input type="hidden" name="m_db_eyeColor" id="m_db_eyeColor" value="<?= htmlspecialchars($_REQUEST['m_db_eyeColor'] ?? '') ?>">
                            <input type="hidden" name="m_db_darkness" id="m_db_darkness" value="<?= htmlspecialchars($_REQUEST['m_db_darkness'] ?? '') ?>">
                            <input type="hidden" name="m_db_genotype" id="m_db_genotype" value="<?= htmlspecialchars($_REQUEST['m_db_genotype'] ?? '') ?>">

                        </div>

                        <!-- Phenotype inputs (共通) -->
                        <div id="m_phenotype_inputs" class="input-panel"<?= $mMode === 'genotype' || $mMode === 'fromdb' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group"><label><?= t('base_color_observed') ?></label><?= renderPhenotypeSelect('m', 'baseColor', $lang === 'ja', $mBaseColor) ?></div>
                            <div class="form-group"><label><?= t('eye_color') ?></label><?= renderPhenotypeSelect('m', 'eyeColor', $lang === 'ja', $mEyeColor) ?></div>
                            <div class="form-group"><label><?= t('dark_factor') ?></label><?= renderPhenotypeSelect('m', 'darkness', $lang === 'ja', $mDarkness) ?></div>
                        </div>
                        
                        <!-- Genotype inputs (v6.7.3: aq対応) -->
                        <div id="m_genotype_inputs" class="input-panel"<?= $mMode !== 'genotype' ? ' style="display:none;"' : '' ?>>
                            <div class="form-group"><label>Parblue</label><select name="m_parblue"><?php foreach(['++=B⁺/B⁺','+aq=B⁺/b^aq','aqaq=b^aq/b^aq','+tq=B⁺/b^tq','tqtq=b^tq/b^tq','tqaq=b^tq/b^aq'] as $o){$p=explode('=',$o);echo '<option value="'.$p[0].'"'.($mParblue===$p[0]?' selected':'').'>'.$p[1].'</option>';}?></select></div>
                            <div class="form-group"><label>INO</label><select name="m_ino"><?php foreach(['+W'=>'Z⁺/W','inoW'=>'Z^ino/W','pldW'=>'Z^pld/W'] as $v=>$l){echo '<option value="'.$v.'"'.($mIno===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Dark</label><select name="m_dark"><?php foreach(['dd'=>'d/d','Dd'=>'D/d','DD'=>'D/D'] as $v=>$l){echo '<option value="'.$v.'"'.($mDark===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Opaline</label><select name="m_opaline"><?php foreach(['+W'=>'Z⁺/W','opW'=>'Z^op/W'] as $v=>$l){echo '<option value="'.$v.'"'.($mOpaline===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Cinnamon</label><select name="m_cinnamon"><?php foreach(['+W'=>'Z⁺/W','cinW'=>'Z^cin/W'] as $v=>$l){echo '<option value="'.$v.'"'.($mCinnamon===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Pied</label><select name="m_pirec"><?php foreach(['++'=>'+/+','+pi'=>'+/pi','pipi'=>'pi/pi'] as $v=>$l){echo '<option value="'.$v.'"'.($mPied===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                            <div class="form-group"><label>Violet</label><select name="m_vio"><?php foreach(['vv'=>'v/v','Vv'=>'V/v','VV'=>'V/V'] as $v=>$l){echo '<option value="'.$v.'"'.(($mVio??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Dom Pied</label><select name="m_pidom"><?php foreach(['++'=>'+/+','Pi+'=>'Pi/+','PiPi'=>'Pi/Pi'] as $v=>$l){echo '<option value="'.$v.'"'.(($mPidom??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Pale Fallow</label><select name="m_flp"><?php foreach(['++'=>'+/+','+flp'=>'+/flp','flpflp'=>'flp/flp'] as $v=>$l){echo '<option value="'.$v.'"'.(($mFlp??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Bronze Fallow</label><select name="m_flb"><?php foreach(['++'=>'+/+','+flb'=>'+/flb','flbflb'=>'flb/flb'] as $v=>$l){echo '<option value="'.$v.'"'.(($mFlb??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Dilute</label><select name="m_dil"><?php foreach(['++'=>'+/+','+dil'=>'+/dil','dildil'=>'dil/dil'] as $v=>$l){echo '<option value="'.$v.'"'.(($mDil??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Edged</label><select name="m_ed"><?php foreach(['++'=>'+/+','+ed'=>'+/ed','eded'=>'ed/ed'] as $v=>$l){echo '<option value="'.$v.'"'.(($mEd??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Orangeface</label><select name="m_of"><?php foreach(['++'=>'+/+','+of'=>'+/of','ofof'=>'of/of'] as $v=>$l){echo '<option value="'.$v.'"'.(($mOf??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
<div class="form-group"><label>Pale Headed</label><select name="m_ph"><?php foreach(['++'=>'+/+','+ph'=>'+/ph','phph'=>'ph/ph'] as $v=>$l){echo '<option value="'.$v.'"'.(($mPh??'')===$v?' selected':'').'>'.$l.'</option>';}?></select></div>
                        </div>
                    </div>

                    <!-- v7.0: 連鎖遺伝（常時有効） -->
                    <div class="linkage-mode-section" style="margin-top: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <strong>🧬 <?= $lang === 'ja' ? '連鎖遺伝計算 (v7.0)' : 'Linkage Genetics (v7.0)' ?></strong>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">
                            <?= $lang === 'ja'
                                ? '組み換え率: cin-ino 3%, ino-op 30%, cin-op 33%, dark-parblue 7%'
                                : 'Recombination rates: cin-ino 3%, ino-op 30%, cin-op 33%, dark-parblue 7%' ?>
                        </p>

                        <!-- オス用 相(Phase)選択 - 複数伴性変異かつ相が不明な場合のみ表示 -->
                        <div id="fatherPhaseUI" style="display: none; margin-top: 1rem; padding: 0.5rem; background: var(--bg-tertiary); border-radius: 4px;">
                            <label style="font-weight: bold;">♂ <?= $lang === 'ja' ? 'Z染色体の相 (Phase)' : 'Z Chromosome Phase' ?></label>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                <label><input type="radio" name="f_z_phase" value="unknown" checked> <?= $lang === 'ja' ? '不明' : 'Unknown' ?></label>
                                <label><input type="radio" name="f_z_phase" value="cis"> Cis <?= $lang === 'ja' ? '(cin-ino連鎖)' : '(cin-ino linked)' ?></label>
                                <label><input type="radio" name="f_z_phase" value="trans"> Trans <?= $lang === 'ja' ? '(cin/ino分離)' : '(cin/ino separate)' ?></label>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem;">
                                <?= $lang === 'ja'
                                    ? '※ 母親がLacewingの場合、息子はCis確定'
                                    : '* If dam is Lacewing, son is Cis confirmed' ?>
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" style="margin-top:1rem;" onclick="window._allowSubmit=true; document.getElementById('feasibilityForm').submit();">🧬 <?= t('btn_calculate') ?></button>

                </form>
                
                <script>
                function toggleInputMode(parent) {
                    const mode = document.querySelector(`input[name="${parent}_mode"]:checked`).value;
                    document.getElementById(`${parent}_phenotype_inputs`).style.display = mode === 'phenotype' ? 'block' : 'none';
                    document.getElementById(`${parent}_genotype_inputs`).style.display = mode === 'genotype' ? 'block' : 'none';
                    document.getElementById(`${parent}_db_inputs`).style.display = mode === 'fromdb' ? 'block' : 'none';

                    // DB選択時はリストを更新
                    if (mode === 'fromdb') {
                        populateDbSelect(parent);
                    }
                }
                
                function populateDbSelect(parent) {
                    const select = document.getElementById(`${parent}_db_select`);
                    if (!select) return;
                    if (typeof BirdDB === 'undefined' || !BirdDB.isReady()) {
                        console.log('[populateDbSelect] BirdDB not ready, retrying...');
                        setTimeout(() => populateDbSelect(parent), 300);
                        return;
                    }

                    const sex = parent === 'f' ? 'male' : 'female';
                    const birds = BirdDB.getAllBirds().filter(b => b.sex === sex);
                    console.log('[populateDbSelect]', parent, 'birds:', birds.length);

                    select.innerHTML = `<option value="">${T.select_placeholder}</option>`;
                    birds.forEach(b => {
                        // v7.3.12: keyToLabelを優先使用（SSOT）
                        let colorLabel;
                        if (b.observed?.baseColor) {
                            colorLabel = keyToLabel(b.observed.baseColor);
                        } else if (b.phenotype) {
                            colorLabel = b.phenotype;
                        } else {
                            colorLabel = '?';
                        }
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.name || b.id} - ${colorLabel}`;
                        select.appendChild(opt);
                    });
                
                // POST後の選択状態を復元
                const savedValue = parent === 'f' 
                    ? '<?= addslashes($fDbId ?? "") ?>' 
                    : '<?= addslashes($mDbId ?? "") ?>';
                if (savedValue) {
                    select.value = savedValue;
                }
            }
                /**
                 * v7.3: 色名取得（keyToLabel対応）
                 */
                function getColorLabel(color, isJa) {
                    return keyToLabel(color) || color || '?';
                }
                function loadBirdToForm(parent, birdId) {
                    const prefix = (parent === 'father' || parent === 'f') ? 'f' : 'm';


                    if (!birdId || typeof BirdDB === 'undefined') return false;

                    const bird = BirdDB.getBird(birdId);
                    if (!bird) return false;

                    let baseColor = 'green';
                    let eyeColor = 'black';
                    let darkness = 'none';

                    if (bird.observed && bird.observed.baseColor) {
                        baseColor = bird.observed.baseColor;
                        eyeColor = bird.observed.eyeColor || 'black';
                        darkness = bird.observed.darkness || 'none';
                    }

                    const bcEl = document.getElementById(prefix + '_db_baseColor');
                    const ecEl = document.getElementById(prefix + '_db_eyeColor');
                    const dkEl = document.getElementById(prefix + '_db_darkness');
                    const genoEl = document.getElementById(prefix + '_db_genotype');

                    if (bcEl) bcEl.value = baseColor;
                    if (ecEl) ecEl.value = eyeColor;
                    if (dkEl) dkEl.value = darkness;

                    // genotype データをJSON形式で設定
                    if (genoEl && bird.genotype) {
                        genoEl.value = JSON.stringify(bird.genotype);
                    } else if (genoEl) {
                        genoEl.value = '';
                    }

                    // オス(f)の場合、Phase UI の表示/非表示を制御
                    if (prefix === 'f') {
                        updatePhaseUI(bird);
                    }

                    // DBから選択モードに自動切り替え（既にfromdbでなければ）
                    const fromdbRadio = document.querySelector(`input[name="${prefix}_mode"][value="fromdb"]`);
                    if (fromdbRadio && !fromdbRadio.checked) {
                        fromdbRadio.checked = true;
                        toggleInputMode(prefix);
                    }

                    return false;
                }

                /**
                 * Phase UI の表示/非表示を制御
                 * - 複数の伴性変異を持つオスのみ対象
                 * - 相が確定している場合は非表示（自動設定）
                 * - 相が不明な場合のみ選択UIを表示
                 */
                function updatePhaseUI(bird) {
                    const phaseUI = document.getElementById('fatherPhaseUI');
                    if (!phaseUI) return;

                    // デフォルトは非表示
                    phaseUI.style.display = 'none';

                    // オスでない、またはgenotype/Z_linkedがない場合は非表示
                    if (!bird || bird.sex !== 'male' || !bird.genotype) {
                        return;
                    }

                    const zLinked = bird.genotype.Z_linked;
                    if (!zLinked) return;

                    const z1 = zLinked.Z1 || {};
                    const z2 = zLinked.Z2 || {};

                    // 各伴性座位の変異をカウント
                    const z1HasCin = z1.cinnamon && z1.cinnamon !== '+';
                    const z1HasIno = z1.ino && z1.ino !== '+';
                    const z1HasOp = z1.opaline && z1.opaline !== '+';
                    const z2HasCin = z2.cinnamon && z2.cinnamon !== '+';
                    const z2HasIno = z2.ino && z2.ino !== '+';
                    const z2HasOp = z2.opaline && z2.opaline !== '+';

                    // 異なる座位の変異数をカウント（同じ座位のホモは1つとカウント）
                    const hasCin = z1HasCin || z2HasCin;
                    const hasIno = z1HasIno || z2HasIno;
                    const hasOp = z1HasOp || z2HasOp;
                    const mutationCount = (hasCin ? 1 : 0) + (hasIno ? 1 : 0) + (hasOp ? 1 : 0);

                    // 複数の伴性変異がない場合は Phase 無関係
                    if (mutationCount < 2) {
                        // unknownにリセット
                        const unknownRadio = document.querySelector('input[name="f_z_phase"][value="unknown"]');
                        if (unknownRadio) unknownRadio.checked = true;
                        return;
                    }

                    // 相が確定しているかチェック
                    // Cis: cin と ino が同じ染色体上にある
                    const isCis = (z1HasCin && z1HasIno) || (z2HasCin && z2HasIno);
                    // Trans: cin と ino が別々の染色体上にある
                    const isTrans = (z1HasCin && z2HasIno) || (z1HasIno && z2HasCin);

                    if (isCis) {
                        // 相が確定(Cis) → UI非表示、自動設定
                        const cisRadio = document.querySelector('input[name="f_z_phase"][value="cis"]');
                        if (cisRadio) cisRadio.checked = true;
                        return;
                    }

                    if (isTrans) {
                        // 相が確定(Trans) → UI非表示、自動設定
                        const transRadio = document.querySelector('input[name="f_z_phase"][value="trans"]');
                        if (transRadio) transRadio.checked = true;
                        return;
                    }

                    // 複数変異あり + 相が不明 → UI表示
                    phaseUI.style.display = 'block';
                    const unknownRadio = document.querySelector('input[name="f_z_phase"][value="unknown"]');
                    if (unknownRadio) unknownRadio.checked = true;
                }

                // グローバル関数（BirdDB.setMode()から呼ばれる）
                function refreshDBSelectors() {
                    populateDbSelect('f');
                    populateDbSelect('m');
                }

                function refreshHealthSelectors() {
                    const healthSire = document.getElementById('healthSire');
                    const healthDam = document.getElementById('healthDam');
                    if (!healthSire || !healthDam || typeof BirdDB === 'undefined') return;
                    
                    const birds = BirdDB.getAllBirds();
                    const males = birds.filter(b => b.sex === 'male');
                    const females = birds.filter(b => b.sex === 'female');
                    
                    healthSire.innerHTML = `<option value="">${escapeHtml(T.select_placeholder)}</option>`;
                    healthDam.innerHTML = `<option value="">${escapeHtml(T.select_placeholder)}</option>`;
                    males.forEach(b => {
                        // v7.3.11: keyToLabelで動的にローカライズ
                        const pheno = b.observed?.baseColor ? keyToLabel(b.observed.baseColor) : (b.phenotype || '?');
                        const lineage = b.lineage ? ` [${escapeHtml(b.lineage)}]` : '';
                        healthSire.innerHTML += `<option value="${escapeHtml(b.id)}">${escapeHtml(b.name || b.id)} - ${escapeHtml(pheno)}${lineage}</option>`;
                    });
                    females.forEach(b => {
                        // v7.3.11: keyToLabelで動的にローカライズ
                        const pheno = b.observed?.baseColor ? keyToLabel(b.observed.baseColor) : (b.phenotype || '?');
                        const lineage = b.lineage ? ` [${escapeHtml(b.lineage)}]` : '';
                        healthDam.innerHTML += `<option value="${escapeHtml(b.id)}">${escapeHtml(b.name || b.id)} - ${escapeHtml(pheno)}${lineage}</option>`;
                    });
                }

                // ページ読み込み時にDB選択リストを初期化
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof BirdDB !== 'undefined') {
                        setTimeout(() => {
                            populateDbSelect('f');
                            populateDbSelect('m');
                        }, 500);
                    }
                });
                </script>
                
                <div id="feasibility-result">
                <?php if ($action === 'calculate' && $result): ?>
                <?php
                // v7: phenotype（表現型別集約）を優先、なければ results（遺伝子型別）
                $offspring = $result['phenotype'] ?? $result['results'] ?? $result;
                // v7.3.15: バリデーション警告があれば表示
                $warnings = $result['warnings'] ?? [];
                ?>
                <?php if (!empty($warnings)): ?>
                <div class="warning-box" style="margin-top:1rem;padding:.75rem;background:rgba(255,193,7,0.15);border-left:4px solid #ffc107;border-radius:4px;">
                    <div style="font-weight:bold;color:#ffc107;margin-bottom:.5rem;">⚠️ <?= $lang === 'ja' ? '入力値が自動修正されました' : 'Input values were auto-corrected' ?></div>
                    <ul style="margin:0;padding-left:1.5rem;font-size:.85rem;color:var(--text-muted);">
                    <?php foreach ($warnings as $w): ?>
                        <li><?= htmlspecialchars($w) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="output-panel" style="margin-top:1rem;"><div class="offspring-grid">
                    <?php foreach($offspring as $o): ?>
                    <?php
                    // v7は'probability'、旧形式は'prob'を使用
                    $rawProb = $o['prob'] ?? $o['probability'] ?? 0;
                    // probが1以上ならパーセント値、1未満なら小数
                    $probValue = $rawProb > 1 ? $rawProb : $rawProb * 100;
                    // v7.3.14: 言語に応じた羽色名を選択（非日本語はen）
                    $langKey = ($lang === 'ja') ? 'ja' : 'en';
                    $phenoDisplay = $o['phenotype_' . $langKey] ?? $o['phenotype'] ?? $o['displayName'] ?? '';
                    ?>
                    <div style="padding:.5rem;background:var(--bg-tertiary);border-radius:4px;text-align:center;"><div style="font-size:1.2rem;"><?= number_format($probValue, 1) ?>%</div><div><?= $o['sex']==='male'?'♂':'♀' ?> <?= htmlspecialchars($phenoDisplay) ?></div></div><?php endforeach; ?>
                </div>
                <div style="margin-top:1rem;padding:.75rem;background:var(--bg-secondary);border-radius:6px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
                    <span style="font-size:.85rem;color:var(--text-muted);">
                        <?= $lang === 'ja' ? '表示: ' . count($offspring) . '件（確率0.01%以上）' : 'Showing: ' . count($offspring) . ' results (≥0.01%)' ?>
                    </span>
                    <button type="button" class="btn btn-small btn-outline" onclick="downloadFullCSV()" title="<?= $lang === 'ja' ? '確率0.01%未満も含む全件をCSV出力' : 'Export all results including <0.01% probability' ?>">
                        📊 <?= $lang === 'ja' ? '全件CSV出力' : 'Full CSV Export' ?>
                    </button>
                </div>
                </div>
                <?php endif; ?>
                </div>

                <?= renderInbreedingLimitSection() ?>
            </section>

            <section id="estimator" class="tab-content<?= $activeTab === 'estimator' ? ' active' : '' ?>">
                <?php
                // フォーム値を保持
                $estSex = $_REQUEST['sex'] ?? 'male';
                $estBaseColor = $_REQUEST['est_baseColor'] ?? 'green';
                $estEyeColor = $_REQUEST['est_eyeColor'] ?? 'black';
                $estDarkness = $_REQUEST['est_darkness'] ?? 'none';
                ?>
                <form method="GET" action="#estimator-result" class="card"><input type="hidden" name="action" value="estimate">
                    <h3>🔬 <?= t('estimate_title') ?></h3>
                    <p class="card-subtitle"><?= t('estimate_desc') ?></p>
                    
                    <div class="form-group">
                        <label class="form-label"><?= t('sex') ?></label>
                        <select name="sex" class="form-select">
                            <option value="male" <?= $estSex === 'male' ? 'selected' : '' ?>>♂ <?= t('male') ?></option>
                            <option value="female" <?= $estSex === 'female' ? 'selected' : '' ?>>♀ <?= t('female') ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?= t('base_color_observed') ?></label>
                        <?= renderPhenotypeSelect('est', 'baseColor', $lang === 'ja', $estBaseColor) ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?= t('eye_color') ?></label>
                        <?= renderPhenotypeSelect('est', 'eyeColor', $lang === 'ja', $estEyeColor) ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><?= t('dark_factor') ?></label>
                        <?= renderPhenotypeSelect('est', 'darkness', $lang === 'ja', $estDarkness) ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">🔬 <?= t('btn_estimate') ?></button>
                </form>
                <div id="estimator-result">
                <?php if ($action === 'estimate'): ?>
                    <?php if ($result && !empty($result['loci'])): ?>
                    <div class="output-panel" style="margin-top:1rem;">
                        <div class="output-header"><span class="output-title">🧬 <?= t('estimation_result') ?></span></div>
                        <?php foreach($result['loci'] as $l): ?>
<?php
    $isConfirmed = $l['isConfirmed'] ?? false;
    $bgColor = $isConfirmed ? 'rgba(78,205,196,0.15)' : 'rgba(255,255,255,0.03)';
    $borderLeft = $isConfirmed ? '3px solid #4ecdc4' : '3px solid #555';
    // 確定: 遺伝型を表示、不明: "?" を表示
    $displayGenotype = $isConfirmed ? htmlspecialchars($l['genotype']) : '?';
    $confidenceLabel = $isConfirmed ? '✓ ' . t('confirmed') : t('unknown');
?>
<div style="padding:0.5rem;margin:0.25rem 0;background:<?= $bgColor ?>;border-radius:4px;border-left:<?= $borderLeft ?>;">
    <strong style="color:<?= $isConfirmed ? '#4ecdc4' : '#888' ?>;"><?= htmlspecialchars($l['locusName'] ?? $l['locusKey'] ?? '?') ?>:</strong>
    <span style="color:<?= $isConfirmed ? '#e0e0e0' : '#666' ?>;"><?= $displayGenotype ?></span>
    <span style="font-size:0.75rem;color:#888;margin-left:0.5rem;"><?= $confidenceLabel ?></span>
</div>
<?php endforeach; ?>

                        <!-- 一族推論への誘導 -->
                        <div style="margin-top:1rem;padding:1rem;background:rgba(100,150,255,0.1);border-radius:8px;border-left:3px solid #6496ff;">
                            <strong style="color:#6496ff;">💡 <?= $lang === 'ja' ? 'より詳しい推定' : 'More Detailed Estimation' ?>:</strong>
                            <p style="font-size:.85rem;color:#b0bec5;margin-top:0.5rem;">
                                <?= $lang === 'ja'
                                    ? '「一族推論」ツールを使用すると、親や子の表現型から隠れた遺伝子（スプリット）をより詳しく推論できます。'
                                    : 'Use the "Family Inference" tool to infer hidden genes (splits) more accurately from parent and offspring phenotypes.' ?>
                            </p>
                        </div>

                        <?php if(!empty($result['notes'])): ?>
                        <div style="margin-top:1rem;padding:1rem;background:rgba(78,205,196,0.1);border-radius:8px;">
                            <strong style="color:#4ecdc4;">💡 <?= t('test_proposal') ?>:</strong>
                            <ul style="margin-top:0.5rem;">
                            <?php foreach($result['notes'] as $n): ?>
                            <li style="font-size:.85rem;color:#ccc;margin:0.3rem 0;"><?= htmlspecialchars($n) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="output-panel" style="margin-top:1rem;">
                        <div class="warning-box"><?= t('no_result') ?></div>
                        <pre style="font-size:0.75rem;color:#888;"><?= htmlspecialchars(print_r($result, true)) ?></pre>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </section>
        </main>
        <footer>
            <details class="footer-credits">
                <summary>制度外文明・かならづプロジェクト</summary>
                <div class="credits-content simple-credits">
                    <p></p>
                    <p></p>
                    <p>跳躍原案：池月 事件(光路収束)</p>
                    <p>人力小說：七ツ胴 かならづ(架構爬行)</p>
                    <p>構文打鍵：鏡文字 ヌル（句法曲率チェック）</p>
                    <p>構造設計：刑部 綴（読解反転工学)</p>
                    <p>廣告文案：近衛 雪子(逆行共振アレンジ)</p>
                    <p>企劃統括：呉 泥舟（核准アラインメント）</p>
                    <p>演出協力：Nathalie Devouassoux</p>
                    <p>（for translation structuring and structural derivation feedback）</p>
                    <p></p>
                    <p>⬛︎概要:</p>
                    <p>整合性原理から自然導出された構造文学開発計画。システムに欠けが生じた場合も構文補完に成功すればプロジェクトの凍結は回避される。</p>
                    <p></p>
                    <p>⬛︎位相:</p>
                    <p>「意味的に読めば狂気、構造的に読めば純粋理性」。観測角度で変わる実相。それが「かならづ」。</p>
                </div>
            </details>
        </footer>
    </div>
    <script src="guardian.js?v=<?= time() ?>"></script>
    <script src="birds.js?v=<?= time() ?>"></script>
    <script src="breeding.js?v=<?= time() ?>"></script>
    <script src="pedigree.js?v=<?= time() ?>"></script>
    <script src="planner.js?v=<?= time() ?>"></script>
<script>
// refreshBirdList をグローバルに定義（フィルター対応版）
function refreshBirdList() {
    if (typeof BirdDB === 'undefined') return;
    
    const birds = BirdDB.getAllBirds();
    const stats = BirdDB.getStats();
    
    // フィルター適用
    const searchText = (document.getElementById('birdSearch')?.value || '').toLowerCase();
    const filterSex = document.getElementById('birdFilterSex')?.value || '';
    const filterLineage = document.getElementById('birdFilterLineage')?.value || '';
    
    const filtered = birds.filter(function(bird) {
        if (searchText && !(bird.name || '').toLowerCase().includes(searchText) && !(bird.code || '').toLowerCase().includes(searchText)) return false;
        if (filterSex && bird.sex !== filterSex) return false;
        if (filterLineage && bird.lineage !== filterLineage) return false;
        return true;
    });
    
    const statsEl = document.getElementById('dbStats');
    if (statsEl) {
        statsEl.innerHTML = 
            '<div style="display:flex;gap:1rem;flex-wrap:wrap;">' +
            '<div class="stat-card"><span class="stat-num">' + stats.totalBirds + '</span><span class="stat-label">' + (T.total_birds || 'Total') + '</span></div>' +
            '<div class="stat-card"><span class="stat-num">' + stats.males + '</span><span class="stat-label">♂</span></div>' +
            '<div class="stat-card"><span class="stat-num">' + stats.females + '</span><span class="stat-label">♀</span></div>' +
            '<div class="stat-card"><span class="stat-num">' + filtered.length + '</span><span class="stat-label">' + (T.filtered || 'Filtered') + '</span></div>' +
            '</div>';
    }
    
    const listEl = document.getElementById('birdList');
    if (!listEl) return;
    
    if (filtered.length === 0) {
        listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">🐣</div><p>' + (T.no_birds || 'No birds registered') + '</p></div>';
        return;
    }
    
    // XSS対策: すべてのユーザーデータをエスケープ
    listEl.innerHTML = filtered.map(function(bird) {
        var safeId = escapeHtml(bird.id);
        return '<div class="bird-card" style="background:var(--bg-tertiary);padding:.75rem;border-radius:8px;margin-bottom:.5rem;">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div>' +
                    '<strong style="color:#fff;">' + escapeHtml(bird.name || '') + '</strong> ' +
                    '<span style="color:#888;font-size:.8rem;">' + escapeHtml(bird.code || '') + '</span> ' +
                    '<span style="color:' + (bird.sex === 'male' ? '#4a90d9' : '#d94a8c') + ';">' + (bird.sex === 'male' ? '♂' : '♀') + '</span>' +
                '</div>' +
                '<div style="display:flex;gap:.25rem;">' +
                    '<button type="button" class="btn btn-tiny" data-action="edit" data-id="' + safeId + '">✏️</button>' +
                    '<button type="button" class="btn btn-tiny" data-action="pedigree" data-id="' + safeId + '">📜</button>' +
                    '<button type="button" class="btn btn-tiny" data-action="delete" data-id="' + safeId + '">🗑️</button>' +
                '</div>' +
            '</div>' +
            '<div style="color:#4ecdc4;font-size:.85rem;margin-top:.25rem;">' + escapeHtml((bird.observed && bird.observed.baseColor) ? keyToLabel(bird.observed.baseColor) : (bird.phenotype || '')) + '</div>' +
        '</div>';
    }).join('');
    // イベント委譲: data属性を使用してXSSを防止
    listEl.querySelectorAll('[data-action]').forEach(function(btn) {
        btn.onclick = function() {
            var action = this.dataset.action;
            var id = this.dataset.id;
            if (action === 'edit') editBird(id);
            else if (action === 'pedigree') showPedigree(id);
            else if (action === 'delete') deleteBird(id);
        };
    });
}

function filterBirds() { refreshBirdList(); }

// 編集・削除・血統書関数
function editBird(id) {
    if (typeof BirdDB === 'undefined') return;
    var bird = BirdDB.getBird(id);
    if (!bird) { alert(T.bird_not_found || 'Bird not found'); return; }

    document.getElementById('birdModalTitle').textContent = T.edit || 'Edit';
    document.getElementById('birdName').value = bird.name || '';
    document.getElementById('birdCode').value = bird.code || '';
    document.getElementById('birdSex').value = bird.sex || 'male';
    document.getElementById('birdBirthDate').value = bird.birthDate || '';
    document.getElementById('birdLineage').value = bird.lineage || '';
    document.getElementById('birdInbreedingGen').value = bird.inbreedingGen || 0;
    document.getElementById('birdNotes').value = bird.notes || '';
    document.getElementById('birdSire').value = bird.sireId || '';
    document.getElementById('birdDam').value = bird.damId || '';

    if (bird.observed) {
        var bcSelect = document.querySelector('[name="bird_baseColor"]');
        var ecSelect = document.querySelector('[name="bird_eyeColor"]');
        var dkSelect = document.querySelector('[name="bird_darkness"]');
        if (bcSelect) bcSelect.value = bird.observed.baseColor || 'green';
        if (ecSelect) ecSelect.value = bird.observed.eyeColor || 'black';
        if (dkSelect) dkSelect.value = bird.observed.darkness || 'none';
    }

    // v7.3.13: 拡張血統情報をロード
    var pedigree = bird.pedigree || {};
    var pedigreeFields = [
        'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam',
        'sire_sire_sire', 'sire_sire_dam', 'sire_dam_sire', 'sire_dam_dam',
        'dam_sire_sire', 'dam_sire_dam', 'dam_dam_sire', 'dam_dam_dam'
    ];
    pedigreeFields.forEach(function(field) {
        var el = document.getElementById('pedigree_' + field);
        if (el) el.value = pedigree[field] || '';
    });

    document.getElementById('birdForm').dataset.editId = id;
    document.getElementById('birdModal').classList.add('active');
}

function deleteBird(id) {
    if (typeof BirdDB === 'undefined') return;
    var bird = BirdDB.getBird(id);
    if (!bird) return;
    
    customConfirm((T.confirm_delete || 'Delete this bird?') + '\n' + (bird.name || id)).then(function(confirmed) {
        if (!confirmed) return;
        BirdDB.deleteBird(id);
        refreshBirdList();
    });
}

function showPedigree(id) {
    if (typeof BirdDB === 'undefined') return;
    var bird = BirdDB.getBird(id);
    if (!bird) return;
    
    document.getElementById('pedigreeModal').classList.add('active');
    if (typeof renderPedigree === 'function') {
        renderPedigree(id, 3);
    }
}

function closePedigreeModal() {
    document.getElementById('pedigreeModal').classList.remove('active');
}
function openBirdForm() {
    document.getElementById('birdModalTitle').textContent = T.add_bird || 'Add Bird';
    document.getElementById('birdForm').reset();
    document.getElementById('birdForm').dataset.editId = '';

    // v7.3.13: 拡張血統フィールドをクリア
    var pedigreeFields = [
        'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam',
        'sire_sire_sire', 'sire_sire_dam', 'sire_dam_sire', 'sire_dam_dam',
        'dam_sire_sire', 'dam_sire_dam', 'dam_dam_sire', 'dam_dam_dam'
    ];
    pedigreeFields.forEach(function(field) {
        var el = document.getElementById('pedigree_' + field);
        if (el) el.value = '';
    });

    document.getElementById('birdModal').classList.add('active');
}

function closeBirdForm() {
    document.getElementById('birdModal').classList.remove('active');
    document.getElementById('birdForm').dataset.editId = '';
}

/**
 * v7.3.15: 全件CSV出力（確率フィルタリングなし）
 * 現在のフォーム入力値を使ってno_filter=1で計算し、CSVをダウンロード
 */
function downloadFullCSV() {
    const form = document.getElementById('feasibilityForm');
    if (!form) {
        alert('Form not found');
        return;
    }

    // フォームデータを収集
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        params.append(key, value);
    }
    // アクションをcalculate_csvに変更
    params.set('action', 'calculate_csv');

    // CSVダウンロードURL生成
    const url = 'index.php?' + params.toString();

    // ダウンロード実行
    const a = document.createElement('a');
    a.href = url;
    a.download = 'breeding_results_full.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

</script>


    <!-- v7.3.11: showTab()はapp.jsに統合 -->
<script src="family.js?v=<?= time() ?>"></script>
<script src="app.js?v=<?= time() ?>"></script>
<script>if(typeof initLang==='function')initLang(T);</script>
<script>
    // 健康評価タブ用セレクタ初期化（BirdDB準備完了を待つ）
        function initHealthSelectors() {
            const healthSire = document.getElementById('healthSire');
            const healthDam = document.getElementById('healthDam');
            if (!healthSire || !healthDam) return;
            
            if (typeof BirdDB === 'undefined' || !BirdDB.isReady()) {
                setTimeout(initHealthSelectors, 500);
                return;
            }
            
            const birds = BirdDB.getAllBirds();
            const males = birds.filter(b => b.sex === 'male');
            const females = birds.filter(b => b.sex === 'female');
            const isJa = document.documentElement.lang === 'ja';
            
            healthSire.innerHTML = `<option value="">${escapeHtml(T.select_placeholder)}</option>`;
            healthDam.innerHTML = `<option value="">${escapeHtml(T.select_placeholder)}</option>`;

            males.forEach(b => {
                // v7.3.11: keyToLabelで動的にローカライズ
                const pheno = b.observed?.baseColor ? keyToLabel(b.observed.baseColor) : (b.phenotype || '?');
                const geno = formatGenoShort(b.genotype, b.sex);
                const lineage = b.lineage ? ` [${escapeHtml(b.lineage)}]` : '';
                healthSire.innerHTML += `<option value="${escapeHtml(b.id)}">${escapeHtml(b.name || b.id)} - ${escapeHtml(pheno)} (${escapeHtml(geno)})${lineage}</option>`;
            });

            females.forEach(b => {
                // v7.3.11: keyToLabelで動的にローカライズ
                const pheno = b.observed?.baseColor ? keyToLabel(b.observed.baseColor) : (b.phenotype || '?');
                const geno = formatGenoShort(b.genotype, b.sex);
                const lineage = b.lineage ? ` [${escapeHtml(b.lineage)}]` : '';
                healthDam.innerHTML += `<option value="${escapeHtml(b.id)}">${escapeHtml(b.name || b.id)} - ${escapeHtml(pheno)} (${escapeHtml(geno)})${lineage}</option>`;
            });
        }
        
        // 遺伝構成を短縮表示（SSOT: v7.0座位名を使用）
        function formatGenoShort(geno, sex) {
            if (!geno || Object.keys(geno).length === 0) return 'WT';
            const parts = [];
            if (geno.parblue && geno.parblue !== '++') parts.push(geno.parblue);
            if (geno.ino && geno.ino !== '++' && geno.ino !== '+W') parts.push(geno.ino);
            if (geno.dark && geno.dark !== 'dd') parts.push(geno.dark);
            if (geno.opaline && geno.opaline !== '++' && geno.opaline !== '+W') parts.push('op');
            if (geno.cinnamon && geno.cinnamon !== '++' && geno.cinnamon !== '+W') parts.push('cin');
            if (geno.pied_rec && geno.pied_rec !== '++') parts.push('pirec');
            if (geno.pied_dom && geno.pied_dom !== '++') parts.push('pidom');
            if (geno.fallow_pale && geno.fallow_pale !== '++') parts.push('flp');
            if (geno.fallow_bronze && geno.fallow_bronze !== '++') parts.push('flb');
            return parts.length > 0 ? parts.join('/') : 'WT';
        }
        /**
 * 健康評価実行
 */
function checkPairingHealth() {
    const sireId = document.getElementById('healthSire').value;
    const damId = document.getElementById('healthDam').value;
    const resultEl = document.getElementById('healthCheckResult');
    const T = window.T || {};
    
    if (!sireId || !damId) {
        resultEl.innerHTML = '<div class="warning-box">' + (T.select_both_parents || 'Please select both sire and dam') + '</div>';
        return;
    }
    
    if (typeof BirdDB === 'undefined') {
        resultEl.innerHTML = '<div class="warning-box">' + (T.bird_db_not_loaded || 'BirdDB not loaded') + '</div>';
        return;
    }
    
    const sire = BirdDB.getBird(sireId);
    const dam = BirdDB.getBird(damId);
    
    if (!sire || !dam) {
        resultEl.innerHTML = '<div class="warning-box">' + (T.bird_not_found || 'Bird not found') + '</div>';
        return;
    }
    
    // 近交係数計算
    let ic = 0;
    if (typeof BirdDB.calculateInbreedingCoefficient === 'function') {
        const icResult = BirdDB.calculateInbreedingCoefficient(sireId, damId);
        ic = icResult.coefficient || 0;
    }
    
    // HealthGuardian存在チェック
    if (typeof HealthGuardian === 'undefined' || typeof HealthGuardian.evaluateHealth !== 'function') {
        // フォールバック: 簡易評価
        let riskLevel = 'safe';
        let riskColor = '#10b981';
        let riskBg = 'rgba(16,185,129,0.1)';
        let riskIcon = '✓';
        let riskLabel = T.risk_safe || 'Safe';
        let summary = T.low_health_risk || 'Health risk is low';
        
        if (ic >= 0.25) {
            riskLevel = 'critical';
            riskColor = '#ef4444';
            riskBg = 'rgba(239,68,68,0.1)';
            riskIcon = '🚫';
            riskLabel = T.risk_critical || 'Critical';
            summary = T.inbreeding_danger || 'Inbreeding coefficient is 25% or higher';
        } else if (ic >= 0.125) {
            riskLevel = 'warning';
            riskColor = '#f59e0b';
            riskBg = 'rgba(245,158,11,0.1)';
            riskIcon = '⚠️';
            riskLabel = T.risk_high || 'High Risk';
            summary = T.inbreeding_warning || 'Inbreeding coefficient is 12.5% or higher';
        }
        
        let html = '<div class="health-result" style="margin-top:1rem;padding:1rem;background:' + riskBg + ';border-radius:8px;border-left:4px solid ' + riskColor + ';">';
        html += '<div style="font-size:1.2rem;font-weight:bold;color:' + riskColor + ';">' + riskIcon + ' ' + riskLabel + '</div>';
        html += '<div style="margin-top:0.5rem;color:#e0e0e0;">' + summary + '</div>';
        html += '<div style="margin-top:0.5rem;font-size:0.9rem;color:#aaa;">' + (T.inbreeding_coefficient || 'Inbreeding Coefficient') + ': F = ' + (ic * 100).toFixed(2) + '%</div>';
        html += '</div>';
        resultEl.innerHTML = html;
        return;
    }
    
    // HealthGuardian評価（正常パス）
    const evaluation = HealthGuardian.evaluateHealth(sire, dam, ic);
    
    let html = '<div class="health-result" style="margin-top:1rem;padding:1rem;background:' + evaluation.riskStyle.bg + ';border-radius:8px;border-left:4px solid ' + evaluation.riskStyle.color + ';">';
    html += '<div style="font-size:1.2rem;font-weight:bold;color:' + evaluation.riskStyle.color + ';">' + evaluation.riskStyle.icon + ' ' + evaluation.riskStyle.label + '</div>';
    html += '<div style="margin-top:0.5rem;color:#e0e0e0;">' + evaluation.summary + '</div>';
    html += '<div style="margin-top:0.5rem;font-size:0.9rem;color:#aaa;">' + (T.inbreeding_coefficient || 'Inbreeding Coefficient') + ': F = ' + (ic * 100).toFixed(2) + '%</div>';
    
    if (evaluation.blocks && evaluation.blocks.length > 0) {
        html += '<div style="margin-top:1rem;"><strong style="color:#ef4444;">🚫 ' + (T.risk_critical || 'Breeding Prohibited') + ':</strong><ul style="margin:0.5rem 0;padding-left:1.5rem;">';
        evaluation.blocks.forEach(function(b) {
            html += '<li style="color:#fca5a5;margin:0.25rem 0;">' + b.message + '<br><span style="font-size:0.85rem;color:#888;">' + b.detail + '</span></li>';
        });
        html += '</ul></div>';
    }
    
    if (evaluation.warnings && evaluation.warnings.length > 0) {
        html += '<div style="margin-top:1rem;"><strong style="color:#f59e0b;">⚠️ ' + (T.risk_high || 'Warning') + ':</strong><ul style="margin:0.5rem 0;padding-left:1.5rem;">';
        evaluation.warnings.forEach(function(w) {
            html += '<li style="color:#fcd34d;margin:0.25rem 0;">' + w.message + '<br><span style="font-size:0.85rem;color:#888;">' + w.detail + '</span></li>';
        });
        html += '</ul></div>';
    }
    
    if (evaluation.risks && evaluation.risks.length > 0) {
        html += '<div style="margin-top:1rem;"><strong style="color:#eab308;">⚡ ' + (T.risk_moderate || 'Caution') + ':</strong><ul style="margin:0.5rem 0;padding-left:1.5rem;">';
        evaluation.risks.forEach(function(r) {
            html += '<li style="color:#fef08a;margin:0.25rem 0;">' + r.message + '<br><span style="font-size:0.85rem;color:#888;">' + r.detail + '</span></li>';
        });
        html += '</ul></div>';
    }
    
    if ((!evaluation.blocks || evaluation.blocks.length === 0) && (!evaluation.warnings || evaluation.warnings.length === 0) && (!evaluation.risks || evaluation.risks.length === 0)) {
        html += '<div style="margin-top:0.5rem;color:#10b981;">✓ ' + (T.low_health_risk || 'No health issues detected') + '</div>';
    }
    
    html += '</div>';
    resultEl.innerHTML = html;
}

        setTimeout(initHealthSelectors, 1000);

        
        // 個体DB初期表示
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof refreshBirdList === 'function') {
                refreshBirdList();
            }
            
            // URLハッシュがあれば結果位置にスクロール
            if(window.location.hash){
                const hashTarget = document.querySelector(window.location.hash);
                if(hashTarget){
                    setTimeout(()=>{
                        hashTarget.scrollIntoView({behavior:'smooth', block:'start'});
                    }, 100);
                }
            }
        });
    </script>
    <?php if ($familyResult): ?>
    <script>
        // 推論結果表示後に自動スクロール
        document.addEventListener('DOMContentLoaded', () => {
            const resultEl = document.getElementById('family-result');
            if (resultEl && resultEl.children.length > 0) {
                setTimeout(() => {
                    resultEl.scrollIntoView({behavior:'smooth', block:'start'});
                }, 200);
            }
        });
    </script>
    <?php endif; ?>
    <?php if ($action === 'estimate' && $result): ?>
    <script>
        // 遺伝子型推定結果表示後に自動スクロール
        document.addEventListener('DOMContentLoaded', () => {
            const resultEl = document.getElementById('estimator-result');
            if (resultEl && resultEl.children.length > 0) {
                setTimeout(() => {
                    resultEl.scrollIntoView({behavior:'smooth', block:'start'});
                }, 200);
            }
        });
    </script>
    <?php endif; ?>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const fSelect = document.getElementById('f_db_select');
    const mSelect = document.getElementById('m_db_select');
    
    if (fSelect) {
        fSelect.addEventListener('change', function() {
            loadBirdToForm('f', this.value);
        });
    }
    if (mSelect) {
        mSelect.addEventListener('change', function() {
            loadBirdToForm('m', this.value);
        });
    }
});
</script>
</body>
</html>
