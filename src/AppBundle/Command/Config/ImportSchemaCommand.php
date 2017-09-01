<?php

namespace AppBundle\Command\Config;

use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSchemaCommand extends ContainerAwareCommand
{
    /** @var PDOConnection */
    private $database;

    /**
     * @param PDOConnection $connection
     */
    public function __construct(PDOConnection $connection)
    {
        parent::__construct();
        $this->database = $connection;
    }

    protected function configure()
    {
        $this->setName('app:config:import-schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->database->exec(
            file_get_contents(
                $this->getContainer()->getParameter('kernel.project_dir') . '/app/config/schema.sql'
            )
        );
    }
}
