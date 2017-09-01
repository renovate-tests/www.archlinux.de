<?php

namespace AppBundle\ArchLinux;

class Package
{
    /** @var \SplFileInfo */
    private $packageDir;
    /** @var \SplFileInfo */
    private $descFile;
    /** @var array */
    private $desc;
    /** @var array */
    private $files;

    /**
     * @param \SplFileInfo $packageDir
     */
    public function __construct(\SplFileInfo $packageDir)
    {
        $this->packageDir = $packageDir;
        $this->descFile = new \SplFileInfo($packageDir->getPathname() . '/desc');
    }

    /**
     * @param \SplFileInfo $descFile
     *
     * @return array
     */
    private function loadInfo(\SplFileInfo $descFile): array
    {
        $index = '';
        $data = array();
        $file = $descFile->openFile();
        $file->setFlags(\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);

        foreach ($file as $line) {
            if (substr($line, 0, 1) == '%' && substr($line, -1) == '%') {
                $index = substr($line, 1, -1);
                $data[$index] = array();
            } else {
                $data[$index][] = $line;
            }
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return null|string
     */
    private function readValue(string $key, ?string $default = ''): ?string
    {
        $list = $this->readList($key);
        if (isset($list[0])) {
            return $list[0];
        } else {
            return $default;
        }
    }

    /**
     * @param string $key
     * @param array|null $default
     * @return array|null
     */
    private function readList(string $key, ?array $default = []): ?array
    {
        if (is_null($this->desc)) {
            $this->desc = $this->loadInfo($this->descFile);
        }
        if (isset($this->desc[$key])) {
            return $this->desc[$key];
        } else {
            return $default;
        }
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->readValue('FILENAME');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->readValue('NAME');
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->readValue('BASE', $this->getName());
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->readValue('VERSION');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->readValue('DESC');
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->readList('GROUPS');
    }

    /**
     * @return int
     */
    public function getCompressedSize(): int
    {
        return (int)$this->readValue('CSIZE', '0');
    }

    /**
     * @return int
     */
    public function getInstalledSize(): int
    {
        return (int)$this->readValue('ISIZE', '0');
    }

    /**
     * @return string
     */
    public function getMD5SUM(): string
    {
        return $this->readValue('MD5SUM');
    }

    /**
     * @return string|null
     */
    public function getSHA256SUM(): ?string
    {
        return $this->readValue('SHA256SUM', null);
    }

    /**
     * @return string|null
     */
    public function getPGPSignature(): ?string
    {
        return $this->readValue('PGPSIG', null);
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return $this->readValue('URL');
    }

    /**
     * @return array
     */
    public function getLicenses(): array
    {
        return $this->readList('LICENSE');
    }

    /**
     * @return string
     */
    public function getArch(): string
    {
        return $this->readValue('ARCH');
    }

    /**
     * @return int
     */
    public function getBuildDate(): int
    {
        return (int)$this->readValue('BUILDDATE', '0');
    }

    /**
     * @return string
     */
    public function getPackager(): string
    {
        return $this->readValue('PACKAGER');
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        return $this->readList('REPLACES');
    }

    /**
     * @return array
     */
    public function getDepends(): array
    {
        return $this->readList('DEPENDS');
    }

    /**
     * @return array
     */
    public function getConflicts(): array
    {
        return $this->readList('CONFLICTS');
    }

    /**
     * @return array
     */
    public function getProvides(): array
    {
        return $this->readList('PROVIDES');
    }

    /**
     * @return array
     */
    public function getOptDepends(): array
    {
        return $this->readList('OPTDEPENDS');
    }

    /**
     * @return array
     */
    public function getMakeDepends(): array
    {
        return $this->readList('MAKEDEPENDS');
    }

    /**
     * @return array
     */
    public function getCheckDepends(): array
    {
        return $this->readList('CHECKDEPENDS');
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        if (is_null($this->files)) {
            $this->files = $this->loadInfo(
                new \SplFileInfo($this->packageDir->getPathname() . '/files')
            )['FILES'];
        }

        return $this->files;
    }

    /**
     * @return int
     */
    public function getMTime(): int
    {
        return $this->descFile->getMTime();
    }
}
