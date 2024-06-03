<?php

/**
 * ADA.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\OAuth2Storage;

use OAuth2\Storage\Pdo as OAuth2PDO;

class ADA extends OAuth2PDO
{
    private $tmp_userID = null;

    public function __construct($connection, $config = [])
    {
        $tbl_prefix = 'module_oauth2_';

        parent::__construct($connection, $config);

        array_walk($this->config, function (&$item, $key, $prefix) {
            if (str_ends_with($key, 'table')) {
                $item = $prefix . $item;
            }
        }, $tbl_prefix);
    }

    /**
     * Overridden to temporarily store the user_id associated with the
     * given $client_id and $client_secret
     * Looks like they're only stored in the access_token_table
     * when the user authenticates itself, i.e. does a login
     *
     * (non-PHPdoc)
     * @see \OAuth2\Storage\Pdo::checkClientCredentials()
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where client_id = :client_id', $this->config['client_table']));
        $stmt->execute(compact('client_id'));
        $result = $stmt->fetch();

        $this->tmp_userID = $result['user_id'];

        // make this extensible
        return $result['client_secret'] == $client_secret;
    }

    /**
     * Overridden to retrieve and force the insert of the user_id associated with the
     * given $client_id and $client_secret in the access_token_table
     *
     * (non-PHPdoc)
     * @see \OAuth2\Storage\Pdo::checkClientCredentials()
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        $passUserId = null;
        if (is_null($user_id) && !is_null($this->tmp_userID)) {
            $passUserId = $this->tmp_userID;
        } else {
            $passUserId = $user_id;
        }
        return parent::setAccessToken($access_token, $client_id, $passUserId, $expires, $scope);
    }
}
