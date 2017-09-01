<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\PDOConnection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PackagesSuggestController extends Controller
{
    /** @var PDOConnection */
    private $database;

    /**
     * @param PDOConnection $connection
     */
    public function __construct(PDOConnection $connection)
    {
        $this->database = $connection;
    }

    /**
     * @Route("/packages/suggest", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function suggestAction(Request $request): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $this->database->prepare('
                        SELECT DISTINCT
                            packages.name
                        FROM
                            packages
                        WHERE
                            packages.name LIKE :name
                        ORDER BY
                            packages.name ASC
                        LIMIT 10
                    ');
        $suggestions->bindValue('name', $term . '%', \PDO::PARAM_STR);
        $suggestions->execute();

        return $this->json($suggestions->fetchAll(\PDO::FETCH_COLUMN));
    }
}
