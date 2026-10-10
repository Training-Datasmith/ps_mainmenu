<?php

error_reporting(-1);
ini_set('display_errors', '1');

$repo = dirname(dirname(__DIR__));
$mutations = array(
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => "' . pSQL(\$l['label']) . ', ' . pSQL(\$l['link']) . '",
        'to' => "(int) \$l['label'] . ', ' . (int) \$l['link']",
        'test' => 'testShopDuplicationPreservesLabelsLinksAndNewWindow',
        'class' => 'MenuTopLinksTest',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => '$conf = implode(\',\', $parts);',
        'to' => '$conf .= (string) ($key > 1 ? \',\' : \'\') . $value;',
        'test' => 'testMultiShopItemsStaySeparatedInNameOrder',
        'class' => 'MenuBuilderTest',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`',
        'to' => '',
        'test' => 'testCustomNestedCategoriesFilterByCustomerGroup',
        'class' => 'NestedCatalogTest',
    ),
    array(
        'file' => $repo . '/ps_mainmenu.php',
        'from' => "Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', implode(',', \$tokens));",
        'to' => "Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', str_replace(array('LNK' . \$id_linksmenutop . ',', 'LNK' . \$id_linksmenutop), '', \$currentItems));",
        'test' => 'testDeletingLinkOneDoesNotCorruptLinkTen',
        'class' => 'GetContentTest',
    ),
);

foreach ($mutations as $mutation) {
    $backup = file_get_contents($mutation['file']);
    $broken = str_replace($mutation['from'], $mutation['to'], $backup, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Mutation count for {$mutation['test']} was {$count}\n");
        exit(1);
    }
    file_put_contents($mutation['file'], $broken);
    $cmd = 'bash ' . escapeshellarg($repo . '/tests/run-filtered.sh') . ' ' .
        escapeshellarg($mutation['class']) . ' ' . escapeshellarg($mutation['test']);
    exec($cmd, $output, $code);
    file_put_contents($mutation['file'], $backup);
    if ($code === 0) {
        fwrite(STDERR, "Expected failure for {$mutation['test']} but test passed\n");
        exit(1);
    }
}

echo "prove-fixes: OK\n";
