<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

use itnovum\openITCOCKPIT\Core\DowntimeServiceConditions;
use itnovum\openITCOCKPIT\Core\Views\Downtime;

class DowntimeService extends Statusengine3ModuleAppModel {

    public $useTable = 'service_downtimehistory';
    public $tablePrefix = 'statusengine_';

    public function getQuery(DowntimeServiceConditions $Conditions, $paginatorConditions) {
        $fields = [
            'DowntimeService.author_name',
            'DowntimeService.comment_data',
            'DowntimeService.entry_time',
            'DowntimeService.scheduled_start_time',
            'DowntimeService.scheduled_end_time',
            'DowntimeService.duration',
            'DowntimeService.was_started',
            'DowntimeService.internal_downtime_id',
            'DowntimeService.was_cancelled',


            'Host.id',
            'Host.uuid',
            'Host.name',

            'Service.id',
            'Service.uuid',
            'Service.name'
        ];

        $query = [
            'recursive' => -1,
            'fields'    => $fields,
            'joins'     => [
                [
                    'table'      => 'hosts',
                    'type'       => 'INNER',
                    'alias'      => 'Host',
                    'conditions' =>
                        'Host.uuid = DowntimeService.hostname',
                ],
                [
                    'table'      => 'services',
                    'type'       => 'INNER',
                    'alias'      => 'Service',
                    'conditions' =>
                        'Service.uuid = DowntimeService.service_description',
                ],
            ],

            'conditions' => [
                'DowntimeService.scheduled_start_time >' => $Conditions->getFrom(),
                'DowntimeService.scheduled_start_time <' => $Conditions->getTo()
            ],

            'order' => $Conditions->getOrder(),
            'limit' => $Conditions->getLimit(),
        ];

        if ($Conditions->hideExpired()) {
            $query['conditions']['DowntimeService.scheduled_end_time >'] = time();
        }

        if ($Conditions->hasContainerIds()) {
            $db = $this->getDataSource();
            $subQuery = $db->buildStatement([
                'fields'     => ['Host.uuid'],
                'table'      => 'hosts',
                'alias'      => 'Host',
                'limit'      => null,
                'offset'     => null,
                'joins'      => [
                    [
                        'table'      => 'hosts_to_containers',
                        'alias'      => 'HostsToContainers',
                        'type'       => 'INNER',
                        'conditions' => [
                            'HostsToContainers.host_id = Host.id',
                        ],
                    ]
                ],
                'conditions' => [
                    'HostsToContainers.container_id' => $Conditions->getContainerIds()
                ],
                'order'      => null,
                'group'      => null
            ], $this);
            $subQuery = 'DowntimeService.hostname IN (' . $subQuery . ') ';
            $subQueryExpression = $db->expression($subQuery);
            $query['conditions'][] = $subQueryExpression->value;
        }

        //Merge ListFilter conditions
        $query['conditions'] = Hash::merge($paginatorConditions, $query['conditions']);
        if (isset($query['conditions']['DowntimeService.was_started'])) {
            $query['conditions']['DowntimeService.was_started'] = (int)$query['conditions']['DowntimeService.was_started'];
        }

        if (isset($query['conditions']['DowntimeService.was_cancelled'])) {
            $query['conditions']['DowntimeService.was_cancelled'] = (int)$query['conditions']['DowntimeService.was_cancelled'];
        }

        if ($Conditions->isRunning()) {
            $query['conditions']['DowntimeService.scheduled_end_time >'] = time();
            $query['conditions']['DowntimeService.was_started'] = 1;
            $query['conditions']['DowntimeService.was_cancelled'] = 0;
        }


        return $query;
    }

    /**
     * @param DowntimeServiceConditions $Conditions
     * @return array
     */
    public function getQueryForReporting(DowntimeServiceConditions $Conditions) {
        $query = [
            'fields' => [
                'DowntimeService.author_name',
                'DowntimeService.comment_data',
                'DowntimeService.scheduled_start_time',
                'DowntimeService.scheduled_end_time',
                'DowntimeService.duration',
                'DowntimeService.was_started',
                'DowntimeService.was_cancelled',
                'DowntimeService.hostname',
                'Host.uuid',
                'Service.uuid'
            ],
            'joins'  => [
                [
                    'table'      => 'hosts',
                    'type'       => 'INNER',
                    'alias'      => 'Host',
                    'conditions' =>
                        'Host.uuid = DowntimeService.hostname',
                ],
                [
                    'table'      => 'services',
                    'type'       => 'INNER',
                    'alias'      => 'Service',
                    'conditions' =>
                        'Service.uuid = DowntimeService.service_description',
                ]
            ],
            'order'  => $Conditions->getOrder()
        ];

        if ($Conditions->hasHostUuids()) {
            $query['conditions']['DowntimeService.hostname'] = $Conditions->getHostUuids();
        }

        if ($Conditions->hasServiceUuids()) {
            $query['conditions']['DowntimeService.service_description'] = $Conditions->getServiceUuids();
        }

        $query['conditions']['DowntimeService.was_cancelled'] = 0;

        $query['conditions']['OR'] = [
            '"' . $Conditions->getFrom() . '"
            BETWEEN DowntimeService.scheduled_start_time
            AND DowntimeService.scheduled_end_time',
            '"' . $Conditions->getTo() . '"
            BETWEEN DowntimeService.scheduled_start_time
            AND DowntimeService.scheduled_end_time',
            'DowntimeService.scheduled_start_time BETWEEN "' . $Conditions->getFrom() . '"
            AND "' . $Conditions->getTo() . '"',

        ];

        return $query;
    }

    /**
     * @param $hostId
     * @param Downtime $Downtime
     * @return array|null
     */
    public function getServiceDowntimesByHostAndDowntime($hostId, Downtime $Downtime) {
        $query = [
            'conditions' => [
                'Service.host_id'                      => $hostId,
                'DowntimeService.scheduled_start_time' => $Downtime->getScheduledStartTime(),
                'DowntimeService.scheduled_end_time'   => $Downtime->getScheduledEndTime()
            ],
            'fields'     => [
                'DowntimeService.internal_downtime_id',
            ]
        ];
        $result = $this->find('all', $query);
        if (empty($result)) {
            return [];
        }

        return Hash::extract($result, '{n}.DowntimeService.internal_downtime_id');
    }

    /**
     * @param null $uuid
     * @param bool $isRunning
     * @return array|null
     */
    public function byServiceUuid($uuid = null, $isRunning = false) {
        if ($uuid !== null) {

            $query = [
                'conditions' => [
                    'service_description' => $uuid,
                ],
                'order'      => [
                    'DowntimeService.entry_time' => 'DESC',
                ],
            ];

            if ($isRunning) {
                $query['conditions']['DowntimeService.scheduled_end_time >'] = time();
                $query['conditions']['DowntimeService.was_started'] = 1;
                $query['conditions']['DowntimeService.was_cancelled'] = 0;
            }

            $downtime = $this->find('first', $query);

            return $downtime;

        }

        return [];
    }

}

