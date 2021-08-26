<?php
namespace OffbeatWP\AcfBlocks\Console;

use OffbeatWP\Console\AbstractCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Install extends AbstractCommand
{
    public const COMMAND = 'acf-blocks:install';

    public function execute($args, $argsNamed)
    {
        $this->copyFolder(__DIR__ . '/../../templates/Block', get_template_directory() . '/components/Block');

        $this->success('Successfully installed');
    }

    protected function copyFolder($source, $dest)
    {
        if (is_dir($dest)) {
            $this->error("Component already exists ({$dest})");
            exit;
        }

        mkdir($dest, 0755);
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST) as $item) {

            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

}
