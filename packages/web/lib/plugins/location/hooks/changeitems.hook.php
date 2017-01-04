<?php
/**
 * Changes the elements we need.
 *
 * PHP version 5
 *
 * @category ChangeItems
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @author   Lee Rowlett <nah@nah.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Changes the elements we need.
 *
 * @category ChangeItems
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @author   Lee Rowlett <nah@nah.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class ChangeItems extends Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'ChangeItems';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Add Location to Active Tasks';
    /**
     * The active flag.
     *
     * @var bool
     */
    public $active = true;
    /**
     * The node this hook enacts with.
     *
     * @var string
     */
    public $node = 'location';
    /**
     * Sets up storage node.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function storageNodeSetting($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if (!$arguments['Host']->isValid()) {
            return;
        }
        $Locations = self::getClass('LocationAssociationManager')->find(
            array(
                'hostID' => $arguments['Host']->get('id')
            )
        );
        $Locations = self::getSubObjectIDs(
            'LocationAssociation',
            array(
                'hostID' => $arguments['Host']->get('id')
            ),
            'locationID'
        );
        $method = false;
        foreach ((array)self::getClass('LocationManager')
            ->find(array('id' => $Locations)) as $Location
        ) {
            $Host =& $arguments['Host'];
            $Task = $Host->get('task');
            $TaskType =& $arguments['TaskType'];
            if ($Task->isValid()
                && ($Task->isCapture()
                || $Task->isMulticast())
            ) {
                $method = 'getMasterStorageNode';
            } elseif ($TaskType instanceof TaskType
                && $TaskType->isValid()
                && ($TaskType->isCapture()
                || $TaskType->isMulticast())
            ) {
                $method = 'getMasterStorageNode';
            }
            $StorageGroup = $Location
                ->getStorageGroup();
            if ($StorageGroup->isValid()) {
                if (!isset($arguments['snapin'])
                    || ($arguments['snapin'] === true
                    && self::getSetting('FOG_SNAPIN_LOCATION_SEND_ENABLED') > 0)
                ) {
                    $arguments['StorageNode'] = $Location
                        ->getStorageNode();
                }
                if (!$method) {
                    continue;
                }
                $arguments['StorageNode'] = $Location
                    ->getStorageGroup()
                    ->{$method}();
            }
            unset($Location);
        }
    }
    /**
     * Sets up storage group.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function storageGroupSetting($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if (!$arguments['Host']->isValid()) {
            return;
        }
        $Locations = self::getSubObjectIDs(
            'LocationAssociation',
            array(
                'hostID' => $arguments['Host']->get('id')
            ),
            'locationID'
        );
        foreach ((array)self::getClass('LocationManager')
            ->find(array('id' => $Locations)) as &$Location
        ) {
            $StorageGroup = $Location
                ->getStorageGroup();
            if (!$StorageGroup->isValid()) {
                continue;
            }
            $arguments['StorageGroup'] = $StorageGroup;
            unset($Location);
        }
    }
    /**
     * Sets up boot item information.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function bootItemSettings($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if (!$arguments['Host']->isValid()) {
            return;
        }
        $Locations = self::getSubObjectIDs(
            'LocationAssociation',
            array(
                'hostID' => $arguments['Host']->get('id')
            ),
            'locationID'
        );
        $Locations = self::getSubObjectIDs(
            'Location',
            array(
                'id' => $Locations
            )
        );
        $find = array(
            'hostID' => $arguments['Host']->get('id'),
            'locationID' => $Locations
        );
        foreach ((array)self::getClass('LocationAssociationManager')
            ->find($find) as $Location
        ) {
            if (!$Location->isTFTP()) {
                continue;
            }
            $StorageNode = $Location
                ->getLocation()
                ->getStorageNode();
            if (!$StorageNode->isValid()) {
                continue;
            }
            $ip = $StorageNode->get('ip');
            $memtest = $arguments['memtest'];
            $memdisk = $arguments['memdisk'];
            $bzImage = $arguments['bzImage'];
            $initrd = $arguments['initrd'];
            $arguments['webserver'] = $ip;
            $arguments['webroot'] = $webroot;
            $arguments['memdisk'] = "http://${ip}/fog/service/ipxe/$memdisk";
            $arguments['memtest'] = "http://${ip}/fog/service/ipxe/$memtest";
            $arguments['bzImage'] = "http://${ip}/fog/service/ipxe/$bzImage";
            $arguments['imagefile'] = "http://${ip}/fog/service/ipxe/$initrd";
            unset($Location);
        }
    }
    /**
     * Alters master nodes.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function alterMasters($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if (!$arguments['FOGServiceClass'] instanceof MulticastManager) {
            return;
        }
        $storagenodeIDs = self::getSubObjectIDs(
            'Location',
            '',
            'storagenodeID'
        );
        $storagenodeIDs = self::fastmerge(
            (array) $storagenodeIDs,
            (array) $arguments['MasterIDs']
        );
        $storagenodeIDs = array_filter($storagenodeIDs);
        $storagenodeIDs = array_unique($storagenodeIDs);
        $arguments['StorageNodes'] = self::getClass('StorageNodeManager')
            ->find(
                array(
                    'id' => $storagenodeIDs
                )
            );
        foreach ($arguments['StorageNodes'] as &$StorageNode) {
            if (!$StorageNode->isValid()) {
                continue;
            }
            if (!$StorageNode->get('isMaster')) {
                $StorageNode->set('isMaster', 1);
            }
            unset($StorageNode);
        }
    }
    /**
     * Makes master nodes.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function makeMaster($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if (!$arguments['FOGServiceClass'] instanceof MulticastTask) {
            return;
        }
        $arguments['StorageNode']->set('isMaster', 1);
    }
}
$ChangeItems = new ChangeItems();
$HookManager
    ->register(
        'SNAPIN_NODE',
        array(
            $ChangeItems,
            'storageNodeSetting'
        )
    );
$HookManager
    ->register(
        'SNAPIN_GROUP',
        array(
            $ChangeItems,
            'storageGroupSetting'
        )
    );
$HookManager
    ->register(
        'BOOT_ITEM_NEW_SETTINGS',
        array(
            $ChangeItems,
            'bootItemSettings'
        )
    );
$HookManager
    ->register(
        'BOOT_TASK_NEW_SETTINGS',
        array(
            $ChangeItems,
            'storageGroupSetting'
        )
    );
$HookManager
    ->register(
        'HOST_NEW_SETTINGS',
        array(
            $ChangeItems,
            'storageNodeSetting'
        )
    );
$HookManager
    ->register(
        'HOST_NEW_SETTINGS',
        array(
            $ChangeItems,
            'storageGroupSetting'
        )
    );
$HookManager
    ->register(
        'BOOT_TASK_NEW_SETTINGS',
        array(
            $ChangeItems,
            'storageNodeSetting'
        )
    );
$HookManager
    ->register(
        'CHECK_NODE_MASTERS',
        array(
            $ChangeItems,
            'alterMasters'
        )
    );
$HookManager
    ->register(
        'CHECK_NODE_MASTER',
        array(
            $ChangeItems,
            'makeMaster'
        )
    );
/**
 * $HookManager
 *     ->register(
 *         'HOST_EDIT_AFTER_SAVE',
 *         array(
 *             $ChangeItems,
 *             'hostEditAfterSave'
 *         )
 *     );
 */
