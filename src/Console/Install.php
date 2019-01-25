<?php
namespace OffbeatWP\AcfBlocks\Console;

use OffbeatWP\Console\AbstractCommand;

class Install extends AbstractCommand
{
    const COMMAND = 'acf-blocks:install';

    public function execute($args, $argsNamed)
    {
        $this->copyFolder(dirname(__FILE__) . '/../../templates/Block', get_template_directory() . '/components/Block');
    }

    protected function copyFolder($source, $dest)
    {
        mkdir($dest, 0755);
        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST) as $item) {

            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

}
