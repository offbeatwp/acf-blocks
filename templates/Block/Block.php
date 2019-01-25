<?php
namespace Components\Block;

use \OffbeatWP\Components\AbstractComponent;

class Block extends AbstractComponent
{
    public function render($settings)
    {
        $blockContent = $settings->blockContent;
        unset($settings->blockContent);

        return $this->view('block', [
            'blockContent' => $blockContent,
        ]);
    }

}
