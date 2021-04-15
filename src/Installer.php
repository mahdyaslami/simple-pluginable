<?php

namespace Simplex\Plugins;

/**
 * Make functionality of installing workspace of plugins in workspace of your project.
 */
final class Installer
{
    /**
     * Name of file that contain list of plugins that have their workspace installed.
     */
    const PLUGINS_JSON = 'plugins.json';

    /**
     * Name of installer of a plugin.
     */
    const INSTALLER_PHP = 'installer.php';

    /**
     * Name of directory that is contain workspace of a plugin.
     */
    const WORKSPACE_DIRECTORY = 'workspace';

    /**
     * List of plugins that have their workspace installed.
     * 
     * @var null|array<string>
     */
    protected static $plugins = null;

    /**
     * Path to workspace of project.
     * 
     * @var null|string
     */
    protected static $basePath = null;

    /**
     * Installation Manager.
     * 
     * @var null|\Composer\Installer\InstallationManager
     */
    protected static $installationManager = null;

    /**
     * List of installed packages.
     * 
     * @var null|array
     */
    protected static $packages = null;

    /**
     * @var null|\Symfony\Component\Filesystem\Filesystem
     */
    protected static $filesystem = null;

    /**
     * Initilize installationManager and packages.
     * 
     * @param  \Composer\Script\Event $event
     * @return void
     */
    protected static function configure($event)
    {
        $composer = $event->getComposer();
        $repositoryManager = $composer->getRepositoryManager();
        self::$installationManager = $composer->getInstallationManager();
        $localRepository = $repositoryManager->getLocalRepository();
        self::$packages = $localRepository->getPackages();
    }

    /**
     * Get package install path.
     * 
     * @param  string $package
     * @return string
     */
    protected static function getInstallPath($package)
    {
        return self::$installationManager->getInstallPath($package);
    }

    /**
     * Get list of installed plugins.
     * 
     * @return array<string>
     */
    protected static function plugins()
    {
        if (self::$plugins) {
            return self::$plugins;
        }

        $path = self::basePath(self::PLUGINS_JSON);

        if (!file_exists($path)) {
            return self::$plugins = [];
        }

        self::$plugins = json_decode(file_get_contents($path));

        if (!self::$plugins) {
            return self::$plugins = [];
        }

        return self::$plugins;
    }

    /**
     * Add package to plugins list.
     * 
     * @param  \Composer\Package\CompletePackage $package
     * @return void
     */
    protected static function add($package)
    {
        array_push(self::$plugins, (string) $package);
    }

    /**
     * Save installed plugins.
     * 
     * @return bool
     */
    protected static function save()
    {
        return file_put_contents(self::basePath(self::PLUGINS_JSON), json_encode(self::$plugins));
    }

    /**
     * Copy and override plugin workspace into project directory.
     * 
     * @param  string $path Path to package directory.
     * @return void
     */
    protected static function overrideWorkspace($path)
    {
        if (!self::$filesystem) {
            self::$filesystem = new \Symfony\Component\Filesystem\Filesystem();
        }

        self::$filesystem->mirror($path . DIRECTORY_SEPARATOR . self::WORKSPACE_DIRECTORY, self::basePath(), null, [
            'override' => true
        ]);
    }

    /**
     * Install a package workspace.
     * 
     * @param  \Composer\Package\CompletePackage $package
     * @return bool
     */
    protected static function install($package)
    {
        $path = self::getInstallPath($package);

        $installer = $path . DIRECTORY_SEPARATOR . self::INSTALLER_PHP;
        if (!file_exists($installer)) {
            return false;
        }

        if (in_array((string) $package, self::plugins())) {
            return false;
        }

        //
        // Get option to plugin installer to copy and override its workspace
        // into project directory without implementation just by setting this
        // variable with true.
        //
        $override = false;

        require_once($installer);

        if ($override) {
            self::overrideWorkspace($path);
        }

        self::add($package);

        return true;
    }

    /**
     * Get the base path of the project installation.
     * 
     * @param  string $path
     * @return string
     */
    public static function basePath($path = '')
    {
        if (self::$basePath) {
            $path = trim($path, '/\\');

            return self::$basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
        }

        self::$basePath = getcwd();

        return self::basePath($path);
    }

    /**
     * The callback that run after the dumpautoload command.
     * 
     * @param  \Composer\Script\Event $event
     * @return void
     */
    public static function postAutoloadDump($event)
    {
        self::configure($event);

        foreach (self::$packages as $package) {
            if (self::install($package)) {
                echo "\"{$package}\" workspace installed successfully.\n";
            }
        }

        self::save();
    }
}
