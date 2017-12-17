<?php

namespace App\DataFixtures;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class PackageFixtures extends Fixture
{
    /** @var AbstractRelationRepository */
    private $relationRepository;

    /**
     * @param AbstractRelationRepository $relationRepository
     */
    public function __construct(AbstractRelationRepository $relationRepository)
    {
        $this->relationRepository = $relationRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $coreRepository = new Repository('core', Architecture::X86_64);
        $extraRepository = new Repository('extra', Architecture::X86_64);

        $glibc = new Package($coreRepository, 'glibc', '2.0-1', Architecture::X86_64);

        $pcre = new Package($coreRepository, 'pcre', '8.0-1', Architecture::X86_64);

        $pacman = new Package($coreRepository, 'pacman', '5.0-1', Architecture::X86_64);
        $pacman->addDependency(new Dependency($glibc->getName()));

        $php = new Package($extraRepository, 'php', '7.0-1', Architecture::X86_64);
        $php->addDependency(new Dependency($glibc->getName()));
        $php->addDependency(new Dependency($pcre->getName()));
        $php->addOptionalDependency(new OptionalDependency($pacman->getName()));

        $manager->persist($coreRepository);
        $manager->persist($extraRepository);

        $manager->persist($glibc);
        $manager->persist($pcre);
        $manager->persist($php);
        $manager->persist($pacman);

        $manager->flush();

        $this->relationRepository->updateTargets();
    }
}