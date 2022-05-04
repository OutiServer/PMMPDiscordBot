<?php

declare(strict_types=1);

namespace Ken_Cir\DiscordBot\Tasks;

use AttachableLogger;
use Discord\Discord;
use Discord\DiscordCommandClient;
use Discord\WebSockets\Intents;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use pocketmine\thread\Thread;
use pocketmine\utils\Config;
use React\EventLoop\Factory;

class DiscordBotThread extends Thread
{
    private AttachableLogger $logger;

    private Config $config;

    private string $vendorPath;

    private bool $stop;

    public function __construct(AttachableLogger $logger, Config $config, string $vendorPath)
    {
        $this->logger = $logger;
        $this->vendorPath = $vendorPath;
        $this->stop = false;
    }

    protected function onRun(): void
    {
        $this->registerClassLoaders();

        include "{$this->vendorPath}vendor/autoload.php";

        $loop = Factory::create();
        $logger = new Logger('Logger');
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));
        try {
            $discord = new DiscordCommandClient([
                "token" => $this->config->get("discord.token", ""),
                "prefix" => $this->config->get("discord.prefix", "!"),
                "discordOptions" => [
                    "loop" => $loop,
                    "logger" => $logger,
                    'loadAllMembers' => true,
                    'intents' => Intents::GUILDS | Intents::GUILD_MESSAGES
                ]
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error($e->getTraceAsString());
            $this->logger->emergency("DiscordBotのログインに失敗しました");
            $this->stop = true;
            $this->quit();
            return;
        }

        $loop->addPeriodicTimer(1, function () use ($discord) {
            if ($this->stop) {
                $discord->close();
                $discord->getLoop()->stop();
            }
        });
    }

    public function stop(): void
    {
    }
}