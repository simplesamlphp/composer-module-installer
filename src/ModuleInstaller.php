<?php

namespace SimpleSAML\Composer;

use InvalidArgumentException;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

use function in_array;
use function is_string;
use function mb_strtolower;
use function preg_match;
use function sprintf;

class ModuleInstaller extends LibraryInstaller
{
    /** @var string */
    public const MIXED_CASE = 'ssp-mixedcase-module-name';

    /** @var array */
    public const SUPPORTED = ['simplesamlphp-assets', 'simplesamlphp-module'];


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
        $ssp_path = '.';
        $ssp_pack = $this->composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage('simplesamlphp/simplesamlphp', '*');
        if ($ssp_pack !== null) {
            $ssp_path = $this->composer->getInstallationManager()->getInstallPath($ssp_pack);
        }

        $name = $package->getPrettyName();
        if (!preg_match('@^.*/simplesamlphp-(module|assets)-(.+)$@', $name, $matches)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to install module %s, package name must be on the form "VENDOR/simplesamlphp-%s-MODULENAME".',
                $name,
                $matches[1]
            ));
        }

        $moduleType = $matches[1];
        $moduleDir = $matches[2];

        if (!preg_match('@^[a-z0-9_.-]*$@', $moduleDir)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to install module %s, module name must only contain characters from a-z, 0-9, "_", "." and "-".',
                $name
            ));
        } elseif ($moduleDir[0] === '.') {
            throw new InvalidArgumentException(sprintf(
                'Unable to install module %s, module name cannot start with ".".',
                $name
            ));
        }

        /* Composer packages are supposed to only contain lowercase letters, but historically many modules have had names in mixed case.
         * We must provide a way to handle those. Here we allow the module directory to be overridden with a mixed case name.
         */
        $extraData = $package->getExtra();
        if (isset($extraData[self::MIXED_CASE])) {
            $mixedCaseModuleName = $extraData[self::MIXED_CASE];
            if (!is_string($mixedCaseModuleName)) {
                throw new InvalidArgumentException(sprintf(
                    'Unable to install module %s, "%s" must be a string.',
                    $name,
                    self::MIXED_CASE
                ));
            }
            if (mb_strtolower($mixedCaseModuleName, 'utf-8') !== $moduleDir) {
                throw new InvalidArgumentException(sprintf(
                    'Unable to install module %s, "%s" must match the package name except that it can contain uppercase letters.',
                    $name,
                    self::MIXED_CASE
                ));
            }
            $moduleDir = $mixedCaseModuleName;
        }

        switch ($moduleType) {
            case 'assets':
                return $ssp_path . '/public/' . $moduleDir;
                break;
            case 'module':
                return $ssp_path . '/modules/' . $moduleDir;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Unsupported type: %s', $moduleType));
        }
    }


    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, self::SUPPORTED);
    }
}
