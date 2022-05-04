<?php

declare(strict_types=1);

namespace Ken_Cir\DiscordBot;

use Ken_Cir\DiscordBot\Tasks\DiscordBotThread;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

class DiscordBot extends PluginBase
{
    use SingletonTrait;

    private DiscordBotThread $discordBotThread;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        $this->discordBotThread = new DiscordBotThread();

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function (): void {
                ob_start();
            }
        ), 10);

        $this->discordBotThread->start(PTHREADS_INHERIT_CONSTANTS);
    }

    protected function onDisable(): void
    {
        if (isset($this->discordBotThread)) {
            $this->discordBotThread->quit();
        }

        if (ob_get_contents()) {
            ob_flush();
            ob_end_clean();
        }
    }
}