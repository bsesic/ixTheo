<?php
namespace VuFind\Db\Row;

/**
 * Row Definition for ixtheo_user
 *
 * @category VuFind
 * @package  Db_Row
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class IxTheoUser extends RowGateway
{
    /**
     * Constructor
     *
     * @param \Zend\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'ixtheo_user', $adapter);
    }
}
