<?php

error_reporting(-1);
ini_set('display_errors', '1');

if (!getenv('PS_MAINMENU_RUN_PROOFS')) {
    fwrite(STDERR, "prove-fixes is opt-in: set PS_MAINMENU_RUN_PROOFS=1\n");
    exit(0);
}

$repo = dirname(dirname(__DIR__));
$mutations = array(
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => <<<'NEEDLE'
                $parts = [];
                foreach ($shops as $shop_id) {
                    $shop_group_id = Shop::getGroupFromShop($shop_id);
                    $parts[] = (string) Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shop_group_id, $shop_id);
                }
                $conf = implode(',', $parts);
NEEDLE
,
        'to' => <<<'NEEDLE'
                foreach ($shops as $key => $shop_id) {
                    $shop_group_id = Shop::getGroupFromShop($shop_id);
                    $conf .= (string) ($key > 1 ? ',' : '') . Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $shop_group_id, $shop_id);
                }
NEEDLE
,
        'test' => 'testMultiShopItemsStaySeparatedInNameOrder',
        'class' => 'MenuBuilderTest',
        'expect' => 'assertion',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => <<<'NEEDLE'
, \'' . pSQL($l['label']) . '\', \'' . pSQL($l['link']) . '\' )
NEEDLE
,
        'to' => <<<'NEEDLE'
, ' . (int) $l['label'] . ', ' . (int) $l['link'] . ' )
NEEDLE
,
        'test' => 'testShopDuplicationPreservesLabelsLinksAndNewWindow',
        'class' => 'MenuTopLinksTest',
        'expect' => 'assertion',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => <<<'NEEDLE'
LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`
NEEDLE
,
        'to' => '',
        'test' => 'testCustomNestedCategoriesFilterByCustomerGroup',
        'class' => 'NestedCatalogTest',
        'expect' => 'foreach',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => "Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', implode(',', \$tokens));",
        'to' => "Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', str_replace(array('LNK' . \$id_linksmenutop . ',', 'LNK' . \$id_linksmenutop), '', \$currentItems));",
        'test' => 'testDeletingLinkOneDoesNotCorruptLinkTen',
        'class' => 'GetContentTest',
        'expect' => 'assertion',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => "implode(',', array_map('intval', \$groups))",
        'to' => "implode(',', \$groups)",
        'test' => 'testCustomNestedCategoriesFilterByCustomerGroup',
        'class' => 'NestedCatalogTest',
        'expect' => 'assertion',
    ),
);

$restoreFile = null;
$restoreContent = null;

function psMainmenuProveFixesRestore()
{
    global $restoreFile, $restoreContent;
    if ($restoreFile !== null && $restoreContent !== null && is_string($restoreContent)) {
        file_put_contents($restoreFile, $restoreContent);
    }
}

register_shutdown_function('psMainmenuProveFixesRestore');
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
}
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function () {
        psMainmenuProveFixesRestore();
        exit(130);
    });
    pcntl_signal(SIGTERM, function () {
        psMainmenuProveFixesRestore();
        exit(143);
    });
}

function psMainmenuProveFixesValidateOutput(array $output, $class, $test, $expect)
{
    $text = implode("\n", $output);
    $qualified = $class . '::' . $test;
    if (!preg_match('/\bTests:\s+1\b/', $text)) {
        return 'PHPUnit did not report exactly one test (missing Tests: 1)';
    }
    if (strpos($text, $qualified) === false) {
        return 'PHPUnit output missing ' . $qualified;
    }
    if ($expect === 'foreach') {
        if (!preg_match('/Invalid argument supplied for foreach|foreach\(\)/', $text)) {
            return 'Expected foreach() diagnostic in output';
        }

        return null;
    }
    if (!preg_match('/\bFAILURES!\b|\bThere was 1 failure\b|\bFailed asserting\b/', $text)) {
        return 'Expected assertion failure in PHPUnit output';
    }

    return null;
}

foreach ($mutations as $mutation) {
    $backup = file_get_contents($mutation['file']);
    $restoreFile = $mutation['file'];
    $restoreContent = $backup;

    $broken = str_replace($mutation['from'], $mutation['to'], $backup, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Mutation count for {$mutation['test']} was {$count}\n");
        exit(1);
    }
    file_put_contents($mutation['file'], $broken);

    $lintOutput = array();
    $lintCode = 0;
    exec('php -l ' . escapeshellarg($mutation['file']) . ' 2>&1', $lintOutput, $lintCode);
    if ($lintCode !== 0) {
        psMainmenuProveFixesRestore();
        fwrite(STDERR, "php -l failed for {$mutation['test']}: " . implode("\n", $lintOutput) . "\n");
        exit(1);
    }

    $cmd = 'bash ' . escapeshellarg($repo . '/tests/run-filtered.sh') . ' ' .
        escapeshellarg($mutation['class']) . ' ' . escapeshellarg($mutation['test']);
    $output = array();
    exec($cmd, $output, $code);
    psMainmenuProveFixesRestore();
    $restoreFile = null;
    $restoreContent = null;

    if ($code === 127) {
        fwrite(STDERR, "Runner missing for {$mutation['test']} (exit 127)\n");
        exit(1);
    }
    if ($code === 0) {
        fwrite(STDERR, "Expected failure for {$mutation['test']} but test passed\n");
        exit(1);
    }

    $validationError = psMainmenuProveFixesValidateOutput(
        $output,
        $mutation['class'],
        $mutation['test'],
        $mutation['expect']
    );
    if ($validationError !== null) {
        fwrite(STDERR, "Proof validation failed for {$mutation['test']}: {$validationError}\n");
        fwrite(STDERR, implode("\n", $output) . "\n");
        exit(1);
    }

    echo "PROOF OK: {$mutation['class']}::{$mutation['test']} ({$mutation['expect']})\n";
}

echo "prove-fixes: OK\n";
