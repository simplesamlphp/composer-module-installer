<?php
namespace SimpleSamlPhp\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class ModuleInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getPackageBasePath(PackageInterface $package)
    {
        $name = $package->getPrettyName();
        if (!preg_match('@^.*/simplesamlphp-module-(.+)$@', $name, $matches)) {
            throw new \InvalidArgumentException('Unable to install module, package name must be on the form "VENDOR/simplesamlphp-module-MODULENAME".');
        }
        $moduleDir = $matches[1];

        if (!preg_match('@^[a-z0-9_.-]*$@', $moduleDir)) {
            throw new \InvalidArgumentException('Unable to install module, module name must only contain characters from a-z, 0-9, "_", "." and "-".');
        }
        if ($moduleDir[0] === '.') {
            throw new \InvalidArgumentException('Unable to install module, module name cannot start with ".".');
        }

        return 'modules/' . $moduleDir;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'simplesamlphp-module' === $packageType;
    }
}
