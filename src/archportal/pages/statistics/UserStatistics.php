<?php
/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\pages\statistics;

use archportal\lib\ObjectStore;
use archportal\lib\StatisticsPage;
use Doctrine\DBAL\Driver\Connection;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserStatistics extends StatisticsPage
{
    /** @var Connection */
    private $database;
    /** @var ObjectStore */
    private $objectStore;

    /**
     * @param Connection $connection
     * @param ObjectStore $objectStore
     */
    public function __construct(Connection $connection, ObjectStore $objectStore)
    {
        parent::__construct();
        $this->database = $connection;
        $this->objectStore = $objectStore;
    }

    public function prepare()
    {
        $this->setTitle('User statistics');
        if (!($body = $this->objectStore->getObject('UserStatistics'))) {
            throw new NotFoundHttpException('No data found!');
        }
        $this->setBody($body);
    }

    public function updateDatabaseCache()
    {
        try {
            $this->database->beginTransaction();
            self::$barColors = self::MultiColorFade(self::$barColorArray);
            $log = $this->getCommonPackageUsageStatistics();
            $body = '<div class="box">
            <table id="packagedetails">
                <tr>
                    <th colspan="2" style="margin:0;padding:0;"><h1 id="packagename">User statistics</h1></th>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Common statistics</th>
                </tr>
                <tr>
                    <th>Submissions</th>
                    <td>'.number_format($log['submissions']).'</td>
                </tr>
                <tr>
                    <th>Different IPs</th>
                    <td>'.number_format($log['differentips']).'</td>
                </tr>
                <tr>
                    <th colspan="2" class="packagedetailshead">Countries</th>
                </tr>
                    '.$this->getCountryStatistics().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Mirrors</th>
                </tr>
                    '.$this->getMirrorStatistics().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Mirrors per Country</th>
                </tr>
                    '.$this->getMirrorCountryStatistics().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Mirror protocolls</th>
                </tr>
                    '.$this->getMirrorProtocollStatistics().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per architectures</th>
                </tr>
                    '.$this->getSubmissionsPerArchitecture().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Submissions per CPU architectures</th>
                </tr>
                    '.$this->getSubmissionsPerCpuArchitecture().'
                <tr>
                    <th colspan="2" class="packagedetailshead">Common statistics</th>
                </tr>
                <tr>
                    <th>Sum of submitted packages</th>
                    <td>'.number_format((float) $log['sumcount']).'</td>
                </tr>
                <tr>
                    <th>Number of different packages</th>
                    <td>'.number_format((float) $log['diffcount']).'</td>
                </tr>
                <tr>
                    <th>Lowest number of installed packages</th>
                    <td>'.number_format((float) $log['mincount']).'</td>
                </tr>
                <tr>
                    <th>Highest number of installed packages</th>
                    <td>'.number_format((float) $log['maxcount']).'</td>
                </tr>
                <tr>
                    <th>Average number of installed packages</th>
                    <td>'.number_format((float) $log['avgcount']).'</td>
                </tr>
            </table>
            </div>
            ';
            $this->objectStore->addObject('UserStatistics', $body);
            $this->database->commit();
        } catch (RuntimeException $e) {
            $this->database->rollBack();
            echo 'UserStatistics failed:'.$e->getMessage();
        }
    }

    /**
     * @return array
     */
    private function getCommonPackageUsageStatistics(): array
    {
        return $this->database->query('
        SELECT
            (SELECT COUNT(*) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS submissions,
            (SELECT COUNT(*) FROM (SELECT * FROM pkgstats_users WHERE time >= '.self::getRangeTime().' GROUP BY ip) AS temp) AS differentips,
            (SELECT MIN(time) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS minvisited,
            (SELECT MAX(time) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS maxvisited,
            (SELECT SUM(count) FROM pkgstats_packages WHERE month >= '.self::getRangeYearMonth().') AS sumcount,
            (SELECT COUNT(*) FROM (SELECT DISTINCT pkgname FROM pkgstats_packages WHERE month >= '.self::getRangeYearMonth().') AS diffpkgs) AS diffcount,
            (SELECT MIN(packages) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS mincount,
            (SELECT MAX(packages) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS maxcount,
            (SELECT AVG(packages) FROM pkgstats_users WHERE time >= '.self::getRangeTime().') AS avgcount
        ')->fetch();
    }

    /**
     * @return string
     */
    private function getCountryStatistics(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(countryCode)
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        ')->fetchColumn();
        $countries = $this->database->query('
        SELECT
            countries.name AS country,
            COUNT(countryCode) AS count
        FROM
            pkgstats_users
            JOIN countries
            ON pkgstats_users.countryCode = countries.code
        WHERE
            pkgstats_users.time >= '.self::getRangeTime().'
        GROUP BY
            pkgstats_users.countryCode
        HAVING
            count >= '.(floor($total / 100)).'
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($countries as $country) {
            $list .= '<tr><th>'.$country['country'].'</th><td>'.self::getBar((int) $country['count'],
                    $total).'</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private function getMirrorStatistics(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(mirror)
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        ')->fetchColumn();
        $mirrors = $this->database->query('
        SELECT
            mirror,
            COUNT(mirror) AS count
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        GROUP BY
            mirror
        HAVING
            count >= '.(floor($total / 100)).'
        ');
        $hosts = array();
        foreach ($mirrors as $mirror) {
            $host = parse_url($mirror['mirror'], PHP_URL_HOST);
            if ($host === false || empty($host)) {
                $host = 'unknown';
            }
            if (isset($hosts[$host])) {
                $hosts[$host] += $mirror['count'];
            } else {
                $hosts[$host] = $mirror['count'];
            }
        }
        arsort($hosts);
        $list = '';
        foreach ($hosts as $host => $count) {
            $list .= '<tr><th>'.$host.'</th><td>'.self::getBar($count, $total).'</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private function getMirrorCountryStatistics(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(countryCode)
        FROM
            mirrors
        ')->fetchColumn();
        $countries = $this->database->query('
        SELECT
            countries.name AS country,
            COUNT(countryCode) AS count
        FROM
            mirrors
            JOIN countries
            ON mirrors.countryCode = countries.code
        GROUP BY
            mirrors.countryCode
        HAVING
            count > '.(floor($total / 100)).'
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($countries as $country) {
            $list .= '<tr><th>'.$country['country'].'</th><td>'.self::getBar((int) $country['count'],
                    $total).'</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private function getMirrorProtocollStatistics(): string
    {
        $protocolls = array(
            'http' => 0,
            'ftp' => 0,
        );
        $total = $this->database->query('
        SELECT
            COUNT(mirror)
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        ')->fetchColumn();
        foreach ($protocolls as $protocoll => $count) {
            $protocolls[$protocoll] = $this->database->query('
            SELECT
                COUNT(mirror)
            FROM
                pkgstats_users
            WHERE
                time >= '.self::getRangeTime().'
                AND mirror LIKE \''.$protocoll.'%\'
            ')->fetchColumn();
        }
        arsort($protocolls);
        $list = '';
        foreach ($protocolls as $protocoll => $count) {
            $list .= '<tr><th>'.$protocoll.'</th><td>'.self::getBar($count, $total).'</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private function getSubmissionsPerArchitecture(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        ')->fetchColumn();
        $arches = $this->database->query('
        SELECT
            COUNT(*) AS count,
            arch AS name
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
        GROUP BY
            arch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>'.$arch['name'].'</th><td>'.self::getBar((int) $arch['count'], $total).'</td></tr>';
        }

        return $list;
    }

    /**
     * @return string
     */
    private function getSubmissionsPerCpuArchitecture(): string
    {
        $total = $this->database->query('
        SELECT
            COUNT(*)
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
            AND cpuarch IS NOT NULL
        ')->fetchColumn();
        $arches = $this->database->query('
        SELECT
            COUNT(cpuarch) AS count,
            cpuarch AS name
        FROM
            pkgstats_users
        WHERE
            time >= '.self::getRangeTime().'
            AND cpuarch IS NOT NULL
        GROUP BY
            cpuarch
        ORDER BY
            count DESC
        ');
        $list = '';
        foreach ($arches as $arch) {
            $list .= '<tr><th>'.$arch['name'].'</th><td>'.self::getBar((int) $arch['count'], $total).'</td></tr>';
        }

        return $list;
    }
}
