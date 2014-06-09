<?php
/*
*    simpleSAMLphp-oauth2server is an OAuth 2.0 authorization and resource server in the form of a simpleSAMLphp module
*
*    Copyright (C) 2014  Bjorn R. Jensen
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*
*/

$this->data['header'] = $this->t('{oauth2server:oauth2server:token_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <input type="hidden" name="stateId" value="<?php echo htmlspecialchars($this->data['stateId']) ?>"/>

        <label for="token">
            <?php echo $this->t('{oauth2server:oauth2server:token_header}'); ?>
        </label>
        <table id="token">
            <?php $token = $this->data['token']; ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:token_id_header}'); ?></td>
                <td><?php echo htmlspecialchars($token['id']); ?></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:token_type_header}'); ?></td>
                <td><?php echo htmlspecialchars($token['type']); ?></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:token_client_id_header}'); ?></td>
                <td><?php echo htmlspecialchars($token['clientId']); ?></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:token_expire_time_header}'); ?></td>
                <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $token['expire'])); ?></td>
            </tr>
            <?php
            foreach ($token['scopes'] as $scope) {
                ?>
                <tr>
                    <td></td>
                    <td><?php echo $this->t('{oauth2server:oauth2server:' . $scope . '}') ?></td>
                </tr>
            <?php
            }
            ?>
        </table>
    </form>
<?php

$this->includeAtTemplateBase('includes/footer.php');
