<?php
namespace SimpleSamlPhp\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

class ModuleInstaller extends LibraryInstaller
{
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        try {
            return parent::isInstalled($repo, $package);
        } catch (MissingSimpleSamlException $e) {
            return false;
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->getPackageBasePath($package);
    }


    /**
     * {@inheritDoc}
     */
    protected function getPackageBasePath(PackageInterface $package)
    {
        $name = $package->getPrettyName();
        if ($this->composer->getPackage()->getName() === 'simplesamlphp/simplesamlphp') {
            $ssp_path = '.';
        } else {
            $ssp_pack = $this->composer
                ->getRepositoryManager()
                ->getLocalRepository()
                ->findPackage('simplesamlphp/simplesamlphp', '*');

            if ($ssp_pack === null) {
                throw new MissingSimpleSamlException('Unable to install module ' . $name .'. Couldn\'t find install path of simplesamlphp/simplesamlphp.');
            }

            $ssp_path = $this->composer->getInstallationManager()->getInstallPath($ssp_pack);
        }

        if (!preg_match('@^.*/simplesamlphp-module-(.+)$@', $name, $matches)) {
            throw new \InvalidArgumentException('Unable to install module ' . $name .', package name must be on the form "VENDOR/simplesamlphp-module-MODULENAME".');
        }
        $moduleDir = $matches[1];

        if (!preg_match('@^[a-z0-9_.-]*$@', $moduleDir)) {
            throw new \InvalidArgumentException('Unable to install module ' . $name .', module name must only contain characters from a-z, 0-9, "_", "." and "-".');
        }
        if ($moduleDir[0] === '.') {
            throw new \InvalidArgumentException('Unable to install module ' . $name .', module name cannot start with ".".');
        }

        /* Composer packages are supposed to only contain lowercase letters, but historically many modules have had names in mixed case.
         * We must provide a way to handle those. Here we allow the module directory to be overridden with a mixed case name.
         */
        $extraData = $package->getExtra();
        if (isset($extraData['ssp-mixedcase-module-name'])) {
            $mixedCaseModuleName = $extraData['ssp-mixedcase-module-name'];
            if (!is_string($mixedCaseModuleName)) {
                throw new \InvalidArgumentException('Unable to install module ' . $name .', "ssp-mixedcase-module-name" must be a string.');
            }
            if (mb_strtolower($mixedCaseModuleName, 'utf-8') !== $moduleDir) {
                throw new \InvalidArgumentException('Unable to install module ' . $name .', "ssp-mixedcase-module-name" must match the package name except that it can contain uppercase letters.');
            }
            $moduleDir = $mixedCaseModuleName;
        }

        return $ssp_path.'/modules/'.$moduleDir;
    }


    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'simplesamlphp-module' === $packageType;
    }
}
