<?php

/*
 * Copyright (C) 2026 Subwaystudios
 * Copyright (C) 2026 probablysubeditor69204
 */

namespace Pterodactyl\Services\CrashManager;

class Engine
{
    private array $rules = [
        [
            'id' => 'eula',
            'priority' => 100,
            'match' => ['eula=false', 'Failed to load eula.txt', 'You need to agree to the EULA'],
            'label' => 'NOTICE',
            'problem' => 'EULA not accepted',
            'description' => 'Minecraft will not start until you accept the EULA.',
            'fix' => 'Open eula.txt in your server root and set eula=true, then restart.',
            'stop' => true,
        ],
        [
            'id' => 'oom_heap',
            'priority' => 99,
            'match' => ['OutOfMemoryError: Java heap space'],
            'label' => 'CRITICAL',
            'problem' => 'Server ran out of RAM (Java heap)',
            'description' => 'The server exhausted its allocated Java heap memory.',
            'fix' => 'Increase the -Xmx flag in your startup command (e.g. -Xmx4G). If already high, reduce mod count or lower view distance.',
            'stop' => true,
        ],
        [
            'id' => 'oom_gc',
            'priority' => 99,
            'match' => ['OutOfMemoryError: GC overhead limit exceeded'],
            'label' => 'CRITICAL',
            'problem' => 'Server ran out of RAM (GC overhead)',
            'description' => 'Java spent more than 98% of time on garbage collection and made no progress.',
            'fix' => 'Increase -Xmx RAM allocation. Use Aikar\'s optimised JVM flags. Reduce plugin/mod count.',
            'stop' => true,
        ],
        [
            'id' => 'oom_kill',
            'priority' => 99,
            'match' => ['Exit code: 137', 'return value: 137', 'Out of memory: true'],
            'label' => 'CRITICAL',
            'problem' => 'Server killed by OS (out of physical RAM)',
            'description' => 'The operating system forcibly killed the Java process because the machine ran out of physical RAM.',
            'fix' => 'Reduce -Xmx so Java does not exceed physical RAM. Leave headroom for the OS (e.g. if you have 8GB, set -Xmx6G max).',
            'stop' => true,
        ],
        [
            'id' => 'watchdog',
            'priority' => 95,
            'match' => ['A single server tick took', 'Watchdog', 'forcibly terminated'],
            'label' => 'CRITICAL',
            'problem' => 'Server froze — Watchdog killed it',
            'description' => 'A server tick took too long and the watchdog safety mechanism shut it down.',
            'fix' => 'Find the cause of the freeze: infinite loops in plugins/mods, massive chunk loading, heavy redstone, or too many entities. Install Spark profiler to diagnose.',
            'stop' => false,
        ],
        [
            'id' => 'port_bind',
            'priority' => 95,
            'match' => ['FAILED TO BIND TO PORT', 'Address already in use', 'Failed to bind to port'],
            'label' => 'STARTUP ERROR',
            'problem' => 'Port already in use',
            'description' => 'Another process is already using the server port.',
            'fix' => 'Kill all Java processes, or change server-port in server.properties to a free port.',
            'stop' => true,
        ],
        [
            'id' => 'wrong_java',
            'priority' => 90,
            'match' => ['UnsupportedClassVersionError', 'Unsupported major.minor version'],
            'label' => 'JAVA VERSION',
            'problem' => 'Wrong Java version',
            'description' => 'The server JAR requires a different Java version than what is installed.',
            'fix' => 'MC 1.8-1.16 needs Java 8/11. MC 1.17 needs Java 16. MC 1.18-1.20.4 needs Java 17. MC 1.20.5+ needs Java 21.',
            'stop' => true,
        ],
        [
            'id' => 'corrupt_jar',
            'priority' => 90,
            'match' => ['Invalid or corrupt jarfile', 'Could not find main class', 'Invalid or corrupt jar'],
            'label' => 'STARTUP ERROR',
            'problem' => 'Corrupted or invalid server JAR',
            'description' => 'The server .jar file is incomplete or damaged.',
            'fix' => 'Re-download the server JAR from the official source and re-upload it.',
            'stop' => true,
        ],
        [
            'id' => 'client_mod',
            'priority' => 85,
            'match' => ['invalid dist DEDICATED_SERVER', 'net/minecraft/client/'],
            'label' => 'MOD RESOLUTION',
            'problem' => 'Client-only mod installed on server',
            'description' => 'One or more mods contain client-side rendering code that cannot run on a dedicated server.',
            'fix' => 'Remove all client-only mods from your mods/ folder. Common offenders: Sodium, Iris, OptiFine, JourneyMap, Xaero\'s Minimap, Dynamic Surroundings, any HUD or shader mod.',
            'stop' => false,
            'group' => true,
        ],
        [
            'id' => 'missing_mod_dep',
            'priority' => 85,
            'match' => ['MissingModsException', 'missing required mod', 'requires mod', 'Missing or unsupported mandatory dependencies'],
            'label' => 'MISSING DEPENDENCY',
            'problem' => 'Missing mod dependency',
            'description' => 'A mod requires another mod that is not installed or is the wrong version.',
            'fix' => 'Read the crash log for the required mod name and version, then install it.',
            'stop' => false,
        ],
        [
            'id' => 'duplicate_mod',
            'priority' => 80,
            'match' => ['Found duplicate mod', 'Duplicate mods found', 'multiple files for mod'],
            'label' => 'DUPLICATE MOD',
            'problem' => 'Duplicate mod files found',
            'description' => 'Two versions of the same mod are in the mods/ folder.',
            'fix' => 'Delete all copies of the mod and keep only one correct version.',
            'stop' => false,
        ],
        [
            'id' => 'fabric_api_missing',
            'priority' => 85,
            'match' => ['Fabric API not found', 'net.fabricmc.fabric.api'],
            'label' => 'MISSING DEPENDENCY',
            'problem' => 'Fabric API not installed',
            'description' => 'Most Fabric mods require Fabric API but it is not installed.',
            'fix' => 'Download Fabric API from Modrinth and place it in your mods/ folder.',
            'stop' => false,
        ],
        [
            'id' => 'mixin_error',
            'priority' => 75,
            'match' => ['Error while applying mixin', 'MixinApplyError', 'org.spongepowered.asm.mixin'],
            'label' => 'MOD CONFLICT',
            'problem' => 'Fabric Mixin error',
            'description' => 'A Fabric mod is injecting into a class in an incompatible way. Usually a version mismatch.',
            'fix' => 'Update Fabric Loader and all mods to their latest compatible versions. Remove mods one by one to isolate the conflict.',
            'stop' => false,
        ],
        [
            'id' => 'ticking_entity',
            'priority' => 80,
            'match' => ['Ticking entity', 'Exception ticking entity', 'Entity being ticked'],
            'label' => 'WORLD CORRUPTION',
            'problem' => 'Corrupt entity in world',
            'description' => 'An entity has corrupted NBT data and crashes the server every tick.',
            'fix' => 'Note the coordinates in the crash log. Use MCA Selector to delete the entity at those coordinates.',
            'stop' => false,
        ],
        [
            'id' => 'ticking_block',
            'priority' => 80,
            'match' => ['Ticking block entity', 'Exception ticking tile entity', 'TileEntity'],
            'label' => 'WORLD CORRUPTION',
            'problem' => 'Corrupt block entity in world',
            'description' => 'A block (furnace, chest, hopper, etc.) has corrupted data.',
            'fix' => 'Note the coordinates in the crash log. Use MCA Selector to remove or replace the block at those coordinates.',
            'stop' => false,
        ],
        [
            'id' => 'corrupt_level',
            'priority' => 85,
            'match' => ['level.dat corrupted', 'Failed to load level data'],
            'label' => 'WORLD CORRUPTION',
            'problem' => 'Corrupted level.dat',
            'description' => 'The main world file is damaged.',
            'fix' => 'Rename level.dat to level.dat.bak, then rename level.dat_old to level.dat. Restore from backup if that fails.',
            'stop' => true,
        ],
        [
            'id' => 'disk_full',
            'priority' => 95,
            'match' => ['No space left on device'],
            'label' => 'DISK FULL',
            'problem' => 'Server disk is full',
            'description' => 'The server cannot save world data because the disk has no free space.',
            'fix' => 'Free up disk space. Delete old backups, unused world files, and old logs.',
            'stop' => true,
        ],
        [
            'id' => 'yaml_config',
            'priority' => 70,
            'match' => ['while parsing a block mapping', 'mapping values are not allowed', 'could not determine a constructor'],
            'label' => 'CONFIG ERROR',
            'problem' => 'Broken YAML config file',
            'description' => 'A plugin config.yml has invalid indentation or syntax.',
            'fix' => 'Use yamllint.com to validate the config file. Delete it to let the plugin regenerate defaults.',
            'stop' => false,
        ],
        [
            'id' => 'plugin_load_fail',
            'priority' => 70,
            'match' => ['Could not load plugin', 'InvalidDescriptionException', 'Invalid plugin.yml'],
            'label' => 'PLUGIN ERROR',
            'problem' => 'Plugin failed to load',
            'description' => 'A plugin has a broken or missing plugin.yml.',
            'fix' => 'Re-download the plugin from its official source.',
            'stop' => false,
        ],
        [
            'id' => 'plugin_dep_missing',
            'priority' => 75,
            'match' => ['Cannot find required dependency', 'depends on', 'which is not loaded'],
            'label' => 'MISSING DEPENDENCY',
            'problem' => 'Plugin dependency missing',
            'description' => 'A plugin requires another plugin to be installed first.',
            'fix' => 'Install the dependency plugin listed in the error (e.g. ProtocolLib, Vault, PlaceholderAPI).',
            'stop' => false,
        ],
        [
            'id' => 'api_mismatch',
            'priority' => 70,
            'match' => ['NoSuchMethodError', 'Unsupported API version'],
            'label' => 'API MISMATCH',
            'problem' => 'Plugin or mod API version mismatch',
            'description' => 'A plugin or mod was compiled against a different server version.',
            'fix' => 'Update the plugin or mod to a version compatible with your current server version.',
            'stop' => false,
        ],
        [
            'id' => 'null_pointer',
            'priority' => 40,
            'match' => ['NullPointerException'],
            'label' => 'MOD/PLUGIN BUG',
            'problem' => 'NullPointerException in a plugin or mod',
            'description' => 'A plugin or mod tried to use a null object — this is a bug in that plugin or mod.',
            'fix' => 'Identify the plugin or mod from the stack trace and update or remove it.',
            'stop' => false,
        ],
        [
            'id' => 'stackoverflow',
            'priority' => 60,
            'match' => ['StackOverflowError'],
            'label' => 'PLUGIN BUG',
            'problem' => 'StackOverflowError — infinite recursion',
            'description' => 'A plugin or mod is calling itself recursively without stopping.',
            'fix' => 'Look for repeated class names in the stack trace. Update or remove that plugin or mod.',
            'stop' => false,
        ],
        [
            'id' => 'exit_1',
            'priority' => 30,
            'match' => ['return value: 1', 'exit code: 1'],
            'label' => 'STARTUP FAILURE',
            'problem' => 'Server exited with code 1',
            'description' => 'Generic Java startup failure. Usually wrong Java version, corrupted JAR, or bad startup flags.',
            'fix' => 'Check the full log above this line for the real error. Validate JAR, Java version, and startup command.',
            'stop' => false,
        ],
    ];

