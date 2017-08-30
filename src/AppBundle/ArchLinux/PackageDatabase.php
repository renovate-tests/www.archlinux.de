<?php

namespace AppBundle\ArchLinux;

class PackageDatabase implements \IteratorAggregate
{
    /** @var \SplFileInfo */
    private $databaseFile;
    /** @var \FilesystemIterator */
    private $databaseDirectory;

    /**
     * @param \SplFileInfo $databaseFile
     */
    public function __construct(\SplFileInfo $databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    /**
     * @return \FilesystemIterator
     */
    private function getDatabaseDirectory(): \FilesystemIterator
    {
        if (is_null($this->databaseDirectory)) {
            $tarExtractor = new PackageDatabaseReader($this->databaseFile);
            $this->databaseDirectory = $tarExtractor->extract();
        }

        return $this->databaseDirectory;
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return (function () {
            /** @var \SplFileInfo $packageDirectory */
            foreach ($this->getDatabaseDirectory() as $packageDirectory) {
                yield new Package($packageDirectory);
            }
        })();
    }
}