    public function analyse(string $content): array
    {
        $matched = [];
        $groupedIds = [];

        foreach ($this->rules as $rule) {
            foreach ($rule['match'] as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $id = $rule['id'];

                    if (isset($rule['group']) && $rule['group']) {
                        if (in_array($id, $groupedIds)) {
                            break;
                        }
                        $groupedIds[] = $id;
                    }

                    $matched[] = [
                        'id' => $id,
                        'priority' => $rule['priority'],
                        'label' => $rule['label'],
                        'problem' => $rule['problem'],
                        'description' => $rule['description'],
                        'fix' => $rule['fix'],
                        'stop' => $rule['stop'] ?? false,
                    ];

                    if ($rule['stop'] ?? false) {
                        break 2;
                    }

                    break;
                }
            }
        }

        usort($matched, fn($a, $b) => $b['priority'] - $a['priority']);

        $top = array_slice($matched, 0, 4);

        return $top;
    }

    public function detectPlatform(string $content): string
    {
        if (stripos($content, 'NeoForge') !== false) return 'NeoForge';
        if (stripos($content, 'Forge Mod Loader') !== false) return 'Forge';
        if (stripos($content, 'net.fabricmc') !== false) return 'Fabric';
        if (stripos($content, 'Quilt Loader') !== false) return 'Quilt';
        if (stripos($content, 'Purpur') !== false) return 'Purpur';
        if (stripos($content, 'This server is running Paper') !== false) return 'Paper';
        if (stripos($content, 'Powered by Bukkit') !== false) return 'Spigot';
        if (stripos($content, 'Mohist') !== false) return 'Mohist';
        return 'Vanilla';
    }

    public function extractMinecraftVersion(string $content): ?string
    {
        if (preg_match('/Minecraft Version:\s*([\d.]+)/', $content, $m)) return $m[1];
        if (preg_match('/server version ([\d.]+)/', $content, $m)) return $m[1];
        if (preg_match('/for Minecraft ([\d.]+)/', $content, $m)) return $m[1];
        return null;
    }

    public function isOutOfMemory(string $content): bool
    {
        return stripos($content, 'OutOfMemoryError') !== false
            || stripos($content, 'Out of memory: true') !== false
            || stripos($content, 'return value: 137') !== false;
    }
}
